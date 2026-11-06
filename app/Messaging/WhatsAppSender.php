<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Models\WhatsappSubscription;
use Illuminate\Support\Facades\DB;

/**
 * Mengirim satu pesan ke pelanggan lewat MessagingGateway dan mencatat `whatsapp_messages` (tanpa isi pesan).
 * Gagal beruntun ≥ max_consecutive_failures → langganan `failed` (WHATSAPP.md §5.5).
 * Hanya mengirim ke langganan yang sudah terverifikasi lewat "DAFTAR" (nomor tersimpan).
 */
final class WhatsAppSender
{
    public function __construct(private readonly MessagingGateway $gateway) {}

    public function send(WhatsappSubscription $subscription, string $kind, string $text, ?string $briefDate = null): SendResult
    {
        $phone = $subscription->phone_encrypted;
        if ($phone === null || $subscription->verified_at === null) {
            return new SendResult(false, error: 'not_verified');
        }

        $result = $this->gateway->send($phone, $text);

        DB::table('whatsapp_messages')->insert([
            'subscription_id' => $subscription->id,
            'direction' => 'out',
            'provider' => $this->gateway->name(),
            'provider_message_id' => $result->providerMessageId,
            'kind' => $kind,
            'status' => $result->ok ? 'sent' : 'failed',
            'error_code' => $result->error === null ? null : mb_substr($result->error, 0, 120),
            'brief_date' => $briefDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($result->ok) {
            $subscription->consecutive_failures = 0;
        } else {
            $subscription->consecutive_failures++;
            if ($subscription->consecutive_failures >= (int) config('messaging.max_consecutive_failures')) {
                $subscription->status = WhatsappSubscription::FAILED;
            }
        }
        $subscription->save();

        return $result;
    }

    /** Balasan ke nomor yang belum berlangganan (mereka yang memulai percakapan). Tidak dicatat nomornya. */
    public function replyToUnknown(string $phone, string $text): SendResult
    {
        return $this->gateway->send($phone, $text);
    }
}
