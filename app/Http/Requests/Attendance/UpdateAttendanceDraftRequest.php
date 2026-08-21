<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use App\Domain\Attendance\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAttendanceDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('attendance.take');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'organization_id' => ['prohibited'],
            'center_id' => ['prohibited'],
            'course_session_id' => ['prohibited'],
            'attendances' => ['required', 'array', 'max:100'],
            'attendances.*.learner_uuid' => ['required', 'uuid', 'distinct'],
            'attendances.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
        ];
    }
}
