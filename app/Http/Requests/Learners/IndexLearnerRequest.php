<?php

declare(strict_types=1);

namespace App\Http\Requests\Learners;

use App\Domain\Learner\Enums\LearnerLanguage;
use App\Domain\Learner\Enums\LearnerLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexLearnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('learners.view');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'archived', 'all'])],
            'level' => ['nullable', Rule::enum(LearnerLevel::class)],
            'language' => ['nullable', Rule::enum(LearnerLanguage::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
