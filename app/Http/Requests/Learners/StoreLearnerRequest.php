<?php

declare(strict_types=1);

namespace App\Http\Requests\Learners;

final class StoreLearnerRequest extends AbstractLearnerRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('learners.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->learnerRules();
    }
}
