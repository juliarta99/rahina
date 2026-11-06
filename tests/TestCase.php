<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Laravel\Fortify\Features;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->refuseRemoteDatabase();

        // Test tidak pernah memanggil API sungguhan (Gemini, Fonnte); semua lewat Http::fake().
        Http::preventStrayRequests();
    }

    /**
     * Test tidak boleh berjalan ke database Supabase (CLAUDE.md §10).
     * Biasanya terjadi bila `config:cache` aktif sehingga env phpunit.xml diabaikan.
     */
    private function refuseRemoteDatabase(): void
    {
        $default = (string) config('database.default');
        $host = (string) config("database.connections.{$default}.host", '');

        if (str_contains($host, 'supabase')) {
            throw new RuntimeException("Test menolak berjalan ke database remote [{$host}]. Jalankan `php artisan config:clear`.");
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
