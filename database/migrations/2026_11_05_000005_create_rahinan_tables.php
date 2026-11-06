<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar rahinan dan pemetaannya ke tanggal (hasil hitung engine).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rahinan', function (Blueprint $table): void {
            $table->smallInteger('id')->primary();
            $table->string('slug', 64)->unique();
            $table->string('name', 64);
            $table->enum('kind', ['pawukon', 'sasih', 'kajeng_kliwon', 'purnama_tilem', 'nasional']);
            $table->text('description')->nullable();
            $table->foreignId('source_id')->nullable()->constrained('sources');
        });

        Schema::create('calendar_day_rahinan', function (Blueprint $table): void {
            $table->date('date');
            $table->smallInteger('rahinan_id');
            $table->primary(['date', 'rahinan_id']);
            $table->foreign('date')->references('date')->on('calendar_days')->cascadeOnDelete();
            $table->foreign('rahinan_id')->references('id')->on('rahinan');
            $table->index('rahinan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_day_rahinan');
        Schema::dropIfExists('rahinan');
    }
};
