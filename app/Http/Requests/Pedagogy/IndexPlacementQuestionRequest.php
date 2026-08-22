<?php

declare(strict_types=1);

namespace App\Http\Requests\Pedagogy;

use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Pedagogy\Enums\QuestionSource;
use App\Domain\Pedagogy\Enums\QuestionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexPlacementQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('placement_questions.view');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'level' => ['nullable', Rule::enum(LearnerLevel::class)],
            'source' => ['nullable', Rule::enum(QuestionSource::class)],
            'status' => ['nullable', Rule::enum(QuestionStatus::class)],
        ];
    }
}
