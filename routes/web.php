<?php

use App\Http\Controllers\AskController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DayDetailController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\RahinaProfileController;
use App\Http\Controllers\Webhooks\FonnteWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/kalender', CalendarController::class)->name('calendar');
Route::get('/landing', LandingController::class)->name('landing');
Route::get('/hari/{date}', DayDetailController::class)
    ->where('date', '\d{4}-\d{2}-\d{2}')
    ->name('day.show');
Route::get('/tanya', [AskController::class, 'show'])->name('ask');
Route::post('/tanya', [AskController::class, 'store'])->name('ask.store');

Route::get('/tentang', fn () => inertia('about'))->name('about');
Route::get('/tentang-kami', fn () => redirect()->route('about'));
Route::get('/kebijakan-privasi', fn () => inertia('privacy'))->name('privacy');
Route::get('/syarat-ketentuan', fn () => inertia('terms'))->name('terms');

// Pratinjau halaman error kustom (404, 500, 403, 503)
Route::get('/error/{code}', function (int $code = 404) {
    abort_if(! in_array($code, [404, 500, 403, 503], true), 404);

    return inertia('error', ['status' => $code]);
})->name('error.preview');

Route::post('/webhooks/fonnte/{secret}', FonnteWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.fonnte');

Route::middleware('auth')->group(function () {
    Route::get('/profil', [RahinaProfileController::class, 'show'])->name('rahina.profile');
    Route::post('/profil/whatsapp', [RahinaProfileController::class, 'enable'])->name('rahina.whatsapp.enable');
    Route::post('/profil/whatsapp/kode', [RahinaProfileController::class, 'newCode'])->name('rahina.whatsapp.code');
    Route::patch('/profil/whatsapp', [RahinaProfileController::class, 'update'])->name('rahina.whatsapp.update');
    Route::delete('/profil/whatsapp', [RahinaProfileController::class, 'destroy'])->name('rahina.whatsapp.destroy');
});

// Dashboard bawaan starter kit diarahkan ke profil Rahina.
Route::redirect('dashboard', '/profil')->name('dashboard');

require __DIR__.'/settings.php';
