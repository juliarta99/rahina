<?php

declare(strict_types=1);

namespace App\Wariga;

use App\Data\CalendarDayRecord;
use Illuminate\Support\Facades\DB;

/**
 * Memvalidasi aturan dewasa (format `database/data/dewasa_rules.json`) sebelum masuk DB:
 * struktur, field/operator kondisi, nama wuku/wewaran/sasih/ingkel yang benar-benar ada,
 * sumber wajib, dan kategori efek yang dikenal.
 *
 * Input: satu aturan (array). Output: daftar pesan error (kosong = valid).
 */
final class DewasaRuleValidator
{
    private const NUMERIC_FIELDS = ['urip_total', 'sasih_day', 'sasih_number'];

    private const BOOL_FIELDS = ['is_penanggal', 'is_purnama', 'is_tilem'];

    /**
     * @param  array<string, list<string>>  $names  nilai nama yang sah per field (wuku, ekawara…, sasih, ingkel)
     * @param  list<string>  $sourceCodes
     * @param  list<string>  $categoryCodes
     */
    public function __construct(
        private readonly array $names,
        private readonly array $sourceCodes,
        private readonly array $categoryCodes,
    ) {}

    public static function fromDatabase(): self
    {
        $names = [
            'wuku' => self::strings(DB::table('wuku')->orderBy('id')->pluck('name')->all()),
            'sasih' => self::strings(DB::table('sasih')->orderBy('id')->pluck('name')->all()),
        ];

        foreach (CalendarDayRecord::WEWARAN as $cycle => $key) {
            $names[$key] = self::strings(DB::table('wewaran_values')->where('cycle', $cycle)->orderBy('position')->pluck('name')->all());
        }

        $master = json_decode((string) file_get_contents(database_path('data/wariga_master.json')), true);
        $names['ingkel'] = is_array($master) && is_array($master['ingkel'] ?? null) ? self::strings($master['ingkel']) : [];

        return new self(
            $names,
            self::strings(DB::table('sources')->pluck('code')->all()),
            self::strings(DB::table('activity_categories')->pluck('code')->all()),
        );
    }

    /**
     * @param  array<mixed>  $rule
     * @return list<string>
     */
    public function validate(array $rule): array
    {
        $errors = [];
        $code = $rule['code'] ?? null;
        $label = is_string($code) ? $code : '(tanpa code)';

        if (! is_string($code) || preg_match('/^[a-z0-9_]{1,64}$/', $code) !== 1) {
            $errors[] = 'code wajib slug huruf kecil/angka/garis bawah, maks 64';
        }
        foreach (['name' => 96, 'summary' => 600] as $field => $max) {
            $value = $rule[$field] ?? null;
            if (! is_string($value) || trim($value) === '' || mb_strlen($value) > $max) {
                $errors[] = "{$field} wajib diisi (maks {$max} karakter)";
            }
        }
        if (! in_array($rule['verdict'] ?? null, ['ayu', 'ala', 'campur'], true)) {
            $errors[] = 'verdict harus ayu/ala/campur';
        }
        $level = $rule['alahing_level'] ?? null;
        if ($level !== null && (! is_int($level) || $level < 1 || $level > 5)) {
            $errors[] = 'alahing_level harus 1–5 atau null';
        }
        if (! in_array($rule['source'] ?? null, $this->sourceCodes, true)) {
            $errors[] = 'source wajib berupa code di tabel sources: '.json_encode($rule['source'] ?? null);
        }
        if (isset($rule['validated_at']) && (! is_string($rule['validated_at']) || strtotime($rule['validated_at']) === false || empty($rule['validated_by']))) {
            $errors[] = 'validated_at harus tanggal valid dan validated_by wajib diisi';
        }

        $effects = $rule['effects'] ?? null;
        if (! is_array($effects) || $effects === []) {
            $errors[] = 'effects wajib berisi minimal satu kategori';
        } else {
            foreach ($effects as $i => $effect) {
                if (! is_array($effect) || ! in_array($effect['category'] ?? null, $this->categoryCodes, true)) {
                    $errors[] = "effects[{$i}].category tidak dikenal";
                }
                if (! is_array($effect) || ! in_array($effect['effect'] ?? null, ['baik', 'tidak_baik'], true)) {
                    $errors[] = "effects[{$i}].effect harus baik/tidak_baik";
                }
            }
        }

        $conditions = $rule['conditions'] ?? null;
        if (! is_array($conditions) || $conditions === []) {
            $errors[] = 'conditions wajib berupa objek';
        } else {
            $this->node($conditions, 'conditions', $errors);
        }

        return array_map(fn (string $e): string => "[{$label}] {$e}", $errors);
    }

