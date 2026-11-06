<?php

declare(strict_types=1);

use App\Wariga\CalendarRepository;
use Illuminate\Support\Facades\DB;

/*
| Pipeline lengkap dengan aturan SINTETIS (tests/Fixtures/dewasa_rules.synthetic.json):
| seed → import tanggal acuan → load-rules → compute-dewasa → repository.
*/

const SYNTHETIC_RULES = 'tests/Fixtures/dewasa_rules.synthetic.json';

beforeEach(function (): void {
    seedMasters();
    $this->artisan('wariga:import', ['path' => base_path('tests/Fixtures/calendar/reference.jsonl')])->assertSuccessful();
    $this->artisan('wariga:load-rules', ['path' => base_path(SYNTHETIC_RULES)])->assertSuccessful();
});

function matchedDates(string $code): array
{
    return DB::table('calendar_day_dewasa as cd')
        ->join('dewasa_rules as r', 'r.id', '=', 'cd.rule_id')
        ->where('r.code', $code)
        ->orderBy('cd.date')
        ->pluck('cd.date')
        ->map(fn ($d): string => substr((string) $d, 0, 10))
        ->all();
}

it('mengisi kategori aktivitas 6 domain', function (): void {
    expect(DB::table('activity_categories')->whereNull('parent_id')->pluck('domain')->sort()->values()->all())
        ->toBe(['aneka', 'pembangunan', 'peralatan', 'pertanian', 'peternakan', 'upacara'])
        ->and(DB::table('activity_categories')->where('code', 'upacara.ngaben')->value('requires_cultural_notice'))->toBeTruthy();
});

it('file aturan produksi selalu lolos validator dan bersumber', function (): void {
    $this->artisan('wariga:load-rules')->assertSuccessful();

    $production = json_decode((string) file_get_contents(database_path('data/dewasa_rules.json')), true);
    expect(DB::table('dewasa_rules')->where('is_active', true)->count())->toBe(count($production['rules']))
        ->and(DB::table('dewasa_rules')->whereNull('source_id')->count())->toBe(0);
});

it('menghitung dewasa sesuai kondisi aturan', function (): void {
    $this->artisan('wariga:compute-dewasa')
        ->expectsOutputToContain('30 hari × 3 aturan')
        ->assertSuccessful();

    $galungan = collect(json_decode((string) file_get_contents(base_path('tests/Fixtures/reference_days.json')), true)['days'])
        ->where('label', 'Galungan')->pluck('date')->sort()->values()->all();
    $nyepi = collect(json_decode((string) file_get_contents(base_path('tests/Fixtures/reference_days.json')), true)['days'])
        ->filter(fn (array $d): bool => ($d['expected']['rahinan'] ?? []) === ['nyepi'])->pluck('date')->sort()->values()->all();

    // Semua Galungan (Buda Kliwon) harus cocok, dan setiap tanggal yang cocok memang Buda Kliwon.
    $budaKliwon = matchedDates('test_sintetis_buda_kliwon');
    expect(array_diff($galungan, $budaKliwon))->toBe([]);
    foreach ($budaKliwon as $date) {
        $day = app(CalendarRepository::class)->day($date);
        expect([$day->saptawara(), $day->pancawara()])->toBe(['Buda', 'Kliwon']);
    }

    expect(matchedDates('test_sintetis_awal_kadasa'))->toBe($nyepi)
        ->and(matchedDates('test_sintetis_saniscara_bukan_kuningan'))->toBe(['2025-02-08', '2025-02-22', '2025-03-29', '2025-09-06']);
});

