<?php

declare(strict_types=1);

namespace App\AI;

use App\AI\Tools\ConsultKnowledgeTool;
use App\AI\Tools\ExplainDewasaTool;
use App\AI\Tools\FindGoodDaysTool;
use App\AI\Tools\GetDayTool;
use App\AI\Tools\MapActivityTool;
use App\AI\Tools\Tool;
use App\Wariga\CategoryCatalog;
use InvalidArgumentException;
use JsonException;
use Throwable;

/**
 * Loop function calling Tanya Wariga (AI_IMPLEMENTATION.md §5.2), maksimal RAHINA_AGENT_MAX_STEPS panggilan model.
 *
 *   model → functionCall? → jalankan tool PHP → functionResponse (atau .error) → model … → jawaban JSON
 *
 * Model hanya memilih tool & merangkai kalimat. Semua tanggal & dewasa berasal dari tool, dicatat di
 * AgentContext, lalu diperiksa OutputGuard. JSON tidak valid → satu kali pesan koreksi.
 *
 * Input: pertanyaan, riwayat singkat (teks). Output: array{output: array, context: AgentContext, model: string, latency_ms: int}.
 *
 * @throws GeminiException gagal memanggil model (pemanggil memakai fallback).
 * @throws AgentException langkah habis / keluaran tetap tidak valid (pemanggil memakai fallback dari AgentContext).
 */
final class AgentRunner
{
    /** @var array<string, Tool> */
    private array $tools = [];

