<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

final readonly class OrganizationRoleController
{
    public function __construct(private TenantContext $tenant, private AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('roles.view'), 403);

        return Inertia::render('Organization/Roles/Index', [
            'roles' => Role::query()->where('organization_id', $this->tenant->id())
                ->with('permissions:id,name')->withCount('memberships')->orderByDesc('is_system')->orderBy('name')->get(),
            'permissions' => Permission::query()->orderBy('name')->get(['id', 'name']),
            'can' => [
                'create' => $request->user()->can('roles.create'),
                'update' => $request->user()->can('roles.update'),
                'delete' => $request->user()->can('roles.delete'),
                'assign' => $request->user()->can('permissions.assign'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('roles.create') && $request->user()->can('permissions.assign'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles')->where('organization_id', $this->tenant->id())],
            'permissions' => ['required', 'array'], 'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);
        $role = DB::transaction(function () use ($data, $request): Role {
            $role = Role::query()->create(['organization_id' => $this->tenant->id(), 'name' => $data['name'], 'guard_name' => 'web', 'is_system' => false]);
            $role->syncPermissions($data['permissions']);
            $this->audit->record('role.created', $request->user(), $this->tenant->organization(), $role, ['permissions' => $data['permissions']]);

            return $role;
        });

        return back()->with('status', "Rôle {$role->name} créé.");
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_unless($request->user()->can('roles.update') && $request->user()->can('permissions.assign'), 403);
        abort_unless((int) $role->organization_id === $this->tenant->id() && ! $role->is_system, 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles')->where('organization_id', $this->tenant->id())->ignore($role)],
            'permissions' => ['required', 'array'], 'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);
        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions']);
        $this->audit->record('role.updated', $request->user(), $this->tenant->organization(), $role, ['permissions' => $data['permissions']]);

        return back()->with('status', 'Rôle mis à jour.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        abort_unless($request->user()->can('roles.delete'), 403);
        abort_unless((int) $role->organization_id === $this->tenant->id() && ! $role->is_system, 403);
        abort_if($role->memberships()->exists(), 422, 'Ce rôle est encore attribué.');
        $this->audit->record('role.deleted', $request->user(), $this->tenant->organization(), $role, ['name' => $role->name]);
        $role->delete();

        return back()->with('status', 'Rôle supprimé.');
    }
}
