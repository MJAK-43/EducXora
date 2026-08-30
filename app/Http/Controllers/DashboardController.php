<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Dashboard\Queries\DashboardOverviewQuery;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function __construct(private TenantContext $tenant, private DashboardOverviewQuery $overview) {}

    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'organization' => $this->tenant->organization()->only(['uuid', 'name', 'status']),
            'overview' => $this->overview->execute($request->user()),
        ]);
    }
}
