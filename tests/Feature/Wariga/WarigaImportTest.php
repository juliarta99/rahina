<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    seedMasters();
});

it('mengisi data master dari wariga_master.json', function (): void {
    expect(DB::table('wuku')->count())->toBe(30)
        ->and(DB::table('wewaran_values')->count())->toBe(55)
        ->and(DB::table('sasih')->count())->toBe(20)
        ->and(DB::table('sasih')->where('is_intercalary', false)->count())->toBe(12)
        ->and(DB::table('rahinan')->count())->toBe(31)
        ->and(DB::table('sources')->where('code', 'balinese-date-js')->value('license'))->toBe('MIT');
});

it('seeder bisa dijalankan ulang tanpa duplikat', function (): void {
    seedMasters();

    expect(DB::table('wuku')->count())->toBe(30)
        ->and(DB::table('sources')->count())->toBe(8);
});

it('mengimpor jsonl dan idempoten saat diulang', function (): void {
    $file = base_path('tests/Fixtures/calendar/reference.jsonl');

    $this->artisan('wariga:import', ['path' => $file])->assertSuccessful();
    $before = DB::table('calendar_day_rahinan')->count();
    $this->artisan('wariga:import', ['path' => $file])->assertSuccessful();

    expect(DB::table('calendar_days')->count())->toBe(30)
        ->and(DB::table('calendar_day_rahinan')->count())->toBe($before)
        ->and($before)->toBeGreaterThan(0);
});

it('menolak baris rusak dengan menyebut nomor baris', function (): void {
    $file = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rahina-bad-'.uniqid().'.jsonl';
    file_put_contents($file, "{\"date\":\"2025-01-01\"}\n");

    $this->artisan('wariga:import', ['path' => $file])
        ->expectsOutputToContain(':1:')
        ->assertFailed();

    expect(DB::table('calendar_days')->count())->toBe(0);
    unlink($file);
});

it('gagal dengan pesan jelas bila folder kosong', function (): void {
    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rahina-empty-'.uniqid();
    mkdir($dir);

    $this->artisan('wariga:import', ['path' => $dir])
        ->expectsOutputToContain('compute.mjs')
        ->assertFailed();

    rmdir($dir);
});
