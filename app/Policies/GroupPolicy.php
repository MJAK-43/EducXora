<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Learning\Models\Group;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;

final readonly class GroupPolicy
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer) {}

    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'group.view');
    }

    public function view(User $user, Group $group): bool
    {
        return $this->sameTenant($group) && $this->viewAny($user)
            && ($this->authorizer->allows($user, 'group.update') || (int) $group->teacher_membership_id === (int) $this->tenant->membership()?->getKey());
    }

    public function create(User $user): bool
    {
        return $this->authorizer->allows($user, 'group.create');
    }

    public function update(User $user, Group $group): bool
    {
        return $this->sameTenant($group) && $this->authorizer->allows($user, 'group.update');
    }

    public function archive(User $user, Group $group): bool
    {
        return $this->sameTenant($group) && $this->authorizer->allows($user, 'group.archive');
    }

    public function restore(User $user, Group $group): bool
    {
        return $this->sameTenant($group) && $this->authorizer->allows($user, 'group.restore');
    }

    public function manageLearners(User $user, Group $group): bool
    {
        return $this->sameTenant($group) && $this->authorizer->allows($user, 'group.learners.manage');
    }

    private function sameTenant(Group $group): bool
    {
        return $this->tenant->resolved() && (int) $group->organization_id === $this->tenant->id();
    }
}
