<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi pertanyaan Tanya Wariga: teks ≤ 500 karakter, kategori pilihan (klarifikasi),
 * dan riwayat singkat (maks 4 giliran, hanya teks).
 */
class AskRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'min:2', 'max:500'],
            'category' => ['nullable', 'string', 'max:64', Rule::exists('activity_categories', 'code')],
            'history' => ['nullable', 'array', 'max:4'],
            'history.*.role' => ['required', Rule::in(['user', 'assistant'])],
            'history.*.text' => ['required', 'string', 'max:600'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question.required' => 'Tulis pertanyaan terlebih dahulu.',
            'question.max' => 'Pertanyaan maksimal 500 karakter.',
        ];
    }

    /**
     * @return list<array{role: 'user'|'assistant', text: string}>
     */
    public function history(): array
    {
        /** @var list<array{role: 'user'|'assistant', text: string}> $history */
        $history = array_values((array) ($this->validated('history') ?? []));

        return $history;
    }
}
