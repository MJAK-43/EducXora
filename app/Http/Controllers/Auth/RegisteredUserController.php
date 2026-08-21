<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Services\Organizations\OrganizationCreator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final readonly class RegisteredUserController
{
    public function __construct(private OrganizationCreator $creator) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'organization_name' => ['required', 'string', 'max:160'],
            'first_name' => ['required', 'string', 'max:80'], 'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        [$user, $organization] = $this->creator->createForNewOwner($data);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put(['authenticated_at' => now()->timestamp, 'active_organization_uuid' => $organization->uuid]);

        return redirect()->route('verification.notice');
    }
}
