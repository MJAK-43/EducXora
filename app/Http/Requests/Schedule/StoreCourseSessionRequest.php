<?php

declare(strict_types=1);

namespace App\Http\Requests\Schedule;

final class StoreCourseSessionRequest extends AbstractCourseSessionRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('schedule.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->sessionRules();
    }
}
