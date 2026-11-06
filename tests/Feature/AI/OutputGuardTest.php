<?php

declare(strict_types=1);

use App\AI\OutputGuard;
use App\AI\OutputGuardException;
use App\Data\BriefInput;
use App\Data\CalendarDay;
use App\Data\DailyBrief;
use App\Wariga\BriefInputBuilder;
use App\Wariga\BriefTemplate;

beforeEach(function (): void {
    seedReferenceCalendar();
});

function galunganInput(): BriefInput
{
    return app(BriefInputBuilder::class)->build('2025-04-23');
}

function validBrief(array $override = []): array
{
    return array_merge([
        'headline' => 'Buda Kliwon Dungulan, hari Galungan',
        'points' => [
            ['verdict' => 'ayu', 'text' => 'Baik untuk menanam padi, karena hari ini jatuh [[DEWASA:SINTETIS_A]].', 'rule_ids' => [ruleId('test_sintetis_buda_kliwon')]],
            ['verdict' => 'info', 'text' => 'Wuku Dungulan, sasih Kadasa.', 'rule_ids' => []],
        ],
        'rahinan_note' => 'Hari ini bertepatan dengan Galungan.',
    ], $override);
}

function guardBrief(array $output, ?BriefInput $input = null): DailyBrief
{
    return app(OutputGuard::class)->brief($output, $input ?? galunganInput(), 'test-model', 'daily_brief.v1');
}

function violations(array $output, ?BriefInput $input = null): string
{
    try {
        guardBrief($output, $input);
    } catch (OutputGuardException $e) {
        return implode("\n", $e->violations);
    }

    return '';
}

it('meloloskan keluaran yang valid', function (): void {
    $brief = guardBrief(validBrief());

    expect($brief->generatedBy)->toBe('ai')
        ->and($brief->points)->toHaveCount(2)
        ->and($brief->ruleIds())->toBe([ruleId('test_sintetis_buda_kliwon')]);
});

it('menolak rule_ids yang tidak dikirim ke model (sumber karangan)', function (): void {
    expect(violations(validBrief(['points' => [
        ['verdict' => 'ayu', 'text' => 'Baik untuk menanam.', 'rule_ids' => [999]],
        ['verdict' => 'info', 'text' => 'Info.', 'rule_ids' => []],
    ]])))->toContain('rule_ids tidak ada di data (999)');
});

it('menolak aturan yang belum divalidasi walau ada di database', function (): void {
    $unvalidated = ruleId('test_sintetis_awal_kadasa');

    expect(violations(validBrief(['points' => [
        ['verdict' => 'ala', 'text' => 'Hindari membangun.', 'rule_ids' => [$unvalidated]],
        ['verdict' => 'info', 'text' => 'Info.', 'rule_ids' => []],
    ]])))->toContain('rule_ids tidak ada di data');
});

it('mewajibkan sumber untuk poin ayu/ala/campur', function (): void {
    expect(violations(validBrief(['points' => [
        ['verdict' => 'ayu', 'text' => 'Hari yang baik.', 'rule_ids' => []],
        ['verdict' => 'info', 'text' => 'Info.', 'rule_ids' => []],
    ]])))->toContain('wajib mencantumkan rule_ids');
});

it('menolak verdict yang tidak sesuai efek aturan', function (): void {
    // Aturan sintetis B (tidak tervalidasi) tidak tampil; aturan C hanya berefek "baik" untuk aneka.
    expect(violations(validBrief(['points' => [
        ['verdict' => 'ayu', 'text' => 'Baik.', 'rule_ids' => [ruleId('test_sintetis_buda_kliwon')]],
        ['verdict' => 'campur', 'text' => 'Ada pertimbangan.', 'rule_ids' => [ruleId('test_sintetis_saniscara_bukan_kuningan')]],
    ]])))->toContain('tidak sesuai efek');
});

it('menolak jumlah poin dan panjang teks di luar batas', function (): void {
    expect(violations(validBrief(['points' => [['verdict' => 'info', 'text' => 'Satu.', 'rule_ids' => []]]])))
        ->toContain('2–4 poin')
        ->and(violations(validBrief(['headline' => str_repeat('a', 91)])))->toContain('headline');
});

it('menolak tanggal di luar data', function (string $text): void {
    expect(violations(validBrief(['points' => [
        ['verdict' => 'info', 'text' => $text, 'rule_ids' => []],
        ['verdict' => 'info', 'text' => 'Info.', 'rule_ids' => []],
    ]])))->toContain('tanggal di luar data');
})->with([
    'ISO' => ['Hari baik berikutnya 2025-05-01.'],
    'nama bulan' => ['Coba juga 15 Desember.'],
    'tahun lain' => ['Seperti 23 April 2024.'],
]);

it('mengizinkan menyebut tanggal hari itu sendiri', function (): void {
    expect(violations(validBrief(['headline' => 'Rabu, 23 April 2025'])))->toBe('');
});

it('menolak rahinan yang tidak jatuh hari itu', function (): void {
    expect(violations(validBrief(['headline' => 'Galungan dan Nyepi'])))->toContain('rahinan yang tidak jatuh hari ini: nyepi');
});

it('tidak salah menganggap nama wuku sebagai rahinan', function (): void {
    // "Kuningan" adalah nama wuku dan juga rahinan.
    expect(violations(validBrief(['headline' => 'Sepuluh hari lagi masuk wuku Kuningan'])))->toBe('');
});

it('menolak rahinan_note pada hari tanpa rahinan', function (): void {
    $input = galunganInput();
    $d = $input->day;
    $noRahinan = new BriefInput(new CalendarDay(
        $d->date, $d->sakaYear, $d->wuku, $d->wewaran, $d->uripTotal, $d->sasih, $d->isNampihSasih,
        $d->sasihDay, $d->sasihDayEnd, $d->isPenanggal, $d->isPurnama, $d->isTilem, $d->isNgunaRatri,
        $d->ingkel, [], $d->engine,
    ), $input->dewasa);

    expect(violations(validBrief(['headline' => 'Buda Kliwon']), $noRahinan))->toContain('rahinan_note harus null');
});

it('menolak nama dewasa yang tidak ada di data', function (): void {
    expect(violations(validBrief(['headline' => 'Hari ini [[DEWASA:SINTETIS_B]]'])))->toContain('dewasa yang tidak ada di data');
});

it('membersihkan HTML, markdown, dan emoji', function (): void {
    $brief = guardBrief(validBrief(['headline' => "<b>**Galungan**</b> \u{1F338} hari raya!!!"]));

    expect($brief->headline)->toBe('Galungan hari raya!');
});

it('template non-AI selalu lolos guard untuk semua tanggal acuan', function (): void {
    $doc = json_decode((string) file_get_contents(base_path('tests/Fixtures/reference_days.json')), true);
    $builder = app(BriefInputBuilder::class);

    foreach ($doc['days'] as $day) {
        $input = $builder->build($day['date']);
        $template = app(BriefTemplate::class)->build($input, 'daily_brief.v1');

        expect(violations($template->content(), $input))->toBe('', $day['date']);
    }
});
