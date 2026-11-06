<?php

declare(strict_types=1);

namespace App\Messaging;

use Illuminate\Http\Request;

/**
 * Abstraksi penyedia WhatsApp (docs/WHATSAPP.md §1). Implementasi: FonnteGateway (sekarang), FakeGateway (test/lokal),
 * CloudApiGateway (rencana). Logika bisnis hanya bergantung pada interface ini.
 */
interface MessagingGateway
{
    public function name(): string;

    /** Kirim satu pesan teks. Tidak melempar exception; kegagalan dikembalikan di SendResult. */
    public function send(string $to, string $message): SendResult;

    /**
     * Nomor device pengirim, status koneksi, dan kuota. Di-cache (config messaging.device_cache_minutes).
     *
     * @return array{device: ?string, connected: bool, quota: ?string}
     */
    public function deviceInfo(): array;

    /** Ubah request webhook menjadi pesan masuk; null bila bukan pesan teks yang valid. */
    public function parseInbound(Request $request): ?Inbound;
}
