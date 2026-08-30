<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Queries;

use App\Domain\Attendance\Enums\AttendanceSheetStatus;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\LearnerAttendance;
use App\Domain\Learner\Enums\LearnerStatus;
use App\Domain\Learner\Models\Learner;
use App\Domain\Learning\Enums\GroupStatus;
use App\Domain\Learning\Models\Group;
use App\Domain\Scheduling\Models\CourseSession;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final readonly class DashboardOverviewQuery
{
    public function __construct(
        private TenantContext $tenant,
        private MembershipAuthorizer $authorizer,
        private Clock $clock,
    ) {}

    /** @return array<string, mixed> */
    public function execute(User $user): array
    {
        $timezone = $this->tenant->organization()->timezone ?: 'Africa/Douala';
        $today = $this->clock->now($timezone);
        $from = $today->startOfDay()->utc();
        $until = $today->addDay()->startOfDay()->utc();
        $membershipId = $this->tenant->membership()?->getKey() ?? 0;
        $canViewLearners = $this->authorizer->allows($user, 'learners.view');
        $canViewGroups = $this->authorizer->allows($user, 'group.view');
        $canViewSchedule = $this->authorizer->allows($user, 'schedule.view');
        $canViewAttendance = $this->authorizer->allows($user, 'attendance.view');
        $canViewAudit = $this->authorizer->allows($user, 'audit.view');
        $canManageSchedule = $this->authorizer->allows($user, 'schedule.create');

        /** @var Collection<int, CourseSession> $sessions */
        $sessions = $canViewSchedule ? CourseSession::query()
            ->with(['group', 'teacherMembership.user', 'attendanceSheet'])
            ->where('starts_at', '>=', $from)
            ->where('starts_at', '<', $until)
            ->when(! $canManageSchedule, fn (Builder $query) => $query->where('teacher_membership_id', $membershipId))
            ->orderBy('starts_at')
            ->get() : new Collection;

        $activeGroupCount = null;
        /** @var Collection<int, Group> $groups */
        $groups = new Collection;
        if ($canViewGroups) {
            $groupsQuery = Group::query()
                ->with('teacherMembership.user')
                ->withCount('activeAssignments')
                ->where('status', GroupStatus::Active)
                ->when(! $canManageSchedule, fn (Builder $query) => $query->where('teacher_membership_id', $membershipId));
            $activeGroupCount = (clone $groupsQuery)->count();
            $groups = $groupsQuery->orderByDesc('active_assignments_count')->limit(5)->get();
        }

        $validatedSheetIds = $sessions->filter(
            fn (CourseSession $session) => $canViewAttendance
                && $session->attendanceSheet?->status === AttendanceSheetStatus::Validated,
        )->map(fn (CourseSession $session): int => (int) $session->attendanceSheet?->getKey());
        $attendanceQuery = LearnerAttendance::query()->whereIn('attendance_sheet_id', $validatedSheetIds);
        $attendanceTotal = (clone $attendanceQuery)->whereNotNull('status')->count();
        $presentCount = (clone $attendanceQuery)->where('status', AttendanceStatus::Present)->count();

        return [
            'dateLabel' => ucfirst($today->locale('fr')->isoFormat('dddd D MMMM YYYY')),
            'metrics' => [
                'activeLearners' => $canViewLearners ? Learner::query()->where('status', LearnerStatus::Active)->count() : null,
                'activeGroups' => $activeGroupCount,
                'sessionsToday' => $sessions->count(),
                'attendanceRate' => $attendanceTotal > 0 ? (int) round(($presentCount / $attendanceTotal) * 100) : null,
            ],
            'attendance' => [
                'present' => $presentCount,
                'absent' => (clone $attendanceQuery)->where('status', AttendanceStatus::Absent)->count(),
                'excused' => (clone $attendanceQuery)->where('status', AttendanceStatus::Excused)->count(),
                'pendingSessions' => $canViewAttendance ? $sessions->filter(fn (CourseSession $session) => $session->attendanceSheet === null || $session->attendanceSheet->status !== AttendanceSheetStatus::Validated)->count() : 0,
            ],
            'todaySessions' => $sessions->map(fn (CourseSession $session): array => [
                'uuid' => $session->uuid,
                'groupName' => $session->group->name,
                'teacherName' => $session->teacherMembership->user->name,
                'room' => $session->room,
                'startsAt' => $session->starts_at->setTimezone($timezone)->format('H:i'),
                'endsAt' => $session->ends_at->setTimezone($timezone)->format('H:i'),
                'status' => $session->status->value,
                'attendanceUuid' => $canViewAttendance ? $session->attendanceSheet?->uuid : null,
                'attendanceStatus' => $canViewAttendance ? ($session->attendanceSheet?->status->value ?? 'not_started') : 'restricted',
            ])->values(),
            'groups' => $groups->map(fn (Group $group): array => [
                'uuid' => $group->uuid,
                'name' => $group->name,
                'level' => $group->level->value,
                'teacherName' => $group->teacherMembership->user->name,
                'learnerCount' => (int) $group->active_assignments_count,
                'capacity' => $group->capacity,
            ])->values(),
            'recentLearners' => $canViewLearners
                ? Learner::query()->latest('id')->limit(5)->get()->map(fn (Learner $learner): array => [
                    'uuid' => $learner->uuid,
                    'name' => $learner->fullName(),
                    'level' => $learner->current_level->value,
                    'status' => $learner->status->value,
                    'registeredOn' => $learner->registered_on->format('d/m/Y'),
                ])->values()
                : [],
            'recentActivity' => $canViewAudit
                ? AuditLog::query()->with('actor:id,name')->where('organization_id', $this->tenant->id())
                    ->latest('created_at')->limit(6)->get()->map(fn (AuditLog $log): array => [
                        'action' => $log->action,
                        'actor' => $log->actor_id ? $log->actor->name : 'Système',
                        'occurredAt' => CarbonImmutable::parse((string) $log->created_at)->setTimezone($timezone)->locale('fr')->diffForHumans(),
                    ])->values()
                : [],
            'can' => [
                'viewLearners' => $canViewLearners,
                'viewGroups' => $canViewGroups,
                'viewSchedule' => $canViewSchedule,
                'viewAttendance' => $canViewAttendance,
                'viewAudit' => $canViewAudit,
            ],
        ];
    }
}
