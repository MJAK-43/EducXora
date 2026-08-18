<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_is_available_through_inertia(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertInertia(fn (Assert $page) => $page->component('Home'));
    }

    public function test_healthcheck_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_ui_catalog_is_available_only_in_local_environment(): void
    {
        $this->get('/dev/ui')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dev/UiKit'));

        $this->app->detectEnvironment(fn (): string => 'production');

        $this->get('/dev/ui')
            ->assertNotFound()
            ->assertHeader('Content-Security-Policy');
    }

    public function test_missing_pages_use_the_safe_error_view(): void
    {
        $this->get('/route-that-does-not-exist')
            ->assertNotFound()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertSee('Page introuvable');
    }

    public function test_forbidden_page_uses_the_safe_error_view(): void
    {
        Route::get('/__test/forbidden', fn () => abort(403));

        $this->get('/__test/forbidden')
            ->assertForbidden()
            ->assertSee('Accès non autorisé')
            ->assertDontSee('Stack trace');
    }

    public function test_server_error_page_uses_the_safe_error_view(): void
    {
        Route::get('/__test/failure', fn () => abort(500));

        $this->get('/__test/failure')
            ->assertInternalServerError()
            ->assertSee('Service momentanément indisponible')
            ->assertDontSee('Stack trace');
    }

    public function test_postgresql_and_redis_are_reachable(): void
    {
        self::assertSame('pgsql', DB::getDriverName());
        self::assertNotEmpty(DB::select('select 1 as connected'));

        Redis::set('eduxora:foundation:test', 'ready');
        self::assertSame('ready', Redis::get('eduxora:foundation:test'));
        Redis::del('eduxora:foundation:test');
    }

    public function test_foundation_diagnostics_succeed(): void
    {
        $this->artisan('eduxora:foundation-check')->assertSuccessful();
    }
}
