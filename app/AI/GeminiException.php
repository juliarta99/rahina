<?php

declare(strict_types=1);

namespace App\AI;

use RuntimeException;
use Throwable;

/**
 * Kegagalan memanggil Gemini. `reason` dipakai untuk log & keputusan fallback:
 * not_configured | http | timeout | blocked | invalid_response | invalid_json.
 */
final class GeminiException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message,
        public readonly ?int $status = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
