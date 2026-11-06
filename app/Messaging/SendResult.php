<?php

declare(strict_types=1);

namespace App\Messaging;

/**
 * Hasil pengiriman: sukses + id pesan penyedia, atau gagal + kode error singkat (tanpa data pribadi).
 */
final readonly class SendResult
{
    public function __construct(
        public bool $ok,
        public ?string $providerMessageId = null,
        public ?string $error = null,
    ) {}
}
