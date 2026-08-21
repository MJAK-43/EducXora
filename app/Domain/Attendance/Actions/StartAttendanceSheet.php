<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Enums\AttendanceSheetStatus;
use App\Domain\Attendance\Models\AttendanceSheet;
use App\Domain\Learner\Enums\LearnerStatus;
use App\Domain\Learning\Models\GroupLearnerAssignment;
use App\Domain\Scheduling\Models\CourseSession;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class StartAttendanceSheet
{
    public function __construct(private AuditLogger $audit, private TenantContext $tenant) {}

    public function execute(CourseSession $session, User $actor): AttendanceSheet
    {
        return DB::transaction(function () use ($session, $actor): AttendanceSheet {
            $lockedSession = CourseSession::query()->lockForUpdate()->findOrFail($session->getKey());
            if ($lockedSession->isCancelled()) {
                throw ValidationException::withMessages(['session' => 'Une séance annulée ne peut pas être pointée.']);
            }

            $existing = AttendanceSheet::query()->where('course_session_id', $lockedSession->getKey())->first();
            if ($existing) {
                return $existing->load(['learnerAttendances.learner', 'teacherAttendance']);
            }

            $sheet = AttendanceSheet::query()->create([
                'course_session_id' => $lockedSession->getKey(),
                'group_id' => $lockedSession->group_id,
                'teacher_membership_id' => $lockedSession->teacher_membership_id,
                'status' => AttendanceSheetStatus::Draft,
                'created_by' => $actor->getKey(),
            ]);

            $assignments = GroupLearnerAssignment::query()->with('learner')
                ->where('group_id', $lockedSession->group_id)
                ->where('assigned_at', '<=', $lockedSession->starts_at)
                ->where(fn (Builder $query) => $query->whereNull('detached_at')->orWhere('detached_at', '>', $lockedSession->starts_at))
                ->whereHas('learner', fn (Builder $query) => $query
                    ->where('status', LearnerStatus::Active)
                    ->orWhere('archived_at', '>', $lockedSession->starts_at))
                ->get();

            $sheet->learnerAttendances()->createMany($assignments->map(fn (GroupLearnerAssignment $assignment): array => [
                'learner_id' => $assignment->learner_id,
            ])->all());

            $this->audit->record('attendance.created', $actor, $this->tenant->organization(), $sheet, [
                'session_uuid' => $lockedSession->uuid,
                'learner_count' => $assignments->count(),
            ]);

            return $sheet->load(['learnerAttendances.learner', 'teacherAttendance']);
        });
    }
}
