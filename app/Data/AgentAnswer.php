<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Jawaban Tanya Wariga yang SUDAH lolos OutputGuard (atau disusun template dari hasil tool).
 * Data tanggal (wewaran, wuku, nama dewasa) diambil dari hasil tool, bukan dari teks model.
 */
final readonly class AgentAnswer
{
    public const BY_AI = 'ai';

    public const BY_TEMPLATE = 'template';

    /**
     * @param  list<array{date: string, saptawara: string, pancawara: string, wuku: string, sasih: string, rahinan: list<string>, verdict: string, reason: string, support: list<array{id: int, name: string}>, block: list<array{id: int, name: string}>}>  $dates
     * @param  list<array{code: string, name: string}>  $clarificationOptions
     * @param  list<string>  $droppedDates  tanggal dari model yang dibuang guard
     * @param  list<string>  $steps  status langkah agent
     */
    public function __construct(
        public string $answer,
        public array $dates,
        public bool $needsClarification,
        public array $clarificationOptions,
        public bool $culturalNotice,
        public bool $outOfScope,
        public string $generatedBy,
        public array $droppedDates = [],
        public array $steps = [],
        public int $sourceCount = 0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'answer' => $this->answer,
            'dates' => $this->dates,
            'needs_clarification' => $this->needsClarification,
            'clarification_options' => $this->clarificationOptions,
            'cultural_notice' => $this->culturalNotice,
            'out_of_scope' => $this->outOfScope,
            'generated_by' => $this->generatedBy,
            'dropped_dates' => $this->droppedDates,
            'steps' => $this->steps,
            'source_count' => $this->sourceCount,
        ];
    }
}
