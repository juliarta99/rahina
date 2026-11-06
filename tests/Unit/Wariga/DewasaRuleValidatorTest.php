<?php

declare(strict_types=1);

use App\Wariga\DewasaRuleValidator;

function ruleValidator(): DewasaRuleValidator
{
    return new DewasaRuleValidator(
        ['wuku' => ['Sinta', 'Dungulan'], 'saptawara' => ['Soma', 'Buda'], 'pancawara' => ['Kliwon'], 'sasih' => ['Kadasa'], 'ingkel' => ['Wong']],
        ['ardhana-2005'],
        ['pertanian.padi'],
    );
}

function sampleRule(array $override = []): array
{
    return array_merge([
        'code' => 'contoh_aturan',
        'name' => '[[DEWASA:CONTOH]]',
        'summary' => 'Ringkasan.',
        'verdict' => 'ayu',
        'alahing_level' => 2,
        'conditions' => ['all' => [['field' => 'saptawara', 'in' => ['Buda']], ['field' => 'urip_total', 'between' => [10, 15]]]],
        'source' => 'ardhana-2005',
        'source_page' => '12',
        'effects' => [['category' => 'pertanian.padi', 'effect' => 'baik']],
    ], $override);
}

it('menerima aturan yang valid', function (): void {
    expect(ruleValidator()->validate(sampleRule()))->toBe([]);
});

it('menolak aturan tidak valid dengan pesan yang jelas', function (array $override, string $message): void {
    $errors = ruleValidator()->validate(sampleRule($override));

    expect(implode("\n", $errors))->toContain($message);
})->with([
    'salah ketik nama' => [['conditions' => ['field' => 'saptawara', 'in' => ['Budha']]], 'nama saptawara tidak dikenal "Budha"'],
    'field asing' => [['conditions' => ['field' => 'zodiak', 'eq' => 'x']], 'field tidak dikenal'],
    'between untuk nama' => [['conditions' => ['field' => 'wuku', 'between' => ['Sinta', 'Dungulan']]], 'hanya untuk urip_total/sasih_day'],
    'eq_field ke field nama' => [['conditions' => ['field' => 'sasih_day', 'eq_field' => 'wuku']], 'eq_field hanya untuk'],
    'boolean bukan eq' => [['conditions' => ['field' => 'is_tilem', 'in' => [true]]], 'hanya mendukung eq'],
    'angka bukan int' => [['conditions' => ['field' => 'sasih_day', 'eq' => '1']], 'harus angka bulat'],
    'tanpa sumber' => [['source' => null], 'source wajib'],
    'kategori asing' => [['effects' => [['category' => 'pertanian.cabai', 'effect' => 'baik']]], 'category tidak dikenal'],
    'efek salah' => [['effects' => [['category' => 'pertanian.padi', 'effect' => 'bagus']]], 'baik/tidak_baik'],
    'tanpa efek' => [['effects' => []], 'effects wajib'],
    'verdict salah' => [['verdict' => 'baik'], 'verdict harus'],
    'alahing di luar 1-5' => [['alahing_level' => 9], 'alahing_level'],
    'validasi tanpa validator' => [['validated_at' => '2026-11-05'], 'validated_by wajib'],
    'code bukan slug' => [['code' => 'Aturan Baru'], 'code wajib slug'],
    'with field asing' => [['conditions' => ['field' => 'sasih_day', 'eq' => 1, 'with' => ['bulan' => 1]]], 'with: field tidak dikenal'],
]);
