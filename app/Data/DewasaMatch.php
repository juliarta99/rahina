<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Satu dewasa yang berlaku pada suatu tanggal, lengkap dengan sumber, efek per kategori,
 * dan unsur yang membuatnya cocok (`matchedOn`) untuk penjelasan.
 */
final readonly class DewasaMatch
{
    /**
     * @param  list<array{category: string, category_name: string, domain: string, effect: string}>  $effects
     * @param  list<array<string, mixed>>  $matchedOn
     */
    public function __construct(
        public int $ruleId,
        public string $code,
        public string $name,
        public string $summary,
        public string $verdict,
        public ?int $alahingLevel,
        public string $sourceTitle,
        public ?string $sourcePage,
        public bool $isValidated,
        public array $effects,
        public array $matchedOn,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rule_id' => $this->ruleId,
            'code' => $this->code,
            'name' => $this->name,
            'summary' => $this->summary,
            'verdict' => $this->verdict,
            'alahing_level' => $this->alahingLevel,
            'source' => ['title' => $this->sourceTitle, 'page' => $this->sourcePage],
            'is_validated' => $this->isValidated,
            'effects' => $this->effects,
            'matched_on' => $this->matchedOn,
        ];
    }
}
