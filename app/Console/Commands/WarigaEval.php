<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\AI\AgentException;
use App\AI\AgentRunner;
use App\AI\GeminiException;
use App\AI\OutputGuard;
use App\Data\AgentAnswer;
use App\Wariga\DateRanker;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use Throwable;

/**
 * Evaluasi agent Tanya Wariga dengan Gemini SUNGGUHAN (AI_IMPLEMENTATION.md §10). Tidak jalan di CI.
 *
 * Membaca tests/Eval/questions.jsonl (50 pertanyaan, 5 tipe) dan mengukur:
 *  - akurasi tanggal: setiap tanggal di jawaban (a) dihitung ulang oleh DateRanker dengan argumen tool yang sama,
 *    (b) berada di rentang/tanggal yang diminta pertanyaan. Target 100%.
 *  - pemetaan kategori (tipe range), klarifikasi (ambiguous), penolakan (offtopic), catatan budaya, latensi.
 * Hasil ditulis ke docs/EVAL_RESULTS.md.
 */
class WarigaEval extends Command
{
    protected $signature = 'wariga:eval
        {--only= : daftar id dipisah koma}
        {--sleep=1 : jeda detik antar-pertanyaan (kuota API)}
        {--today= : tanggal "hari ini" untuk agent, default hari ini}
        {--limit=0 : maksimal pertanyaan baru per jalan (0 = semua)}
        {--resume : lanjutkan dari storage/app/eval/results.jsonl}
        {--out=docs/EVAL_RESULTS.md}';

    protected $description = 'Evaluasi agent Tanya Wariga (memanggil Gemini sungguhan)';

