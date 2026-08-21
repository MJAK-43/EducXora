<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Scheduling\Models\CourseSession;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;

final readonly class CourseSessionPolicy
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer) {}

    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'schedule.view');
    }

    public function view(User $user, CourseSession $session): bool
    {
        return $this->sameTenant($session) && $this->viewAny($user)
            && ($this->authorizer->allows($user, 'schedule.create') || (int) $session->teacher_membership_id === (int) $this->tenant->membership()?->getKey());
    }

    public function create(User $user): bool
    {
        return $this->authorizer->allows($user, 'schedule.create');
    }

    public function update(User $user, CourseSession $session): bool
    {
        return $this->sameTenant($session) && $this->authorizer->allows($user, 'schedule.update');
    }

    public function cancel(User $user, CourseSession $session): bool
    {
        return $this->sameTenant($session) && $this->authorizer->allows($user, 'schedule.cancel');
    }

    private function sameTenant(CourseSession $session): bool
    {
        return $this->tenant->resolved() && (int) $session->organization_id === $this->tenant->id();
    }
}
