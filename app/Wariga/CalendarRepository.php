<?php

declare(strict_types=1);

namespace App\Wariga;

use App\Data\CalendarDay;
use App\Data\CalendarDayRecord;
use App\Data\DewasaMatch;
use App\Data\WarigaValue;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Membaca `calendar_days` beserta nama wuku/wewaran/sasih dan rahinan.
 * Tidak menghitung apa pun: hanya membaca hasil precompute.
 */
final class CalendarRepository
{
    public const MASTERS_CACHE_KEY = 'wariga:masters:v3';

    /** @var array{wuku: array<int, WarigaValue>, wewaran: array<int, WarigaValue>, sasih: array<int, WarigaValue>, intercalary: list<int>, sasih_base: array<int, int>}|null */
    private ?array $masters = null;

    /**
     * @return CalendarDay|null null jika tanggal belum diimpor / di luar rentang.
     */
    public function day(DateTimeInterface|string $date): ?CalendarDay
    {
        $key = self::toDateString($date);

        return $this->range($key, $key)[0] ?? null;
    }

    /**
     * Semua hari dalam rentang [start, end] inklusif, urut tanggal.
     *
     * @return list<CalendarDay>
     */
    public function range(DateTimeInterface|string $start, DateTimeInterface|string $end): array
    {
        $from = self::toDateString($start);
        $to = self::toDateString($end);

        $rows = DB::table('calendar_days')->whereBetween('date', [$from, $to])->orderBy('date')->get();
        if ($rows->isEmpty()) {
            return [];
        }

        /** @var array<string, list<array{id: int, slug: string, name: string, kind: string}>> $rahinanByDate */
        $rahinanByDate = [];
        $rahinanRows = DB::table('calendar_day_rahinan as cr')
            ->join('rahinan as r', 'r.id', '=', 'cr.rahinan_id')
            ->whereBetween('cr.date', [$from, $to])
            ->orderBy('r.id')
            ->get(['cr.date', 'r.id', 'r.slug', 'r.name', 'r.kind']);
        foreach ($rahinanRows as $r) {
            $rahinanByDate[substr((string) $r->date, 0, 10)][] = [
                'id' => (int) $r->id,
                'slug' => (string) $r->slug,
                'name' => (string) $r->name,
                'kind' => (string) $r->kind,
            ];
        }

        $m = $this->masters();
        $intercalary = $m['intercalary'];

        $days = [];
        foreach ($rows as $row) {
            $date = substr((string) $row->date, 0, 10);
            $wewaran = [];
            foreach (CalendarDayRecord::WEWARAN as $cycle) {
                $id = $row->{"{$cycle}_id"};
                $wewaran[$cycle] = $id === null ? null : $m['wewaran'][(int) $id];
            }

            $days[] = new CalendarDay(
                date: $date,
                sakaYear: (int) $row->saka_year,
                wuku: $m['wuku'][(int) $row->wuku_id],
                wewaran: $wewaran,
                uripTotal: (int) $row->urip_total,
                sasih: $m['sasih'][(int) $row->sasih_id],
                isNampihSasih: in_array((int) $row->sasih_id, $intercalary, true),
                sasihDay: (int) $row->sasih_day,
                sasihDayEnd: $row->sasih_day_end === null ? null : (int) $row->sasih_day_end,
                isPenanggal: (bool) $row->is_penanggal,
                isPurnama: (bool) $row->is_purnama,
                isTilem: (bool) $row->is_tilem,
                isNgunaRatri: (bool) $row->is_nguna_ratri,
                ingkel: $row->ingkel === null ? null : (string) $row->ingkel,
                rahinan: $rahinanByDate[$date] ?? [],
                engine: (string) $row->engine,
                sasihNumber: $m['sasih_base'][(int) $row->sasih_id] ?? 0,
                paringkelan: array_map(fn (mixed $v): ?string => $v === null ? null : (string) $v, array_intersect_key((array) $row, array_flip(CalendarDayRecord::PARINGKELAN))),
            );
        }

        return $days;
    }

