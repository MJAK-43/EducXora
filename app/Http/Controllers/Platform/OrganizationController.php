<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Services\Audit\AuditLogger;
use App\Services\Authorization\RoleProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class OrganizationController
{
    public function __construct(private RoleProvisioner $roles, private AuditLogger $audit) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Organizations/Index', [
            'organizations' => Organization::query()->withCount('memberships')->latest()->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'], 'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
        $organization = Organization::query()->create([...$data, 'status' => OrganizationStatus::Active]);
        $this->roles->provisionOrganization($organization);
        $this->audit->record('organization.created_by_platform', $request->user(), $organization, $organization);

        return back()->with('status', 'Organisation créée.');
    }

    public function status(Request $request, Organization $organization): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:active,suspended']]);
        $organization->update(['status' => $data['status']]);
        $this->audit->record('organization.status_changed', $request->user(), $organization, $organization, ['status' => $data['status']]);

        return back()->with('status', 'Statut mis à jour.');
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
        $organization->update($data);
        $this->audit->record('organization.updated_by_platform', $request->user(), $organization, $organization, ['fields' => array_keys($data)]);

        return back()->with('status', 'Organisation mise à jour.');
    }
}
