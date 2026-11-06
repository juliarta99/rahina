<?php

declare(strict_types=1);

/*
| Konfigurasi domain Rahina. Nama model Gemini HANYA diambil dari sini
| (sumber: GEMINI_MODEL di .env). Jangan hardcode di kelas lain.
*/

return [

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'temperature' => 0.2,
        'timeout_seconds' => 15,
        // Jeda retry untuk 429/5xx/koneksi (AI_IMPLEMENTATION.md §8).
        'retry_delays_ms' => [500, 1500],
    ],

    'agent' => [
        'max_steps' => (int) env('RAHINA_AGENT_MAX_STEPS', 4),
        'max_range_days' => 92,
        // Gemini 3 "thinking level". minimal ≈ 1-2 s per langkah vs ±10 s default (diukur 2026-11-06);
        // tugas agent sempit & terstruktur, perhitungan tetap di kode.
        'thinking_level' => env('RAHINA_AGENT_THINKING', 'minimal'),
    ],

    'ai_rate_per_min' => (int) env('RAHINA_AI_RATE_PER_MIN', 20),

    'brief' => [
        'prompt' => 'daily_brief',
        'prompt_version' => 1,
        // Batas waktu generate sinkron saat halaman dibuka (ARCHITECTURE.md §2.2).
        'sync_timeout_seconds' => 10,
        // Setelah gagal, jangan coba AI lagi untuk tanggal yang sama selama ini (hemat kuota).
        'fallback_cooldown_minutes' => 15,
    ],

    'brief_cache_hours' => (int) env('RAHINA_BRIEF_CACHE_HOURS', 24),

    'dewasa' => [
        // Aturan dari sumber berizin ini boleh tampil walau belum ditinjau narasumber Rahina
        // (validated_at kosong). UI tetap menandainya "belum ditinjau". Kosongkan untuk mode ketat.
        'display_unreviewed_sources' => ['kalenderbali-org'],
    ],

    'calendar' => [
        'min_year' => 2020,
        'max_year' => 2035,
    ],

];
