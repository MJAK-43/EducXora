<?php

use App\Http\Middleware\EnsureLocalEnvironment;
use App\Http\Middleware\EnsureOrganizationIsActive;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureSessionIsFresh;
use App\Http\Middleware\EnsureTenantMembership;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');
        $middleware->append(SecurityHeaders::class);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'local.only' => EnsureLocalEnvironment::class,
            'active' => EnsureUserIsActive::class,
            'fresh.session' => EnsureSessionIsFresh::class,
            'tenant' => ResolveTenant::class,
            'tenant.member' => EnsureTenantMembership::class,
            'organization.active' => EnsureOrganizationIsActive::class,
            'platform.admin' => EnsurePlatformAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
