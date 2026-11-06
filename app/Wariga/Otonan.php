<?php

declare(strict_types=1);

namespace App\Wariga;

use Carbon\CarbonImmutable;

/**
 * Otonan = hari kelahiran menurut pawukon (kombinasi wuku + saptawara + pancawara), berulang tepat setiap 210 hari
 * (30 wuku × 7 hari; 7 dan 5 sama-sama membagi 210). Perhitungan aritmetika murni, tanpa AI dan tanpa data pribadi
 * keluar dari server. Tanggal lahir di luar rentang tabel kalender tetap bisa dihitung karena hanya memakai siklus.
 */
final class Otonan
{
    public const CYCLE_DAYS = 210;

    /** Otonan berikutnya pada atau setelah `$from`. */
    public static function next(CarbonImmutable $birthDate, CarbonImmutable $from): CarbonImmutable
    {
        $birth = $birthDate->startOfDay();
        $from = $from->startOfDay();
        if ($from->lte($birth)) {
            return $birth;
        }
        $elapsed = (int) $birth->diffInDays($from);
        $remainder = $elapsed % self::CYCLE_DAYS;

        return $remainder === 0 ? $from : $from->addDays(self::CYCLE_DAYS - $remainder);
    }

    public static function daysUntilNext(CarbonImmutable $birthDate, CarbonImmutable $from): int
    {
        return (int) $from->startOfDay()->diffInDays(self::next($birthDate, $from));
    }
}
