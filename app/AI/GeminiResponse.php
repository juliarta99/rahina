<?php

declare(strict_types=1);

namespace App\AI;

/**
 * Hasil satu panggilan generateContent.
 *
 * @phpstan-type FunctionCall array{name: string, args: array<string, mixed>}
 */
final readonly class GeminiResponse
{
    /**
     * @param  list<array{name: string, args: array<string, mixed>}>  $functionCalls
     * @param  array<string, mixed>  $modelContent  konten `role: model` apa adanya, untuk dikirim ulang di percakapan multi-langkah
     */
    public function __construct(
        public string $text,
        public array $functionCalls,
        public array $modelContent,
        public string $model,
        public int $latencyMs,
        public ?string $finishReason,
    ) {}
}