    /**
     * @param  array<mixed>  $node
     * @param  list<string>  $errors
     */
    private function node(array $node, string $path, array &$errors): void
    {
        foreach (['all', 'any'] as $group) {
            if (array_key_exists($group, $node)) {
                if (! is_array($node[$group]) || ! array_is_list($node[$group]) || $node[$group] === []) {
                    $errors[] = "{$path}.{$group} harus daftar yang tidak kosong";

                    return;
                }
                foreach ($node[$group] as $i => $child) {
                    is_array($child)
                        ? $this->node($child, "{$path}.{$group}[{$i}]", $errors)
                        : $errors[] = "{$path}.{$group}[{$i}] harus objek";
                }

                return;
            }
        }

        if (array_key_exists('not', $node)) {
            is_array($node['not']) && $node['not'] !== []
                ? $this->node($node['not'], "{$path}.not", $errors)
                : $errors[] = "{$path}.not harus objek";

            return;
        }

        $field = $node['field'] ?? null;
        if (! is_string($field) || ! in_array($field, DewasaEngine::FIELDS, true)) {
            $errors[] = "{$path}: field tidak dikenal ".json_encode($field);

            return;
        }

        $ops = array_values(array_intersect(array_keys($node), DewasaEngine::OPERATORS));
        if (count($ops) !== 1) {
            $errors[] = "{$path}: harus punya tepat satu operator";

            return;
        }
        $this->value($field, $ops[0], $node[$ops[0]], $path, $errors);

        if (isset($node['with'])) {
            if (! is_array($node['with'])) {
                $errors[] = "{$path}.with harus objek";

                return;
            }
            foreach ($node['with'] as $withField => $withValue) {
                if (! in_array($withField, DewasaEngine::FIELDS, true)) {
                    $errors[] = "{$path}.with: field tidak dikenal ".json_encode($withField);

                    continue;
                }
                $this->value((string) $withField, 'eq', $withValue, "{$path}.with", $errors);
            }
        }
    }

    /**
     * @param  list<string>  $errors
     */
    private function value(string $field, string $op, mixed $value, string $path, array &$errors): void
    {
        if ($op === 'eq_field') {
            if (! in_array($field, self::NUMERIC_FIELDS, true) || ! is_string($value) || ! in_array($value, self::NUMERIC_FIELDS, true)) {
                $errors[] = "{$path}: eq_field hanya untuk membandingkan dua field angka (urip_total/sasih_day/sasih_number)";
            }

            return;
        }

        $values = match ($op) {
            'in', 'not_in' => is_array($value) && array_is_list($value) && $value !== [] ? $value : null,
            'between' => is_array($value) && array_is_list($value) && count($value) === 2 ? $value : null,
            default => [$value],
        };

        if ($values === null) {
            $errors[] = "{$path}: nilai {$op} untuk {$field} tidak valid";

            return;
        }

        if (in_array($field, self::BOOL_FIELDS, true)) {
            if ($op !== 'eq' || ! is_bool($value)) {
                $errors[] = "{$path}: {$field} hanya mendukung eq dengan true/false";
            }

            return;
        }

        if (in_array($field, self::NUMERIC_FIELDS, true)) {
            foreach ($values as $v) {
                if (! is_int($v)) {
                    $errors[] = "{$path}: {$field} harus angka bulat";

                    return;
                }
            }

            return;
        }

        if (in_array($op, ['between', 'gte', 'lte'], true)) {
            $errors[] = "{$path}: operator {$op} hanya untuk urip_total/sasih_day/sasih_number";

            return;
        }

        foreach ($values as $v) {
            if (! in_array($v, $this->names[$field] ?? [], true)) {
                $errors[] = "{$path}: nama {$field} tidak dikenal ".json_encode($v).' (cek ejaan, harus sama persis dengan data master)';
            }
        }
    }

    /**
     * @param  array<mixed>  $values
     * @return list<string>
     */
    private static function strings(array $values): array
    {
        return array_values(array_map(fn (mixed $v): string => is_scalar($v) ? (string) $v : '', $values));
    }
}
