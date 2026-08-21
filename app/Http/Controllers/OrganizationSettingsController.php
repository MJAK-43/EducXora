<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class OrganizationSettingsController
{
    public function __construct(private TenantContext $tenant, private AuditLogger $audit) {}

    public function edit(Request $request): Response
    {
        abort_unless($request->user()->can('organization.view'), 403);

        return Inertia::render('Organization/Settings', [
            'organization' => $this->tenant->organization()->only(['uuid', 'name', 'slug', 'email', 'phone', 'country_code', 'timezone', 'currency', 'locale']),
            'canUpdate' => $request->user()->can('organization.update'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('organization.update'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'], 'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:30'], 'country_code' => ['required', 'string', 'size:2'],
            'timezone' => ['required', 'timezone'], 'currency' => ['required', 'string', 'size:3'],
            'locale' => ['required', 'in:fr,en'],
        ]);
        $organization = $this->tenant->organization();
        $organization->update($data);
        $this->audit->record('organization.updated', $request->user(), $organization, $organization, ['fields' => array_keys($data)]);

        return back()->with('status', 'Organisation mise à jour.');
    }
}
