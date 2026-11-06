<?php

declare(strict_types=1);

namespace App\Wariga;

use App\Data\CalendarDay;
use App\Data\CalendarDayRecord;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;

/**
 * Mengevaluasi `dewasa_rules.conditions` terhadap hari kalender secara deterministik
 * dan menulis hasilnya ke `calendar_day_dewasa` (beserta `matched_on` untuk penjelasan).
 *
 * Format kondisi: lihat docs/DATABASE_SCHEMA.md §4.
 *   node  := {"all": [node]} | {"any": [node]} | {"not": node} | leaf
 *   leaf  := {"field": F, <op>: nilai, "with"?: {F: nilai}}
 *   op    := in | not_in | eq | between | gte | lte
 *
 * @throws InvalidArgumentException jika kondisi memakai field/operator yang tidak dikenal.
 */
final class DewasaEngine
{
    public const FIELDS = [
        'wuku', 'ekawara', 'dwiwara', 'triwara', 'caturwara', 'pancawara', 'sadwara', 'saptawara',
        'astawara', 'sangawara', 'dasawara', 'urip_total', 'sasih', 'sasih_number', 'sasih_day',
        'is_penanggal', 'is_purnama', 'is_tilem', 'ingkel',
    ];

    /** `eq_field`: nilai field sama dengan nilai field lain hari itu (mis. sasih_day = sasih_number). */
    public const OPERATORS = ['in', 'not_in', 'eq', 'between', 'gte', 'lte', 'eq_field'];

    public function __construct(private readonly CalendarRepository $calendar) {}

    /**
     * Fakta satu hari dalam bentuk yang dipakai kondisi (nama, bukan id).
     * `sasih_day` berupa daftar: dua nilai pada hari ngunaratri, dan leaf cocok bila salah satunya cocok.
     *
     * @return array<string, string|int|bool|null|list<int>>
     */
    public static function factsFor(CalendarDay $day): array
    {
        $facts = [
            'wuku' => $day->wuku->name,
            'urip_total' => $day->uripTotal,
            'sasih' => $day->sasih->name,
            'sasih_number' => $day->sasihNumber,
            'sasih_day' => $day->sasihDayEnd === null ? [$day->sasihDay] : [$day->sasihDay, $day->sasihDayEnd],
            'is_penanggal' => $day->isPenanggal,
            'is_purnama' => $day->isPurnama,
            'is_tilem' => $day->isTilem,
            'ingkel' => $day->ingkel,
        ];

        foreach (CalendarDayRecord::WEWARAN as $cycle) {
            $facts[$cycle] = $day->wewaran[$cycle]->name ?? null;
        }

        return $facts;
    }

    /**
     * Evaluasi satu node kondisi.
     *
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $facts
     * @return list<array{field: string, op: string, expected: mixed, actual: mixed}>|null
     *                                                                                     null = tidak cocok; array (boleh kosong) = cocok, berisi unsur penyebabnya.
     */
    public function match(array $node, array $facts): ?array
    {
        if (array_key_exists('all', $node)) {
            $matched = [];
            foreach ($this->children($node['all'], 'all') as $child) {
                $result = $this->match($child, $facts);
                if ($result === null) {
                    return null;
                }
                array_push($matched, ...$result);
            }

            return $matched;
        }

        if (array_key_exists('any', $node)) {
            foreach ($this->children($node['any'], 'any') as $child) {
                $result = $this->match($child, $facts);
                if ($result !== null) {
                    return $result;
                }
            }

            return null;
        }

        if (array_key_exists('not', $node)) {
            if (! is_array($node['not'])) {
                throw new InvalidArgumentException('"not" harus berisi satu node');
            }

            /** @var array<string, mixed> $inner */
            $inner = $node['not'];

            return $this->match($inner, $facts) === null ? [] : null;
        }

        return $this->matchLeaf($node, $facts);
    }

