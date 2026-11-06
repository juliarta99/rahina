<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Detail satu hari kalender Bali (hasil perhitungan deterministik, tanpa dewasa).
 * Dipakai halaman detail hari, ringkasan harian, dan tool agent `get_day`.
 */
final readonly class CalendarDay
{
    /**
     * @param  array<string, ?WarigaValue>  $wewaran  kunci: ekawara … dasawara
     * @param  list<array{id: int, slug: string, name: string, kind: string}>  $rahinan
     * @param  array<string, ?string>  $paringkelan  jejepan, lintang, panca_suda, … (nama saja)
     * @param  int  $sasihNumber  urutan sasih dasar 1-12 (nampih/mala memakai urutan sasih dasarnya)
     */
    public function __construct(
        public string $date,
        public int $sakaYear,
        public WarigaValue $wuku,
        public array $wewaran,
        public int $uripTotal,
        public WarigaValue $sasih,
        public bool $isNampihSasih,
        public int $sasihDay,
        public ?int $sasihDayEnd,
        public bool $isPenanggal,
        public bool $isPurnama,
        public bool $isTilem,
        public bool $isNgunaRatri,
        public ?string $ingkel,
        public array $rahinan,
        public string $engine,
        public array $paringkelan = [],
        public int $sasihNumber = 0,
    ) {}

    public function saptawara(): string
    {
        return $this->wewaran['saptawara']->name ?? '';
    }

    public function pancawara(): string
    {
        return $this->wewaran['pancawara']->name ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'saka_year' => $this->sakaYear,
            'wuku' => $this->wuku->toArray(),
            'wewaran' => array_map(fn (?WarigaValue $v): ?array => $v?->toArray(), $this->wewaran),
            'urip_total' => $this->uripTotal,
            'sasih' => $this->sasih->toArray() + ['is_nampih' => $this->isNampihSasih, 'number' => $this->sasihNumber],
            'sasih_day' => $this->sasihDay,
            'sasih_day_end' => $this->sasihDayEnd,
            'is_penanggal' => $this->isPenanggal,
            'is_purnama' => $this->isPurnama,
            'is_tilem' => $this->isTilem,
            'is_nguna_ratri' => $this->isNgunaRatri,
            'ingkel' => $this->ingkel,
            'rahinan' => $this->rahinan,
            'paringkelan' => $this->paringkelan,
            'engine' => $this->engine,
        ];
    }
}
