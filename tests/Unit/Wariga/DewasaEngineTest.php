<?php

declare(strict_types=1);

use App\Wariga\CalendarRepository;
use App\Wariga\DewasaEngine;

function sampleFacts(array $override = []): array
{
    return array_merge([
        'wuku' => 'Dungulan', 'ekawara' => null, 'dwiwara' => 'Menga', 'triwara' => 'Kajeng',
        'caturwara' => 'Sri', 'pancawara' => 'Kliwon', 'sadwara' => 'Was', 'saptawara' => 'Buda',
        'astawara' => 'Sri', 'sangawara' => 'Dangu', 'dasawara' => 'Pandita', 'urip_total' => 15,
        'sasih' => 'Kadasa', 'sasih_number' => 10, 'sasih_day' => [3], 'is_penanggal' => true, 'is_purnama' => false,
        'is_tilem' => false, 'ingkel' => 'Wong',
    ], $override);
}

function dewasaEngine(): DewasaEngine
{
    return new DewasaEngine(new CalendarRepository);
}

it('mengevaluasi operator dasar', function (array $leaf, bool $expected): void {
    expect(dewasaEngine()->match($leaf, sampleFacts()) !== null)->toBe($expected);
})->with([
    'in cocok' => [['field' => 'saptawara', 'in' => ['Buda', 'Soma']], true],
    'in tidak cocok' => [['field' => 'saptawara', 'in' => ['Soma']], false],
    'not_in' => [['field' => 'wuku', 'not_in' => ['Sinta']], true],
    'eq' => [['field' => 'pancawara', 'eq' => 'Kliwon'], true],
    'eq boolean' => [['field' => 'is_purnama', 'eq' => true], false],
    'between batas atas' => [['field' => 'urip_total', 'between' => [10, 15]], true],
    'between di luar' => [['field' => 'urip_total', 'between' => [16, 20]], false],
    'gte' => [['field' => 'urip_total', 'gte' => 15], true],
    'lte' => [['field' => 'urip_total', 'lte' => 14], false],
    'ekawara kosong + in' => [['field' => 'ekawara', 'in' => ['Luang']], false],
    'ekawara kosong + not_in' => [['field' => 'ekawara', 'not_in' => ['Luang']], true],
]);

it('menggabungkan all, any, dan not', function (): void {
    $node = ['all' => [
        ['field' => 'saptawara', 'eq' => 'Buda'],
        ['any' => [
            ['field' => 'wuku', 'in' => ['Sinta']],
            ['field' => 'pancawara', 'eq' => 'Kliwon'],
        ]],
        ['not' => ['field' => 'is_tilem', 'eq' => true]],
    ]];

    expect(dewasaEngine()->match($node, sampleFacts()))->not->toBeNull()
        ->and(dewasaEngine()->match($node, sampleFacts(['is_tilem' => true])))->toBeNull()
        ->and(dewasaEngine()->match($node, sampleFacts(['pancawara' => 'Pon'])))->toBeNull();
});

it('mencatat unsur penyebab di matched_on', function (): void {
    $matched = dewasaEngine()->match(['all' => [
        ['field' => 'saptawara', 'eq' => 'Buda'],
        ['field' => 'sasih_day', 'between' => [1, 5], 'with' => ['is_penanggal' => true]],
    ]], sampleFacts());

    expect($matched)->toBe([
        ['field' => 'saptawara', 'op' => 'eq', 'expected' => 'Buda', 'actual' => 'Buda'],
        ['field' => 'sasih_day', 'op' => 'between', 'expected' => [1, 5], 'actual' => 3],
        ['field' => 'is_penanggal', 'op' => 'eq', 'expected' => true, 'actual' => true],
    ]);
});

it('syarat with yang gagal membatalkan leaf', function (): void {
    $leaf = ['field' => 'sasih_day', 'between' => [1, 5], 'with' => ['is_penanggal' => true]];

    expect(dewasaEngine()->match($leaf, sampleFacts(['is_penanggal' => false])))->toBeNull();
});

it('hari ngunaratri cocok bila salah satu hari sasih memenuhi', function (): void {
    $leaf = ['field' => 'sasih_day', 'eq' => 1];

    expect(dewasaEngine()->match($leaf, sampleFacts(['sasih_day' => [15, 1]]))[0]['actual'])->toBe(1)
        ->and(dewasaEngine()->match($leaf, sampleFacts(['sasih_day' => [14]])))->toBeNull();
});

it('menolak field atau operator yang tidak dikenal', function (array $node): void {
    expect(fn () => dewasaEngine()->match($node, sampleFacts()))->toThrow(InvalidArgumentException::class);
})->with([
    'field asing' => [['field' => 'zodiak', 'eq' => 'x']],
    'tanpa operator' => [['field' => 'wuku']],
    'dua operator' => [['field' => 'wuku', 'eq' => 'Sinta', 'in' => ['Sinta']]],
    'all kosong' => [['all' => []]],
]);

it('eq_field mencocokkan hari sasih dengan urutan sasih (pola Naga Naut)', function (): void {
    $leaf = ['field' => 'sasih_day', 'eq_field' => 'sasih_number'];

    expect(dewasaEngine()->match($leaf, sampleFacts(['sasih_day' => [10]])))->not->toBeNull()
        ->and(dewasaEngine()->match($leaf, sampleFacts(['sasih_day' => [9, 10]])))->not->toBeNull()
        ->and(dewasaEngine()->match($leaf, sampleFacts(['sasih_day' => [3]])))->toBeNull();
});
