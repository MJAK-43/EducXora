<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MembershipStatus;
use App\Models\OrganizationMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class OrganizationSelectionController
{
    public function index(Request $request): Response
    {
        $organizations = OrganizationMembership::query()->with('organization')
            ->where('user_id', $request->user()->getKey())
            ->where('status', MembershipStatus::Active)->get()
            ->map(fn (OrganizationMembership $membership): array => [
                'uuid' => $membership->organization->uuid,
                'name' => $membership->organization->name,
                'status' => $membership->organization->statusValue(),
            ]);

        return Inertia::render('Organizations/Select', ['organizations' => $organizations]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['organization_uuid' => ['required', 'uuid']]);
        $allowed = OrganizationMembership::query()->where('user_id', $request->user()->getKey())
            ->where('status', MembershipStatus::Active)
            ->whereHas('organization', fn ($query) => $query->where('uuid', $data['organization_uuid']))
            ->exists();
        abort_unless($allowed, 403);
        $request->session()->put('active_organization_uuid', $data['organization_uuid']);

        return redirect()->route('dashboard');
    }
}
