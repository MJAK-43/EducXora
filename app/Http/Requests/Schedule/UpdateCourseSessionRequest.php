<?php

declare(strict_types=1);

namespace App\Http\Requests\Schedule;

final class UpdateCourseSessionRequest extends AbstractCourseSessionRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('schedule.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->sessionRules();
    }
}
