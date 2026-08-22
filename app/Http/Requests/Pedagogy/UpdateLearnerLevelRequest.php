<?php

declare(strict_types=1);

namespace App\Http\Requests\Pedagogy;

use App\Domain\Learner\Enums\LearnerLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateLearnerLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('learner_levels.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'organization_id' => ['prohibited'],
            'center_id' => ['prohibited'],
            'level' => ['required', Rule::enum(LearnerLevel::class)],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
