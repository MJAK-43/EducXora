<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\MembershipStatus;
use App\Models\OrganizationMembership;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
                'environment' => app()->environment(),
            ],
            'auth' => fn (): array => [
                'user' => $request->user()?->only(['uuid', 'name', 'first_name', 'last_name', 'email']),
                'isSuperAdmin' => (bool) $request->user()?->isSuperAdmin(),
                'canViewLearners' => (bool) $request->user()?->can('learners.view'),
                'canViewGroups' => (bool) $request->user()?->can('group.view'),
                'canViewSchedule' => (bool) $request->user()?->can('schedule.view'),
                'canViewAttendance' => (bool) $request->user()?->can('attendance.view'),
                'canViewPedagogy' => (bool) $request->user()?->can('placement_questions.view'),
                'organizations' => $request->user()
                    ? OrganizationMembership::query()->with('organization')
                        ->where('user_id', $request->user()->getKey())
                        ->where('status', MembershipStatus::Active)->get()
                        ->map(fn (OrganizationMembership $membership): array => [
                            'uuid' => $membership->organization->uuid,
                            'name' => $membership->organization->name,
                        ])->values()
                    : [],
                'activeOrganizationUuid' => $request->session()->get('active_organization_uuid'),
            ],
            'flash' => fn (): array => ['status' => $request->session()->get('status')],
        ];
    }
}
