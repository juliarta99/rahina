<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_briefs', function (Blueprint $table): void {
            $table->text('summary')->nullable()->after('content');
        });

        // Isi kolom summary untuk data yang sudah ada di tabel daily_briefs
        $rows = DB::table('daily_briefs')->get();
        foreach ($rows as $row) {
            $content = json_decode((string) $row->content, true);
            if (is_array($content)) {
                $lines = [];
                if (! empty($content['headline'])) {
                    $lines[] = $content['headline'];
                }
                if (! empty($content['points']) && is_array($content['points'])) {
                    foreach ($content['points'] as $p) {
                        if (! empty($p['text'])) {
                            $lines[] = $p['text'];
                        }
                    }
                }
                if (! empty($content['rahinan_note'])) {
                    $lines[] = $content['rahinan_note'];
                }
                $summaryText = implode(' ', $lines);
                DB::table('daily_briefs')->where('id', $row->id)->update([
                    'summary' => $summaryText,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('daily_briefs', function (Blueprint $table): void {
            $table->dropColumn('summary');
        });
    }
};

