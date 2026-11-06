<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 6 domain kegiatan kalender Bali + sub-kategori awal dan alias bahasa sehari-hari.
 * Ikon mengikuti docs/DESIGN_SYSTEM.md §5. Idempoten (berdasarkan `code`).
 * Sub-kategori & alias baru ditambahkan di sini atau lewat konfirmasi pengguna (`source = user_confirmed`).
 */
class ActivityCategorySeeder extends Seeder
{
    /** @var list<array{code: string, domain: string, name: string, icon: string, notice?: bool, parent?: string, aliases?: list<string>}> */
    private const CATEGORIES = [
        ['code' => 'upacara', 'domain' => 'upacara', 'name' => 'Upacara / Yadnya', 'icon' => 'FlowerLotus', 'notice' => true],
        ['code' => 'pertanian', 'domain' => 'pertanian', 'name' => 'Pertanian / Perkebunan', 'icon' => 'Plant', 'aliases' => ['menanam', 'bercocok tanam', 'berkebun', 'tanam cabai', 'menanam sayur', 'tanam jagung', 'menanam pohon', 'tanam bibit', 'panen']],
        ['code' => 'peternakan', 'domain' => 'peternakan', 'name' => 'Peternakan / Perikanan', 'icon' => 'Fish', 'aliases' => ['beternak', 'pelihara ayam', 'beli sapi', 'ternak babi', 'tebar bibit ikan', 'membuat kandang']],
        ['code' => 'peralatan', 'domain' => 'peralatan', 'name' => 'Peralatan', 'icon' => 'Wrench', 'aliases' => ['membuat alat', 'membuat senjata', 'memperbaiki alat']],
        ['code' => 'pembangunan', 'domain' => 'pembangunan', 'name' => 'Pembangunan', 'icon' => 'HouseLine', 'aliases' => ['bangun rumah', 'membangun rumah', 'renovasi rumah', 'pasang atap', 'pindah rumah', 'membuat sumur']],
        ['code' => 'aneka', 'domain' => 'aneka', 'name' => 'Aneka kegiatan', 'icon' => 'SquaresFour'],

        ['code' => 'upacara.pawiwahan', 'parent' => 'upacara', 'domain' => 'upacara', 'name' => 'Pawiwahan (pernikahan)', 'icon' => 'FlowerLotus', 'notice' => true, 'aliases' => ['menikah', 'nikah', 'pernikahan', 'pawiwahan', 'perkawinan']],
        ['code' => 'upacara.ngaben', 'parent' => 'upacara', 'domain' => 'upacara', 'name' => 'Ngaben', 'icon' => 'FlowerLotus', 'notice' => true, 'aliases' => ['ngaben']],
        ['code' => 'upacara.melaspas', 'parent' => 'upacara', 'domain' => 'upacara', 'name' => 'Melaspas', 'icon' => 'FlowerLotus', 'notice' => true, 'aliases' => ['melaspas']],
        ['code' => 'pertanian.padi', 'parent' => 'pertanian', 'domain' => 'pertanian', 'name' => 'Menanam padi', 'icon' => 'Plant', 'aliases' => ['menanam padi', 'tanam padi', 'nandur padi']],
        ['code' => 'pertanian.umbi', 'parent' => 'pertanian', 'domain' => 'pertanian', 'name' => 'Menanam umbi-umbian', 'icon' => 'Plant', 'aliases' => ['nanam singkong', 'tanam ubi', 'tanam ketela', 'menanam umbi']],
        ['code' => 'dagang.buka_usaha', 'parent' => 'aneka', 'domain' => 'aneka', 'name' => 'Membuka usaha / berdagang', 'icon' => 'Storefront', 'aliases' => ['buka warung', 'buka usaha', 'buka toko', 'mulai jualan', 'berdagang', 'membuka bisnis']],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $c) {
            $parentId = isset($c['parent']) ? DB::table('activity_categories')->where('code', $c['parent'])->value('id') : null;

            DB::table('activity_categories')->updateOrInsert(['code' => $c['code']], [
                'parent_id' => $parentId,
                'domain' => $c['domain'],
                'name' => $c['name'],
                'icon' => $c['icon'],
                'requires_cultural_notice' => $c['notice'] ?? false,
            ]);

            $id = DB::table('activity_categories')->where('code', $c['code'])->value('id');
            foreach ($c['aliases'] ?? [] as $phrase) {
                DB::table('activity_aliases')->updateOrInsert(
                    ['category_id' => $id, 'phrase' => $phrase],
                    ['source' => 'seed'],
                );
            }
        }
    }
}
