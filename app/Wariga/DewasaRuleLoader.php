<?php

declare(strict_types=1);

namespace App\Wariga;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;

/**
 * Memuat aturan dewasa + urutan prioritas dari file JSON ke DB.
 * Semua aturan divalidasi dulu; satu error saja membatalkan seluruh pemuatan.
 * Aturan yang ada di DB tetapi tidak ada di file dinonaktifkan (`is_active = false`), tidak dihapus.
 *
 * Input: path JSON `{"rules": [...], "precedence": [...]}`.
 * Output: {rules, effects, precedence, deactivated}.
 *
 * @throws InvalidArgumentException berisi seluruh pesan validasi.
 */
final class DewasaRuleLoader
{
    /**
     * @return array{rules: int, effects: int, precedence: int, deactivated: int}
     */
    public function load(string $path): array
    {
        try {
            $data = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException("{$path}: JSON tidak valid ({$e->getMessage()})", previous: $e);
        }

        if (! is_array($data) || ! is_array($data['rules'] ?? null) || ! is_array($data['precedence'] ?? [])) {
            throw new InvalidArgumentException("{$path}: harus berisi objek {\"rules\": [...], \"precedence\": [...]}");
        }

        /** @var list<array<string, mixed>> $rules */
        $rules = array_values($data['rules']);
        /** @var list<array<string, mixed>> $precedence */
        $precedence = array_values($data['precedence'] ?? []);

        $validator = DewasaRuleValidator::fromDatabase();
        $errors = [];
        $codes = [];
        foreach ($rules as $rule) {
            array_push($errors, ...$validator->validate($rule));
            $code = $rule['code'] ?? null;
            if (is_string($code) && in_array($code, $codes, true)) {
                $errors[] = "[{$code}] code duplikat";
            }
            $codes[] = $code;
        }
        $sources = DB::table('sources')->pluck('id', 'code');
        foreach ($precedence as $i => $p) {
            if (! is_string($p['layer'] ?? null) || ! is_int($p['rank'] ?? null) || ! isset($sources[$p['source'] ?? ''])) {
                $errors[] = "precedence[{$i}] wajib berisi layer (string), rank (int), source (code sumber)";
            }
        }

        if ($errors !== []) {
            throw new InvalidArgumentException("Aturan dewasa tidak valid:\n- ".implode("\n- ", $errors));
        }

        $categories = DB::table('activity_categories')->pluck('id', 'code');
        $effectCount = 0;

        $deactivated = DB::transaction(function () use ($rules, $precedence, $sources, $categories, $codes, &$effectCount): int {
            $now = now();

            foreach ($rules as $rule) {
                DB::table('dewasa_rules')->updateOrInsert(['code' => $rule['code']], [
                    'name' => $rule['name'],
                    'summary' => $rule['summary'],
                    'verdict' => $rule['verdict'],
                    'alahing_level' => $rule['alahing_level'] ?? null,
                    'conditions' => json_encode($rule['conditions'], JSON_THROW_ON_ERROR),
                    'source_id' => $sources[$rule['source']],
                    'source_page' => $rule['source_page'] ?? null,
                    'validated_by' => $rule['validated_by'] ?? null,
                    'validated_at' => $rule['validated_at'] ?? null,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);

                $ruleId = DB::table('dewasa_rules')->where('code', $rule['code'])->value('id');
                DB::table('dewasa_rule_effects')->where('rule_id', $ruleId)->delete();

                /** @var list<array{category: string, effect: string, note?: string|null}> $effects */
                $effects = $rule['effects'];
                $rows = [];
                foreach ($effects as $effect) {
                    $rows[$effect['category'].'|'.$effect['effect']] = [
                        'rule_id' => $ruleId,
                        'category_id' => $categories[$effect['category']],
                        'effect' => $effect['effect'],
                        'note' => $effect['note'] ?? null,
                    ];
                }
                DB::table('dewasa_rule_effects')->insert(array_values($rows));
                $effectCount += count($rows);
            }

            foreach ($precedence as $p) {
                DB::table('dewasa_precedence')->updateOrInsert(['layer' => $p['layer']], [
                    'rank' => $p['rank'],
                    'source_id' => $sources[$p['source']],
                    'source_page' => $p['source_page'] ?? null,
                ]);
            }

            return DB::table('dewasa_rules')->whereNotIn('code', $codes)->where('is_active', true)->update(['is_active' => false, 'updated_at' => $now]);
        });

        return [
            'rules' => count($rules),
            'effects' => $effectCount,
            'precedence' => count($precedence),
            'deactivated' => $deactivated,
        ];
    }
}
