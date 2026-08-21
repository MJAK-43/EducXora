<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Actions;

use App\Domain\Learning\Enums\GroupStatus;
use App\Domain\Learning\Models\Group;
use App\Domain\Learning\Queries\TeacherMembershipQuery;
use App\Domain\Scheduling\Enums\CourseSessionStatus;
use App\Domain\Scheduling\Models\CourseSession;
use App\Domain\Scheduling\Services\ScheduleConflictGuard;
use App\Domain\Scheduling\Support\CourseSessionPeriod;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CreateCourseSession
{
    public function __construct(
        private TeacherMembershipQuery $teachers,
        private CourseSessionPeriod $period,
        private ScheduleConflictGuard $conflicts,
        private AuditLogger $audit,
        private TenantContext $tenant,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes, User $actor): CourseSession
    {
        $group = Group::query()->where('uuid', $attributes['group_uuid'])->firstOrFail();
        if ($group->status !== GroupStatus::Active) {
            throw ValidationException::withMessages(['group_uuid' => 'Le groupe doit être actif.']);
        }
        $teacher = $this->teachers->find((string) $attributes['teacher_membership_uuid']);
        [$startsAt, $endsAt] = $this->period->fromInput($attributes);
        $room = filled($attributes['room'] ?? null) ? trim((string) $attributes['room']) : null;
        $this->conflicts->ensureAvailable((int) $group->getKey(), (int) $teacher->getKey(), $room, $startsAt, $endsAt);

        try {
            return DB::transaction(function () use ($group, $teacher, $room, $startsAt, $endsAt, $actor): CourseSession {
                $session = CourseSession::query()->create([
                    'group_id' => $group->getKey(), 'teacher_membership_id' => $teacher->getKey(), 'room' => $room,
                    'starts_at' => $startsAt, 'ends_at' => $endsAt,
                    'status' => CourseSessionStatus::Scheduled, 'created_by' => $actor->getKey(),
                ]);
                $this->audit->record('lesson.created', $actor, $this->tenant->organization(), $session, [
                    'group_uuid' => $group->uuid, 'starts_at' => $startsAt->toIso8601String(),
                ]);

                return $session->refresh();
            });
        } catch (QueryException $exception) {
            $this->conflicts->rethrowDatabaseConflict($exception);
        }
    }
}
