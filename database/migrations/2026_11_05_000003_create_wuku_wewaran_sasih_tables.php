<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master wariga: wuku (30), nilai wewaran (10 siklus, 55 nilai), sasih (12 + mala/nampih).
 * Id wewaran_values = cycle * 100 + position, sehingga deterministik antar-lingkungan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wuku', function (Blueprint $table): void {
            $table->smallInteger('id')->primary();
            $table->string('name', 32)->unique();
            $table->smallInteger('urip');
            $table->text('description')->nullable();
            $table->foreignId('source_id')->nullable()->constrained('sources');
        });

        Schema::create('wewaran_values', function (Blueprint $table): void {
            $table->smallInteger('id')->primary();
            $table->smallInteger('cycle');
            $table->smallInteger('position');
            $table->string('name', 32);
            $table->smallInteger('urip')->nullable();
            $table->unique(['cycle', 'position']);
        });

        Schema::create('sasih', function (Blueprint $table): void {
            $table->smallInteger('id')->primary();
            $table->string('name', 32)->unique();
            $table->smallInteger('base_id');
            $table->boolean('is_intercalary')->default(false);
            $table->string('alt_name', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sasih');
        Schema::dropIfExists('wewaran_values');
        Schema::dropIfExists('wuku');
    }
};
