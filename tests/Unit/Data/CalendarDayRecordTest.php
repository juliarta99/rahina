<?php

declare(strict_types=1);

use App\Data\CalendarDayRecord;

function validRecord(array $override = []): array
{
    return array_merge([
        'date' => '2025-03-29', 'saka_year' => 1947, 'wuku' => 7,
        'ekawara' => 1, 'dwiwara' => 1, 'triwara' => 1, 'caturwara' => 4, 'pancawara' => 4,
        'sadwara' => 3, 'saptawara' => 7, 'astawara' => 8, 'sangawara' => 9, 'dasawara' => 10,
        'urip_total' => 17, 'sasih' => 10, 'sasih_day' => 1, 'sasih_day_end' => null,
        'is_penanggal' => true, 'is_purnama' => false, 'is_tilem' => false, 'is_nguna_ratri' => false,
        'ingkel' => 'Wong', 'rahinan' => [5, 25], 'engine' => 'balinese-date-js@1.0.1',
    ], $override);
}

it('memetakan posisi wewaran ke id cycle*100+position', function (): void {
    $row = CalendarDayRecord::fromArray(validRecord())->toRow('2026-01-01T00:00:00+08:00');

    expect($row['pancawara_id'])->toBe(504)
        ->and($row['saptawara_id'])->toBe(707)
        ->and($row['ekawara_id'])->toBe(101)
        ->and($row['sasih_id'])->toBe(10);
});

it('mengizinkan ekawara kosong', function (): void {
    $row = CalendarDayRecord::fromArray(validRecord(['ekawara' => null]))->toRow('now');

    expect($row['ekawara_id'])->toBeNull();
});

it('menolak data tidak valid', function (array $override, string $message): void {
    expect(fn () => CalendarDayRecord::fromArray(validRecord($override)))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'tanggal mustahil' => [['date' => '2025-02-30'], 'Tanggal tidak valid'],
    'pancawara di luar siklus' => [['pancawara' => 6], 'pancawara di luar rentang'],
    'wuku di luar rentang' => [['wuku' => 31], 'wuku di luar rentang'],
    'saptawara kosong' => [['saptawara' => null], 'saptawara di luar rentang'],
    'boolean salah tipe' => [['is_purnama' => 'ya'], 'is_purnama'],
    'rahinan bukan integer' => [['rahinan' => ['nyepi']], 'rahinan'],
    'paringkelan terlalu panjang' => [['lintang' => str_repeat('x', 33)], 'lintang harus string'],
]);
