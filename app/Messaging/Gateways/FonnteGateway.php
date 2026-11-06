<?php

declare(strict_types=1);

namespace App\Messaging\Gateways;

use App\Messaging\Inbound;
use App\Messaging\MessagingGateway;
use App\Messaging\SendResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * SATU-SATUNYA tempat HTTP call ke api.fonnte.com (CLAUDE.md §10). Token device dari FONNTE_TOKEN,
 * dikirim di header Authorization. Tidak pernah mencatat nomor atau isi pesan ke log.
 */
final class FonnteGateway implements MessagingGateway
{
    public const DEVICE_CACHE_KEY = 'fonnte:device';

    public function __construct(private readonly HttpFactory $http) {}

    public function name(): string
    {
        return 'fonnte';
    }

    public function send(string $to, string $message): SendResult
    {
        try {
            $response = $this->client()->asForm()->post($this->url('/send'), [
                'target' => $to,
                'message' => $message,
                'typing' => 'true',
            ]);
        } catch (ConnectionException) {
            return new SendResult(false, error: 'connection');
        }

        if (! $response->successful() || $response->json('status') !== true) {
            $reason = $response->json('reason');

            return new SendResult(false, error: is_string($reason) ? mb_substr($reason, 0, 120) : "http_{$response->status()}");
        }

        $id = $response->json('id.0') ?? $response->json('id');

        return new SendResult(true, is_scalar($id) ? (string) $id : null);
    }

    public function deviceInfo(): array
    {
        /** @var array{device: ?string, connected: bool, quota: ?string} */
        return Cache::remember(self::DEVICE_CACHE_KEY, now()->addMinutes((int) config('messaging.device_cache_minutes')), function (): array {
            try {
                $response = $this->client()->post($this->url('/device'));
            } catch (ConnectionException) {
                return ['device' => null, 'connected' => false, 'quota' => null];
            }
            $device = $response->json('device');

            return [
                'device' => is_scalar($device) && (string) $device !== '' ? Inbound::normalizePhone((string) $device) : null,
                'connected' => $response->json('device_status') === 'connect',
                'quota' => is_scalar($response->json('quota')) ? (string) $response->json('quota') : null,
            ];
        });
    }

    public function parseInbound(Request $request): ?Inbound
    {
        return Inbound::fromFonnte($request);
    }

    private function client(): PendingRequest
    {
        return $this->http
            ->withHeaders(['Authorization' => (string) config('messaging.fonnte.token')])
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout((int) config('messaging.fonnte.timeout_seconds'));
    }

    /** Basis URL tanpa path endpoint (toleran bila FONNTE_API_URL berisi ".../send"). */
    private function url(string $path): string
    {
        $base = (string) preg_replace('#/(send|device)/?$#', '', rtrim((string) config('messaging.fonnte.api_url'), '/'));

        return $base.$path;
    }
}
