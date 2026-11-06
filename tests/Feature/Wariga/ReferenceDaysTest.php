<?php

declare(strict_types=1);

use App\Wariga\CalendarRepository;
use Illuminate\Support\Facades\Cache;

/*
| Tanggal acuan (tests/Fixtures/reference_days.json). Nilai harapan berasal dari fakta publik
| (libur nasional/daerah, siklus pawukon), BUKAN dari engine, sehingga test ini benar-benar
| memverifikasi engine + import + repository.
*/

function referenceDays(): array
{
    $doc = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/Fixtures/reference_days.json'), true);

    return $doc['days'];
}

dataset('reference_days', fn (): array => collect(referenceDays())
    ->mapWithKeys(fn (array $d): array => ["{$d['date']} {$d['label']}" => [$d['date'], $d['expected']]])
    ->all());

beforeEach(function (): void {
    seedMasters();
    $this->artisan('wariga:import', ['path' => base_path('tests/Fixtures/calendar/reference.jsonl')])->assertSuccessful();
});

it('cocok dengan tanggal acuan', function (string $date, array $expected): void {
    $day = app(CalendarRepository::class)->day($date);

    expect($day)->not->toBeNull();

    $slugs = array_column($day->rahinan, 'slug');
    foreach ($expected['rahinan'] ?? [] as $slug) {
        expect($slugs)->toContain($slug);
    }

    $actual = [
        'wuku' => $day->wuku->name,
        'saptawara' => $day->saptawara(),
        'pancawara' => $day->pancawara(),
        'sasih' => $day->sasih->name,
        'sasih_day' => $day->sasihDay,
        'is_penanggal' => $day->isPenanggal,
        'saka_year' => $day->sakaYear,
    ];
    foreach ($actual as $field => $value) {
        if (array_key_exists($field, $expected)) {
            expect($value)->toBe($expected[$field], "{$date}: {$field}");
        }
    }
})->with('reference_days');

it('memiliki minimal 30 tanggal acuan', function (): void {
    expect(count(referenceDays()))->toBeGreaterThanOrEqual(30);
});

it('mengembalikan null untuk tanggal yang belum diimpor', function (): void {
    expect(app(CalendarRepository::class)->day('2019-01-01'))->toBeNull();
});

it('data master tetap terbaca dari cache yang ter-serialisasi (regresi Laravel 13)', function (): void {
    config(['cache.default' => 'file']);
    Cache::forget(CalendarRepository::MASTERS_CACHE_KEY);

    // Instance pertama mengisi cache, instance kedua membaca dari file cache.
    (new CalendarRepository)->day('2025-04-23');
    $day = (new CalendarRepository)->day('2025-04-23');

    expect($day->wuku->name)->toBe('Dungulan')
        ->and($day->pancawara())->toBe('Kliwon');

    Cache::forget(CalendarRepository::MASTERS_CACHE_KEY);
});
