<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\BuildDailyBriefAction;
use App\Data\DailyBrief;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Mengisi cache `daily_briefs` (satu ringkasan umum per hari). Dijadwalkan harian; sebelum penjurian
 * jalankan `--days=14` agar demo tidak bergantung pada kuota API (ARCHITECTURE.md §4).
 */
class BriefGenerate extends Command
{
    protected $signature = 'brief:generate
        {--date= : Tanggal awal YYYY-MM-DD (default: hari ini WITA)}
        {--days=1 : Jumlah hari}
        {--force : Abaikan cache & jeda cooldown}';

    protected $description = 'Generate ringkasan harian Rahina Ini';

    public function handle(BuildDailyBriefAction $action): int
    {
        $dateOption = $this->option('date');
        if ($dateOption !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $dateOption) !== 1) {
            $this->components->error('--date harus berformat YYYY-MM-DD');

            return self::FAILURE;
        }

        $start = $dateOption === null ? CarbonImmutable::today() : CarbonImmutable::parse((string) $dateOption);
        $days = max(1, min(92, (int) $this->option('days')));

        $counts = ['ai' => 0, 'template' => 0, 'missing' => 0];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->addDays($i)->toDateString();
            $brief = $action->execute($date, (bool) $this->option('force'));
            $status = $brief === null ? 'missing' : ($brief->generatedBy === DailyBrief::BY_AI ? 'ai' : 'template');
            $counts[$status]++;
            $this->components->twoColumnDetail($date, match ($status) {
                'ai' => 'AI',
                'template' => 'template (fallback)',
                'missing' => 'tanggal tidak ada di kalender',
            });
        }

        $this->components->info("AI: {$counts['ai']}, template: {$counts['template']}, tidak tersedia: {$counts['missing']}.");

        return $counts['missing'] > 0 && $counts['ai'] + $counts['template'] === 0 ? self::FAILURE : self::SUCCESS;
    }
}
