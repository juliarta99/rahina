<?php

declare(strict_types=1);

namespace App\AI\Tools;

use App\AI\AgentContext;
use InvalidArgumentException;

/**
 * Satu fungsi yang boleh dipanggil model (function calling). Tool hanya MEMBACA data deterministik
 * dari domain Wariga; tidak ada perhitungan kalender oleh model.
 */
interface Tool
{
    public function name(): string;

    /**
     * Deklarasi fungsi format Gemini (`functionDeclarations[]`).
     *
     * @return array<string, mixed>
     */
    public function declaration(): array;

    /**
     * Teks status untuk UI, mis. "Mencari hari baik 2026-12-01 s.d. 2026-12-31".
     *
     * @param  array<string, mixed>  $args
     */
    public function status(array $args): string;

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed> dikirim balik ke model sebagai `functionResponse.response`
     *
     * @throws InvalidArgumentException argumen tidak valid; pesannya dikirim ke model untuk dijelaskan.
     */
    public function run(array $args, AgentContext $context): array;
}
