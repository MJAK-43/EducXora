<?php

declare(strict_types=1);

namespace App\Http\Requests\Groups;

use App\Domain\Learner\Enums\LearnerLanguage;
use App\Domain\Learner\Enums\LearnerLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class AbstractGroupRequest extends FormRequest
{
    /** @return array<string, mixed> */
    protected function groupRules(): array
    {
        return [
            'organization_id' => ['prohibited'],
            'center_id' => ['prohibited'],
            'name' => ['required', 'string', 'max:120'],
            'language' => ['required', Rule::enum(LearnerLanguage::class)],
            'level' => ['required', Rule::enum(LearnerLevel::class)],
            'capacity' => ['required', 'integer', 'min:1', 'max:65535'],
            'teacher_membership_uuid' => ['required', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name'))]);
    }
}
