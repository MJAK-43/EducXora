<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

final class ValidateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('attendance.validate');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['organization_id' => ['prohibited'], 'center_id' => ['prohibited']];
    }
}
