<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mengaktifkan ekstensi Postgres yang dipakai Rahina (pg_trgm di schema `extensions`,
 * sesuai docs/SUPABASE.md §1). Dilewati pada driver selain pgsql (mis. SQLite untuk test).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('create schema if not exists extensions');
        DB::statement('create extension if not exists pg_trgm with schema extensions');
    }

    public function down(): void
    {
        // Sengaja tidak menghapus ekstensi: bisa dipakai objek lain di database.
    }
};
