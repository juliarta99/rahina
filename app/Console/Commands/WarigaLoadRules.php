<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Wariga\DewasaRuleLoader;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Memvalidasi lalu memuat aturan dewasa dari JSON. Setelahnya jalankan `wariga:compute-dewasa`.
 */
class WarigaLoadRules extends Command
{
    protected $signature = 'wariga:load-rules {path=database/data/dewasa_rules.json}';

    protected $description = 'Validasi & muat aturan dewasa + urutan prioritas dari JSON';

    public function handle(DewasaRuleLoader $loader): int
    {
        $path = (string) $this->argument('path');
        if (! is_file($path)) {
            $this->components->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        try {
            $result = $loader->load($path);
        } catch (InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            '%d aturan, %d efek, %d prioritas dimuat; %d aturan lama dinonaktifkan.',
            $result['rules'], $result['effects'], $result['precedence'], $result['deactivated'],
        ));

        return self::SUCCESS;
    }
}
