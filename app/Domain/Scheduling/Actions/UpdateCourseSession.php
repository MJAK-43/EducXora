<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Actions;

use App\Domain\Learning\Enums\GroupStatus;
use App\Domain\Learning\Models\Group;
use App\Domain\Learning\Queries\TeacherMembershipQuery;
use App\Domain\Scheduling\Models\CourseSession;
use App\Domain\Scheduling\Services\ScheduleConflictGuard;
use App\Domain\Scheduling\Support\CourseSessionPeriod;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateCourseSession
{
    public function __construct(
        private TeacherMembershipQuery $teachers,
        private CourseSessionPeriod $period,
        private ScheduleConflictGuard $conflicts,
        private AuditLogger $audit,
        private TenantContext $tenant,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(CourseSession $session, array $attributes, User $actor): CourseSession
    {
        if ($session->isCancelled()) {
            throw ValidationException::withMessages(['session' => 'Une séance annulée ne peut pas être modifiée.']);
        }
        $group = Group::query()->where('uuid', $attributes['group_uuid'])->firstOrFail();
        if ($group->status !== GroupStatus::Active) {
            throw ValidationException::withMessages(['group_uuid' => 'Le groupe doit être actif.']);
        }
        $teacher = $this->teachers->find((string) $attributes['teacher_membership_uuid']);
        [$startsAt, $endsAt] = $this->period->fromInput($attributes);
        $room = filled($attributes['room'] ?? null) ? trim((string) $attributes['room']) : null;
        $this->conflicts->ensureAvailable((int) $group->getKey(), (int) $teacher->getKey(), $room, $startsAt, $endsAt, (int) $session->getKey());

        try {
            return DB::transaction(function () use ($session, $group, $teacher, $room, $startsAt, $endsAt, $actor): CourseSession {
                $before = $session->only(['group_id', 'teacher_membership_id', 'room', 'starts_at', 'ends_at']);
                $session->update([
                    'group_id' => $group->getKey(), 'teacher_membership_id' => $teacher->getKey(), 'room' => $room,
                    'starts_at' => $startsAt, 'ends_at' => $endsAt,
                ]);
                $this->audit->record('lesson.updated', $actor, $this->tenant->organization(), $session, [
                    'before' => $before, 'after' => $session->only(['group_id', 'teacher_membership_id', 'room', 'starts_at', 'ends_at']),
                ]);

                return $session->refresh();
            });
        } catch (QueryException $exception) {
            $this->conflicts->rethrowDatabaseConflict($exception);
        }
    }
}
