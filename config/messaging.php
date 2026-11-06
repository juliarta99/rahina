<?php

declare(strict_types=1);

/*
| Rahina Pagi via WhatsApp (docs/WHATSAPP.md §7). Hanya driver & token dari .env;
| pengaturan pengiriman ada di sini.
*/

return [

    // fonnte | fake (test & lokal). cloud_api direncanakan.
    'driver' => env('MESSAGING_DRIVER', 'fake'),

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
        'api_url' => env('FONNTE_API_URL', 'https://api.fonnte.com'),
        'timeout_seconds' => 15,
    ],

    // Nomor device untuk driver fake (tautan wa.me di lokal). Fonnte membaca dari /device.
    'fake_device' => '620000000000',

    'send_time' => env('WA_SEND_TIME', '06:00'),            // WITA
    'send_window_minutes' => 30,
    'delay_seconds' => [4, 12],        // jeda acak antar pesan
    'device_cache_minutes' => 60,
    'link_code_minutes' => 15,
    'max_consecutive_failures' => 3,

];
