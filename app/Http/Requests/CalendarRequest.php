<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Parameter kalender bulanan: `bulan` (Y-m, 2020-01…2035-12).
 */
class CalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $min = (int) config('rahina.calendar.min_year');
        $max = (int) config('rahina.calendar.max_year');

        return [
            'bulan' => ['nullable', 'date_format:Y-m', "after_or_equal:{$min}-01", "before_or_equal:{$max}-12"],
        ];
    }

    public function month(): CarbonImmutable
    {
        $month = $this->validated('bulan');

        return is_string($month)
            ? CarbonImmutable::createFromFormat('!Y-m', $month) ?: CarbonImmutable::today()->startOfMonth()
            : CarbonImmutable::today()->startOfMonth();
    }
}
