<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    seedReferenceCalendar();
    $this->travelTo('2025-04-23 09:00:00');
});

it('menampilkan grid bulan 6 minggu tanpa memanggil AI', function (): void {
    Http::fake();

    $this->get('/?bulan=2025-04')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar')
            ->where('month', '2025-04')
            ->where('today', '2025-04-23')
            ->has('weeks', 6)
            ->has('weeks.0', 7)
            ->where('weeks.0.0.date', '2025-03-30')
            ->where('weeks.0.0.in_month', false)
            ->where('todayDay.wuku.name', 'Dungulan'));
    Http::assertNothingSent();
});

it('menandai sel dengan data hasil hitung dan jumlah dewasa tervalidasi', function (): void {
    $this->get('/?bulan=2025-04')
        ->assertInertia(function (Assert $page): void {
            $cells = collect($page->toArray()['props']['weeks'])->flatten(1)->keyBy('date');
            $galungan = $cells['2025-04-23'];

            expect($galungan['available'])->toBeTrue()
                ->and($galungan['saptawara'])->toBe('Buda')
                ->and($galungan['pancawara'])->toBe('Kliwon')
                ->and($galungan['rahinan'])->toContain('Galungan')
                ->and($galungan['dewasa'])->toBe(['baik' => 1, 'tidak_baik' => 1])
                // Tanggal yang tidak ada di fixture tidak boleh dikarang.
                ->and($cells['2025-04-22']['available'])->toBeFalse();
        });
});

it('jumlah dewasa mencakup semua domain kegiatan', function (): void {
    $this->get('/?bulan=2025-04')
        ->assertInertia(function (Assert $page): void {
            $cell = collect($page->toArray()['props']['weeks'])->flatten(1)->firstWhere('date', '2025-04-23');

            expect($cell['dewasa'])->toBe(['baik' => 1, 'tidak_baik' => 1]);
        });
});

it('menampilkan rahinan mendatang dengan H-n', function (): void {
    $this->get('/?bulan=2025-04')
        ->assertInertia(fn (Assert $page) => $page
            ->where('upcoming.0.date', '2025-04-23')
            ->where('upcoming.0.days_until', 0)
            ->where('upcoming.1.name', 'Kuningan')
            ->where('upcoming.1.days_until', 10));
});

it('menolak bulan di luar rentang', function (string $query): void {
    $this->get("/?{$query}")->assertSessionHasErrors();
})->with([
    'sebelum 2020' => ['bulan=2019-12'],
    'setelah 2035' => ['bulan=2036-01'],
    'format salah' => ['bulan=04-2025'],
]);
