<?php

declare(strict_types=1);

namespace App\Actions;

use App\AI\GeminiClient;
use App\AI\GeminiException;
use App\AI\InteractionLog;
use App\AI\OutputGuard;
use App\AI\OutputGuardException;
use App\AI\PromptRepository;
use App\Data\BriefInput;
use App\Data\DailyBrief;
use App\Wariga\BriefInputBuilder;
use App\Wariga\BriefTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Fitur A "Rahina Ini": satu ringkasan harian umum per tanggal (AI_IMPLEMENTATION.md §3).
 *
 * Alur: cache `daily_briefs` → data kalender + dewasa tervalidasi → Gemini (JSON schema)
 *       → OutputGuard → retry 1× dengan pesan koreksi → gagal: template non-AI.
 *
 * Input: tanggal (Y-m-d). Output: DailyBrief, atau null bila tanggal tidak ada di kalender.
 * `allowAi = false` (mis. batas laju terlampaui): pakai cache bila ada, selain itu template.
 * Tidak melempar exception karena kegagalan AI; selalu ada fallback.
 */
final class BuildDailyBriefAction
{
    public function __construct(
        private readonly BriefInputBuilder $inputs,
        private readonly GeminiClient $gemini,
        private readonly OutputGuard $guard,
        private readonly PromptRepository $prompts,
        private readonly BriefTemplate $template,
        private readonly InteractionLog $log,
    ) {}

    public function execute(string $date, bool $force = false, ?int $timeoutSeconds = null, bool $allowAi = true): ?DailyBrief
    {
        $id = (string) config('rahina.brief.prompt');
        $version = (int) config('rahina.brief.prompt_version');
        $versionTag = PromptRepository::versionTag($id, $version);

        if (! $force && ($cached = $this->cached($date, $versionTag)) !== null) {
            return $cached;
        }

        $input = $this->inputs->build($date);
        if ($input === null) {
            return null;
        }

        $cooldownKey = "brief:cooldown:{$date}:{$versionTag}";
        if (! $allowAi || (! $force && Cache::has($cooldownKey))) {
            return $this->template->build($input, $versionTag);
        }

        $payload = json_encode($input->toPromptPayload(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $hash = hash('sha256', $payload);
        $latency = 0;
        $model = $this->gemini->model();

        try {
            $brief = $this->generate($input, $payload, $this->prompts->get($id, $version)['body'], $versionTag, $timeoutSeconds, $latency, $model);
        } catch (GeminiException|OutputGuardException $e) {
            $reason = $e instanceof GeminiException ? "{$e->reason}: {$e->getMessage()}" : $e->getMessage();
            $this->log->record('brief', $hash, 'fallback', $model, $versionTag, $latency, $reason);
            Cache::put($cooldownKey, true, now()->addMinutes((int) config('rahina.brief.fallback_cooldown_minutes')));

            return $this->template->build($input, $versionTag);
        }

        $this->store($brief);
        $this->log->record('brief', $hash, 'ok', $brief->model, $versionTag, $latency);

        return $brief;
    }

    /**
     * Satu panggilan + satu kali koreksi bila JSON/guard gagal.
     *
     * @throws GeminiException|OutputGuardException
     */
    private function generate(BriefInput $input, string $payload, string $system, string $versionTag, ?int $timeout, int &$latency, string &$model): DailyBrief
    {
        $user = "Data hari ini (JSON):\n{$payload}";
        $history = [];

        for ($attempt = 1; ; $attempt++) {
            $result = null;
            try {
                $result = $this->gemini->generateJson($system, $user, OutputGuard::briefSchema(), $history, $timeout);
                $latency += $result['response']->latencyMs;
                $model = $result['response']->model;

                return $this->guard->brief($result['data'], $input, $model, $versionTag);
            } catch (GeminiException $e) {
                if ($e->reason !== 'invalid_json' || $attempt >= 2) {
                    throw $e;
                }
                $history = [['role' => 'user', 'parts' => [['text' => 'Keluaran sebelumnya bukan JSON valid. Kirim ulang HANYA JSON sesuai schema.']]]];
            } catch (OutputGuardException $e) {
                if ($attempt >= 2 || $result === null) {
                    throw $e;
                }
                $history = [
                    $result['response']->modelContent,
                    ['role' => 'user', 'parts' => [['text' => "Jawaban sebelumnya ditolak karena:\n- ".implode("\n- ", $e->violations)."\nPerbaiki dan kirim ulang HANYA JSON sesuai schema, hanya memakai data yang diberikan."]]],
                ];
            }
        }
    }

    private function cached(string $date, string $versionTag): ?DailyBrief
    {
        $row = DB::table('daily_briefs')
            ->where('date', $date)->where('persona', DailyBrief::SCOPE)->where('prompt_version', $versionTag)
            ->first();

        // Hemat token AI: bila ringkasan untuk tanggal ini sudah ada di database (apapun versi prompt-nya),
        // gunakan data yang tersimpan agar tidak memanggil Gemini API berulang kali.
        if ($row === null) {
            $row = DB::table('daily_briefs')
                ->where('date', $date)->where('persona', DailyBrief::SCOPE)
                ->orderByDesc('created_at')
                ->first();
        }

        if ($row === null) {
            return null;
        }

        $content = json_decode((string) $row->content, true);
        if (! is_array($content) || ! is_string($content['headline'] ?? null) || ! is_array($content['points'] ?? null)) {
            return null;
        }

        /** @var list<array{verdict: string, text: string, rule_ids: list<int>}> $points */
        $points = $content['points'];

        return new DailyBrief(
            date: $date,
            headline: $content['headline'],
            points: $points,
            rahinanNote: is_string($content['rahinan_note'] ?? null) ? $content['rahinan_note'] : null,
            generatedBy: (string) $row->model === 'template' ? DailyBrief::BY_TEMPLATE : DailyBrief::BY_AI,
            model: (string) $row->model,
            promptVersion: (string) ($row->prompt_version ?? $versionTag),
            summary: is_string($row->summary ?? null) ? $row->summary : null,
        );
    }

    private function store(DailyBrief $brief): void
    {
        DB::table('daily_briefs')->upsert([[
            'date' => $brief->date,
            'persona' => DailyBrief::SCOPE,
            'prompt_version' => $brief->promptVersion,
            'content' => json_encode($brief->content(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'summary' => $brief->summary(),
            'rule_ids' => json_encode($brief->ruleIds(), JSON_THROW_ON_ERROR),
            'model' => mb_substr($brief->model, 0, 64),
            'created_at' => now(),
        ]], ['date', 'persona', 'prompt_version'], ['content', 'summary', 'rule_ids', 'model', 'created_at']);
    }
}
