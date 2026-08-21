<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Queries;

use App\Domain\Scheduling\Models\CourseSession;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final readonly class CourseSessionIndexQuery
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer) {}

    /** @param array<string, mixed> $filters
     * @return Builder<CourseSession>
     */
    public function build(CarbonImmutable $weekStart, array $filters, User $user): Builder
    {
        $timezone = $this->tenant->organization()->timezone;
        $query = CourseSession::query()->with(['group', 'teacherMembership.user'])
            ->where('starts_at', '>=', $weekStart->setTimezone($timezone)->startOfDay()->utc())
            ->where('starts_at', '<', $weekStart->setTimezone($timezone)->addWeek()->startOfDay()->utc())
            ->orderBy('starts_at');

        if (! $this->authorizer->allows($user, 'schedule.create')) {
            $query->where('teacher_membership_id', $this->tenant->membership()?->getKey() ?? 0);
        }
        if (! empty($filters['group'])) {
            $query->whereHas('group', fn (Builder $group) => $group->where('uuid', $filters['group']));
        }
        if (! empty($filters['teacher'])) {
            $query->whereHas('teacherMembership', fn (Builder $teacher) => $teacher->where('uuid', $filters['teacher']));
        }

        return $query;
    }
}
