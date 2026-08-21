<?php

namespace App\Providers;

use App\Domain\Learner\Models\Learner;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Policies\AuditLogPolicy;
use App\Policies\LearnerPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserInvitationPolicy;
use App\Policies\UserPolicy;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, fn (): TenantContext => new TenantContext);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(UserInvitation::class, UserInvitationPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Learner::class, LearnerPolicy::class);

        Gate::before(function (User $user, string $ability): ?bool {
            return $user->isSuperAdmin() ? true : null;
        });

        foreach (config('authorization.permissions', []) as $permission) {
            Gate::define($permission, fn (User $user): bool => app(MembershipAuthorizer::class)->allows($user, $permission));
        }
    }
}
