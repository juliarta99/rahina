<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AskWarigaAction;
use App\Http\Requests\AskRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman "Tanya Wariga" (Inertia) dan endpoint jawaban (JSON). Tipis: logika di AskWarigaAction.
 * Gemini tidak pernah dipanggil dari frontend; semua lewat endpoint ini + OutputGuard.
 */
class AskController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('ask', ['today' => now()->toDateString()]);
    }

    public function store(AskRequest $request, AskWarigaAction $ask): JsonResponse
    {
        $key = 'ask:'.($request->user()?->getAuthIdentifier() ?? $request->ip());
        $perMinute = (int) config('rahina.ai_rate_per_min');
        if (RateLimiter::tooManyAttempts($key, $perMinute)) {
            return response()->json([
                'message' => 'Terlalu banyak pertanyaan dalam satu menit. Tunggu sebentar lalu coba lagi.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }
        RateLimiter::hit($key);

        $category = $request->validated('category');
        $answer = $ask->execute(
            (string) $request->validated('question'),
            is_string($category) ? $category : null,
            $request->history(),
        );

        return response()->json($answer->toArray());
    }
}