it('repository hanya menampilkan aturan tervalidasi, lengkap dengan sumber dan alasan', function (): void {
    $this->artisan('wariga:compute-dewasa')->assertSuccessful();
    $repo = app(CalendarRepository::class);

    $galungan = $repo->dewasa('2025-04-23');
    expect($galungan)->toHaveCount(1)
        ->and($galungan[0]->code)->toBe('test_sintetis_buda_kliwon')
        ->and($galungan[0]->sourceTitle)->toBe('Pokok-Pokok Wariga')
        ->and($galungan[0]->effects)->toBe([
            ['category' => 'dagang.buka_usaha', 'category_name' => 'Membuka usaha / berdagang', 'domain' => 'aneka', 'effect' => 'tidak_baik'],
            ['category' => 'pertanian.padi', 'category_name' => 'Menanam padi', 'domain' => 'pertanian', 'effect' => 'baik'],
        ])
        ->and(array_column($galungan[0]->matchedOn, 'actual'))->toBe(['Buda', 'Kliwon']);

    expect($repo->dewasa('2024-03-11'))->toBe([])
        ->and($repo->dewasa('2024-03-11', onlyValidated: false))->toHaveCount(1);
});

it('compute ulang bersifat idempoten dan bisa dibatasi per aturan/rentang', function (): void {
    $this->artisan('wariga:compute-dewasa')->assertSuccessful();
    $total = DB::table('calendar_day_dewasa')->count();

    $this->artisan('wariga:compute-dewasa', ['--rule' => ['test_sintetis_buda_kliwon'], '--from' => '2025-01-01', '--to' => '2025-12-31'])
        ->expectsOutputToContain('× 1 aturan')
        ->assertSuccessful();

    expect(DB::table('calendar_day_dewasa')->count())->toBe($total);
});

it('aturan yang dihapus dari file dinonaktifkan dan tidak dihitung lagi', function (): void {
    $file = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rahina-rules-'.uniqid().'.json';
    $data = json_decode((string) file_get_contents(base_path(SYNTHETIC_RULES)), true);
    $data['rules'] = array_slice($data['rules'], 0, 1);
    file_put_contents($file, json_encode($data));

    $this->artisan('wariga:load-rules', ['path' => $file])
        ->expectsOutputToContain('2 aturan lama dinonaktifkan')
        ->assertSuccessful();
    $this->artisan('wariga:compute-dewasa')->expectsOutputToContain('× 1 aturan')->assertSuccessful();

    expect(DB::table('dewasa_rules')->where('is_active', true)->count())->toBe(1);
    unlink($file);
});

it('menolak seluruh file bila ada satu aturan tidak valid', function (): void {
    $file = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rahina-rules-'.uniqid().'.json';
    $data = json_decode((string) file_get_contents(base_path(SYNTHETIC_RULES)), true);
    $data['rules'][] = array_merge($data['rules'][0], ['code' => 'salah_ketik', 'conditions' => ['field' => 'saptawara', 'eq' => 'Budha']]);
    file_put_contents($file, json_encode($data));

    $this->artisan('wariga:load-rules', ['path' => $file])
        ->expectsOutputToContain('Budha')
        ->assertFailed();

    expect(DB::table('dewasa_rules')->where('code', 'salah_ketik')->exists())->toBeFalse();
    unlink($file);
});

it('aturan belum ditinjau dari sumber berizin tetap tampil, kecuali mode ketat', function (): void {
    $this->artisan('wariga:compute-dewasa')->assertSuccessful();
    // Aturan B (belum divalidasi) dipindah ke sumber berizin kalenderbali.org.
    DB::table('dewasa_rules')->where('code', 'test_sintetis_awal_kadasa')
        ->update(['source_id' => DB::table('sources')->where('code', 'kalenderbali-org')->value('id')]);
    $repo = app(CalendarRepository::class);

    $shown = $repo->dewasa('2024-03-11');
    expect($shown)->toHaveCount(1)
        ->and($shown[0]->isValidated)->toBeFalse()
        ->and($shown[0]->sourceTitle)->toContain('kalenderbali.org')
        ->and($repo->dewasaCounts('2024-03-11', '2024-03-11'))->toBe(['2024-03-11' => ['baik' => 0, 'tidak_baik' => 1]]);

    config(['rahina.dewasa.display_unreviewed_sources' => []]);
    expect($repo->dewasa('2024-03-11'))->toBe([])
        ->and($repo->dewasaCounts('2024-03-11', '2024-03-11'))->toBe([]);
});
