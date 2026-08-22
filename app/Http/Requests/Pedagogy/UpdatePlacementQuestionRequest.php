<?php

declare(strict_types=1);

namespace App\Http\Requests\Pedagogy;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePlacementQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('placement_questions.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return StorePlacementQuestionRequest::questionRules();
    }
}