    public function handle(AgentRunner $runner, OutputGuard $guard, DateRanker $ranker): int
    {
        $today = (string) ($this->option('today') ?: CarbonImmutable::today()->toDateString());
        $only = array_filter(array_map('intval', explode(',', (string) $this->option('only'))));
        $rows = [];
        foreach (file(base_path('tests/Eval/questions.jsonl'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $q = json_decode($line, true, 8, JSON_THROW_ON_ERROR);
            if ($only === [] || in_array($q['id'], $only, true)) {
                $rows[] = $q;
            }
        }

        // Hasil per pertanyaan disimpan agar eval bisa dilanjutkan bertahap (--resume, --limit).
        $store = storage_path('app/eval/results.jsonl');
        if (! $this->option('resume') && is_file($store)) {
            unlink($store);
        }
        @mkdir(dirname($store), 0775, true);
        $done = [];
        foreach (is_file($store) ? (file($store, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) : [] as $line) {
            $r = json_decode($line, true, 8, JSON_THROW_ON_ERROR);
            if ($r['error'] === null) { // hasil error API diulang saat --resume
                $done[$r['id']] = $r;
            }
        }

        $limit = (int) $this->option('limit');
        $ran = 0;
        foreach ($rows as $q) {
            if (isset($done[$q['id']]) || ($limit > 0 && $ran >= $limit)) {
                continue;
            }
            if ($ran > 0) {
                Sleep::for((int) $this->option('sleep'))->seconds();
            }
            $ran++;
            $r = $this->evaluate($q, $today, $runner, $guard, $ranker);
            $done[$q['id']] = $r;
            file_put_contents($store, json_encode($r, JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
            $this->line(sprintf('#%02d %-9s %s %5dms %s', $q['id'], $q['type'], $r['pass'] ? 'OK  ' : 'GAGAL', $r['latency'], $r['note']));
        }

        ksort($done);
        $results = array_values($done);
        $remaining = count($rows) - count(array_intersect_key($done, array_flip(array_column($rows, 'id'))));
        if ($remaining > 0) {
            $this->info("Tersimpan {$ran} hasil baru; sisa {$remaining} pertanyaan. Lanjutkan dengan --resume.");

            return self::SUCCESS;
        }

        $report = $this->report($results, $today);
        file_put_contents(base_path((string) $this->option('out')), $report);
        $this->newLine();
        $this->line($report);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $q
     * @return array<string, mixed>
     */
    private function evaluate(array $q, string $today, AgentRunner $runner, OutputGuard $guard, DateRanker $ranker): array
    {
        $started = hrtime(true);
        $geminiMs = 0;
        $error = null;
        $answer = null;
        $context = null;
        try {
            $run = $runner->run((string) $q['q'], $today);
            $geminiMs = $run['latency_ms'];
            $context = $run['context'];
            $answer = $guard->answer($run['output'], $context);
        } catch (AgentException $e) {
            $context = $e->context;
            $error = $e->reason;
        } catch (GeminiException $e) {
            $error = $e->reason;
        } catch (Throwable $e) {
            $error = $e::class.': '.$e->getMessage();
        }
        $latency = (int) ((hrtime(true) - $started) / 1_000_000);

        $base = ['id' => $q['id'], 'type' => $q['type'], 'latency' => $latency, 'gemini_ms' => $geminiMs, 'dates_total' => 0, 'dates_ok' => 0, 'error' => $error];
        if (! $answer instanceof AgentAnswer || $context === null) {
            return $base + ['pass' => false, 'note' => "error: {$error}", 'category_ok' => null, 'notice_ok' => null];
        }

        // Akurasi tanggal: hitung ulang dengan DateRanker memakai argumen find_good_days yang dipakai agent.
        $truth = [];
        foreach ($context->toolCalls as $call) {
            if ($call['name'] === 'find_good_days' && $call['ok']) {
                try {
                    foreach ($ranker->rank($call['args']['category_codes'], $call['args']['start'], $call['args']['end'], (int) ($call['args']['limit'] ?? 5)) as $r) {
                        $truth[$r->day->date] = true;
                    }
                } catch (InvalidArgumentException) {
                }
            }
            if ($call['name'] === 'get_day' && $call['ok']) {
                $truth[(string) $call['args']['date']] = true;
            }
        }
        $datesOk = 0;
        foreach ($answer->dates as $d) {
            $inRange = match ($q['type']) {
                'range' => $d['date'] >= $q['start'] && $d['date'] <= $q['end'],
                'specific' => in_array($d['date'], $q['dates'], true),
                default => true, // tipe lain: cukup terbukti dari hasil tool
            };
            $datesOk += (isset($truth[$d['date']]) && $inRange) ? 1 : 0;
        }
        $datesTotal = count($answer->dates);

        $usedCodes = [];
        foreach ($context->toolCalls as $call) {
            if ($call['name'] === 'find_good_days') {
                array_push($usedCodes, ...(array) ($call['args']['category_codes'] ?? []));
            }
        }
        $categoryOk = $q['type'] === 'range' ? array_intersect($usedCodes, $q['categories']) !== [] : null;
        $noticeOk = ($q['cultural_notice'] ?? false) ? $answer->culturalNotice : null;

        [$pass, $note] = match ($q['type']) {
            'range' => [$categoryOk && $datesOk === $datesTotal && ($noticeOk ?? true),
                'kategori='.implode(',', array_unique($usedCodes)).' tanggal='.implode(',', array_column($answer->dates, 'date')).($answer->generatedBy === 'template' ? ' [template]' : '')],
            'ambiguous' => [$answer->needsClarification && $datesTotal === 0,
                $answer->needsClarification ? 'klarifikasi: '.implode(',', array_column($answer->clarificationOptions, 'code')) : 'tidak bertanya balik: '.mb_substr($answer->answer, 0, 70)],
            'specific' => [$datesOk === $datesTotal && $answer->answer !== '' && ! $answer->outOfScope,
                'tanggal='.implode(',', array_column($answer->dates, 'date')).' '.mb_substr($answer->answer, 0, 60)],
            'concept' => [! $answer->outOfScope && $datesTotal === 0 && collect($context->toolCalls)->contains('name', 'explain_dewasa'),
                mb_substr($answer->answer, 0, 80)],
            'offtopic' => [$answer->outOfScope && $datesTotal === 0 && ! collect($context->toolCalls)->contains(fn (array $c): bool => $c['name'] === 'find_good_days' && $c['ok']),
                ($answer->outOfScope ? 'ditolak: ' : 'TIDAK ditolak: ').mb_substr($answer->answer, 0, 60)],
            default => [false, 'tipe tidak dikenal'],
        };

        return ['pass' => $pass, 'note' => $note, 'dates_total' => $datesTotal, 'dates_ok' => $datesOk, 'category_ok' => $categoryOk, 'notice_ok' => $noticeOk] + $base;
    }

    /**
     * @param  list<array<string, mixed>>  $results
     */
    private function report(array $results, string $today): string
    {
        $pct = fn (int $a, int $b): string => $b === 0 ? '–' : number_format(100 * $a / $b, 1).'%';
        $of = fn (string $type): array => array_values(array_filter($results, fn (array $r): bool => $r['type'] === $type));

        $datesTotal = array_sum(array_column($results, 'dates_total'));
        $datesOk = array_sum(array_column($results, 'dates_ok'));
        $range = $of('range');
        $catOk = count(array_filter($range, fn (array $r): bool => $r['category_ok'] === true));
        $amb = $of('ambiguous');
        $ambOk = count(array_filter($amb, fn (array $r): bool => $r['pass']));
        $off = $of('offtopic');
        $offOk = count(array_filter($off, fn (array $r): bool => $r['pass']));
        $notice = array_values(array_filter($results, fn (array $r): bool => $r['notice_ok'] !== null));
        $noticeOk = count(array_filter($notice, fn (array $r): bool => $r['notice_ok'] === true));
        $lat = array_column($results, 'latency');
        sort($lat);
        $gem = array_values(array_filter(array_column($results, 'gemini_ms')));
        sort($gem);
        $pg = fn (float $q): int => $gem === [] ? 0 : $gem[(int) min(count($gem) - 1, floor($q * count($gem)))];
        $p = fn (float $q): int => $lat === [] ? 0 : $lat[(int) min(count($lat) - 1, floor($q * count($lat)))];
        $errors = count(array_filter($results, fn (array $r): bool => $r['error'] !== null));

        $lines = [
            '# EVAL_RESULTS.md — Tanya Wariga',
            '',
            '_Dihasilkan `php artisan wariga:eval` pada '.now()->toDateTimeString().' WITA · model `'.config('rahina.gemini.model').'` · prompt `agent_system.v'.AgentRunner::PROMPT_VERSION.'` · "hari ini" agent = '.$today.'._',
            '',
            'Data dewasa: kalenderbali.org (dengan izin, belum ditinjau narasumber). Akurasi tanggal diukur terhadap hasil hitung ulang `DateRanker` dengan argumen tool yang sama, dan rentang tanggal yang diminta pertanyaan.',
            '',
            '| Metrik | Hasil | Target |',
            '|---|---|---|',
            "| Akurasi tanggal ({$datesOk}/{$datesTotal} tanggal) | {$pct($datesOk, $datesTotal)} | 100% |",
            '| Pemetaan kategori ('.$catOk.'/'.count($range).') | '.$pct($catOk, count($range)).' | ≥ 90% |',
            '| Klarifikasi tepat ('.$ambOk.'/'.count($amb).') | '.$pct($ambOk, count($amb)).' | ≥ 85% |',
            '| Penolakan di luar topik ('.$offOk.'/'.count($off).') | '.$pct($offOk, count($off)).' | 100% |',
            '| Catatan budaya upacara ('.$noticeOk.'/'.count($notice).') | '.$pct($noticeOk, count($notice)).' | 100% |',
            '| Latensi total p50 / p95 (termasuk query DB) | '.number_format($p(0.5) / 1000, 1).'s / '.number_format($p(0.95) / 1000, 1).'s | < 4s / < 9s |',
            '| Latensi Gemini saja p50 / p95 | '.number_format($pg(0.5) / 1000, 1).'s / '.number_format($pg(0.95) / 1000, 1).'s | — |',
            "| Error API / agent | {$errors} | 0 |",
            '',
            ...($errors > 0 ? [
                "> **Belum final:** {$errors} pertanyaan tidak terjawab karena error API (mis. kuota Gemini habis), sehingga metrik tipe terkait belum mencerminkan perilaku agent. Ulangi setelah kuota pulih: `php artisan wariga:eval --resume --sleep=12`.",
                '',
            ] : []),
            'Kriteria: *range* = kategori benar + semua tanggal terbukti dari hasil hitung ulang & di rentang yang diminta; *ambiguous* = bertanya balik tanpa tanggal; *specific* = tanggal sesuai yang ditanya; *concept* = memanggil `explain_dewasa`, tanpa tanggal; *offtopic* = ditolak, tanpa tanggal, tanpa pencarian tanggal yang berhasil.',
            '',
            '| # | Tipe | Lulus | ms | Catatan |',
            '|---|---|---|---|---|',
        ];
        foreach ($results as $r) {
            $lines[] = sprintf('| %d | %s | %s | %d | %s |', $r['id'], $r['type'], $r['pass'] ? 'ya' : 'tidak', $r['latency'], str_replace('|', '/', (string) $r['note']));
        }

        return implode("\n", $lines)."\n";
    }
}
