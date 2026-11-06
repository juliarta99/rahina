<?php

namespace App\Providers;

use App\Messaging\Gateways\FakeGateway;
use App\Messaging\Gateways\FonnteGateway;
use App\Messaging\MessagingGateway;
use App\Wariga\CalendarRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Satu instance per request agar data master tidak dimuat berulang.
        $this->app->scoped(CalendarRepository::class);

        // Driver WhatsApp dari config/messaging.php (fonnte | fake). Fake = singleton agar test bisa memeriksa pesan.
        $this->app->singleton(FakeGateway::class);
        $this->app->bind(MessagingGateway::class, fn ($app): MessagingGateway => match (config('messaging.driver')) {
            'fonnte' => $app->make(FonnteGateway::class),
            default => $app->make(FakeGateway::class),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->lockPublicSchemaAfterMigrations();
    }

    /**
     * Setiap selesai `migrate` di Postgres, nyalakan RLS untuk tabel baru (docs/SUPABASE.md §4).
     */
    protected function lockPublicSchemaAfterMigrations(): void
    {
        Event::listen(MigrationsEnded::class, function (MigrationsEnded $event): void {
            if ($event->method === 'up' && DB::getDriverName() === 'pgsql') {
                Artisan::call('db:lock-public');
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
