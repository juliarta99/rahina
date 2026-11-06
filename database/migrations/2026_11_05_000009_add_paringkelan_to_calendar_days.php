<?php

declare(strict_types=1);

use App\Data\CalendarDayRecord;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nama paringkelan hasil hitung engine (jejepan, lintang, pancasuda, dll.). Hanya nama;
 * arti/penjelasan tidak disimpan sampai ada sumber tervalidasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_days', function (Blueprint $table): void {
            foreach (CalendarDayRecord::PARINGKELAN as $column) {
                $table->string($column, 32)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('calendar_days', function (Blueprint $table): void {
            $table->dropColumn(CalendarDayRecord::PARINGKELAN);
        });
    }
};
