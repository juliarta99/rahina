<?php

declare(strict_types=1);

namespace App\Wariga;

use App\Data\CalendarDayRecord;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;
use SplFileObject;

/**
 * Mengimpor JSONL kalender ke `calendar_days` dan `calendar_day_rahinan`.
 * Idempoten: baris lama ditimpa (upsert), rahinan per tanggal diganti.
 *
 * Input: path file .jsonl. Output: jumlah hari yang diimpor.
 *
 * @throws InvalidArgumentException jika ada baris tidak valid (disertai nama file & nomor baris).
 */
final class CalendarImporter
{
    private const BATCH = 500;

    public function importFile(string $path): int
    {
        $file = new SplFileObject($path);
        $computedAt = now()->toIso8601String();
        $batch = [];
        $count = 0;
        $lineNo = 0;

        while (! $file->eof()) {
            $line = trim((string) $file->fgets());
            $lineNo++;
            if ($line === '') {
                continue;
            }

            try {
                $data = json_decode($line, true, 8, JSON_THROW_ON_ERROR);
                if (! is_array($data)) {
                    throw new InvalidArgumentException('Baris bukan objek JSON');
                }
                /** @var array<string, mixed> $data */
                $batch[] = CalendarDayRecord::fromArray($data);
            } catch (JsonException|InvalidArgumentException $e) {
                throw new InvalidArgumentException(basename($path).":{$lineNo}: {$e->getMessage()}", previous: $e);
            }

            if (count($batch) >= self::BATCH) {
                $count += $this->write($batch, $computedAt);
                $batch = [];
            }
        }

        return $count + ($batch === [] ? 0 : $this->write($batch, $computedAt));
    }

    /**
     * @param  non-empty-list<CalendarDayRecord>  $records
     */
    private function write(array $records, string $computedAt): int
    {
        DB::transaction(function () use ($records, $computedAt): void {
            $rows = array_map(fn (CalendarDayRecord $r): array => $r->toRow($computedAt), $records);
            $columns = array_values(array_diff(array_keys($rows[0]), ['date']));
            DB::table('calendar_days')->upsert($rows, ['date'], $columns);

            $dates = array_map(fn (CalendarDayRecord $r): string => $r->date, $records);
            DB::table('calendar_day_rahinan')->whereIn('date', $dates)->delete();

            $links = [];
            foreach ($records as $r) {
                foreach ($r->rahinanIds as $id) {
                    $links[] = ['date' => $r->date, 'rahinan_id' => $id];
                }
            }
            if ($links !== []) {
                DB::table('calendar_day_rahinan')->insert($links);
            }
        });

        return count($records);
    }
}
