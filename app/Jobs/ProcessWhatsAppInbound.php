<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Messaging\InboundRouter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Pesan masuk WhatsApp diproses di queue (webhook hanya menyimpan hash & langsung 200 OK, WHATSAPP.md §2.2).
 * Payload job hanya berisi nomor pengirim & teks; tidak ditulis ke log. Tidak di-retry agar balasan tidak dobel.
 */
class ProcessWhatsAppInbound implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 90;

    public function __construct(
        public readonly string $phone,
        public readonly string $text,
    ) {}

    public function handle(InboundRouter $router): void
    {
        $router->handle($this->phone, $this->text);
    }
}
