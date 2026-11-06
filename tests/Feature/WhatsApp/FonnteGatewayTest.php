<?php

declare(strict_types=1);

use App\Messaging\Gateways\FonnteGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config(['messaging.fonnte.token' => 'token-test', 'messaging.fonnte.api_url' => 'https://api.fonnte.com/send']);
    Cache::forget(FonnteGateway::DEVICE_CACHE_KEY);
});

it('mengirim pesan dengan token di header dan membaca id pesan', function (): void {
    Http::fake(['api.fonnte.com/send' => Http::response(['status' => true, 'id' => ['123']])]);

    $result = app(FonnteGateway::class)->send('6281234567890', 'Halo');

    expect($result->ok)->toBeTrue()->and($result->providerMessageId)->toBe('123');
    Http::assertSent(fn (Request $r): bool => $r->url() === 'https://api.fonnte.com/send'
        && $r->header('Authorization') === ['token-test']
        && $r['target'] === '6281234567890');
});

it('mengembalikan alasan gagal tanpa melempar exception', function (): void {
    Http::fake(['api.fonnte.com/send' => Http::response(['status' => false, 'reason' => 'token invalid'])]);

    $result = app(FonnteGateway::class)->send('6281234567890', 'Halo');

    expect($result->ok)->toBeFalse()->and($result->error)->toBe('token invalid');
});

it('membaca device (toleran FONNTE_API_URL berakhiran /send), menormalkan nomor, dan meng-cache', function (): void {
    Http::fake(['api.fonnte.com/device' => Http::response(['status' => true, 'device' => '081234500011', 'device_status' => 'connect', 'quota' => '1000'])]);

    $gateway = app(FonnteGateway::class);
    $first = $gateway->deviceInfo();
    $gateway->deviceInfo();

    expect($first)->toBe(['device' => '6281234500011', 'connected' => true, 'quota' => '1000']);
    Http::assertSentCount(1);
});
