<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Sleep;

beforeEach(function (): void {
    Sleep::fake();
    seedReferenceCalendar();
});

function detailBrief(): array
{
    return [
        'headline' => 'Buda Kliwon Dungulan',
        'points' => [
            ['verdict' => 'ayu', 'text' => 'Baik untuk menanam padi.', 'rule_ids' => [ruleId('test_sintetis_buda_kliwon')]],
            ['verdict' => 'info', 'text' => 'Penanggal sasih Kadasa.', 'rule_ids' => []],
        ],
        'rahinan_note' => 'Hari ini Galungan.',
    ];
}

it('mengembalikan ringkasan AI, data lengkap, paringkelan, dan dewasa bersumber', function (): void {
    Http::fake([GEMINI_URL => Http::response(geminiFixture('text_response', detailBrief()))]);

    $this->getJson('/hari/2025-04-23')
        ->assertOk()
        ->assertJsonPath('brief.generated_by', 'ai')
        ->assertJsonPath('brief.points.0.verdict', 'ayu')
        ->assertJsonPath('day.wuku.name', 'Dungulan')
        ->assertJsonPath('day.wewaran.pancawara.name', 'Kliwon')
        ->assertJsonPath('day.wewaran.pancawara.urip', 8)
        ->assertJsonStructure(['day' => ['paringkelan' => ['jejepan', 'lintang', 'panca_suda', 'rakam', 'eka_jala_rsi', 'watek_alit', 'watek_madya', 'pararasan', 'pratithi_samut_pada']]])
        ->assertJsonPath('dewasa.0.source.title', 'Pokok-Pokok Wariga')
        ->assertJsonPath('sourceCount', 1)
        ->assertJsonPath('culturalNotice', false);
});

it('tetap mengembalikan data dengan ringkasan template saat AI gagal', function (): void {
    Http::fake([GEMINI_URL => Http::response(['error' => 'down'], 503)]);

    $this->getJson('/hari/2025-04-23')
        ->assertOk()
        ->assertJsonPath('brief.generated_by', 'template')
        ->assertJsonPath('day.wuku.name', 'Dungulan');
});

it('tidak memanggil AI bila batas laju tercapai', function (): void {
    Http::fake();
    config(['rahina.ai_rate_per_min' => 1]);
    RateLimiter::hit('brief:127.0.0.1');

    $this->getJson('/hari/2025-04-23')->assertOk()->assertJsonPath('brief.generated_by', 'template');
    Http::assertNothingSent();
});

it('404 untuk tanggal yang belum ada di kalender, tanpa AI', function (): void {
    Http::fake();

    $this->getJson('/hari/2030-01-01')->assertNotFound()->assertJsonPath('message', fn (string $m) => str_contains($m, '2020–2035'));
    Http::assertNothingSent();
});

it('validasi tanggal', function (): void {
    $this->getJson('/hari/2025-02-30')->assertUnprocessable();
});
