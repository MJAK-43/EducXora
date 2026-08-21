<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Queries;

use App\Domain\Scheduling\Models\CourseSession;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final readonly class AttendanceSessionIndexQuery
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer) {}

    /** @param array<string, mixed> $filters
     * @return Builder<CourseSession>
     */
    public function build(CarbonImmutable $from, CarbonImmutable $to, array $filters, User $user): Builder
    {
        $query = CourseSession::query()->with(['group', 'teacherMembership.user', 'attendanceSheet'])
            ->where('starts_at', '>=', $from->utc())
            ->where('starts_at', '<', $to->utc())
            ->orderByDesc('starts_at');

        if (! $this->authorizer->allows($user, 'attendance.view_reports')) {
            $query->where('teacher_membership_id', $this->tenant->membership()?->getKey() ?? 0);
        }
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'non_pointed') {
                $query->doesntHave('attendanceSheet');
            } else {
                $query->whereHas(
                    'attendanceSheet',
                    fn (Builder $sheet) => $sheet->where('status', $filters['status']),
                );
            }
        }

        return $query;
    }
}
