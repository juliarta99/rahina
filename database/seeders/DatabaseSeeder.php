<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Master data wariga (idempoten). User contoh hanya untuk lingkungan lokal.
     */
    public function run(): void
    {
        $this->call([
            SourceSeeder::class,
            WarigaMasterSeeder::class,
            ActivityCategorySeeder::class,
            DewasaRuleSeeder::class,
        ]);

        // Akun lokal untuk uji (nomor fiktif berawalan 0800, bukan prefiks seluler aktif).
        // Masuk dengan nomor 0800-0000-0001 atau 0800-0000-0002; kata sandi di bawah.
        if (app()->environment('local')) {
            foreach ([
                ['6280000000001', 'Penguji Rahina', 'rahina-demo-2026'],
                ['6280000000002', 'Test User', 'password'],
            ] as [$phone, $name, $password]) {
                User::query()->updateOrCreate(['phone' => $phone], ['name' => $name, 'password' => $password]);
            }
        }
    }
}
