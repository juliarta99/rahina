<?php

declare(strict_types=1);

namespace App\AI\Tools;

use App\AI\AgentContext;
use App\Wariga\ActivityMatcher;
use App\Wariga\CategoryCatalog;
use InvalidArgumentException;

/**
 * `map_activity`: kalimat aktivitas → kandidat kategori (trigram). Bila tidak yakin, juga mengembalikan
 * daftar lengkap kategori resmi agar model memilih dari enum atau bertanya balik (AI_IMPLEMENTATION.md §4).
 */
final class MapActivityTool implements Tool
{
    public function __construct(
        private readonly ActivityMatcher $matcher,
        private readonly CategoryCatalog $catalog,
    ) {}

    public function name(): string
    {
        return 'map_activity';
    }

    public function declaration(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Memetakan aktivitas dalam bahasa sehari-hari (misal "nanam singkong", "buka warung") ke kode kategori wariga. Panggil sebelum find_good_days bila kategori belum jelas.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'text' => ['type' => 'STRING', 'description' => 'Frasa aktivitas dari pengguna, tanpa kata tanggal.'],
                ],
                'required' => ['text'],
            ],
        ];
    }

    public function status(array $args): string
    {
        return 'Mencocokkan aktivitas: '.mb_substr((string) ($args['text'] ?? ''), 0, 60);
    }

    public function run(array $args, AgentContext $context): array
    {
        $text = trim((string) ($args['text'] ?? ''));
        if ($text === '' || mb_strlen($text) > 200) {
            throw new InvalidArgumentException('text wajib diisi (maks 200 karakter).');
        }

        $matches = $this->matcher->match($text);
        $confident = ActivityMatcher::isConfident($matches);
        if (! $confident && ! $context->categoryChosen) {
            $context->pendingClarification = $matches !== [] ? array_column($matches, 'code') : ['aneka', 'pembangunan', 'upacara'];
        }
        foreach ($matches as $m) {
            $context->categories[$m['code']] ??= ['code' => $m['code'], 'name' => $m['name'], 'requires_cultural_notice' => $m['requires_cultural_notice']];
        }

        $result = ['matches' => $matches, 'confident' => $confident];
        if (! $confident) {
            $result['all_categories'] = array_values(array_map(fn (array $c): array => ['code' => $c['code'], 'name' => $c['name']], $this->catalog->all()));
            $result['hint'] = 'Tidak yakin. Pilih kategori dari all_categories hanya jika maknanya jelas; bila ragu, minta klarifikasi.';
        }

        return $result;
    }
}
