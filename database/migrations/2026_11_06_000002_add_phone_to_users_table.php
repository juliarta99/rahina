<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Akun memakai nomor HP (format 62xxxxxxxxxx) sebagai identitas masuk, bukan email (2026-11-06).
 * Nomor yang sama dipakai untuk Rahina Pagi via WhatsApp. Email tetap ada sebagai kolom opsional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 20)->nullable()->unique()->after('name');
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['phone']);
            $table->dropColumn('phone');
        });
    }
};
