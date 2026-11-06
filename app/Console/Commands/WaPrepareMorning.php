<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\BuildDailyBriefAction;
use App\Jobs\SendMorningBrief;
use App\Messaging\MessagingGateway;
use App\Models\WhatsappSubscription;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Menjadwalkan ringkasan pagi untuk semua langganan aktif (WHATSAPP.md §3.2), dijalankan 05:45 WITA.
 * - Device harus terhubung; bila terputus, batch DITUNDA (tidak dibuang) dan dicatat untuk admin.
 * - Ringkasan hari ini dipastikan ada (AI atau template) sebelum dikirim.
 * - Job dijadwalkan bertahap mulai send_time dengan jeda acak delay_seconds (tanpa blast serentak).
 */
class WaPrepareMorning extends Command
{
    protected $signature = 'wa:prepare-morning {--date= : Tanggal YYYY-MM-DD (default hari ini WITA)} {--now : Kirim mulai sekarang, bukan pukul send_time}';

    protected $description = 'Jadwalkan ringkasan pagi WhatsApp untuk langganan aktif';

    public function handle(MessagingGateway $gateway, BuildDailyBriefAction $briefs): int
    {
        $date = (string) ($this->option('date') ?: CarbonImmutable::today()->toDateString());

        $device = $gateway->deviceInfo();
        if (! $device['connected']) {
            Log::error('[wa] device terputus; batch ringkasan pagi ditunda', ['date' => $date]);
            $this->components->error('Device WhatsApp tidak terhubung. Batch ditunda; periksa dashboard Fonnte lalu jalankan ulang.');

            return self::FAILURE;
        }

        if ($briefs->execute($date) === null) {
            $this->components->error("Tanggal {$date} tidak ada di kalender.");

            return self::FAILURE;
        }

        [$min, $max] = array_map('intval', (array) config('messaging.delay_seconds'));
        [$hour, $minute] = array_map('intval', explode(':', (string) config('messaging.send_time')));
        $start = $this->option('now') ? now() : CarbonImmutable::parse($date)->setTime($hour, $minute);
        $at = $start->isPast() ? now()->toImmutable() : CarbonImmutable::instance($start);
        $windowEnd = $at->addMinutes((int) config('messaging.send_window_minutes'));

        $count = 0;
        WhatsappSubscription::query()
            ->where('status', WhatsappSubscription::ACTIVE)
            ->whereNotNull('verified_at')
            ->where('frequency', 'daily')
            ->where(fn ($q) => $q->whereNull('last_sent_on')->orWhere('last_sent_on', '<', $date))
            ->orderBy('id')
            ->each(function (WhatsappSubscription $s) use (&$at, &$count, $date, $min, $max): void {
                SendMorningBrief::dispatch($s->id, $date)->delay($at);
                $at = $at->addSeconds(random_int($min, $max));
                $count++;
            });

        if ($at->gt($windowEnd)) {
            Log::warning('[wa] jadwal ringkasan pagi melewati jendela kirim', ['date' => $date, 'count' => $count]);
        }
        $this->components->info("{$count} pesan dijadwalkan untuk {$date}.");

        return self::SUCCESS;
    }
}
