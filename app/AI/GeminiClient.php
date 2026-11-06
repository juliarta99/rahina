<?php

declare(strict_types=1);

namespace App\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use JsonException;

/**
 * SATU-SATUNYA tempat HTTP call ke Gemini API (CLAUDE.md §4).
 *
 * - Model & key hanya dari config/rahina.php (← GEMINI_MODEL, GEMINI_API_KEY).
 * - Key dikirim lewat header `x-goog-api-key`, tidak pernah di URL (tidak bocor ke log).
 * - Retry 2× untuk 429/5xx/koneksi dengan jeda 500 ms lalu 1500 ms (AI_IMPLEMENTATION.md §8).
 * - Circuit breaker kuota: 429 karena kuota HARIAN/proyek habis tidak di-retry; panggilan berikutnya langsung
 *   ditolak (`quota`) sampai waktu reset dari Google, agar pengguna cepat mendapat fallback.
 * - Tidak pernah mengirim data pribadi; pemanggil hanya mengirim data kalender publik + teks pertanyaan.
 *
 * @throws GeminiException
 */
final class GeminiClient
{
    public const QUOTA_CACHE_KEY = 'gemini:quota_exhausted_until';

    /** Status circuit breaker dibaca sekali per instance (cache = query DB). */
    private ?bool $quotaBlocked = null;

    public function __construct(private readonly HttpFactory $http) {}

    public function model(): string
    {
        return (string) config('rahina.gemini.model');
    }

