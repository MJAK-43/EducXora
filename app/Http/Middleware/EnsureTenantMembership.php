<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureTenantMembership
{
    public function __construct(private TenantContext $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $membership = $this->tenant->membership();
        abort_unless(
            $membership
            && (int) $membership->user_id === (int) $request->user()?->getKey()
            && $membership->isActive(),
            403,
            'Vous ne faites pas partie de cette organisation.',
        );

        return $next($request);
    }
}
