<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildDailyBriefAction;
use App\Http\Requests\CalendarRequest;
use App\Wariga\CalendarRepository;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller untuk Landing Page / Beranda publik Rahina.
 * Menampilkan hero section dengan maskot, pratinjau hari ini, tata cara,
 * keunggulan, dan informasi penting budaya.
 */
class LandingController extends Controller
{
    public function __invoke(
        CalendarRequest $request,
        CalendarRepository $calendar,
        BuildDailyBriefAction $builder
    ): Response {
        // Jika request membawa query bulan atau view=calendar, arahkan ke tampilan kalender
        if ($request->has('bulan') || $request->query('view') === 'calendar') {
            return app(CalendarController::class)($request, $calendar);
        }

        $today = CarbonImmutable::today();
        $todayDay = null;
        $summary = null;
        $upcoming = [];

        try {
            $todayDay = $calendar->day($today);
            if ($todayDay !== null) {
                $brief = $builder->execute($today->toDateString(), allowAi: false);
                $summary = $brief?->summary();
                $upcoming = $calendar->upcomingRahinan($today, limit: 3);
            }
        } catch (\Throwable) {
            // Graceful fallback jika database kalender belum siap di environment tertentu
        }

        return Inertia::render('welcome', [
            'today' => $today->toDateString(),
            'todayFormatted' => $today->locale('id')->isoFormat('dddd, D MMMM Y'),
            'todayDay' => $todayDay?->toArray(),
            'summary' => $summary,
            'upcoming' => $upcoming,
        ]);
    }
}