    /**
     * Dewasa yang berlaku pada tanggal tersebut (hasil `wariga:compute-dewasa`).
     * Default hanya aturan yang boleh tampil: sudah divalidasi pakar, ATAU berasal dari sumber berizin
     * di config('rahina.dewasa.display_unreviewed_sources') (ditandai `isValidated = false`).
     *
     * @return list<DewasaMatch>
     */
    public function dewasa(DateTimeInterface|string $date, bool $onlyValidated = true): array
    {
        $query = DB::table('calendar_day_dewasa as cd')
            ->join('dewasa_rules as r', 'r.id', '=', 'cd.rule_id')
            ->join('sources as s', 's.id', '=', 'r.source_id')
            ->where('cd.date', self::toDateString($date))
            ->where('r.is_active', true)
            ->orderByDesc('r.alahing_level')
            ->orderBy('r.code');
        if ($onlyValidated) {
            self::displayable($query);
        }

        $rows = $query->get(['r.id', 'r.code', 'r.name', 'r.summary', 'r.verdict', 'r.alahing_level', 'r.source_page', 'r.validated_at', 's.title as source_title', 'cd.matched_on']);
        if ($rows->isEmpty()) {
            return [];
        }

        /** @var array<int, list<array{category: string, category_name: string, domain: string, effect: string}>> $effects */
        $effects = [];
        $effectRows = DB::table('dewasa_rule_effects as e')
            ->join('activity_categories as c', 'c.id', '=', 'e.category_id')
            ->whereIn('e.rule_id', $rows->pluck('id')->all())
            ->orderBy('c.code')
            ->get(['e.rule_id', 'c.code', 'c.name', 'c.domain', 'e.effect']);
        foreach ($effectRows as $e) {
            $effects[(int) $e->rule_id][] = ['category' => (string) $e->code, 'category_name' => (string) $e->name, 'domain' => (string) $e->domain, 'effect' => (string) $e->effect];
        }

        $matches = [];
        foreach ($rows as $r) {
            $matchedOn = json_decode((string) $r->matched_on, true);
            $matches[] = new DewasaMatch(
                ruleId: (int) $r->id,
                code: (string) $r->code,
                name: (string) $r->name,
                summary: (string) $r->summary,
                verdict: (string) $r->verdict,
                alahingLevel: $r->alahing_level === null ? null : (int) $r->alahing_level,
                sourceTitle: (string) $r->source_title,
                sourcePage: $r->source_page === null ? null : (string) $r->source_page,
                isValidated: $r->validated_at !== null,
                effects: $effects[(int) $r->id] ?? [],
                matchedOn: is_array($matchedOn) ? array_values($matchedOn) : [],
            );
        }

        return $matches;
    }

    /**
     * Jumlah dewasa tervalidasi per tanggal yang berefek baik / tidak baik (untuk strip verdict di sel kalender).
     *
     * @param  list<string>|null  $domains  null = semua domain
     * @return array<string, array{baik: int, tidak_baik: int}>
     */
    public function dewasaCounts(DateTimeInterface|string $start, DateTimeInterface|string $end, ?array $domains = null): array
    {
        $query = DB::table('calendar_day_dewasa as cd')
            ->join('dewasa_rules as r', 'r.id', '=', 'cd.rule_id')
            ->join('dewasa_rule_effects as e', 'e.rule_id', '=', 'r.id')
            ->join('activity_categories as c', 'c.id', '=', 'e.category_id')
            ->join('sources as s', 's.id', '=', 'r.source_id')
            ->whereBetween('cd.date', [self::toDateString($start), self::toDateString($end)])
            ->where('r.is_active', true)
            ->groupBy('cd.date', 'e.effect')
            ->selectRaw('cd.date as date, e.effect as effect, count(distinct r.id) as total');
        self::displayable($query);
        if ($domains !== null) {
            $query->whereIn('c.domain', $domains);
        }

        $counts = [];
        foreach ($query->get() as $row) {
            $date = substr((string) $row->date, 0, 10);
            $counts[$date] ??= ['baik' => 0, 'tidak_baik' => 0];
            $counts[$date][$row->effect === 'baik' ? 'baik' : 'tidak_baik'] = (int) $row->total;
        }

        return $counts;
    }

