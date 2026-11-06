<?php

declare(strict_types=1);

namespace App\AI\Tools;

use App\AI\AgentContext;
use App\Data\RankedDay;
use App\Wariga\ActivityMatcher;
use App\Wariga\CategoryCatalog;
use App\Wariga\DateRanker;
use InvalidArgumentException;

/**
 * `find_good_days`: ranking tanggal untuk kategori dalam rentang (maks 92 hari, 2020–2035).
 * Setiap tanggal yang dikembalikan dicatat di AgentContext; hanya tanggal ini yang boleh muncul di jawaban.
 */
final class FindGoodDaysTool implements Tool
{
    public function __construct(
        private readonly DateRanker $ranker,
        private readonly CategoryCatalog $catalog,
        private readonly ActivityMatcher $matcher,
    ) {}

    public function name(): string
    {
        return 'find_good_days';
    }

    public function declaration(): array
    {
        $codes = $this->catalog->codes();

        return [
            'name' => $this->name(),
            'description' => 'Mencari tanggal terbaik untuk kategori aktivitas dalam rentang tanggal, dari hasil perhitungan wariga. Rentang maksimal 92 hari, tahun 2020–2035.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'category_codes' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING', 'enum' => $codes], 'description' => 'Kode kategori aktivitas.'],
                    'start' => ['type' => 'STRING', 'description' => 'Tanggal mulai YYYY-MM-DD.'],
                    'end' => ['type' => 'STRING', 'description' => 'Tanggal akhir YYYY-MM-DD (inklusif).'],
                    'limit' => ['type' => 'INTEGER', 'description' => 'Jumlah tanggal, 1–10. Default 5.'],
                ],
                'required' => ['category_codes', 'start', 'end'],
            ],
        ];
    }

    public function status(array $args): string
    {
        $codes = is_array($args['category_codes'] ?? null) ? implode(', ', array_filter($args['category_codes'], 'is_string')) : '?';

        return sprintf('Mencari hari baik %s, %s s.d. %s', $codes, (string) ($args['start'] ?? '?'), (string) ($args['end'] ?? '?'));
    }

    public function run(array $args, AgentContext $context): array
    {
        // Ditegakkan di kode, bukan hanya di prompt: kategori ragu → tanya balik, jangan menebak (AI_IMPLEMENTATION.md §4.5).
        // Model melewati map_activity: cocokkan sendiri pertanyaan pengguna agar kategori tidak ditebak.
        $mapped = array_filter($context->toolCalls, fn (array $c): bool => $c['name'] === 'map_activity');
        if (! $context->categoryChosen && $context->pendingClarification === null && $mapped === [] && $context->question !== '') {
            $matches = $this->matcher->match($context->question);
            if (! ActivityMatcher::isConfident($matches)) {
                $context->pendingClarification = $matches !== [] ? array_column($matches, 'code') : ['aneka', 'pembangunan', 'upacara'];
            }
        }
        if ($context->pendingClarification !== null && ! $context->categoryChosen) {
            throw new InvalidArgumentException('Kategori aktivitas belum pasti. Jangan mencari tanggal: isi needs_clarification = true dengan clarification_options dari '.implode(', ', $context->pendingClarification).'.');
        }

        $codes = $args['category_codes'] ?? null;
        if (! is_array($codes) || $codes === [] || array_filter($codes, fn (mixed $c): bool => ! is_string($c)) !== []) {
            throw new InvalidArgumentException('category_codes wajib berisi minimal satu kode kategori.');
        }
        /** @var list<string> $codes */
        $codes = array_values(array_unique($codes));
        $limit = is_numeric($args['limit'] ?? null) ? (int) $args['limit'] : 5;

        $ranked = $this->ranker->rank($codes, (string) ($args['start'] ?? ''), (string) ($args['end'] ?? ''), $limit);

        foreach ($codes as $code) {
            if (($c = $this->catalog->get($code)) !== null) {
                $context->categories[$code] = ['code' => $code, 'name' => $c['name'], 'requires_cultural_notice' => $c['requires_cultural_notice']];
            }
        }

        $days = [];
        foreach ($ranked as $r) {
            $entry = self::entry($r);
            $context->addDate($entry + ['origin' => 'find_good_days']);
            if (! in_array($r->day->date, $context->rankedOrder, true)) {
                $context->rankedOrder[] = $r->day->date;
            }
            $days[] = $entry + ['verdict' => $r->verdict()];
        }

        return [
            'category_codes' => $codes,
            'days' => $days,
            'note' => $days === [] ? 'Tidak ada tanggal dengan dewasa pendukung untuk kategori ini di rentang tersebut. Jangan menyebut tanggal lain.' : null,
        ];
    }

    /**
     * @return array{date: string, saptawara: string, pancawara: string, wuku: string, sasih: string, rahinan: list<string>, score: int, support: list<array{id: int, name: string}>, block: list<array{id: int, name: string}>}
     */
    private static function entry(RankedDay $r): array
    {
        $strip = fn (array $rules): array => array_map(fn (array $x): array => ['id' => $x['id'], 'name' => $x['name']], $rules);

        return [
            'date' => $r->day->date,
            'saptawara' => $r->day->saptawara(),
            'pancawara' => $r->day->pancawara(),
            'wuku' => $r->day->wuku->name,
            'sasih' => $r->day->sasih->name,
            'rahinan' => array_column($r->day->rahinan, 'name'),
            'score' => $r->score,
            'support' => $strip($r->support),
            'block' => $strip($r->block),
        ];
    }
}
