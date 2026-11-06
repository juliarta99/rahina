<?php

declare(strict_types=1);

namespace App\AI;

use App\Data\AgentAnswer;
use App\Data\BriefInput;
use App\Data\DailyBrief;
use App\Wariga\CategoryCatalog;
use Illuminate\Support\Facades\DB;

/**
 * Penjaga keluaran LLM. TIDAK ADA keluaran model yang tampil ke pengguna tanpa lewat kelas ini
 * (CLAUDE.md §10, AI_IMPLEMENTATION.md §6).
 *
 * Untuk ringkasan harian (`brief`):
 *  1. Validasi schema & panjang teks.
 *  2. Setiap `rule_ids` harus termasuk aturan yang dikirim ke model (tidak boleh mengarang sumber).
 *  3. Poin ayu/ala/campur wajib punya rule_ids, dan verdict konsisten dengan efek aturan yang dirujuk.
 *  4. Teks tidak boleh menyebut tanggal selain hari itu, rahinan yang tidak jatuh hari itu,
 *     atau nama dewasa yang tidak ada di data.
 *  5. Sanitasi: buang HTML, markdown, emoji, spasi berlebih.
 *
 * @throws OutputGuardException berisi semua pelanggaran.
 */
final class OutputGuard
{
    private const VERDICTS = ['ayu', 'ala', 'campur', 'info'];

    /** Nama dewasa aktif, dibaca sekali per request. @var list<string>|null */
    private ?array $activeRuleNames = null;

    public function __construct(private readonly CategoryCatalog $catalog) {}

