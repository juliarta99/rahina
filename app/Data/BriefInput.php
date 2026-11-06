<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Bahan ringkasan harian satu tanggal: data kalender + dewasa yang boleh tampil beserta efeknya
 * untuk semua domain kegiatan.
 */
final readonly class BriefInput
{
    /**
     * @param  list<DewasaMatch>  $dewasa
     */
    public function __construct(
        public CalendarDay $day,
        public array $dewasa,
    ) {}

    /**
     * @return list<int>
     */
    public function ruleIds(): array
    {
        return array_map(fn (DewasaMatch $d): int => $d->ruleId, $this->dewasa);
    }

    /**
     * Efek (baik/tidak_baik) yang dimiliki sebuah aturan.
     *
     * @return list<string>
     */
    public function effectsOf(int $ruleId): array
    {
        foreach ($this->dewasa as $d) {
            if ($d->ruleId === $ruleId) {
                return array_values(array_unique(array_column($d->effects, 'effect')));
            }
        }

        return [];
    }

    /**
     * Data yang dikirim ke LLM. Hanya data kalender publik; tanpa data pengguna.
     *
     * @return array<string, mixed>
     */
    public function toPromptPayload(): array
    {
        $day = $this->day;

        return [
            'tanggal' => $day->date,
            'saptawara' => $day->saptawara(),
            'pancawara' => $day->pancawara(),
            'wuku' => $day->wuku->name,
            'sasih' => $day->sasih->name,
            'hari_sasih' => ($day->isPenanggal ? 'Penanggal ' : 'Pangelong ').$day->sasihDay
                .($day->sasihDayEnd !== null ? " dan {$day->sasihDayEnd} (ngunaratri)" : ''),
            'purnama' => $day->isPurnama,
            'tilem' => $day->isTilem,
            'rahinan' => array_column($day->rahinan, 'name'),
            'dewasa' => array_map(fn (DewasaMatch $d): array => [
                'id' => $d->ruleId,
                'nama' => $d->name,
                'ringkasan' => $d->summary,
                'sifat' => $d->verdict,
                'alahing' => $d->alahingLevel,
                'efek' => array_map(fn (array $e): array => ['kegiatan' => $e['category_name'], 'efek' => $e['effect']], $d->effects),
            ], $this->dewasa),
        ];
    }
}