    /**
     * Hitung ulang `calendar_day_dewasa` untuk rentang tanggal (default: seluruh kalender).
     *
     * @param  list<string>  $ruleCodes  kosong = semua aturan aktif
     * @return array{days: int, rules: int, matches: int}
     */
    public function compute(?string $from = null, ?string $to = null, array $ruleCodes = []): array
    {
        $from ??= (string) DB::table('calendar_days')->min('date');
        $to ??= (string) DB::table('calendar_days')->max('date');

        $query = DB::table('dewasa_rules')->where('is_active', true);
        if ($ruleCodes !== []) {
            $query->whereIn('code', $ruleCodes);
        }

        /** @var array<int, array<string, mixed>> $rules */
        $rules = [];
        foreach ($query->get(['id', 'code', 'conditions']) as $rule) {
            try {
                $conditions = json_decode((string) $rule->conditions, true, 64, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new InvalidArgumentException("Aturan {$rule->code}: conditions bukan JSON valid", previous: $e);
            }
            if (! is_array($conditions)) {
                throw new InvalidArgumentException("Aturan {$rule->code}: conditions harus objek");
            }
            /** @var array<string, mixed> $conditions */
            $rules[(int) $rule->id] = $conditions;
        }

        $days = 0;
        $matches = 0;
        if ($from === '' || $to === '') {
            return ['days' => 0, 'rules' => count($rules), 'matches' => 0];
        }

        $cursor = CarbonImmutable::parse($from)->startOfYear();
        $end = CarbonImmutable::parse($to);

        while ($cursor->lte($end)) {
            $chunkFrom = max($cursor->toDateString(), $from);
            $chunkTo = min($cursor->endOfYear()->toDateString(), $to);
            $rows = [];

            foreach ($this->calendar->range($chunkFrom, $chunkTo) as $day) {
                $days++;
                $facts = self::factsFor($day);
                foreach ($rules as $ruleId => $conditions) {
                    $matched = $this->match($conditions, $facts);
                    if ($matched !== null) {
                        $rows[] = ['date' => $day->date, 'rule_id' => $ruleId, 'matched_on' => json_encode($matched, JSON_THROW_ON_ERROR)];
                    }
                }
            }

            DB::transaction(function () use ($chunkFrom, $chunkTo, $ruleCodes, $rules, $rows): void {
                $delete = DB::table('calendar_day_dewasa')->whereBetween('date', [$chunkFrom, $chunkTo]);
                if ($ruleCodes !== []) {
                    $delete->whereIn('rule_id', array_keys($rules));
                }
                $delete->delete();

                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('calendar_day_dewasa')->insert($chunk);
                }

                // Ringkasan harian bergantung pada dewasa; cache lama untuk rentang ini tidak berlaku lagi.
                DB::table('daily_briefs')->whereBetween('date', [$chunkFrom, $chunkTo])->delete();
            });

            $matches += count($rows);
            $cursor = $cursor->addYear()->startOfYear();
        }

        return ['days' => $days, 'rules' => count($rules), 'matches' => $matches];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function children(mixed $value, string $key): array
    {
        if (! is_array($value) || ! array_is_list($value) || $value === []) {
            throw new InvalidArgumentException("\"{$key}\" harus berupa daftar node yang tidak kosong");
        }

        foreach ($value as $child) {
            if (! is_array($child)) {
                throw new InvalidArgumentException("Isi \"{$key}\" harus objek");
            }
        }

        /** @var list<array<string, mixed>> $value */
        return $value;
    }

    /**
     * @param  array<string, mixed>  $leaf
     * @param  array<string, mixed>  $facts
     * @return list<array{field: string, op: string, expected: mixed, actual: mixed}>|null
     */
    private function matchLeaf(array $leaf, array $facts): ?array
    {
        $field = $leaf['field'] ?? null;
        if (! is_string($field) || ! in_array($field, self::FIELDS, true)) {
            throw new InvalidArgumentException('Field kondisi tidak dikenal: '.json_encode($field));
        }

        $ops = array_values(array_intersect(array_keys($leaf), self::OPERATORS));
        if (count($ops) !== 1) {
            throw new InvalidArgumentException("Leaf {$field} harus punya tepat satu operator (".implode('/', self::OPERATORS).')');
        }
        $op = $ops[0];
        $expected = $leaf[$op];

        if ($op === 'eq_field') {
            if (! is_string($expected) || ! in_array($expected, self::FIELDS, true)) {
                throw new InvalidArgumentException("eq_field pada {$field} harus berisi nama field yang dikenal");
            }
            $other = $facts[$expected] ?? null;
            $op = 'in';
            $expected = is_array($other) ? $other : [$other];
        }

        $actual = $this->firstSatisfying($facts[$field] ?? null, $op, $expected);
        if ($actual === false) {
            return null;
        }

        $matched = [['field' => $field, 'op' => $op, 'expected' => $expected, 'actual' => $actual]];

        if (isset($leaf['with'])) {
            if (! is_array($leaf['with'])) {
                throw new InvalidArgumentException("\"with\" pada {$field} harus objek");
            }
            foreach ($leaf['with'] as $withField => $withValue) {
                $sub = $this->matchLeaf(['field' => $withField, 'eq' => $withValue], $facts);
                if ($sub === null) {
                    return null;
                }
                array_push($matched, ...$sub);
            }
        }

        return $matched;
    }

    /**
     * Nilai fakta pertama yang memenuhi operator, atau false bila tidak ada.
     * Fakta berbentuk daftar (sasih_day saat ngunaratri) cocok bila salah satu elemennya cocok.
     */
    private function firstSatisfying(mixed $actual, string $op, mixed $expected): mixed
    {
        $candidates = is_array($actual) ? $actual : [$actual];

        foreach ($candidates as $value) {
            if ($this->compare($value, $op, $expected)) {
                return $value;
            }
        }

        return false;
    }

    private function compare(mixed $value, string $op, mixed $expected): bool
    {
        if ($value === null) {
            return $op === 'not_in';
        }

        return match ($op) {
            'eq' => $value === $expected,
            'in' => is_array($expected) && in_array($value, $expected, true),
            'not_in' => is_array($expected) && ! in_array($value, $expected, true),
            'between' => is_array($expected) && count($expected) === 2 && is_numeric($value)
                && $value >= $expected[0] && $value <= $expected[1],
            'gte' => is_numeric($value) && is_numeric($expected) && $value >= $expected,
            'lte' => is_numeric($value) && is_numeric($expected) && $value <= $expected,
            default => throw new InvalidArgumentException("Operator tidak dikenal: {$op}"),
        };
    }
}
