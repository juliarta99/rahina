<?php

declare(strict_types=1);

namespace App\Actions;

use App\AI\AgentContext;
use App\AI\AgentException;
use App\AI\AgentRunner;
use App\AI\AnswerTemplate;
use App\AI\GeminiException;
use App\AI\InteractionLog;
use App\AI\OutputGuard;
use App\AI\PromptRepository;
use App\Data\AgentAnswer;
use App\Wariga\CategoryCatalog;
use Carbon\CarbonImmutable;

/**
 * Fitur C "Tanya Wariga" (AI_IMPLEMENTATION.md §5).
 *
 * Alur: pertanyaan (+ kategori pilihan klarifikasi) → AgentRunner (function calling, maks 4 langkah)
 *       → OutputGuard::answer() → AgentAnswer. Model gagal → AnswerTemplate dari hasil tool yang sudah ada.
 *
 * Input: pertanyaan ≤ 500 karakter, kode kategori opsional, riwayat teks singkat.
 * Output: AgentAnswer. Tidak melempar exception karena kegagalan AI.
 * Privasi: hanya teks pertanyaan dikirim ke Gemini; log hanya menyimpan hash (§9).
 */
final class AskWarigaAction
{
    public function __construct(
        private readonly AgentRunner $runner,
        private readonly OutputGuard $guard,
        private readonly InteractionLog $log,
        private readonly CategoryCatalog $catalog,
    ) {}

    /**
     * @param  list<array{role: 'user'|'assistant', text: string}>  $history
     */
    public function execute(string $question, ?string $categoryCode = null, array $history = []): AgentAnswer
    {
        $today = CarbonImmutable::today()->toDateString();
        $versionTag = PromptRepository::versionTag(AgentRunner::PROMPT_ID, AgentRunner::PROMPT_VERSION);

        $message = $question;
        if ($categoryCode !== null && ($name = $this->catalog->get($categoryCode)['name'] ?? null) !== null) {
            $message .= "\n(Pengguna memilih kategori: {$categoryCode} - {$name}. Langsung cari tanggalnya.)";
        }
        $hash = hash('sha256', $message);

        try {
            $run = $this->runner->run($message, $today, $history, categoryChosen: $message !== $question);
        } catch (GeminiException $e) {
            // Model gagal sebelum ada hasil tool: tidak ada konteks, jawab dengan template.
            $answer = (new AnswerTemplate)->fromContext(new AgentContext($today), unavailableText: $e->reason === 'quota' ? AnswerTemplate::QUOTA : null);
            $this->log->record('ask', $hash, 'fallback', null, $versionTag, null, "{$e->reason}: {$e->getMessage()}");

            return $answer;
        } catch (AgentException $e) {
            $answer = (new AnswerTemplate)->fromContext($e->context, unavailableText: $e->reason === 'gemini_quota' ? AnswerTemplate::QUOTA : null);
            $this->log->record('ask', $hash, 'fallback', null, $versionTag, null, $e->reason, $this->extra($e->context, $answer));

            return $answer;
        }

        $answer = $this->guard->answer($run['output'], $run['context']);
        $status = $answer->outOfScope ? 'refused' : ($answer->generatedBy === AgentAnswer::BY_AI ? 'ok' : 'fallback');
        $this->log->record('ask', $hash, $status, $run['model'], $versionTag, $run['latency_ms'], null, $this->extra($run['context'], $answer));

        return $answer;
    }

    /**
     * @return array<string, mixed>
     */
    private function extra(AgentContext $context, AgentAnswer $answer): array
    {
        return [
            // Nama tool + argumen kalender (kode kategori, rentang tanggal). Teks aktivitas tidak disimpan.
            'tool_calls' => array_map(fn (array $c): array => [
                'name' => $c['name'],
                'ok' => $c['ok'],
                'args' => array_diff_key($c['args'], ['text' => true]),
            ], $context->toolCalls),
            'returned_dates' => array_column($answer->dates, 'date'),
            'dropped_dates' => $answer->droppedDates,
        ];
    }
}
