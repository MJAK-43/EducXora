<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;

final readonly class AuditLogPolicy
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer) {}

    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'audit.view');
    }

    public function view(User $user, AuditLog $log): bool
    {
        return (int) $log->organization_id === $this->tenant->id()
            && $this->authorizer->allows($user, 'audit.view');
    }
}
