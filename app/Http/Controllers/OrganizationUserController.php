<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MembershipStatus;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Services\Audit\AuditLogger;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class OrganizationUserController
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer, private AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('users.view'), 403);
        $memberships = OrganizationMembership::query()->with(['user', 'roles'])
            ->where('organization_id', $this->tenant->id())->latest()->paginate(20)
            ->through(fn (OrganizationMembership $item): array => [
                'id' => $item->id, 'uuid' => $item->user->uuid, 'name' => $item->user->name,
                'email' => $item->user->email, 'status' => $item->statusValue(),
                'roles' => $item->roles->pluck('name'),
                'role_ids' => $item->roles->pluck('id'),
            ]);

        return Inertia::render('Organization/Users/Index', [
            'memberships' => $memberships,
            'roles' => Role::query()->where('organization_id', $this->tenant->id())->orderBy('name')->get(['id', 'name']),
            'can' => ['invite' => $request->user()->can('users.invite'), 'update' => $request->user()->can('users.update')],
        ]);
    }

    public function update(Request $request, OrganizationMembership $membership): RedirectResponse
    {
        abort_unless($request->user()->can('users.update') && (int) $membership->organization_id === $this->tenant->id(), 403);
        $data = $request->validate([
            'status' => ['required', 'in:active,inactive,pending'],
            'roles' => ['required', 'array', 'min:1'], 'roles.*' => ['integer'],
        ]);
        if ($membership->user_id === $request->user()->getKey() && $data['status'] !== MembershipStatus::Active->value) {
            return back()->withErrors(['status' => 'Vous ne pouvez pas désactiver votre propre adhésion.']);
        }
        $membership->update(['status' => $data['status']]);
        $this->authorizer->syncRoles($membership, $data['roles']);
        $this->audit->record('membership.updated', $request->user(), $this->tenant->organization(), $membership, ['status' => $data['status'], 'role_ids' => $data['roles']]);

        return back()->with('status', 'Utilisateur mis à jour.');
    }

    public function destroy(Request $request, OrganizationMembership $membership): RedirectResponse
    {
        abort_unless($request->user()->can('users.delete') && (int) $membership->organization_id === $this->tenant->id(), 403);
        abort_if($membership->user_id === $request->user()->getKey(), 422, 'Vous ne pouvez pas vous désactiver.');
        $membership->update(['status' => MembershipStatus::Inactive]);
        $this->audit->record('membership.deactivated', $request->user(), $this->tenant->organization(), $membership);

        return back()->with('status', 'Utilisateur désactivé.');
    }
}
