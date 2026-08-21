<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AttendanceCorrection;
use App\Domain\Attendance\Models\AttendanceSheet;
use App\Domain\Attendance\Models\TeacherAttendance;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CorrectTeacherAttendance
{
    public function __construct(private AuditLogger $audit, private TenantContext $tenant, private Clock $clock) {}

    public function execute(AttendanceSheet $sheet, AttendanceStatus $status, string $reason, User $actor): TeacherAttendance
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Le motif de correction est obligatoire.']);
        }

        return DB::transaction(function () use ($sheet, $status, $reason, $actor): TeacherAttendance {
            $lockedSheet = AttendanceSheet::query()->lockForUpdate()->findOrFail($sheet->getKey());
            if (! $lockedSheet->isValidated()) {
                throw ValidationException::withMessages(['attendance' => 'Seul un pointage validé peut être corrigé.']);
            }

            $attendance = TeacherAttendance::query()->where('attendance_sheet_id', $lockedSheet->getKey())
                ->lockForUpdate()->firstOrFail();
            $before = $attendance->status;
            if ($before === $status) {
                throw ValidationException::withMessages(['status' => 'Le nouveau statut doit être différent.']);
            }

            AttendanceCorrection::query()->create([
                'attendance_sheet_id' => $lockedSheet->getKey(),
                'teacher_attendance_id' => $attendance->getKey(),
                'before_status' => $before,
                'after_status' => $status,
                'reason' => trim($reason),
                'corrected_by' => $actor->getKey(),
            ]);
            $attendance->update(['status' => $status, 'recorded_by' => $actor->getKey(), 'recorded_at' => $this->clock->now()]);
            $this->audit->record('teacher_attendance.corrected', $actor, $this->tenant->organization(), $lockedSheet, [
                'teacher_membership_uuid' => $lockedSheet->teacherMembership()->value('uuid'),
                'before' => $before->value,
                'after' => $status->value,
                'reason' => trim($reason),
            ]);

            return $attendance->refresh();
        });
    }
}
