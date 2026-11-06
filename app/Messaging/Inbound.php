<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Support\Phone;
use Illuminate\Http\Request;

/**
 * Pesan masuk dari webhook. `sender` & `device` dinormalisasi ke format 62xxxxxxxxxx.
 * Pembacaan field mengikuti format webhook Fonnte (device, sender, message, inboxid, timestamp).
 */
final readonly class Inbound
{
    public function __construct(
        public string $device,
        public string $sender,
        public string $text,
        public string $idempotencyKey,
    ) {}

    public static function fromFonnte(Request $request): ?self
    {
        $sender = $request->input('sender');
        $message = $request->input('message');
        $device = $request->input('device');
        if (! is_scalar($sender) || ! is_string($message) || trim($message) === '' || ! is_scalar($device)) {
            return null;
        }

        // Idempotensi (Fonnte me-retry webhook): pakai inboxid bila ada, selain itu pengirim + waktu + isi.
        $inboxId = $request->input('inboxid');
        $key = is_scalar($inboxId) && (string) $inboxId !== ''
            ? 'fonnte:'.$inboxId
            : implode('|', [(string) $sender, (string) $request->input('timestamp', ''), $message]);

        return new self(
            device: self::normalizePhone((string) $device),
            sender: self::normalizePhone((string) $sender),
            text: mb_substr(trim($message), 0, 1000),
            idempotencyKey: hash('sha256', $key),
        );
    }

    /** Normalisasi nomor Indonesia: 08xx → 628xx, buang karakter non-angka. */
    public static function normalizePhone(string $raw): string
    {
        return Phone::normalize($raw);
    }
}
