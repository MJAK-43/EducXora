<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\MembershipStatus;
use App\Models\OrganizationMembership;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResolveTenant
{
    public function __construct(private TenantContext $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $memberships = OrganizationMembership::query()
            ->with('organization')
            ->where('user_id', $user->getKey())
            ->where('status', MembershipStatus::Active)
            ->get();

        $uuid = $request->session()->get('active_organization_uuid');
        $membership = $memberships->first(fn (OrganizationMembership $item): bool => $item->organization->uuid === $uuid)
            ?? ($memberships->count() === 1 ? $memberships->first() : null);

        if (! $membership) {
            return redirect()->route('organizations.select');
        }

        $request->session()->put('active_organization_uuid', $membership->organization->uuid);
        $this->tenant->set($membership->organization, $membership);

        try {
            return $next($request);
        } finally {
            $this->tenant->clear();
        }
    }
}