    private const MONTHS = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6,
        'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
    ];

    /**
     * Schema respons untuk Gemini (`responseSchema`).
     *
     * @return array<string, mixed>
     */
    public static function briefSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'headline' => ['type' => 'STRING'],
                'points' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'verdict' => ['type' => 'STRING', 'enum' => self::VERDICTS],
                            'text' => ['type' => 'STRING'],
                            'rule_ids' => ['type' => 'ARRAY', 'items' => ['type' => 'INTEGER']],
                        ],
                        'required' => ['verdict', 'text', 'rule_ids'],
                    ],
                ],
                'rahinan_note' => ['type' => 'STRING', 'nullable' => true],
            ],
            'required' => ['headline', 'points', 'rahinan_note'],
        ];
    }

    /**
     * @param  array<string, mixed>  $output
     */
    public function brief(array $output, BriefInput $input, string $model, string $promptVersion): DailyBrief
    {
        $errors = [];
        $allowedIds = $input->ruleIds();

        $headline = is_string($output['headline'] ?? null) ? self::clean($output['headline']) : '';
        if ($headline === '' || mb_strlen($headline) > 90) {
            $errors[] = 'headline wajib 1-90 karakter';
        }

        $rawPoints = $output['points'] ?? null;
        $points = [];
        if (! is_array($rawPoints) || ! array_is_list($rawPoints) || count($rawPoints) < 2 || count($rawPoints) > 4) {
            $errors[] = 'points wajib berisi 2-4 poin';
            $rawPoints = is_array($rawPoints) ? array_values($rawPoints) : [];
        }

        foreach ($rawPoints as $i => $point) {
            $n = $i + 1;
            if (! is_array($point)) {
                $errors[] = "poin {$n} bukan objek";

                continue;
            }

            $verdict = $point['verdict'] ?? null;
            $text = is_string($point['text'] ?? null) ? self::clean($point['text']) : '';
            $ids = $point['rule_ids'] ?? [];

            if (! is_string($verdict) || ! in_array($verdict, self::VERDICTS, true)) {
                $errors[] = "poin {$n}: verdict tidak dikenal";

                continue;
            }
            if ($text === '' || mb_strlen($text) > 140) {
                $errors[] = "poin {$n}: text wajib 1-140 karakter";
            }
            if (! is_array($ids) || array_filter($ids, fn (mixed $id): bool => ! is_int($id)) !== []) {
                $errors[] = "poin {$n}: rule_ids harus daftar angka";

                continue;
            }
            /** @var list<int> $ids */
            $ids = array_values(array_unique($ids));

            $unknown = array_diff($ids, $allowedIds);
            if ($unknown !== []) {
                $errors[] = "poin {$n}: rule_ids tidak ada di data (".implode(',', $unknown).')';
            }

            if ($verdict !== 'info') {
                $effects = [];
                foreach ($ids as $id) {
                    array_push($effects, ...$input->effectsOf($id));
                }
                $hasBaik = in_array('baik', $effects, true);
                $hasBuruk = in_array('tidak_baik', $effects, true);

                // ayu butuh efek baik, ala butuh tidak_baik, campur butuh keduanya.
                $consistent = $verdict === 'ayu' ? $hasBaik : ($verdict === 'ala' ? $hasBuruk : $hasBaik && $hasBuruk);
                if ($ids === []) {
                    $errors[] = "poin {$n}: verdict {$verdict} wajib mencantumkan rule_ids sebagai sumber";
                } elseif (! $consistent) {
                    $errors[] = "poin {$n}: verdict {$verdict} tidak sesuai efek aturan yang dirujuk";
                }
            }

            $points[] = ['verdict' => $verdict, 'text' => $text, 'rule_ids' => $ids];
        }

        $note = $output['rahinan_note'] ?? null;
        $note = is_string($note) ? self::clean($note) : null;
        $note = $note === '' ? null : $note;
        if ($note !== null && $input->day->rahinan === []) {
            $errors[] = 'rahinan_note harus null karena hari ini tidak ada rahinan';
        }
        if ($note !== null && mb_strlen($note) > 160) {
            $errors[] = 'rahinan_note maksimal 160 karakter';
        }

        $allText = implode(' ', [$headline, $note ?? '', ...array_column($points, 'text')]);
        array_push($errors, ...$this->unsupportedMentions($allText, $input));

        if ($errors !== []) {
            throw new OutputGuardException($errors);
        }

        return new DailyBrief(
            date: $input->day->date,
            headline: $headline,
            points: $points,
            rahinanNote: $note,
            generatedBy: DailyBrief::BY_AI,
            model: $model,
            promptVersion: $promptVersion,
        );
    }

    /**
     * Tanya Wariga (AI_IMPLEMENTATION.md §6):
     *  1. Schema & panjang teks.
     *  2. Setiap `dates[].date` harus dikembalikan tool di sesi ini; selain itu DIBUANG dan dicatat (`droppedDates`).
     *  3. `*_rule_ids` disaring ke aturan milik tanggal itu. Data tampilan (wewaran, nama dewasa) diambil dari hasil tool.
     *  4. Tanggal kosong padahal find_good_days punya hasil → jawaban template dari hasil tool.
     *  5. Teks yang menyebut tanggal / dewasa di luar hasil tool diganti kalimat template.
     *  6. Klarifikasi hanya boleh memakai kode kategori resmi.
     *
     * @param  array<string, mixed>  $output
     */
    public function answer(array $output, AgentContext $context): AgentAnswer
    {
        $template = new AnswerTemplate;
        $outOfScope = ($output['out_of_scope'] ?? false) === true;
        $needsClarification = ! $outOfScope && ($output['needs_clarification'] ?? false) === true;
        // Kategori ragu dan tidak ada tanggal: wajib bertanya balik walau model lupa menandainya.
        $forcedClarification = false;
        if (! $outOfScope && ! $needsClarification && $context->pendingClarification !== null && ! $context->categoryChosen && $context->rankedOrder === []) {
            $needsClarification = $forcedClarification = true;
        }

        $text = is_string($output['answer'] ?? null) ? self::clean($output['answer']) : '';
        if ($forcedClarification || $text === '' || mb_strlen($text) > 480 || $this->mentionsUnknown($text, $context)) {
            $text = '';
        }

        $dates = [];
        $dropped = [];
        $rawDates = is_array($output['dates'] ?? null) ? array_values($output['dates']) : [];
        foreach ($rawDates as $item) {
            $date = is_array($item) && is_string($item['date'] ?? null) ? trim($item['date']) : null;
            if ($date === null) {
                continue;
            }
            $known = $context->dates[$date] ?? null;
            if ($known === null) {
                $dropped[] = $date;

                continue;
            }
            if (isset($dates[$date]) || count($dates) >= 5) {
                continue;
            }

            $reason = is_string($item['reason'] ?? null) ? self::clean($item['reason']) : '';
            $misleadingToday = $date !== $context->today && preg_match('/\bhari ini\b/iu', $reason) === 1;
            if ($reason === '' || mb_strlen($reason) > 160 || $misleadingToday || $this->mentionsUnknown($reason, $context)) {
                $reason = $template->reason($known);
            }

            $dates[$date] = $template->dateCard($known, $reason);
        }
        $dates = array_values($dates);

        $generatedBy = AgentAnswer::BY_AI;
        if ($outOfScope || $needsClarification) {
            $dates = [];
        } elseif ($dates === [] && $context->rankedOrder !== []) {
            // Model tidak menyebut tanggal yang valid padahal tool punya hasil: susun dari data (tanpa LLM).
            return $template->fromContext($context, $dropped);
        }

        if ($text === '') {
            if ($dates === [] && ! $outOfScope && ! $needsClarification) {
                return $template->fromContext($context, $dropped);
            }
            $text = $outOfScope ? AnswerTemplate::OUT_OF_SCOPE : ($needsClarification ? AnswerTemplate::CLARIFY : $template->summary($context));
            $generatedBy = AgentAnswer::BY_TEMPLATE;
        }

        $options = $needsClarification ? $this->clarificationOptions($output['clarification_options'] ?? [], $context) : [];
        if ($forcedClarification && $options === []) {
            $needsClarification = false;
        }

        $optionCodes = array_column($options, 'code');
        $notice = ! $outOfScope && (($output['cultural_notice'] ?? false) === true
            || $context->requiresCulturalNotice()
            || array_filter($optionCodes, fn (string $code): bool => $this->catalog->get($code)['requires_cultural_notice'] ?? false) !== []);

        return new AgentAnswer(
            answer: $text,
            dates: $dates,
            needsClarification: $needsClarification,
            clarificationOptions: $options,
            culturalNotice: $notice,
            outOfScope: $outOfScope,
            generatedBy: $generatedBy,
            droppedDates: array_values(array_unique($dropped)),
            steps: $context->steps,
            sourceCount: AnswerTemplate::sourceCount($dates),
        );
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    private function clarificationOptions(mixed $raw, AgentContext $context): array
    {
        $codes = is_array($raw) ? array_values(array_filter($raw, 'is_string')) : [];
        if ($codes === []) {
            $codes = $context->pendingClarification ?? array_keys($context->categories);
        }
        $options = [];
        foreach (array_unique($codes) as $code) {
            $category = $this->catalog->get($code);
            if ($category !== null && count($options) < 4) {
                $options[] = ['code' => $category['code'], 'name' => $category['name']];
            }
        }

        return $options;
    }

    /** Teks menyebut tanggal kalender atau nama dewasa yang tidak ada di hasil tool. */
    private function mentionsUnknown(string $text, AgentContext $context): bool
    {
        $allowed = array_values(array_unique([...array_keys($context->dates), ...$context->allowedDates]));
        if (preg_match_all('/\b(\d{4}-\d{2}-\d{2})\b/', $text, $iso) > 0 && array_diff($iso[1], $allowed) !== []) {
            return true;
        }
        $months = implode('|', array_keys(self::MONTHS));
        if (preg_match_all("/\b(\d{1,2})\s+({$months})\b(?:\s+(\d{4}))?/iu", $text, $named, PREG_SET_ORDER) > 0) {
            foreach ($named as $m) {
                $month = self::MONTHS[mb_strtolower($m[2])];
                $hit = array_filter($allowed, function (string $d) use ($m, $month): bool {
                    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $matches)) {
                        return (int) $matches[3] === (int) $m[1]
                            && (int) $matches[2] === $month
                            && (! isset($m[3]) || (int) $matches[1] === (int) $m[3]);
                    }
                    if (preg_match('/^(\d{2})-(\d{2})$/', $d, $matches)) {
                        return (int) $matches[2] === (int) $m[1]
                            && (int) $matches[1] === $month;
                    }

                    return false;
                });
                if ($hit === []) {
                    return true;
                }
            }
        }

        $lower = mb_strtolower($text);
        $known = array_map('mb_strtolower', $context->knownRuleNames());
        $this->activeRuleNames ??= DB::table('dewasa_rules')->where('is_active', true)->pluck('name')
            ->map(fn (mixed $n): string => (string) $n)->all();
        foreach ($this->activeRuleNames as $name) {
            $name = mb_strtolower((string) $name);
            // Nama sangat pendek (≤ 4 huruf) mudah bertabrakan dengan kata umum; tidak diperiksa.
            if (mb_strlen($name) > 4 && ! in_array($name, $known, true) && preg_match('/\b'.preg_quote($name, '/').'\b/u', $lower) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tanggal, rahinan, dan nama dewasa yang disebut di teks tetapi tidak didukung data.
     *
     * @return list<string>
     */
    private function unsupportedMentions(string $text, BriefInput $input): array
    {
        $errors = [];
        $today = $input->day->date;
        [$y, $m, $d] = array_map('intval', explode('-', $today));

        if (preg_match_all('/\b(\d{4}-\d{2}-\d{2})\b/', $text, $iso) > 0) {
            foreach (array_unique($iso[1]) as $date) {
                if ($date !== $today) {
                    $errors[] = "menyebut tanggal di luar data: {$date}";
                }
            }
        }

        $months = implode('|', array_keys(self::MONTHS));
        if (preg_match_all("/\\b(\\d{1,2})\\s+({$months})\\b(?:\\s+(\\d{4}))?/iu", $text, $named, PREG_SET_ORDER) > 0) {
            foreach ($named as $match) {
                $sameDay = (int) $match[1] === $d && self::MONTHS[mb_strtolower($match[2])] === $m
                    && (! isset($match[3]) || (int) $match[3] === $y);
                if (! $sameDay) {
                    $errors[] = "menyebut tanggal di luar data: {$match[0]}";
                }
            }
        }

        $lower = mb_strtolower($text);
        $todayRahinan = array_map('mb_strtolower', array_column($input->day->rahinan, 'name'));
        $wukuNames = array_map('mb_strtolower', DB::table('wuku')->pluck('name')->map(fn (mixed $n): string => (string) $n)->all());
        foreach (DB::table('rahinan')->pluck('name') as $name) {
            $name = mb_strtolower((string) $name);
            if (in_array($name, $todayRahinan, true) || in_array($name, $wukuNames, true)) {
                continue;
            }
            if (preg_match('/\b'.preg_quote($name, '/').'\b/u', $lower) === 1) {
                $errors[] = "menyebut rahinan yang tidak jatuh hari ini: {$name}";
            }
        }

        $known = array_map(fn ($d): string => mb_strtolower($d->name), $input->dewasa);
        foreach (DB::table('dewasa_rules')->pluck('name') as $name) {
            $name = mb_strtolower((string) $name);
            if (! in_array($name, $known, true) && str_contains($lower, $name)) {
                $errors[] = "menyebut dewasa yang tidak ada di data: {$name}";
            }
        }

        return $errors;
    }

    private static function clean(string $text): string
    {
        $text = strip_tags($text);
        $text = (string) preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $text);
        // Hanya penanda markdown; garis bawah tunggal dipertahankan (bisa bagian dari nama).
        $text = (string) preg_replace(['/\*+|`+|_{2,}/', '/(^|\n)\s*(#+|>)\s*/'], ['', '$1'], $text);
        $text = (string) preg_replace('/!{2,}/', '!', $text);
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }
}
