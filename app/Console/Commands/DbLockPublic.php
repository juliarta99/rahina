<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Menyalakan Row Level Security (tanpa policy) di semua tabel schema `public`,
 * sehingga Data API Supabase (anon/authenticated) tidak bisa membaca/menulis apa pun.
 * Laravel terhubung sebagai role pemilik tabel sehingga tidak terpengaruh.
 *
 * Input: --check (hanya laporkan tabel yang belum terkunci, exit 1 jika ada).
 * Output: daftar tabel yang dikunci. Tidak melakukan apa pun di driver selain pgsql.
 */
class DbLockPublic extends Command
{
    protected $signature = 'db:lock-public {--check : Hanya periksa, tanpa mengubah}';

    protected $description = 'Aktifkan RLS di semua tabel schema public (menutup Data API Supabase)';

    public function handle(): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->components->info('Driver bukan pgsql, dilewati.');

            return self::SUCCESS;
        }

        /** @var list<object{tablename: string}> $unlocked */
        $unlocked = DB::select(<<<'SQL'
            select c.relname as tablename
            from pg_class c
            join pg_namespace n on n.oid = c.relnamespace
            where n.nspname = 'public' and c.relkind in ('r', 'p') and not c.relrowsecurity
            order by c.relname
        SQL);

        if ($unlocked === []) {
            $this->components->info('Semua tabel public sudah memakai RLS.');

            return self::SUCCESS;
        }

        if ($this->option('check')) {
            foreach ($unlocked as $table) {
                $this->components->warn("Belum RLS: {$table->tablename}");
            }

            return self::FAILURE;
        }

        foreach ($unlocked as $table) {
            $name = str_replace('"', '""', $table->tablename);
            DB::statement("alter table public.\"{$name}\" enable row level security");
            $this->components->task("RLS aktif: {$table->tablename}");
        }

        return self::SUCCESS;
    }
}
