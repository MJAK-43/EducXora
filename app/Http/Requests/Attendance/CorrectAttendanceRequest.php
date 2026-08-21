<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use App\Domain\Attendance\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CorrectAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('attendance.correct');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'organization_id' => ['prohibited'],
            'center_id' => ['prohibited'],
            'status' => ['required', Rule::enum(AttendanceStatus::class)],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
