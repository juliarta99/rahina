<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Wariga\CalendarRepository;
use App\Wariga\Otonan;
use Carbon\CarbonImmutable;

/**
 * Bagian "Untuk Anda" di pesan pagi (docs/WHATSAPP.md §4). Deterministik, TANPA LLM; tanggal lahir tidak keluar dari server.
 * Maksimal 2 catatan: otonan hari ini / ≤ 14 hari lagi, lalu rahinan pawukon/sasih ≤ 7 hari lagi.
 * Tidak memuat ramalan watak atau nasib.
 */
final class PersonalNoteBuilder
{
    public function __construct(private readonly CalendarRepository $calendar) {}

    /**
     * @return list<string>
     */
    public function build(CarbonImmutable $today, ?CarbonImmutable $birthDate): array
    {
        $notes = [];

        if ($birthDate !== null) {
            $days = Otonan::daysUntilNext($birthDate, $today);
            if ($days === 0) {
                $notes[] = 'Hari ini otonan Anda.';
            } elseif ($days <= 14) {
                $date = Otonan::next($birthDate, $today);
                $notes[] = "Otonan Anda {$days} hari lagi, ".self::shortDate($date).'.';
            }
        }

        foreach ($this->calendar->upcomingRahinan($today->addDay(), 6, 7) as $r) {
            if (in_array($r['kind'], ['pawukon', 'sasih'], true)) {
                $days = $r['days_until'] + 1;
                $notes[] = "{$r['name']} {$days} hari lagi, ".self::shortDate(CarbonImmutable::parse($r['date'])).'.';
                break;
            }
        }

        return array_slice($notes, 0, 2);
    }

    public static function shortDate(CarbonImmutable $date): string
    {
        return $date->locale('id')->translatedFormat('l, j F');
    }
}
