<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Enums\AttendanceSheetStatus;
use App\Domain\Attendance\Models\AttendanceSheet;
use App\Domain\Scheduling\Models\CourseSession;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ValidateAttendanceSheet
{
    public function __construct(private AuditLogger $audit, private TenantContext $tenant, private Clock $clock) {}

    public function execute(AttendanceSheet $sheet, User $actor): AttendanceSheet
    {
        return DB::transaction(function () use ($sheet, $actor): AttendanceSheet {
            $lockedSheet = AttendanceSheet::query()->lockForUpdate()->findOrFail($sheet->getKey());
            if ($lockedSheet->isValidated()) {
                throw ValidationException::withMessages(['attendance' => 'Ce pointage est déjà validé.']);
            }

            $session = CourseSession::query()->lockForUpdate()->findOrFail($lockedSheet->course_session_id);
            if ($session->isCancelled()) {
                throw ValidationException::withMessages(['attendance' => 'Une séance annulée ne peut pas être validée.']);
            }
            if ($lockedSheet->learnerAttendances()->whereNull('status')->exists()) {
                throw ValidationException::withMessages(['attendances' => 'Tous les apprenants doivent être pointés avant validation.']);
            }
            if (! $lockedSheet->teacherAttendance()->exists()) {
                throw ValidationException::withMessages(['teacher_status' => 'La présence de l’enseignant doit être enregistrée avant validation.']);
            }

            $lockedSheet->update([
                'status' => AttendanceSheetStatus::Validated,
                'validated_at' => $this->clock->now(),
                'validated_by' => $actor->getKey(),
            ]);
            $this->audit->record('attendance.validated', $actor, $this->tenant->organization(), $lockedSheet, [
                'session_uuid' => $session->uuid,
                'learner_count' => $lockedSheet->learnerAttendances()->count(),
            ]);

            return $lockedSheet->refresh()->load(['learnerAttendances.learner', 'teacherAttendance']);
        });
    }
}
