<?php

declare(strict_types=1);

namespace App\Data;

use InvalidArgumentException;

/**
 * Satu baris JSONL hasil `scripts/calendar/compute.mjs` (satu hari kalender).
 *
 * Input: array hasil json_decode. Output: baris siap tulis ke `calendar_days`.
 *
 * @throws InvalidArgumentException jika field wajib hilang atau bertipe salah.
 */
final readonly class CalendarDayRecord
{
    public const WEWARAN = [
        1 => 'ekawara', 2 => 'dwiwara', 3 => 'triwara', 4 => 'caturwara', 5 => 'pancawara',
        6 => 'sadwara', 7 => 'saptawara', 8 => 'astawara', 9 => 'sangawara', 10 => 'dasawara',
    ];

    /** Kolom paringkelan (nama hasil hitung engine). */
    public const PARINGKELAN = [
        'jejepan', 'lintang', 'panca_suda', 'rakam', 'eka_jala_rsi',
        'watek_alit', 'watek_madya', 'pararasan', 'pratithi_samut_pada',
    ];

    /**
     * @param  array<string, ?int>  $wewaranPositions  kunci = nama siklus, nilai = posisi 1-based (null hanya untuk ekawara)
     * @param  list<int>  $rahinanIds
     * @param  array<string, ?string>  $paringkelan  kunci = PARINGKELAN
     */
    public function __construct(
        public string $date,
        public int $sakaYear,
        public int $wukuId,
        public array $wewaranPositions,
        public int $uripTotal,
        public int $sasihId,
        public int $sasihDay,
        public ?int $sasihDayEnd,
        public bool $isPenanggal,
        public bool $isPurnama,
        public bool $isTilem,
        public bool $isNgunaRatri,
        public ?string $ingkel,
        public array $rahinanIds,
        public string $engine,
        public array $paringkelan = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $date = self::str($data, 'date');
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) !== 1 || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            throw new InvalidArgumentException("Tanggal tidak valid: {$date}");
        }

        $positions = [];
        foreach (self::WEWARAN as $cycle => $key) {
            $value = $data[$key] ?? null;
            if ($value === null && $cycle === 1) {
                $positions[$key] = null;

                continue;
            }
            if (! is_int($value) || $value < 1 || $value > $cycle) {
                throw new InvalidArgumentException("{$key} di luar rentang 1–{$cycle} pada {$date}");
            }
            $positions[$key] = $value;
        }

        $rahinan = $data['rahinan'] ?? [];
        if (! is_array($rahinan) || array_filter($rahinan, fn (mixed $id): bool => ! is_int($id)) !== []) {
            throw new InvalidArgumentException("rahinan harus berupa daftar integer pada {$date}");
        }
        /** @var list<int> $rahinan */
        $wuku = self::int($data, 'wuku');
        if ($wuku < 1 || $wuku > 30) {
            throw new InvalidArgumentException("wuku di luar rentang 1–30 pada {$date}");
        }

        $ingkel = $data['ingkel'] ?? null;

        $paringkelan = [];
        foreach (self::PARINGKELAN as $key) {
            $value = $data[$key] ?? null;
            if ($value !== null && (! is_string($value) || mb_strlen($value) > 32)) {
                throw new InvalidArgumentException("{$key} harus string maks 32 karakter pada {$date}");
            }
            $paringkelan[$key] = $value;
        }

        return new self(
            date: $date,
            sakaYear: self::int($data, 'saka_year'),
            wukuId: $wuku,
            wewaranPositions: $positions,
            uripTotal: self::int($data, 'urip_total'),
            sasihId: self::int($data, 'sasih'),
            sasihDay: self::int($data, 'sasih_day'),
            sasihDayEnd: isset($data['sasih_day_end']) ? self::int($data, 'sasih_day_end') : null,
            isPenanggal: self::bool($data, 'is_penanggal'),
            isPurnama: self::bool($data, 'is_purnama'),
            isTilem: self::bool($data, 'is_tilem'),
            isNgunaRatri: self::bool($data, 'is_nguna_ratri'),
            ingkel: is_string($ingkel) ? $ingkel : null,
            rahinanIds: array_values(array_unique($rahinan)),
            engine: self::str($data, 'engine'),
            paringkelan: $paringkelan,
        );
    }

    /** Id `wewaran_values` = cycle * 100 + position. */
    public static function wewaranId(int $cycle, int $position): int
    {
        return $cycle * 100 + $position;
    }

    /**
     * @return array<string, mixed>
     */
    public function toRow(string $computedAt): array
    {
        $row = [
            'date' => $this->date,
            'saka_year' => $this->sakaYear,
            'wuku_id' => $this->wukuId,
        ];

        foreach (self::WEWARAN as $cycle => $key) {
            $position = $this->wewaranPositions[$key];
            $row["{$key}_id"] = $position === null ? null : self::wewaranId($cycle, $position);
        }

        return $row + [
            'urip_total' => $this->uripTotal,
            'sasih_id' => $this->sasihId,
            'sasih_day' => $this->sasihDay,
            'sasih_day_end' => $this->sasihDayEnd,
            'is_penanggal' => $this->isPenanggal,
            'is_purnama' => $this->isPurnama,
            'is_tilem' => $this->isTilem,
            'is_nguna_ratri' => $this->isNgunaRatri,
            'ingkel' => $this->ingkel,
            'engine' => $this->engine,
            'computed_at' => $computedAt,
        ] + array_merge(array_fill_keys(self::PARINGKELAN, null), $this->paringkelan);
    }

    /** @param array<string, mixed> $d */
    private static function int(array $d, string $k): int
    {
        if (! isset($d[$k]) || ! is_int($d[$k])) {
            throw new InvalidArgumentException("Field {$k} wajib integer");
        }

        return $d[$k];
    }

    /** @param array<string, mixed> $d */
    private static function str(array $d, string $k): string
    {
        if (! isset($d[$k]) || ! is_string($d[$k]) || $d[$k] === '') {
            throw new InvalidArgumentException("Field {$k} wajib string");
        }

        return $d[$k];
    }

    /** @param array<string, mixed> $d */
    private static function bool(array $d, string $k): bool
    {
        if (! isset($d[$k]) || ! is_bool($d[$k])) {
            throw new InvalidArgumentException("Field {$k} wajib boolean");
        }

        return $d[$k];
    }
}
