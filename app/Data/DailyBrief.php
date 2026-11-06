<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Ringkasan "Rahina Ini" yang sudah lolos OutputGuard (dari AI) atau dibangun template (tanpa AI).
 */
final readonly class DailyBrief
{
    public const BY_AI = 'ai';

    public const BY_TEMPLATE = 'template';

    /** Nilai tetap kolom `daily_briefs.persona`: satu ringkasan umum untuk semua pengguna (persona dihapus 2026-11-06). */
    public const SCOPE = 'umum';

    /**
     * @param  list<array{verdict: string, text: string, rule_ids: list<int>}>  $points
     */
    public function __construct(
        public string $date,
        public string $headline,
        public array $points,
        public ?string $rahinanNote,
        public string $generatedBy,
        public string $model,
        public string $promptVersion,
    ) {}

    /**
     * @return list<int>
     */
    public function ruleIds(): array
    {
        $ids = [];
        foreach ($this->points as $p) {
            array_push($ids, ...$p['rule_ids']);
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array{headline: string, points: list<array{verdict: string, text: string, rule_ids: list<int>}>, rahinan_note: ?string}
     */
    public function content(): array
    {
        return ['headline' => $this->headline, 'points' => $this->points, 'rahinan_note' => $this->rahinanNote];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->content() + [
            'date' => $this->date,
            'generated_by' => $this->generatedBy,
            'model' => $this->model,
            'prompt_version' => $this->promptVersion,
        ];
    }
}
