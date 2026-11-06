<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sumber rujukan (buku, lontar, jurnal, wawancara, perangkat lunak). Lihat docs/DATA_SOURCES.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->enum('kind', ['book', 'lontar', 'journal', 'interview', 'software']);
            $table->string('title');
            $table->string('author')->nullable();
            $table->smallInteger('year')->nullable();
            $table->string('publisher')->nullable();
            $table->text('url')->nullable();
            $table->string('license', 32);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
