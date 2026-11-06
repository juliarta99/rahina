<?php

declare(strict_types=1);

namespace App\Wariga;

use App\Data\BriefInput;
use App\Data\DailyBrief;
use App\Data\DewasaMatch;

/**
 * Ringkasan cadangan TANPA AI, dibangun langsung dari data (dipakai bila Gemini gagal / guard menolak).
 * Hasilnya selalu memenuhi aturan OutputGuard yang sama dengan keluaran AI.
 */
final class BriefTemplate
{
    public const MODEL = 'template';

    public function build(BriefInput $input, string $promptVersion): DailyBrief
    {
        $day = $input->day;
        $points = [];

        foreach (['baik' => 'ayu', 'tidak_baik' => 'ala'] as $effect => $verdict) {
            $rules = array_values(array_filter(
                $input->dewasa,
                fn (DewasaMatch $d): bool => in_array($effect, array_column($d->effects, 'effect'), true),
            ));
            if ($rules === []) {
                continue;
            }

            $activities = [];
            foreach ($rules as $rule) {
                foreach ($rule->effects as $e) {
                    if ($e['effect'] === $effect) {
                        $activities[$e['category_name']] = true;
                    }
                }
            }

            $lead = $effect === 'baik' ? 'Baik untuk ' : 'Kurang baik untuk ';
            $text = $lead.mb_strtolower(implode(', ', array_keys($activities))).', karena '
                .implode(', ', array_map(fn (DewasaMatch $d): string => $d->name, $rules)).'.';

            $points[] = [
                'verdict' => $verdict,
                'text' => self::limit($text, 140),
                'rule_ids' => array_map(fn (DewasaMatch $d): int => $d->ruleId, $rules),
            ];
        }

        if ($input->dewasa === []) {
            $points[] = [
                'verdict' => 'info',
                'text' => 'Belum ada dewasa tervalidasi untuk hari ini. Data perhitungan tetap tersedia di bawah.',
                'rule_ids' => [],
            ];
        }

        $moon = $day->isPurnama ? ', Purnama' : ($day->isTilem ? ', Tilem' : '');
        $points[] = [
            'verdict' => 'info',
            'text' => ($day->isPenanggal ? 'Penanggal ' : 'Pangelong ').$day->sasihDay
                .($day->sasihDayEnd !== null ? "/{$day->sasihDayEnd} (ngunaratri)" : '')
                ." sasih {$day->sasih->name}{$moon}.",
            'rule_ids' => [],
        ];

        $rahinan = array_column($day->rahinan, 'name');

        return new DailyBrief(
            date: $day->date,
            headline: self::limit("{$day->saptawara()} {$day->pancawara()}, Wuku {$day->wuku->name}", 90),
            points: array_slice($points, 0, 4),
            rahinanNote: $rahinan === [] ? null : self::limit('Hari ini bertepatan dengan '.implode(', ', $rahinan).'.', 160),
            generatedBy: DailyBrief::BY_TEMPLATE,
            model: self::MODEL,
            promptVersion: $promptVersion,
        );
    }

    private static function limit(string $text, int $max): string
    {
        return mb_strlen($text) <= $max ? $text : rtrim(mb_substr($text, 0, $max - 1)).'…';
    }
}
