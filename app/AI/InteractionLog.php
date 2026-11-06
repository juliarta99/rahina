<?php

declare(strict_types=1);

namespace App\AI;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Menulis `ai_interactions` TANPA data pribadi: hanya hash input, metadata, dan status.
 * Kegagalan menulis log tidak boleh menggagalkan fitur.
 */
final class InteractionLog
{
    /**
     * @param  array<string, mixed>  $extra  tool_calls, returned_dates, dropped_dates
     */
    public function record(
        string $feature,
        string $inputHash,
        string $status,
        ?string $model,
        ?string $promptVersion,
        ?int $latencyMs,
        ?string $error = null,
        array $extra = [],
    ): void {
        try {
            DB::table('ai_interactions')->insert([
                'feature' => $feature,
                'input_hash' => $inputHash,
                'tool_calls' => isset($extra['tool_calls']) ? json_encode($extra['tool_calls']) : null,
                'returned_dates' => isset($extra['returned_dates']) ? json_encode($extra['returned_dates']) : null,
                'dropped_dates' => isset($extra['dropped_dates']) ? json_encode($extra['dropped_dates']) : null,
                'model' => $model,
                'prompt_version' => $promptVersion,
                'latency_ms' => $latencyMs,
                'status' => $status,
                'error' => $error === null ? null : mb_substr($error, 0, 500),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
