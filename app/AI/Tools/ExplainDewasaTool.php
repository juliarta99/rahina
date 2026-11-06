<?php

declare(strict_types=1);

namespace App\AI\Tools;

use App\AI\AgentContext;
use App\Wariga\CalendarRepository;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * `explain_dewasa`: arti satu dewasa dari tabel `dewasa_rules` (ringkasan + efek + sumber).
 * Hanya aturan yang boleh tampil; model tidak boleh menjelaskan dewasa dari pengetahuannya sendiri.
 */
final class ExplainDewasaTool implements Tool
{
    public function name(): string
    {
        return 'explain_dewasa';
    }

    public function declaration(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Menjelaskan arti sebuah dewasa (ala ayuning dewasa) berdasarkan data dan sumbernya. Gunakan kode atau nama dewasa.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'code' => ['type' => 'STRING', 'description' => 'Kode atau nama dewasa.'],
                ],
                'required' => ['code'],
            ],
        ];
    }

    public function status(array $args): string
    {
        return 'Membaca penjelasan dewasa '.mb_substr((string) ($args['code'] ?? ''), 0, 40);
    }

    public function run(array $args, AgentContext $context): array
    {
        $needle = trim((string) ($args['code'] ?? ''));
        if ($needle === '' || mb_strlen($needle) > 96) {
            throw new InvalidArgumentException('code wajib diisi.');
        }

        $query = DB::table('dewasa_rules as r')
            ->join('sources as s', 's.id', '=', 'r.source_id')
            ->where('r.is_active', true)
            ->where(fn ($q) => $q->where('r.code', $needle)->orWhereRaw('lower(r.name) = ?', [mb_strtolower($needle)]));
        CalendarRepository::displayable($query);
        $rule = $query->first(['r.id', 'r.code', 'r.name', 'r.summary', 'r.verdict', 'r.alahing_level', 'r.source_page', 'r.validated_at', 's.title']);

        if ($rule === null) {
            return ['found' => false, 'note' => 'Dewasa ini tidak ada di data Rahina. Jangan menjelaskannya dari pengetahuan sendiri.'];
        }

        $effects = DB::table('dewasa_rule_effects as e')
            ->join('activity_categories as c', 'c.id', '=', 'e.category_id')
            ->where('e.rule_id', $rule->id)
            ->get(['c.code', 'c.name', 'e.effect'])
            ->map(fn (object $e): array => ['category' => (string) $e->code, 'category_name' => (string) $e->name, 'effect' => (string) $e->effect])
            ->all();

        $context->explained[] = (string) $rule->name;

        return [
            'found' => true,
            'id' => (int) $rule->id,
            'code' => (string) $rule->code,
            'name' => (string) $rule->name,
            'summary' => (string) $rule->summary,
            'verdict' => (string) $rule->verdict,
            'alahing_level' => $rule->alahing_level === null ? null : (int) $rule->alahing_level,
            'effects' => $effects,
            'source' => ['title' => (string) $rule->title, 'page' => $rule->source_page, 'reviewed' => $rule->validated_at !== null],
        ];
    }
}
