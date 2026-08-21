<?php

declare(strict_types=1);

namespace App\Http\Requests\Groups;

use Illuminate\Foundation\Http\FormRequest;

final class AttachLearnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('group.learners.manage');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['organization_id' => ['prohibited'], 'center_id' => ['prohibited'], 'learner_uuid' => ['required', 'uuid']];
    }
}
