<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache ringkasan harian (per tanggal × persona × versi prompt) dan log interaksi AI tanpa data pribadi.
 * Kolom daftar (rule_ids, returned_dates, dropped_dates) memakai jsonb agar portabel (Postgres & SQLite test).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_briefs', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->enum('persona', ['umum', 'petani', 'pedagang', 'pemangku']);
            $table->jsonb('content');
            $table->jsonb('rule_ids');
            $table->string('model', 64);
            $table->string('prompt_version', 32);
            $table->timestampTz('created_at');
            $table->unique(['date', 'persona', 'prompt_version']);
            $table->foreign('date')->references('date')->on('calendar_days')->cascadeOnDelete();
        });

        Schema::create('ai_interactions', function (Blueprint $table): void {
            $table->id();
            $table->enum('feature', ['brief', 'ask', 'map_activity', 'conflict']);
            $table->char('input_hash', 64);
            $table->jsonb('tool_calls')->nullable();
            $table->jsonb('returned_dates')->nullable();
            $table->jsonb('dropped_dates')->nullable();
            $table->string('model', 64)->nullable();
            $table->string('prompt_version', 32)->nullable();
            $table->integer('latency_ms')->nullable();
            $table->enum('status', ['ok', 'fallback', 'error', 'refused']);
            $table->text('error')->nullable();
            $table->timestampTz('created_at');
            $table->index(['feature', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_interactions');
        Schema::dropIfExists('daily_briefs');
    }
};
