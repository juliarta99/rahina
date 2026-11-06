<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\CalendarDay;
use App\Http\Requests\CalendarRequest;
use App\Wariga\CalendarRepository;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman utama: kalender bulanan + panel "Hari Ini" + rahinan mendatang.
 * Tidak memanggil AI; ringkasan AI dimuat saat tanggal diklik (DayDetailController).
 */
class CalendarController extends Controller
{
    public function __invoke(CalendarRequest $request, CalendarRepository $calendar): Response
    {
        $month = $request->month();
        $today = CarbonImmutable::today();

        // Grid 6 minggu, minggu dimulai Minggu (Redite).
        $gridStart = $month->startOfMonth()->subDays($month->startOfMonth()->dayOfWeek);
        $gridEnd = $gridStart->addDays(41);

        $days = [];
        foreach ($calendar->range($gridStart, $gridEnd) as $day) {
            $days[$day->date] = $day;
        }

        $counts = $calendar->dewasaCounts($gridStart, $gridEnd);

        $cells = [];
        for ($d = $gridStart; $d->lte($gridEnd); $d = $d->addDay()) {
            $key = $d->toDateString();
            $cells[] = self::cell($key, $d->month === $month->month, $days[$key] ?? null, $counts[$key] ?? null);
        }

        $todayDay = $calendar->day($today);

        return Inertia::render('calendar', [
            'month' => $month->format('Y-m'),
            'today' => $today->toDateString(),
            'minMonth' => config('rahina.calendar.min_year').'-01',
            'maxMonth' => config('rahina.calendar.max_year').'-12',
            'weeks' => array_chunk($cells, 7),
            'sasihInMonth' => array_values(array_unique(array_map(
                fn (CalendarDay $day): string => $day->sasih->name,
                array_filter($days, fn (CalendarDay $day): bool => str_starts_with($day->date, $month->format('Y-m'))),
            ))),
            'todayDay' => $todayDay?->toArray(),
            'upcoming' => $todayDay === null ? [] : $calendar->upcomingRahinan($today),
        ]);
    }

    /**
     * @param  array{baik: int, tidak_baik: int}|null  $counts
     * @return array<string, mixed>
     */
    private static function cell(string $date, bool $inMonth, ?CalendarDay $day, ?array $counts): array
    {
        if ($day === null) {
            return ['date' => $date, 'in_month' => $inMonth, 'available' => false];
        }

        return [
            'date' => $date,
            'in_month' => $inMonth,
            'available' => true,
            'saptawara' => $day->saptawara(),
            'pancawara' => $day->pancawara(),
            'wuku' => $day->wuku->name,
            'sasih' => $day->sasih->name,
            'sasih_day' => $day->sasihDay,
            'sasih_day_end' => $day->sasihDayEnd,
            'is_penanggal' => $day->isPenanggal,
            'is_purnama' => $day->isPurnama,
            'is_tilem' => $day->isTilem,
            'is_nguna_ratri' => $day->isNgunaRatri,
            'rahinan' => array_values(array_map(
                fn (array $r): string => $r['name'],
                array_filter($day->rahinan, fn (array $r): bool => ! in_array($r['kind'], ['purnama_tilem'], true)),
            )),
            'dewasa' => $counts ?? ['baik' => 0, 'tidak_baik' => 0],
        ];
    }
}
