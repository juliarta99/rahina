<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalisasi & validasi nomor HP Indonesia ke format 62xxxxxxxxxx (dipakai akun dan WhatsApp).
 * Menerima "0812-3456-7890", "+62 812 3456 7890", "6281234567890", "812 3456 7890".
 */
final class Phone
{
    public static function normalize(string $raw): string
    {
        $digits = (string) preg_replace('/\D+/', '', $raw);
        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }
        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    /** Nomor seluler Indonesia: 62 8xx, total 10–13 digit setelah 62. */
    public static function isValid(string $normalized): bool
    {
        return preg_match('/^628\d{8,11}$/', $normalized) === 1;
    }

    /** Tampilan ringkas, mis. 0812-3456-7890. */
    public static function display(?string $normalized): ?string
    {
        if ($normalized === null || $normalized === '') {
            return null;
        }
        $local = '0'.substr($normalized, 2);

        return trim(implode('-', [substr($local, 0, 4), substr($local, 4, 4), substr($local, 8)]), '-');
    }
}
