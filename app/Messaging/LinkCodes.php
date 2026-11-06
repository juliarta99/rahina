<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Models\WhatsappSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Kode 6 karakter untuk menghubungkan nomor WhatsApp: pengguna mengirim "DAFTAR <KODE>" ke nomor Rahina,
 * sehingga kepemilikan nomor terbukti dan percakapan dimulai oleh pengguna (WHATSAPP.md §3.1).
 */
final class LinkCodes
{
    /** Tanpa huruf/angka yang mirip (0/O, 1/I/L). */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function issue(WhatsappSubscription $subscription): string
    {
        DB::table('whatsapp_link_codes')->where('subscription_id', $subscription->id)->whereNull('used_at')->delete();

        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (DB::table('whatsapp_link_codes')->where('code', $code)->exists());

        DB::table('whatsapp_link_codes')->insert([
            'code' => $code,
            'subscription_id' => $subscription->id,
            'expires_at' => now()->addMinutes((int) config('messaging.link_code_minutes')),
        ]);

        return $code;
    }

    /** Kode aktif (belum dipakai & belum kedaluwarsa) milik langganan, untuk ditampilkan lagi di web. */
    public function active(WhatsappSubscription $subscription): ?string
    {
        $code = DB::table('whatsapp_link_codes')->where('subscription_id', $subscription->id)
            ->whereNull('used_at')->where('expires_at', '>', now())->value('code');

        return is_string($code) ? $code : null;
    }

    /** Tukar kode menjadi langganan; null bila kode salah/kedaluwarsa/sudah dipakai. */
    public function redeem(string $code): ?WhatsappSubscription
    {
        $code = Str::upper(trim($code));
        $row = DB::table('whatsapp_link_codes')->where('code', $code)->whereNull('used_at')->where('expires_at', '>', now())->first();
        if ($row === null) {
            return null;
        }
        DB::table('whatsapp_link_codes')->where('code', $code)->update(['used_at' => now()]);

        return WhatsappSubscription::query()->find((int) $row->subscription_id);
    }

    public static function waLink(string $device, string $code): string
    {
        return 'https://wa.me/'.$device.'?text='.rawurlencode("DAFTAR {$code}");
    }
}
