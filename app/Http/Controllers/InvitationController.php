<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\Invitations\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InvitationController
{
    public function __construct(private InvitationService $invitations) {}

    public function store(Request $request): RedirectResponse
    {
        $request->user()->can('users.invite') || abort(403);
        $data = $request->validate(['email' => ['required', 'email'], 'role_id' => ['required', 'integer']]);
        $role = Role::query()->findOrFail($data['role_id']);
        $this->invitations->invite($data['email'], $role, $request->user());

        return back()->with('status', 'Invitation envoyée.');
    }

    public function show(string $token): Response
    {
        $invitation = $this->invitations->findUsable($token);

        return Inertia::render('Auth/AcceptInvitation', [
            'token' => $token, 'email' => $invitation->email,
            'organization' => $invitation->organization->name,
            'existingUser' => (bool) User::query()->where('email', $invitation->email)->exists(),
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->invitations->findUsable($token);
        $existing = User::query()->where('email', $invitation->email)->first();
        $rules = $existing
            ? []
            : [
                'first_name' => ['required', 'string', 'max:80'], 'last_name' => ['required', 'string', 'max:80'],
                'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            ];
        if ($existing && (! $request->user() || ! $request->user()->is($existing))) {
            return redirect()->route('login')->with('status', 'Connectez-vous avec l’adresse invitée pour continuer.');
        }
        $user = $this->invitations->accept($token, $request->validate($rules), $request->user());
        if (! Auth::check()) {
            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->put('authenticated_at', now()->timestamp);
        }
        $request->session()->put('active_organization_uuid', $invitation->organization->uuid);

        return redirect()->route('dashboard')->with('status', 'Invitation acceptée.');
    }
}
