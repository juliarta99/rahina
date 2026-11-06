<?php

declare(strict_types=1);

use App\Wariga\ActivityMatcher;
use App\Wariga\DateRanker;

beforeEach(fn () => seedReferenceCalendar());

it('meranking tanggal dengan dewasa pendukung untuk kategori', function (): void {
    $ranked = app(DateRanker::class)->rank(['pertanian.padi'], '2025-04-01', '2025-05-31');

    expect($ranked)->toHaveCount(1)
        ->and($ranked[0]->day->date)->toBe('2025-04-23')
        ->and($ranked[0]->score)->toBe(3)
        ->and($ranked[0]->verdict())->toBe('ayu');
});

it('tidak mengembalikan tanggal yang hanya punya penghalang', function (): void {
    // Aturan sintetis A berefek tidak_baik untuk dagang.buka_usaha.
    expect(app(DateRanker::class)->rank(['dagang.buka_usaha'], '2025-04-01', '2025-05-31'))->toBe([]);
});

it('menolak rentang di luar batas', function (string $start, string $end, string $message): void {
    expect(fn () => app(DateRanker::class)->rank(['pertanian.padi'], $start, $end))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    ['2025-01-01', '2025-12-31', 'Rentang maksimal'],
    ['2019-12-01', '2019-12-31', '2020–2035'],
    ['2025-05-01', '2025-04-01', 'lebih awal'],
    ['2025-02-30', '2025-03-01', 'Format tanggal'],
]);

it('menolak kategori yang tidak dikenal', function (): void {
    expect(fn () => app(DateRanker::class)->rank(['tidak.ada'], '2025-04-01', '2025-04-30'))
        ->toThrow(InvalidArgumentException::class, 'Kategori tidak dikenal');
});

it('memetakan frasa sehari-hari ke kategori lewat alias', function (string $text, string $code): void {
    $matches = app(ActivityMatcher::class)->match($text);

    expect($matches[0]['code'])->toBe($code);
})->with([
    ['kapan hari baik nanam singkong?', 'pertanian.umbi'],
    ['mau buka warung bulan depan', 'dagang.buka_usaha'],
    ['hari baik menikah', 'upacara.pawiwahan'],
    ['tanam padi', 'pertanian.padi'],
]);

it('menandai hasil tidak yakin untuk frasa yang tidak dikenal', function (): void {
    expect(ActivityMatcher::isConfident(app(ActivityMatcher::class)->match('beli motor')))->toBeFalse();
});