    /** Hasil tool sesi berjalan, per nama+argumen. @var array<string, array<string, mixed>> */
    private array $results = [];

    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly PromptRepository $prompts,
        private readonly CategoryCatalog $catalog,
        MapActivityTool $mapActivity,
        FindGoodDaysTool $findGoodDays,
        GetDayTool $getDay,
        ExplainDewasaTool $explainDewasa,
        ConsultKnowledgeTool $consultKnowledge,
    ) {
        foreach ([$mapActivity, $findGoodDays, $getDay, $explainDewasa, $consultKnowledge] as $tool) {
            $this->tools[$tool->name()] = $tool;
        }
    }

    public const PROMPT_ID = 'agent_system';

    public const PROMPT_VERSION = 1;

    /**
     * Schema jawaban final (AI_IMPLEMENTATION.md §5.3, + `out_of_scope` untuk penolakan topik).
     *
     * @return array<string, mixed>
     */
    public static function answerSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'answer' => ['type' => 'STRING'],
                'dates' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'date' => ['type' => 'STRING'],
                            'reason' => ['type' => 'STRING'],
                            'support_rule_ids' => ['type' => 'ARRAY', 'items' => ['type' => 'INTEGER']],
                            'block_rule_ids' => ['type' => 'ARRAY', 'items' => ['type' => 'INTEGER']],
                        ],
                        'required' => ['date', 'reason', 'support_rule_ids', 'block_rule_ids'],
                    ],
                ],
                'needs_clarification' => ['type' => 'BOOLEAN'],
                'clarification_options' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'cultural_notice' => ['type' => 'BOOLEAN'],
                'out_of_scope' => ['type' => 'BOOLEAN'],
            ],
            'required' => ['answer', 'dates', 'needs_clarification', 'clarification_options', 'cultural_notice', 'out_of_scope'],
        ];
    }

    /**
     * @param  list<array{role: 'user'|'assistant', text: string}>  $history
     * @param  callable(string): void|null  $onStep  dipanggil setiap tool dijalankan (status untuk log/UI)
     * @return array{output: array<string, mixed>, context: AgentContext, model: string, latency_ms: int}
     */
    public function run(string $question, string $today, array $history = [], ?callable $onStep = null, bool $categoryChosen = false): array
    {
        $context = new AgentContext($today);
        $context->categoryChosen = $categoryChosen;
        $context->question = $question;
        $this->results = [];
        $system = $this->systemPrompt($today);
        $tools = [['functionDeclarations' => array_values(array_map(fn (Tool $t): array => $t->declaration(), $this->tools))]];
        $config = ['responseMimeType' => 'application/json', 'responseSchema' => self::answerSchema()];
        $thinking = (string) config('rahina.agent.thinking_level');
        if ($thinking !== '') {
            $config['thinkingConfig'] = ['thinkingLevel' => $thinking];
        }

        $contents = [];
        foreach (array_slice($history, -4) as $turn) {
            $contents[] = ['role' => $turn['role'] === 'assistant' ? 'model' : 'user', 'parts' => [['text' => mb_substr($turn['text'], 0, 600)]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $question]]];

        $maxSteps = max(1, (int) config('rahina.agent.max_steps'));
        $latency = 0;
        $model = $this->gemini->model();
        $corrected = false;

        for ($step = 1; $step <= $maxSteps + 1; $step++) {
            // Langkah terakhir: pemanggilan tool dimatikan (mode NONE) supaya model wajib menyusun jawaban
            // dari hasil yang sudah ada. Deklarasi tetap dikirim karena riwayat berisi functionCall.
            $final = $step > $maxSteps;
            try {
                $response = $this->gemini->generate(
                    $contents, $system, $config, $tools,
                    toolConfig: $final ? ['functionCallingConfig' => ['mode' => 'NONE']] : [],
                );
            } catch (GeminiException $e) {
                // Model gagal setelah tool berjalan: hasil tool tetap dipakai untuk jawaban template.
                if ($context->toolCalls !== []) {
                    throw new AgentException("gemini_{$e->reason}", $context);
                }

                throw $e;
            }
            $latency += $response->latencyMs;
            $model = $response->model;
            $contents[] = $response->modelContent;

            if ($response->functionCalls !== [] && ! $final) {
                $parts = [];
                foreach ($response->functionCalls as $call) {
                    $parts[] = ['functionResponse' => ['name' => $call['name'], 'response' => $this->runTool($call['name'], $call['args'], $context, $onStep)]];
                }
                $contents[] = ['role' => 'user', 'parts' => $parts];

                continue;
            }

            try {
                $output = json_decode($response->text, true, 32, JSON_THROW_ON_ERROR);
                if (! is_array($output) || array_is_list($output)) {
                    throw new JsonException('bukan objek');
                }
            } catch (JsonException) {
                if ($corrected) {
                    throw new AgentException('invalid_json', $context);
                }
                $corrected = true;
                $contents[] = ['role' => 'user', 'parts' => [['text' => 'Keluaran sebelumnya bukan JSON valid. Kirim ulang jawaban final sebagai satu objek JSON sesuai schema, tanpa teks lain.']]];

                continue;
            }

            /** @var array<string, mixed> $output */
            return ['output' => $output, 'context' => $context, 'model' => $model, 'latency_ms' => $latency];
        }

        throw new AgentException('max_steps', $context);
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function runTool(string $name, array $args, AgentContext $context, ?callable $onStep): array
    {
        $tool = $this->tools[$name] ?? null;
        if ($tool === null) {
            $context->toolCalls[] = ['name' => $name, 'args' => $args, 'ok' => false, 'error' => 'unknown_tool'];

            return ['error' => "Tool {$name} tidak tersedia."];
        }

        // Panggilan identik yang diulang model: pakai hasil sebelumnya (hemat kuota & waktu).
        $cacheKey = $name.':'.json_encode($args);
        if (isset($this->results[$cacheKey])) {
            return $this->results[$cacheKey];
        }

        $status = $tool->status($args);
        $context->steps[] = $status;
        if ($onStep !== null) {
            $onStep($status);
        }

        try {
            $result = $tool->run($args, $context);
            $context->toolCalls[] = ['name' => $name, 'args' => $args, 'ok' => true];

            return $this->results[$cacheKey] = $result;
        } catch (InvalidArgumentException $e) {
            // Error argumen dijelaskan model ke pengguna (AI_IMPLEMENTATION.md §8).
            $context->toolCalls[] = ['name' => $name, 'args' => $args, 'ok' => false, 'error' => $e->getMessage()];

            return ['error' => $e->getMessage()];
        } catch (Throwable $e) {
            report($e);
            $context->toolCalls[] = ['name' => $name, 'args' => $args, 'ok' => false, 'error' => 'internal'];

            return ['error' => 'Data sedang tidak dapat dibaca. Sampaikan maaf dan sarankan mencoba lagi.'];
        }
    }

    private function systemPrompt(string $today): string
    {
        $body = $this->prompts->get(self::PROMPT_ID, self::PROMPT_VERSION)['body'];

        return strtr($body, [
            '{{today}}' => $today,
            '{{min_year}}' => (string) config('rahina.calendar.min_year'),
            '{{max_year}}' => (string) config('rahina.calendar.max_year'),
            '{{max_range_days}}' => (string) config('rahina.agent.max_range_days'),
            '{{categories}}' => implode("\n", array_map(fn (array $c): string => "- {$c['code']} — {$c['name']}", $this->catalog->all())),
        ]);
    }
}