    /**
     * Rahinan mendatang mulai tanggal tertentu. Setiap rahinan hanya diambil kemunculan pertamanya
     * agar daftar tidak didominasi yang sering berulang (kajeng kliwon, purnama, tilem, buda cemeng).
     *
     * @return list<array{date: string, name: string, slug: string, kind: string, days_until: int}>
     */
    public function upcomingRahinan(DateTimeInterface|string $from, int $limit = 8, int $horizonDays = 180): array
    {
        $start = CarbonImmutable::parse(self::toDateString($from));
        $rows = DB::table('calendar_day_rahinan as cr')
            ->join('rahinan as r', 'r.id', '=', 'cr.rahinan_id')
            ->whereBetween('cr.date', [$start->toDateString(), $start->addDays($horizonDays)->toDateString()])
            ->orderBy('cr.date')->orderBy('r.id')
            ->get(['cr.date', 'r.name', 'r.slug', 'r.kind']);

        $items = [];
        $seenRecurring = [];
        foreach ($rows as $r) {
            $slug = (string) $r->slug;
            if (isset($seenRecurring[$slug])) {
                continue;
            }
            $seenRecurring[$slug] = true;
            $date = substr((string) $r->date, 0, 10);
            $items[] = [
                'date' => $date,
                'name' => (string) $r->name,
                'slug' => $slug,
                'kind' => (string) $r->kind,
                'days_until' => (int) $start->diffInDays(CarbonImmutable::parse($date)),
            ];
            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    /**
     * Filter aturan yang boleh tampil (query harus sudah join `dewasa_rules as r` dan `sources as s`).
     */
    public static function displayable(Builder $query): void
    {
        /** @var list<string> $trusted */
        $trusted = (array) config('rahina.dewasa.display_unreviewed_sources', []);

        $query->where(function (Builder $q) use ($trusted): void {
            $q->whereNotNull('r.validated_at');
            if ($trusted !== []) {
                $q->orWhereIn('s.code', $trusted);
            }
        });
    }

    private static function toDateString(DateTimeInterface|string $date): string
    {
        return $date instanceof DateTimeInterface ? $date->format('Y-m-d') : CarbonImmutable::parse($date)->toDateString();
    }

    /**
     * Data master kecil & statis: di-cache per instance dan di cache aplikasi (1 hari).
     * Dihapus oleh WarigaMasterSeeder saat master berubah.
     *
     * @return array{wuku: array<int, WarigaValue>, wewaran: array<int, WarigaValue>, sasih: array<int, WarigaValue>, intercalary: list<int>, sasih_base: array<int, int>}
     */
    private function masters(): array
    {
        if ($this->masters !== null) {
            return $this->masters;
        }

        // Cache hanya menyimpan array skalar: Laravel 13 menolak unserialize objek dari cache
        // (cache.serializable_classes), sehingga objek DTO tidak boleh masuk cache.
        /** @var array{wuku: list<array{0: int, 1: string, 2: ?int}>, wewaran: list<array{0: int, 1: string, 2: ?int}>, sasih: list<array{0: int, 1: string, 2: ?int}>, intercalary: list<int>, sasih_base: array<int, int>} $raw */
        $raw = Cache::remember(self::MASTERS_CACHE_KEY, now()->addDay(), fn (): array => [
            'wuku' => self::rows(DB::table('wuku')->get(['id', 'name', 'urip'])->all()),
            'wewaran' => self::rows(DB::table('wewaran_values')->get(['id', 'name', 'urip'])->all()),
            'sasih' => self::rows(DB::table('sasih')->get(['id', 'name'])->all()),
            'intercalary' => array_values(DB::table('sasih')->where('is_intercalary', true)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all()),
            'sasih_base' => DB::table('sasih')->pluck('base_id', 'id')->mapWithKeys(fn (mixed $base, mixed $id): array => [(int) $id => (int) $base])->all(),
        ]);

        return $this->masters = [
            'wuku' => self::values($raw['wuku']),
            'wewaran' => self::values($raw['wewaran']),
            'sasih' => self::values($raw['sasih']),
            'intercalary' => $raw['intercalary'],
            'sasih_base' => $raw['sasih_base'],
        ];
    }

    /**
     * @param  array<int, stdClass>  $rows
     * @return list<array{0: int, 1: string, 2: ?int}>
     */
    private static function rows(array $rows): array
    {
        return array_values(array_map(
            fn (stdClass $r): array => [(int) $r->id, (string) $r->name, isset($r->urip) ? (int) $r->urip : null],
            $rows,
        ));
    }

    /**
     * @param  list<array{0: int, 1: string, 2: ?int}>  $rows
     * @return array<int, WarigaValue>
     */
    private static function values(array $rows): array
    {
        $values = [];
        foreach ($rows as [$id, $name, $urip]) {
            $values[$id] = new WarigaValue($id, $name, $urip);
        }

        return $values;
    }
}
