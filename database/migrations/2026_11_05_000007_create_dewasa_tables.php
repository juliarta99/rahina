<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aturan dewasa (data, bukan kode), efeknya ke kategori aktivitas, urutan prioritas,
 * dan hasil evaluasi per tanggal (`wariga:compute-dewasa`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dewasa_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name', 96);
            $table->text('summary');
            $table->enum('verdict', ['ayu', 'ala', 'campur']);
            $table->smallInteger('alahing_level')->nullable();
            $table->jsonb('conditions');
            $table->foreignId('source_id')->constrained('sources');
            $table->string('source_page', 32)->nullable();
            $table->string('validated_by')->nullable();
            $table->timestampTz('validated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dewasa_rule_effects', function (Blueprint $table): void {
            $table->foreignId('rule_id')->constrained('dewasa_rules')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('activity_categories');
            $table->enum('effect', ['baik', 'tidak_baik']);
            $table->text('note')->nullable();
            $table->primary(['rule_id', 'category_id', 'effect']);
            $table->index('category_id');
        });

        Schema::create('dewasa_precedence', function (Blueprint $table): void {
            $table->smallIncrements('id');
            $table->string('layer', 32)->unique();
            $table->smallInteger('rank');
            $table->foreignId('source_id')->constrained('sources');
            $table->string('source_page', 32)->nullable();
        });

        Schema::create('calendar_day_dewasa', function (Blueprint $table): void {
            $table->date('date');
            $table->foreignId('rule_id')->constrained('dewasa_rules')->cascadeOnDelete();
            $table->jsonb('matched_on');
            $table->primary(['date', 'rule_id']);
            $table->foreign('date')->references('date')->on('calendar_days')->cascadeOnDelete();
            $table->index('rule_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('create index dewasa_rules_conditions_gin on dewasa_rules using gin (conditions)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_day_dewasa');
        Schema::dropIfExists('dewasa_precedence');
        Schema::dropIfExists('dewasa_rule_effects');
        Schema::dropIfExists('dewasa_rules');
    }
};
