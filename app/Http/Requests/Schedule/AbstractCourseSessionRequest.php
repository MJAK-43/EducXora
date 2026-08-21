<?php

declare(strict_types=1);

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractCourseSessionRequest extends FormRequest
{
    /** @return array<string, mixed> */
    protected function sessionRules(): array
    {
        return [
            'organization_id' => ['prohibited'],
            'center_id' => ['prohibited'],
            'group_uuid' => ['required', 'uuid'],
            'teacher_membership_uuid' => ['required', 'uuid'],
            'room' => ['nullable', 'string', 'max:120'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $room = trim((string) $this->input('room'));
        $this->merge(['room' => $room === '' ? null : $room]);
    }
}
