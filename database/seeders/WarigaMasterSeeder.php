<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Wariga\CalendarRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Master wuku, wewaran, sasih, rahinan dari database/data/wariga_master.json
 * (dihasilkan `node scripts/calendar/masters.mjs` dari balinese-date-js). Idempoten.
 */
class WarigaMasterSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/wariga_master.json');
        /** @var array{wuku: list<array{id:int,name:string,urip:int}>, wewaran: list<array{cycle:int,position:int,name:string,urip:int}>, sasih: list<array{id:int,name:string,base_id:int,is_intercalary:bool}>, rahinan: list<array{id:int,slug:string,name:string,kind:string}>}|null $data */
        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            throw new RuntimeException("Data master tidak valid: {$path}");
        }

        $sourceId = DB::table('sources')->where('code', SourceSeeder::ENGINE)->value('id');

        DB::table('wuku')->upsert(
            array_map(fn (array $w): array => $w + ['source_id' => $sourceId], $data['wuku']),
            ['id'], ['name', 'urip', 'source_id'],
        );

        DB::table('wewaran_values')->upsert(
            array_map(fn (array $w): array => ['id' => $w['cycle'] * 100 + $w['position']] + $w, $data['wewaran']),
            ['id'], ['cycle', 'position', 'name', 'urip'],
        );

        DB::table('sasih')->upsert($data['sasih'], ['id'], ['name', 'base_id', 'is_intercalary']);

        DB::table('rahinan')->upsert(
            array_map(fn (array $r): array => $r + ['source_id' => $sourceId], $data['rahinan']),
            ['id'], ['slug', 'name', 'kind', 'source_id'],
        );

        Cache::forget(CalendarRepository::MASTERS_CACHE_KEY);
    }
}
