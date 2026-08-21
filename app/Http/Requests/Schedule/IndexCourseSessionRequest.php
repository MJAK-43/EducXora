<?php

declare(strict_types=1);

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

final class IndexCourseSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('schedule.view');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'week' => ['nullable', 'date_format:Y-m-d'],
            'group' => ['nullable', 'uuid'],
            'teacher' => ['nullable', 'uuid'],
        ];
    }
}
