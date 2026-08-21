<?php

declare(strict_types=1);

namespace App\Http\Requests\Groups;

final class UpdateGroupRequest extends AbstractGroupRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('group.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->groupRules();
    }
}
