<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Wariga\DewasaEngine;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Mengevaluasi aturan dewasa aktif terhadap `calendar_days` dan mengisi `calendar_day_dewasa`.
 */
class WarigaComputeDewasa extends Command
{
    protected $signature = 'wariga:compute-dewasa
        {--from= : Tanggal awal YYYY-MM-DD (default: awal kalender)}
        {--to= : Tanggal akhir YYYY-MM-DD (default: akhir kalender)}
        {--rule=* : Hanya aturan dengan code ini}';

    protected $description = 'Hitung dewasa per tanggal dari dewasa_rules.conditions';

    public function handle(DewasaEngine $engine): int
    {
        $dates = [];
        foreach (['from', 'to'] as $name) {
            $value = $this->option($name);
            if ($value !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) !== 1) {
                $this->components->error("--{$name} harus berformat YYYY-MM-DD");

                return self::FAILURE;
            }
            $dates[$name] = $value === null ? null : (string) $value;
        }

        /** @var list<string> $rules */
        $rules = array_values(array_filter((array) $this->option('rule'), 'is_string'));

        try {
            $result = $engine->compute($dates['from'], $dates['to'], $rules);
        } catch (InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if ($result['rules'] === 0) {
            $this->components->warn('Belum ada aturan dewasa aktif. Isi database/data/dewasa_rules.json lalu jalankan wariga:load-rules.');
        }

        $this->components->info("{$result['days']} hari × {$result['rules']} aturan → {$result['matches']} kecocokan.");

        return self::SUCCESS;
    }
}
