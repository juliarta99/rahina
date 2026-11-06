<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sumber rujukan dari docs/DATA_SOURCES.md. Idempoten (upsert berdasarkan `code`).
 */
class SourceSeeder extends Seeder
{
    public const ENGINE = 'balinese-date-js';

    public const KALENDERBALI_ORG = 'kalenderbali-org';

    public function run(): void
    {
        $now = now();
        $rows = [
            ['code' => self::ENGINE, 'kind' => 'software', 'title' => 'balinese-date-js', 'author' => 'Putu Andy (rewrite); algoritma asli Ida Bagus Putu Peradnya Dinata', 'year' => 2026, 'publisher' => null, 'url' => 'https://www.npmjs.com/package/balinese-date-js', 'license' => 'MIT', 'notes' => 'Perhitungan pawukon, wewaran, sasih, rahinan.'],
            ['code' => 'balinese-date-js-lib', 'kind' => 'software', 'title' => 'balinese-date-js-lib', 'author' => 'Ida Bagus Putu Peradnya Dinata', 'year' => null, 'publisher' => null, 'url' => 'https://github.com/peradnya/balinese-date-js-lib', 'license' => 'Apache-2.0', 'notes' => 'Rujukan algoritma asli.'],
            ['code' => self::KALENDERBALI_ORG, 'kind' => 'website', 'title' => 'Kalender Bali Digital (kalenderbali.org)', 'author' => 'kalenderbali.org', 'year' => 2026, 'publisher' => null, 'url' => 'https://kalenderbali.org/referensialaayu.php', 'license' => 'izin-pemilik', 'notes' => 'Daftar ala-ayuning dewasa, wewaran penyusun, dan alahing; diambil dengan izin pemilik (docs/DATA_SOURCES.md §2.3). Penjelasan disalin apa adanya, belum ditinjau narasumber Rahina.'],
            ['code' => 'ardhana-2005', 'kind' => 'book', 'title' => 'Pokok-Pokok Wariga', 'author' => 'I.B.S. Ardhana', 'year' => 2005, 'publisher' => 'Paramita', 'url' => null, 'license' => 'copyright', 'notes' => null],
            ['code' => 'yshd-1992', 'kind' => 'book', 'title' => 'Kunci Wariga Dewasa', 'author' => 'Yayasan Satya Hindu Dharma', 'year' => 1992, 'publisher' => null, 'url' => null, 'license' => 'copyright', 'notes' => null],
            ['code' => 'bangli-2010', 'kind' => 'book', 'title' => 'Wariga Dewasa Praktis', 'author' => 'I.B.P. Bangli', 'year' => 2010, 'publisher' => 'Paramita', 'url' => null, 'license' => 'copyright', 'notes' => null],
            ['code' => 'gina-1997', 'kind' => 'book', 'title' => 'Aneka Tarikh', 'author' => 'I.W. Gina', 'year' => 1997, 'publisher' => null, 'url' => null, 'license' => 'copyright', 'notes' => null],
            ['code' => 'rawi-ala-ayuning-dewasa', 'kind' => 'book', 'title' => 'Ala Ayuning Dewasa', 'author' => 'Ketut Bangbang Gde Rawi', 'year' => null, 'publisher' => null, 'url' => null, 'license' => 'copyright', 'notes' => null],
        ];

        DB::table('sources')->upsert(
            array_map(fn (array $r): array => $r + ['created_at' => $now, 'updated_at' => $now], $rows),
            ['code'],
            ['kind', 'title', 'author', 'year', 'publisher', 'url', 'license', 'notes', 'updated_at'],
        );
    }
}
