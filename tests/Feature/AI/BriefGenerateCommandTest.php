<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function (): void {
    Sleep::fake();
    seedReferenceCalendar();
});

it('mengisi cache satu ringkasan umum per tanggal', function (): void {
    Http::fake([GEMINI_URL => Http::response(geminiFixture('text_response', [
        'headline' => 'Buda Kliwon Dungulan',
        'points' => [
            ['verdict' => 'info', 'text' => 'Penanggal sasih Kadasa.', 'rule_ids' => []],
            ['verdict' => 'info', 'text' => 'Wuku Dungulan.', 'rule_ids' => []],
        ],
        'rahinan_note' => 'Hari ini Galungan.',
    ]))]);

    $this->artisan('brief:generate', ['--date' => '2025-04-23', '--days' => 2])
        ->expectsOutputToContain('AI: 1, template: 0')
        ->assertSuccessful();

    expect(DB::table('daily_briefs')->where('date', '2025-04-23')->where('persona', 'umum')->count())->toBe(1);
});

it('melaporkan tanggal yang tidak ada di kalender', function (): void {
    Http::fake();

    $this->artisan('brief:generate', ['--date' => '2019-01-01'])
        ->expectsOutputToContain('tidak tersedia: 1')
        ->assertFailed();
});

it('menolak format tanggal yang salah', function (): void {
    $this->artisan('brief:generate', ['--date' => '23-04-2025'])->assertFailed();
});
