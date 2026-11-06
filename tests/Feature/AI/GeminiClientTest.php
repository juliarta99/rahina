<?php

declare(strict_types=1);

use App\AI\GeminiClient;
use App\AI\GeminiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function (): void {
    Sleep::fake();
});

function client(): GeminiClient
{
    return app(GeminiClient::class);
}

it('mengirim model dari config, key lewat header, dan schema JSON', function (): void {
    Http::fake([GEMINI_URL => Http::response(geminiFixture('text_response', ['ok' => true]))]);

    $result = client()->generateJson('sistem', 'halo', ['type' => 'OBJECT']);

    expect($result['data'])->toBe(['ok' => true])
        ->and($result['response']->model)->toBe('test-model-001');

    Http::assertSent(function (Request $request): bool {
        return str_ends_with($request->url(), '/models/test-model:generateContent')
            && $request->hasHeader('x-goog-api-key', 'test-key')
            && ! str_contains($request->url(), 'test-key')
            && $request['generationConfig']['responseMimeType'] === 'application/json'
            && $request['generationConfig']['temperature'] === 0.2
            && $request['systemInstruction']['parts'][0]['text'] === 'sistem';
    });
});

it('retry pada 429 lalu berhasil', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()
        ->push(['error' => 'quota'], 429)
        ->push(geminiFixture('text_response', ['ok' => 1]))]);

    expect(client()->generateJson('s', 'u', [])['data'])->toBe(['ok' => 1]);
    Http::assertSentCount(2);
});

it('menyerah setelah 3 percobaan pada 5xx', function (): void {
    Http::fake([GEMINI_URL => Http::response(['error' => 'down'], 503)]);

    expect(fn () => client()->generateJson('s', 'u', []))
        ->toThrow(fn (GeminiException $e) => expect([$e->reason, $e->status])->toBe(['http', 503]));
    Http::assertSentCount(3);
});

it('tidak retry pada 400', function (): void {
    Http::fake([GEMINI_URL => Http::response(['error' => 'bad'], 400)]);

    expect(fn () => client()->generateJson('s', 'u', []))->toThrow(GeminiException::class);
    Http::assertSentCount(1);
});

it('melaporkan timeout koneksi', function (): void {
    Http::fake(fn () => throw new ConnectionException('timed out'));

    expect(fn () => client()->generateJson('s', 'u', []))
        ->toThrow(fn (GeminiException $e) => expect($e->reason)->toBe('timeout'));
});

it('menolak keluaran yang bukan JSON', function (): void {
    Http::fake([GEMINI_URL => Http::response(geminiFixture('text_response', 'ini bukan json'))]);

    expect(fn () => client()->generateJson('s', 'u', []))
        ->toThrow(fn (GeminiException $e) => expect($e->reason)->toBe('invalid_json'));
});

it('mengenali permintaan yang diblokir', function (): void {
    Http::fake([GEMINI_URL => Http::response(geminiFixture('blocked'))]);

    expect(fn () => client()->generate([['role' => 'user', 'parts' => [['text' => 'x']]]]))
        ->toThrow(fn (GeminiException $e) => expect($e->reason)->toBe('blocked'));
});

it('mengabaikan bagian "thought" dan membaca function call', function (): void {
    Http::fake([GEMINI_URL => Http::response(geminiFixture('thought_and_function_call', 'jawaban'))]);

    $response = client()->generate([['role' => 'user', 'parts' => [['text' => 'x']]]]);

    expect($response->text)->toBe('jawaban')
        ->and($response->functionCalls)->toBe([['name' => 'get_day', 'args' => ['date' => '2025-04-23']]])
        ->and($response->modelContent['role'])->toBe('model');
});

it('gagal cepat tanpa HTTP bila belum dikonfigurasi', function (): void {
    config(['rahina.gemini.api_key' => '']);
    Http::fake();

    expect(fn () => client()->generateJson('s', 'u', []))
        ->toThrow(fn (GeminiException $e) => expect($e->reason)->toBe('not_configured'));
    Http::assertNothingSent();
});
