<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserInvitation;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;

final readonly class UserInvitationPolicy
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer) {}

    public function create(User $user): bool
    {
        return $this->authorizer->allows($user, 'users.invite');
    }

    public function delete(User $user, UserInvitation $invitation): bool
    {
        return (int) $invitation->organization_id === $this->tenant->id()
            && $this->authorizer->allows($user, 'users.invite');
    }
}
