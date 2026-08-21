<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer) {}

    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'organization' => $this->tenant->organization()->only(['uuid', 'name', 'status']),
            'permissions' => $this->authorizer->permissions($request->user()),
        ]);
    }
}
