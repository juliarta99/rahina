<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Wariga\DewasaRuleLoader;
use Illuminate\Database\Seeder;

/**
 * Memuat aturan dewasa produksi dari database/data/dewasa_rules.json (lewat validator).
 * File ini diisi tim dari buku sumber; Claude tidak mengisi aturan asli (CLAUDE.md §8).
 */
class DewasaRuleSeeder extends Seeder
{
    public function run(DewasaRuleLoader $loader): void
    {
        $loader->load(database_path('data/dewasa_rules.json'));
    }
}
