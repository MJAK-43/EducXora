<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureOrganizationIsActive
{
    public function __construct(private TenantContext $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $this->tenant->organization()->isActive(),
            403,
            'Cette organisation est suspendue.',
        );

        return $next($request);
    }
}
