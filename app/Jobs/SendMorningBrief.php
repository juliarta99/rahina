<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\BuildDailyBriefAction;
use App\Messaging\MessageFormatter;
use App\Messaging\PersonalNoteBuilder;
use App\Messaging\WhatsAppSender;
use App\Models\WhatsappSubscription;
use App\Wariga\CalendarRepository;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Kirim ringkasan pagi ke SATU pelanggan aktif. Idempoten: satu pesan per langganan per tanggal
 * (unik subscription_id + kind + brief_date). Gagal kirim → retry 3× (1, 5, 15 menit).
 * Ringkasan diambil dari cache `daily_briefs` (AI lewat OutputGuard) atau template bila AI gagal.
 */
class SendMorningBrief implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly int $subscriptionId,
        public readonly string $date,
    ) {}

    public function handle(
        BuildDailyBriefAction $briefs,
        CalendarRepository $calendar,
        PersonalNoteBuilder $notes,
        MessageFormatter $formatter,
        WhatsAppSender $sender,
    ): void {
        $subscription = WhatsappSubscription::query()->find($this->subscriptionId);
        if ($subscription === null || $subscription->status !== WhatsappSubscription::ACTIVE) {
            return; // berhenti berlangganan setelah dijadwalkan
        }

        $alreadySent = DB::table('whatsapp_messages')->where('subscription_id', $subscription->id)
            ->where('kind', 'morning_brief')->where('brief_date', $this->date)->where('status', 'sent')->exists();
        if ($alreadySent) {
            return;
        }

        $day = $calendar->day($this->date);
        $brief = $briefs->execute($this->date);
        if ($day === null || $brief === null) {
            return;
        }

        $birth = $subscription->birth_date_encrypted;
        $text = $formatter->morning($brief, $day, $notes->build(
            CarbonImmutable::parse($this->date),
            $birth === null ? null : CarbonImmutable::parse($birth),
        ));

        // Catatan gagal sebelumnya (retry) dihapus agar indeks unik per tanggal tidak menghalangi.
        DB::table('whatsapp_messages')->where('subscription_id', $subscription->id)
            ->where('kind', 'morning_brief')->where('brief_date', $this->date)->where('status', 'failed')->delete();

        $result = $sender->send($subscription, 'morning_brief', $text, $this->date);
        if (! $result->ok) {
            throw new RuntimeException('Kirim ringkasan pagi gagal: '.$result->error);
        }

        $subscription->last_sent_on = CarbonImmutable::parse($this->date);
        $subscription->save();
    }
}
