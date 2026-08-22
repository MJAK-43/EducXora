<?php

declare(strict_types=1);

namespace App\Http\Requests\Pedagogy;

use App\Domain\Learner\Enums\LearnerLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePlacementQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('placement_questions.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return self::questionRules();
    }

    /** @return array<string, mixed> */
    public static function questionRules(): array
    {
        return [
            'organization_id' => ['prohibited'],
            'center_id' => ['prohibited'],
            'source' => ['prohibited'],
            'language' => ['required', Rule::in(['de'])],
            'level' => ['required', Rule::enum(LearnerLevel::class)],
            'prompt' => ['required', 'string', 'min:5', 'max:2000'],
            'choice_a' => ['required', 'string', 'max:1000', 'different:choice_b,choice_c,choice_d'],
            'choice_b' => ['required', 'string', 'max:1000', 'different:choice_a,choice_c,choice_d'],
            'choice_c' => ['required', 'string', 'max:1000', 'different:choice_a,choice_b,choice_d'],
            'choice_d' => ['required', 'string', 'max:1000', 'different:choice_a,choice_b,choice_c'],
            'correct_choice' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
        ];
    }
}
