<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSessionIsFresh
{
    public function handle(Request $request, Closure $next): Response
    {
        $authenticatedAt = (int) $request->session()->get('authenticated_at', 0);

        if ($authenticatedAt === 0 || now()->timestamp - $authenticatedAt > 86400) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Votre session a expiré.');
        }

        return $next($request);
    }
}
