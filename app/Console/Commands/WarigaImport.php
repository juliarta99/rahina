<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Wariga\CalendarImporter;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Mengimpor hasil `scripts/calendar/compute.mjs` (file .jsonl atau folder berisi .jsonl).
 * Output: jumlah hari yang diimpor. Exit 1 jika tidak ada file atau ada baris tidak valid.
 */
class WarigaImport extends Command
{
    protected $signature = 'wariga:import {path=storage/app/calendar : File .jsonl atau folder}';

    protected $description = 'Impor kalender hasil hitung engine ke calendar_days';

    public function handle(CalendarImporter $importer): int
    {
        $path = (string) $this->argument('path');
        $files = is_dir($path) ? (glob(rtrim($path, '/\\').DIRECTORY_SEPARATOR.'*.jsonl') ?: []) : (is_file($path) ? [$path] : []);
        sort($files);

        if ($files === []) {
            $this->components->error("Tidak ada file .jsonl di {$path}. Jalankan dulu: node scripts/calendar/compute.mjs 2020 2035");

            return self::FAILURE;
        }

        $total = 0;
        foreach ($files as $file) {
            try {
                $count = $importer->importFile($file);
            } catch (InvalidArgumentException $e) {
                $this->components->error($e->getMessage());

                return self::FAILURE;
            }
            $total += $count;
            $this->components->twoColumnDetail(basename($file), "{$count} hari");
        }

        $this->components->info("{$total} hari diimpor.");

        return self::SUCCESS;
    }
}
