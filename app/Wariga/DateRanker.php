<?php

declare(strict_types=1);

namespace App\Wariga;

use App\Data\CalendarDay;
use App\Data\RankedDay;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Skor tanggal untuk kategori aktivitas dalam suatu rentang (DATABASE_SCHEMA.md §5, tool `find_good_days`).
 *
 * Skor = Σ tingkat alahing (default 1) dewasa berefek `baik` − Σ yang `tidak_baik`, dihitung atas kategori
 * yang diminta BESERTA induknya (aturan untuk "pertanian" juga berlaku bagi "pertanian.umbi").
 * Hanya hari dengan minimal satu dewasa pendukung yang dikembalikan; urut skor tertinggi lalu tanggal.
 * Murni deterministik, tanpa AI.
 *
 * @throws InvalidArgumentException rentang tidak valid, terlalu panjang, di luar 2020–2035, atau kategori tidak dikenal.
 */
final class DateRanker
{
    public function __construct(
        private readonly CalendarRepository $calendar,
        private readonly CategoryCatalog $catalog,
    ) {}

    /**
     * @param  list<string>  $categoryCodes
     * @return list<RankedDay>
     */
    public function rank(array $categoryCodes, string $start, string $end, int $limit = 5): array
    {
        [$from, $to] = $this->validateRange($start, $end);
        $categoryIds = $this->categoryIdsWithAncestors($categoryCodes);

        $query = DB::table('calendar_day_dewasa as cd')
            ->join('dewasa_rules as r', 'r.id', '=', 'cd.rule_id')
            ->join('sources as s', 's.id', '=', 'r.source_id')
            ->join('dewasa_rule_effects as e', 'e.rule_id', '=', 'r.id')
            ->whereBetween('cd.date', [$from->toDateString(), $to->toDateString()])
            ->where('r.is_active', true)
            ->whereIn('e.category_id', $categoryIds)
            ->orderBy('cd.date')
            ->orderByDesc('r.alahing_level')
            ->select(['cd.date', 'r.id', 'r.name', 'r.alahing_level', 'e.effect']);
        CalendarRepository::displayable($query);

        /** @var array<string, array{pos: int, neg: int, support: array<int, array{id: int, name: string, alahing_level: ?int}>, block: array<int, array{id: int, name: string, alahing_level: ?int}>}> $byDate */
        $byDate = [];
        foreach ($query->get() as $row) {
            $date = substr((string) $row->date, 0, 10);
            $byDate[$date] ??= ['pos' => 0, 'neg' => 0, 'support' => [], 'block' => []];
            $id = (int) $row->id;
            $level = $row->alahing_level === null ? null : (int) $row->alahing_level;
            $bucket = $row->effect === 'baik' ? 'support' : 'block';
            if (isset($byDate[$date][$bucket][$id])) {
                continue; // satu aturan bisa berefek ke kategori & induknya: dihitung sekali.
            }
            $byDate[$date][$bucket][$id] = ['id' => $id, 'name' => (string) $row->name, 'alahing_level' => $level];
            $byDate[$date][$bucket === 'support' ? 'pos' : 'neg'] += $level ?? 1;
        }

        $candidates = array_filter($byDate, fn (array $d): bool => $d['pos'] > 0);
        uksort($candidates, function (string $a, string $b) use ($candidates): int {
            $diff = ($candidates[$b]['pos'] - $candidates[$b]['neg']) <=> ($candidates[$a]['pos'] - $candidates[$a]['neg']);

            return $diff !== 0 ? $diff : strcmp($a, $b);
        });
        $top = array_slice($candidates, 0, max(1, min(10, $limit)), true);
        if ($top === []) {
            return [];
        }

        $days = [];
        foreach ($this->calendar->range(min(array_keys($top)), max(array_keys($top))) as $day) {
            $days[$day->date] = $day;
        }

        $ranked = [];
        foreach ($top as $date => $d) {
            $day = $days[$date] ?? null;
            if (! $day instanceof CalendarDay) {
                continue;
            }
            $ranked[] = new RankedDay(
                day: $day,
                score: $d['pos'] - $d['neg'],
                support: array_values($d['support']),
                block: array_values($d['block']),
            );
        }

        return $ranked;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function validateRange(string $start, string $end): array
    {
        $from = self::parseDate($start);
        $to = self::parseDate($end);
        if ($from === null || $to === null) {
            throw new InvalidArgumentException('Format tanggal harus YYYY-MM-DD.');
        }
        if ($to->lt($from)) {
            throw new InvalidArgumentException('Tanggal akhir lebih awal dari tanggal mulai.');
        }
        $max = (int) config('rahina.agent.max_range_days');
        if ($from->diffInDays($to) + 1 > $max) {
            throw new InvalidArgumentException("Rentang maksimal {$max} hari. Persempit rentang tanggal.");
        }
        $minYear = (int) config('rahina.calendar.min_year');
        $maxYear = (int) config('rahina.calendar.max_year');
        if ($from->year < $minYear || $to->year > $maxYear) {
            throw new InvalidArgumentException("Kalender Rahina hanya mencakup tahun {$minYear}–{$maxYear}.");
        }

        return [$from, $to];
    }

    /**
     * @param  list<string>  $codes
     * @return list<int>
     */
    private function categoryIdsWithAncestors(array $codes): array
    {
        $ids = [];
        foreach ($codes as $code) {
            if ($this->catalog->get($code) === null) {
                throw new InvalidArgumentException("Kategori tidak dikenal: {$code}");
            }
            foreach ($this->catalog->idsWithAncestors($code) as $id) {
                $ids[$id] = true;
            }
        }
        if ($ids === []) {
            throw new InvalidArgumentException('Minimal satu kategori aktivitas.');
        }

        return array_keys($ids);
    }

    private static function parseDate(string $value): ?CarbonImmutable
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);

        return $date instanceof CarbonImmutable && $date->toDateString() === $value ? $date : null;
    }
}
