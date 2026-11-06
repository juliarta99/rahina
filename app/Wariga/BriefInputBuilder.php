<?php

declare(strict_types=1);

namespace App\Wariga;

use App\Data\BriefInput;

/**
 * Menyusun bahan ringkasan harian: hari kalender + dewasa yang boleh tampil, untuk semua domain kegiatan. Tanpa AI.
 */
final class BriefInputBuilder
{
    public function __construct(private readonly CalendarRepository $calendar) {}

    /**
     * @return BriefInput|null null jika tanggal belum ada di kalender.
     */
    public function build(string $date): ?BriefInput
    {
        $day = $this->calendar->day($date);
        if ($day === null) {
            return null;
        }

        $relevant = array_values(array_filter($this->calendar->dewasa($date), fn ($match): bool => $match->effects !== []));

        return new BriefInput($day, $relevant);
    }
}
