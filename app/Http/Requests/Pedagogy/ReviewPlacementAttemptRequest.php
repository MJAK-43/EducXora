<?php

declare(strict_types=1);

namespace App\Http\Requests\Pedagogy;

use App\Domain\Learner\Enums\LearnerLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReviewPlacementAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('placement_tests.review');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'organization_id' => ['prohibited'],
            'center_id' => ['prohibited'],
            'validated_level' => ['required', Rule::enum(LearnerLevel::class)],
            'group_uuid' => ['nullable', 'uuid'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
