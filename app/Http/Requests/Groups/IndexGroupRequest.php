<?php

declare(strict_types=1);

namespace App\Http\Requests\Groups;

use App\Domain\Learner\Enums\LearnerLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('group.view');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'archived', 'all'])],
            'level' => ['nullable', Rule::enum(LearnerLevel::class)],
            'teacher' => ['nullable', 'uuid'],
        ];
    }
}
