<?php

use Illuminate\Support\Facades\Schedule;

/*
| Jadwal Rahina (zona waktu aplikasi: Asia/Makassar / WITA).
*/

// Ringkasan harian umum, sebelum ada pengguna yang membuka aplikasi (ARCHITECTURE.md §2).
Schedule::command('brief:generate')->dailyAt('00:05')->withoutOverlapping();

// Rahina Pagi via WhatsApp: jadwalkan pesan bertahap mulai 06:00 untuk langganan aktif (docs/WHATSAPP.md §3.2).
Schedule::command('wa:prepare-morning')->dailyAt('12:07')->withoutOverlapping();
