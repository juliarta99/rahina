<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori aktivitas (6 domain + sub-kategori) dan alias bahasa bebas untuk pemetaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('activity_categories');
            $table->string('code', 64)->unique();
            $table->enum('domain', ['upacara', 'pertanian', 'peternakan', 'peralatan', 'pembangunan', 'aneka']);
            $table->string('name', 96);
            $table->string('icon', 32);
            $table->boolean('requires_cultural_notice')->default(false);
        });

        Schema::create('activity_aliases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('activity_categories')->cascadeOnDelete();
            $table->string('phrase', 128);
            $table->enum('source', ['seed', 'user_confirmed']);
            $table->unique(['category_id', 'phrase']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('create index activity_aliases_phrase_trgm on activity_aliases using gin (phrase extensions.gin_trgm_ops)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_aliases');
        Schema::dropIfExists('activity_categories');
    }
};
