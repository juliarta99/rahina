<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildDailyBriefAction;
use App\Data\DewasaMatch;
use App\Wariga\CalendarRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Detail satu tanggal untuk popup kalender (JSON): data perhitungan lengkap, dewasa + sumber,
 * dan ringkasan "Rahina Ini" (AI lewat OutputGuard, atau template bila AI gagal / batas laju tercapai).
 */
class DayDetailController extends Controller
{
    public function __invoke(Request $request, string $date, CalendarRepository $calendar, BuildDailyBriefAction $briefs): JsonResponse
    {
        validator(['date' => $date], ['date' => ['required', 'date_format:Y-m-d']])->validate();

        $day = $calendar->day($date);
        if ($day === null) {
            return response()->json(['message' => 'Tanggal ini belum tersedia di kalender Rahina (2020–2035).'], 404);
        }

        // Batas panggilan AI per pengguna (AI_IMPLEMENTATION.md §8). Bila lewat batas: cache atau template.
        $allowAi = RateLimiter::attempt(
            'brief:'.($request->user()?->getAuthIdentifier() ?? $request->ip()),
            (int) config('rahina.ai_rate_per_min'),
            fn (): bool => true,
        );

        $brief = $briefs->execute($date, timeoutSeconds: (int) config('rahina.brief.sync_timeout_seconds'), allowAi: $allowAi);
        $dewasa = $calendar->dewasa($date);

        $referenced = $brief?->ruleIds() ?? [];
        $culturalNotice = array_filter(
            $dewasa,
            fn (DewasaMatch $d): bool => in_array($d->ruleId, $referenced, true)
                && in_array('upacara', array_column($d->effects, 'domain'), true),
        ) !== [];

        return response()->json([
            'date' => $date,
            'day' => $day->toArray(),
            'brief' => $brief?->toArray(),
            'summary' => $brief?->summary(),
            'dewasa' => array_map(fn (DewasaMatch $d): array => $d->toArray(), $dewasa),
            'sourceCount' => count(array_unique(array_map(fn (DewasaMatch $d): string => $d->sourceTitle, $dewasa))),
            'culturalNotice' => $culturalNotice,
        ]);
    }
}
