<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Nilai master wariga (wuku, salah satu wewaran, atau sasih) dalam bentuk ringkas.
 */
final readonly class WarigaValue
{
    public function __construct(
        public int $id,
        public string $name,
        public ?int $urip = null,
    ) {}

    /**
     * @return array{id: int, name: string, urip: ?int}
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'urip' => $this->urip];
    }
}
