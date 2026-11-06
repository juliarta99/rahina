<?php

namespace App\Concerns;

use App\Models\User;
use App\Support\Phone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Aturan profil akun: nama + nomor HP (sudah dinormalkan ke 62xxxxxxxxxx sebelum divalidasi).
 */
trait ProfileValidationRules
{
    /**
     * @return array<string, array<int, Unique|ValidationRule|Closure|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'phone' => $this->phoneRules($userId),
        ];
    }

    /**
     * @return array<int, Unique|ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * @return array<int, Unique|ValidationRule|Closure|array<mixed>|string>
     */
    protected function phoneRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || ! Phone::isValid($value)) {
                    $fail('Nomor HP tidak valid. Gunakan nomor seluler Indonesia, mis. 0812 3456 7890.');
                }
            },
            $userId === null
                ? Rule::unique(User::class, 'phone')
                : Rule::unique(User::class, 'phone')->ignore($userId),
        ];
    }
}
