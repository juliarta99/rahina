<?php

declare(strict_types=1);

use App\Actions\BuildDailyBriefAction;
use App\Data\DailyBrief;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function (): void {
    Sleep::fake();
    seedReferenceCalendar();
});

function goodBrief(): array
{
    return [
        'headline' => 'Buda Kliwon Dungulan',
        'points' => [
            ['verdict' => 'ayu', 'text' => 'Baik untuk menanam padi, karena hari ini jatuh [[DEWASA:SINTETIS_A]].', 'rule_ids' => [ruleId('test_sintetis_buda_kliwon')]],
            ['verdict' => 'info', 'text' => 'Penanggal sasih Kadasa.', 'rule_ids' => []],
        ],
        'rahinan_note' => 'Hari ini Galungan.',
    ];
}

function hallucinatedBrief(): array
{
    return array_merge(goodBrief(), ['points' => [
        ['verdict' => 'ayu', 'text' => 'Baik untuk menanam padi.', 'rule_ids' => [999]],
        ['verdict' => 'info', 'text' => 'Info.', 'rule_ids' => []],
    ]]);
}

function brief(string $date = '2025-04-23'): ?DailyBrief
{
    return app(BuildDailyBriefAction::class)->execute($date);
}

it('menyimpan ringkasan AI yang lolos guard dan memakai cache berikutnya', function (): void {
    Http::fake([GEMINI_URL => Http::response(geminiFixture('text_response', goodBrief()))]);

    $first = brief();
    $second = brief();

    expect($first->generatedBy)->toBe('ai')
        ->and($second->headline)->toBe($first->headline)
        ->and(DB::table('daily_briefs')->count())->toBe(1)
        ->and(DB::table('ai_interactions')->where('status', 'ok')->count())->toBe(1);
    Http::assertSentCount(1);
});

it('mengirim koreksi ke model saat guard menolak, lalu menerima perbaikannya', function (): void {
    Http::fake([GEMINI_URL => Http::sequence()
        ->push(geminiFixture('text_response', hallucinatedBrief()))
        ->push(geminiFixture('text_response', goodBrief()))]);

    expect(brief()->generatedBy)->toBe('ai');

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $r): bool => count($r['contents']) === 3
        && $r['contents'][1]['role'] === 'model'
        && str_contains($r['contents'][2]['parts'][0]['text'], 'rule_ids tidak ada di data'));
});

it('memakai template bila model tetap salah, dan menahan percobaan ulang (cooldown)', function (): void {
    Http::fake([GEMINI_URL => Http::response(geminiFixture('text_response', hallucinatedBrief()))]);

    $brief = brief();
    brief();

    expect($brief->generatedBy)->toBe('template')
        ->and($brief->points)->not->toBeEmpty()
        ->and(DB::table('daily_briefs')->count())->toBe(0)
        ->and(DB::table('ai_interactions')->where('status', 'fallback')->value('error'))->toContain('rule_ids tidak ada di data');
    Http::assertSentCount(2);
});

it('memakai template bila Gemini down', function (): void {
    Http::fake([GEMINI_URL => Http::response(['error' => 'unavailable'], 503)]);

    expect(brief()->generatedBy)->toBe('template');
    Http::assertSentCount(3);
});

it('memakai template tanpa HTTP bila API key belum diisi', function (): void {
    config(['rahina.gemini.api_key' => null]);
    Http::fake();

    expect(brief()->generatedBy)->toBe('template');
    Http::assertNothingSent();
});

it('mengembalikan null untuk tanggal di luar kalender', function (): void {
    Http::fake();

    expect(brief('2019-01-01'))->toBeNull();
    Http::assertNothingSent();
});

it('hanya mengirim data kalender publik ke Gemini (privasi)', function (): void {
    User::factory()->create(['email' => 'rahasia@example.com', 'name' => 'Nama Rahasia']);
    Http::fake([GEMINI_URL => Http::response(geminiFixture('text_response', goodBrief()))]);

    brief();

    Http::assertSent(function (Request $r): bool {
        $body = json_encode($r->data());
        $payload = json_decode(substr($r['contents'][0]['parts'][0]['text'], strlen("Data hari ini (JSON):\n")), true);

        return ! str_contains($body, 'rahasia@example.com')
            && ! str_contains($body, 'Nama Rahasia')
            && array_keys($payload) === ['tanggal', 'saptawara', 'pancawara', 'wuku', 'sasih', 'hari_sasih', 'purnama', 'tilem', 'rahinan', 'dewasa'];
    });
});

it('mengirim dewasa yang boleh tampil untuk semua domain (tanpa persona)', function (): void {
    Http::fake([GEMINI_URL => Http::response(geminiFixture('text_response', goodBrief()))]);

    brief('2025-04-23');

    Http::assertSent(fn (Request $r): bool => str_contains($r['contents'][0]['parts'][0]['text'], '"efek":"baik"')
        && str_contains($r['contents'][0]['parts'][0]['text'], '"efek":"tidak_baik"'));
});

it('menghapus cache ringkasan saat dewasa dihitung ulang', function (): void {
    Http::fake([GEMINI_URL => Http::response(geminiFixture('text_response', goodBrief()))]);
    brief();

    test()->artisan('wariga:compute-dewasa', ['--from' => '2025-04-01', '--to' => '2025-04-30'])->assertSuccessful();

    expect(DB::table('daily_briefs')->count())->toBe(0);
});
