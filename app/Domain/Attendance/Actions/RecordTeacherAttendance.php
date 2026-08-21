<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AttendanceSheet;
use App\Domain\Attendance\Models\TeacherAttendance;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RecordTeacherAttendance
{
    public function __construct(private AuditLogger $audit, private TenantContext $tenant, private Clock $clock) {}

    public function execute(AttendanceSheet $sheet, AttendanceStatus $status, User $actor): TeacherAttendance
    {
        return DB::transaction(function () use ($sheet, $status, $actor): TeacherAttendance {
            $lockedSheet = AttendanceSheet::query()->lockForUpdate()->findOrFail($sheet->getKey());
            if ($lockedSheet->isValidated()) {
                throw ValidationException::withMessages(['teacher_status' => 'La présence enseignant est verrouillée après validation.']);
            }

            $attendance = TeacherAttendance::query()->updateOrCreate(
                ['attendance_sheet_id' => $lockedSheet->getKey()],
                [
                    'teacher_membership_id' => $lockedSheet->teacher_membership_id,
                    'status' => $status,
                    'recorded_by' => $actor->getKey(),
                    'recorded_at' => $this->clock->now(),
                ],
            );

            $this->audit->record('teacher_attendance.recorded', $actor, $this->tenant->organization(), $lockedSheet, [
                'teacher_membership_uuid' => $lockedSheet->teacherMembership()->value('uuid'),
                'status' => $status->value,
            ]);

            return $attendance;
        });
    }
}
