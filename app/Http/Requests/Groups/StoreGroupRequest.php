<?php

declare(strict_types=1);

namespace App\Http\Requests\Groups;

final class StoreGroupRequest extends AbstractGroupRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('group.create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->groupRules();
    }
}
