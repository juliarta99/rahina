<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Satu tanggal hasil DateRanker: data kalender + skor + dewasa pendukung/penghalang untuk kategori yang diminta.
 */
final readonly class RankedDay
{
    /**
     * @param  list<array{id: int, name: string, alahing_level: ?int}>  $support
     * @param  list<array{id: int, name: string, alahing_level: ?int}>  $block
     */
    public function __construct(
        public CalendarDay $day,
        public int $score,
        public array $support,
        public array $block,
    ) {}

    /** ayu = hanya pendukung, campur = ada penghalang juga. */
    public function verdict(): string
    {
        return $this->block === [] ? 'ayu' : 'campur';
    }
}
