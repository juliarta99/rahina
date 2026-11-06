<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sumber berupa situs web (mis. kalenderbali.org, dipakai dengan izin pemilik).
 * Postgres: enum Laravel = varchar + CHECK constraint, sehingga constraint-nya diganti langsung
 * (`enum()->change()` menghasilkan SQL yang tidak valid di Postgres).
 */
return new class extends Migration
{
    private const KINDS = ['book', 'lontar', 'journal', 'interview', 'software'];

    public function up(): void
    {
        $this->setKinds([...self::KINDS, 'website']);
    }

    public function down(): void
    {
        $this->setKinds(self::KINDS);
    }

    /**
     * @param  list<string>  $kinds
     */
    private function setKinds(array $kinds): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $list = implode(', ', array_map(fn (string $k): string => "'{$k}'", $kinds));
            DB::statement('alter table sources drop constraint if exists sources_kind_check');
            DB::statement("alter table sources add constraint sources_kind_check check (kind in ({$list}))");

            return;
        }

        Schema::table('sources', function (Blueprint $table) use ($kinds): void {
            $table->enum('kind', $kinds)->change();
        });
    }
};
