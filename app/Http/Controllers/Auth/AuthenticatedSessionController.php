<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AuthenticatedSessionController
{
    public function __construct(private AuditLogger $audit) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Login', ['canResetPassword' => true, 'status' => session('status')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string'], 'remember' => ['boolean']]);
        $key = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($key).' secondes.']);
        }

        if (! Auth::attempt(['email' => strtolower($credentials['email']), 'password' => $credentials['password']], (bool) ($credentials['remember'] ?? false))) {
            RateLimiter::hit($key, 60);
            $this->audit->record('auth.login_failed', metadata: ['identity_hash' => hash('sha256', strtolower($credentials['email']))], request: $request);
            throw ValidationException::withMessages(['email' => 'Les identifiants fournis sont incorrects.']);
        }

        /** @var User $user */
        $user = Auth::user();
        if (! $user->isActive()) {
            Auth::logout();
            RateLimiter::hit($key, 60);
            $this->audit->record('auth.login_blocked', $user, metadata: ['reason' => 'inactive_user'], request: $request);
            throw ValidationException::withMessages(['email' => 'Les identifiants fournis sont incorrects.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('authenticated_at', now()->timestamp);
        $user->forceFill(['last_login_at' => now()])->save();
        $this->audit->record('auth.login', $user, request: $request);

        return redirect()->intended($user->isSuperAdmin() ? route('platform.organizations.index') : route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->audit->record('auth.logout', $user, request: $request);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
