<?php

declare(strict_types=1);

namespace App\Http\Requests\Pedagogy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RecordPlacementAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('placement_tests.complete');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'organization_id' => ['prohibited'],
            'center_id' => ['prohibited'],
            'question_uuid' => ['required', 'uuid'],
            'answer' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
        ];
    }
}
