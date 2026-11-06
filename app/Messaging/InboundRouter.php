<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Actions\AskWarigaAction;
use App\Models\User;
use App\Models\WhatsappSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Memproses pesan masuk WhatsApp (dijalankan di queue, bukan di request webhook).
 * Kata kunci: DAFTAR <kode>, BERHENTI/STOP, MULAI, BANTUAN, PROFIL, HAPUS DATA. Selain itu = pertanyaan ke
 * Tanya Wariga (agent yang sama dengan web). Pertanyaan dikirim ke Gemini TANPA nomor/identitas.
 */
final class InboundRouter
{
    public function __construct(
        private readonly WhatsAppSender $sender,
        private readonly LinkCodes $codes,
        private readonly AskWarigaAction $ask,
        private readonly MessageFormatter $formatter,
    ) {}

    public function handle(string $phone, string $text): void
    {
        $command = mb_strtoupper(trim((string) preg_replace('/\s+/', ' ', $text)));
        $subscription = WhatsappSubscription::findByPhone($phone);

        if (preg_match('/^DAFTAR\s+([A-Z0-9]{6})$/', $command, $m) === 1) {
            $this->register($phone, $this->codes->redeem($m[1]), $subscription);

            return;
        }

        // "DAFTAR" tanpa kode dari nomor yang sama dengan nomor akun: langsung tersambung
        // (pesan masuk dari nomor itu = bukti kepemilikan nomor).
        if ($command === 'DAFTAR') {
            $this->register($phone, $this->pendingForAccountPhone($phone), $subscription);

            return;
        }

        if ($subscription === null) {
            $this->sender->replyToUnknown($phone, 'Nomor ini belum terdaftar di Rahina. Daftar lewat menu Profil di situs Rahina, lalu tekan tombol "Hubungkan WhatsApp".');

            return;
        }

        $subscription->last_inbound_at = now();
        $subscription->save();

        match (true) {
            in_array($command, ['BERHENTI', 'STOP', 'UNREG'], true) => $this->stop($subscription),
            in_array($command, ['MULAI', 'START'], true) => $this->start($subscription),
            in_array($command, ['BANTUAN', 'HELP', 'MENU', '?'], true) => $this->sender->send($subscription, 'system', MessageFormatter::help()),
            $command === 'PROFIL' => $this->sender->send($subscription, 'system', $this->profile($subscription)),
            $command === 'HAPUS DATA' => $this->forget($subscription),
            default => $this->answer($subscription, $text),
        };
    }

    /** Langganan milik akun yang nomor HP-nya sama dengan pengirim (belum aktif). */
    private function pendingForAccountPhone(string $phone): ?WhatsappSubscription
    {
        $user = User::query()->where('phone', $phone)->first();

        return $user === null ? null : WhatsappSubscription::query()->where('user_id', $user->id)->first();
    }

    private function register(string $phone, ?WhatsappSubscription $subscription, ?WhatsappSubscription $existing): void
    {
        if ($subscription === null) {
            $this->sender->replyToUnknown($phone, 'Pendaftaran tidak ditemukan. Masuk ke situs Rahina, buka Profil, tekan "Aktifkan Rahina Pagi", lalu kirim DAFTAR dari nomor akun Anda (atau DAFTAR <kode> dari nomor lain).');

            return;
        }
        if ($existing !== null && $existing->id !== $subscription->id) {
            $this->sender->replyToUnknown($phone, 'Nomor ini sudah terhubung ke akun Rahina lain. Ketik HAPUS DATA dari nomor ini, lalu daftar ulang.');

            return;
        }

        $subscription->setPhone($phone);
        $subscription->status = WhatsappSubscription::ACTIVE;
        $subscription->verified_at = now();
        $subscription->opted_out_at = null;
        $subscription->consecutive_failures = 0;
        $subscription->last_inbound_at = now();
        $subscription->save();

        $this->sender->send($subscription, 'system', implode("\n", [
            '*Rahina Pagi aktif.*',
            'Anda akan menerima ringkasan hari menurut wariga setiap pukul 07.00 WITA.',
            'Simpan nomor ini agar pesan tidak masuk spam. Balas pesan ini kapan saja untuk bertanya hari baik.',
            'Ketik BERHENTI untuk berhenti, BANTUAN untuk daftar perintah.',
        ]));
    }

    private function stop(WhatsappSubscription $subscription): void
    {
        $subscription->status = WhatsappSubscription::OPTED_OUT;
        $subscription->opted_out_at = now();
        $subscription->save();
        $this->sender->send($subscription, 'system', 'Ringkasan pagi dihentikan. Ketik MULAI untuk mengaktifkan lagi, atau HAPUS DATA untuk menghapus nomor Anda.');
    }

    private function start(WhatsappSubscription $subscription): void
    {
        $subscription->status = WhatsappSubscription::ACTIVE;
        $subscription->opted_out_at = null;
        $subscription->consecutive_failures = 0;
        $subscription->save();
        $this->sender->send($subscription, 'system', 'Ringkasan pagi aktif lagi mulai besok pukul 07.00 WITA.');
    }

    private function forget(WhatsappSubscription $subscription): void
    {
        $phone = (string) $subscription->phone_encrypted;
        DB::table('whatsapp_link_codes')->where('subscription_id', $subscription->id)->delete();
        $subscription->delete(); // whatsapp_messages ikut terhapus (cascade)
        $this->sender->replyToUnknown($phone, 'Nomor dan data Anda sudah dihapus dari Rahina. Terima kasih.');
    }

    private function profile(WhatsappSubscription $subscription): string
    {
        $status = match ($subscription->status) {
            WhatsappSubscription::ACTIVE => 'aktif',
            WhatsappSubscription::OPTED_OUT, WhatsappSubscription::PAUSED => 'berhenti',
            default => $subscription->status,
        };

        return implode("\n", [
            '*Profil Rahina*',
            "Ringkasan pagi: {$status}",
            'Tanggal lahir untuk otonan: '.($subscription->birth_date_encrypted !== null ? 'tersimpan' : 'belum diisi'),
            'Ubah pengaturan di menu Profil situs Rahina.',
        ]);
    }

    private function answer(WhatsappSubscription $subscription, string $text): void
    {
        // Batas laju per pelanggan, sama dengan web (AI_IMPLEMENTATION.md §8).
        $key = 'ask:wa:'.$subscription->id;
        if (RateLimiter::tooManyAttempts($key, (int) config('rahina.ai_rate_per_min'))) {
            $this->sender->send($subscription, 'answer', 'Terlalu banyak pertanyaan dalam satu menit. Tunggu sebentar lalu coba lagi.');

            return;
        }
        RateLimiter::hit($key);

        $answer = $this->ask->execute(mb_substr($text, 0, 500));
        $this->sender->send($subscription, 'answer', $this->formatter->answer($answer));
    }
}
