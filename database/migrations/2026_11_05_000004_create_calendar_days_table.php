<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil perhitungan kalender per hari (diisi `wariga:import`, tidak pernah oleh LLM).
 */
return new class extends Migration
{
    private const WEWARAN = [
        'ekawara', 'dwiwara', 'triwara', 'caturwara', 'pancawara',
        'sadwara', 'saptawara', 'astawara', 'sangawara', 'dasawara',
    ];

    public function up(): void
    {
        Schema::create('calendar_days', function (Blueprint $table): void {
            $table->date('date')->primary();
            $table->smallInteger('saka_year');
            $table->smallInteger('wuku_id');
            $table->foreign('wuku_id')->references('id')->on('wuku');

            foreach (self::WEWARAN as $cycle) {
                $column = "{$cycle}_id";
                $table->smallInteger($column)->nullable($cycle === 'ekawara');
                $table->foreign($column)->references('id')->on('wewaran_values');
            }

            $table->smallInteger('urip_total');
            $table->smallInteger('sasih_id');
            $table->foreign('sasih_id')->references('id')->on('sasih');
            $table->smallInteger('sasih_day');
            $table->smallInteger('sasih_day_end')->nullable();
            $table->boolean('is_penanggal');
            $table->boolean('is_purnama');
            $table->boolean('is_tilem');
            $table->boolean('is_nguna_ratri');
            $table->string('ingkel', 16)->nullable();
            $table->string('engine', 32);
            $table->timestampTz('computed_at');

            $table->index('wuku_id');
            $table->index(['sasih_id', 'sasih_day']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('create index calendar_days_purnama_idx on calendar_days (date) where is_purnama');
            DB::statement('create index calendar_days_tilem_idx on calendar_days (date) where is_tilem');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_days');
    }
};
