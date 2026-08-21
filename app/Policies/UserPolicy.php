<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;

final readonly class UserPolicy
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer) {}

    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'users.view');
    }

    public function view(User $user, User $subject): bool
    {
        return $this->belongsToTenant($subject) && $this->authorizer->allows($user, 'users.view');
    }

    public function update(User $user, User $subject): bool
    {
        return $this->belongsToTenant($subject) && $this->authorizer->allows($user, 'users.update');
    }

    public function delete(User $user, User $subject): bool
    {
        return ! $user->is($subject) && $this->belongsToTenant($subject) && $this->authorizer->allows($user, 'users.delete');
    }

    private function belongsToTenant(User $subject): bool
    {
        return $this->tenant->resolved() && $subject->memberships()
            ->where('organization_id', $this->tenant->id())->exists();
    }
}