    /**
     * Panggilan generateContent generik (dipakai juga oleh agent function calling di M4).
     *
     * @param  list<array<string, mixed>>  $contents  riwayat percakapan format Gemini
     * @param  array<string, mixed>  $generationConfig
     * @param  list<array<string, mixed>>  $tools
     * @param  array<string, mixed>  $toolConfig  mis. functionCallingConfig.mode = NONE untuk langkah terakhir agent
     */
    public function generate(
        array $contents,
        ?string $systemInstruction = null,
        array $generationConfig = [],
        array $tools = [],
        ?int $timeoutSeconds = null,
        array $toolConfig = [],
    ): GeminiResponse {
        $key = (string) config('rahina.gemini.api_key');
        $model = $this->model();
        if ($key === '' || $model === '') {
            throw new GeminiException('not_configured', 'GEMINI_API_KEY atau GEMINI_MODEL belum diisi');
        }

        if ($this->quotaBlocked ??= Cache::has(self::QUOTA_CACHE_KEY)) {
            throw new GeminiException('quota', 'Kuota Gemini habis; menunggu reset');
        }

        $payload = ['contents' => $contents];
        if ($systemInstruction !== null) {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemInstruction]]];
        }
        $payload['generationConfig'] = $generationConfig + ['temperature' => (float) config('rahina.gemini.temperature')];
        if ($tools !== []) {
            $payload['tools'] = $tools;
        }
        if ($toolConfig !== []) {
            $payload['toolConfig'] = $toolConfig;
        }

        $url = rtrim((string) config('rahina.gemini.base_url'), '/')."/models/{$model}:generateContent";
        /** @var list<int> $delays */
        $delays = config('rahina.gemini.retry_delays_ms');
        $started = hrtime(true);

        try {
            $response = $this->http
                ->withHeaders(['x-goog-api-key' => $key])
                ->acceptJson()
                ->asJson()
                ->connectTimeout(5)
                ->timeout($timeoutSeconds ?? (int) config('rahina.gemini.timeout_seconds'))
                ->retry($delays === [] ? 1 : [...$delays], 0, fn (\Throwable $e): bool => self::retryable($e), throw: true)
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            throw new GeminiException('timeout', 'Gemini tidak dapat dihubungi: '.$e->getMessage(), previous: $e);
        } catch (RequestException $e) {
            if (self::quotaExhausted($e->response)) {
                $seconds = self::retryAfterSeconds($e->response);
                Cache::put(self::QUOTA_CACHE_KEY, now()->addSeconds($seconds)->toIso8601String(), $seconds);
                $this->quotaBlocked = true;

                throw new GeminiException('quota', "Kuota Gemini habis, coba lagi dalam {$seconds} detik", 429, $e);
            }

            throw new GeminiException('http', "Gemini mengembalikan HTTP {$e->response->status()}", $e->response->status(), $e);
        }

        return $this->parse($response, $model, (int) ((hrtime(true) - $started) / 1_000_000));
    }

    /**
     * Minta keluaran JSON terstruktur (responseSchema) dan kembalikan hasil decode-nya.
     *
     * @param  array<string, mixed>  $schema  schema OpenAPI subset milik Gemini
     * @param  list<array<string, mixed>>  $history  giliran tambahan (mis. koreksi) setelah pesan user pertama
     * @return array{data: array<string, mixed>, response: GeminiResponse}
     */
    public function generateJson(string $system, string $user, array $schema, array $history = [], ?int $timeoutSeconds = null): array
    {
        $contents = [['role' => 'user', 'parts' => [['text' => $user]]], ...$history];

        $response = $this->generate($contents, $system, [
            'responseMimeType' => 'application/json',
            'responseSchema' => $schema,
        ], timeoutSeconds: $timeoutSeconds);

        try {
            $data = json_decode($response->text, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new GeminiException('invalid_json', 'Keluaran model bukan JSON valid', previous: $e);
        }
        if (! is_array($data) || array_is_list($data)) {
            throw new GeminiException('invalid_json', 'Keluaran model harus objek JSON');
        }

        /** @var array<string, mixed> $data */
        return ['data' => $data, 'response' => $response];
    }

    private static function retryable(\Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        return $e instanceof RequestException
            && (($e->response->status() === 429 && ! self::quotaExhausted($e->response)) || $e->response->serverError());
    }

    /** 429 karena kuota harian/proyek habis (bukan lonjakan per menit): percuma di-retry. */
    private static function quotaExhausted(Response $response): bool
    {
        if ($response->status() !== 429) {
            return false;
        }
        $message = (string) $response->json('error.message', '');

        return preg_match('/per day|PerDay|billing|check your plan/i', $message) === 1;
    }

    /** Lama jeda dari RetryInfo.retryDelay atau teks "retry in 7h15m34s"; 60 s – 24 jam, default 1 jam. */
    private static function retryAfterSeconds(Response $response): int
    {
        $seconds = null;
        foreach ((array) $response->json('error.details', []) as $detail) {
            if (is_array($detail) && is_string($detail['retryDelay'] ?? null) && preg_match('/^([\d.]+)s$/', $detail['retryDelay'], $m) === 1) {
                $seconds = (int) ceil((float) $m[1]);
            }
        }
        if ($seconds === null && preg_match('/retry in (?:(\d+)h)?(?:(\d+)m)?(?:([\d.]+)s)?/i', (string) $response->json('error.message', ''), $m) === 1) {
            $seconds = (int) ($m[1] ?? 0) * 3600 + (int) ($m[2] ?? 0) * 60 + (int) ceil((float) ($m[3] ?? 0));
        }

        return max(60, min(86_400, $seconds ?: 3600));
    }

    private function parse(Response $response, string $model, int $latencyMs): GeminiResponse
    {
        $candidate = $response->json('candidates.0');
        if (! is_array($candidate)) {
            $reason = $response->json('promptFeedback.blockReason');
            throw new GeminiException(
                is_string($reason) ? 'blocked' : 'invalid_response',
                is_string($reason) ? "Permintaan diblokir: {$reason}" : 'Respons tanpa kandidat',
            );
        }

        $finish = is_string($candidate['finishReason'] ?? null) ? $candidate['finishReason'] : null;
        if (in_array($finish, ['SAFETY', 'RECITATION', 'BLOCKLIST', 'PROHIBITED_CONTENT'], true)) {
            throw new GeminiException('blocked', "Jawaban dihentikan: {$finish}");
        }

        $content = is_array($candidate['content'] ?? null) ? $candidate['content'] : [];
        $parts = is_array($content['parts'] ?? null) ? $content['parts'] : [];
        $text = '';
        $calls = [];
        foreach ($parts as $part) {
            if (! is_array($part) || ($part['thought'] ?? false) === true) {
                continue;
            }
            if (is_string($part['text'] ?? null)) {
                $text .= $part['text'];
            }
            if (is_array($part['functionCall'] ?? null) && is_string($part['functionCall']['name'] ?? null)) {
                $args = $part['functionCall']['args'] ?? [];
                /** @var array<string, mixed> $args */
                $args = is_array($args) ? $args : [];
                $calls[] = ['name' => $part['functionCall']['name'], 'args' => $args];
            }
        }

        /** @var array<string, mixed> $content */
        return new GeminiResponse(
            text: $text,
            functionCalls: $calls,
            modelContent: $content + ['role' => 'model'],
            model: is_string($response->json('modelVersion')) ? (string) $response->json('modelVersion') : $model,
            latencyMs: $latencyMs,
            finishReason: $finish,
        );
    }
}
