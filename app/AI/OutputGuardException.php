<?php

declare(strict_types=1);

namespace App\AI;

use RuntimeException;

/**
 * Keluaran LLM ditolak OutputGuard. `violations` dipakai untuk pesan koreksi ke model & log.
 */
final class OutputGuardException extends RuntimeException
{
    /**
     * @param  list<string>  $violations
     */
    public function __construct(public readonly array $violations)
    {
        parent::__construct('Keluaran AI ditolak: '.implode('; ', $violations));
    }
}
