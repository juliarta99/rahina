<?php

declare(strict_types=1);

namespace App\AI;

use RuntimeException;

/**
 * Agent tidak menghasilkan jawaban valid (langkah habis / JSON tetap rusak).
 * Membawa AgentContext agar pemanggil tetap bisa menyusun jawaban template dari hasil tool.
 */
final class AgentException extends RuntimeException
{
    public function __construct(public readonly string $reason, public readonly AgentContext $context)
    {
        parent::__construct("Agent gagal: {$reason}");
    }
}
