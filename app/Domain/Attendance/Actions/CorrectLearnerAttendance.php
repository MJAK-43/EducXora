<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AttendanceCorrection;
use App\Domain\Attendance\Models\AttendanceSheet;
use App\Domain\Attendance\Models\LearnerAttendance;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CorrectLearnerAttendance
{
    public function __construct(private AuditLogger $audit, private TenantContext $tenant, private Clock $clock) {}

    public function execute(AttendanceSheet $sheet, string $learnerAttendanceUuid, AttendanceStatus $status, string $reason, User $actor): LearnerAttendance
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Le motif de correction est obligatoire.']);
        }

        return DB::transaction(function () use ($sheet, $learnerAttendanceUuid, $status, $reason, $actor): LearnerAttendance {
            $lockedSheet = AttendanceSheet::query()->lockForUpdate()->findOrFail($sheet->getKey());
            if (! $lockedSheet->isValidated()) {
                throw ValidationException::withMessages(['attendance' => 'Seul un pointage validé peut être corrigé.']);
            }

            $record = LearnerAttendance::query()->where('attendance_sheet_id', $lockedSheet->getKey())
                ->where('uuid', $learnerAttendanceUuid)->with('learner')
                ->lockForUpdate()->firstOrFail();
            $before = $record->status;
            if ($before === null || $before === $status) {
                throw ValidationException::withMessages(['status' => 'Le nouveau statut doit être différent.']);
            }

            AttendanceCorrection::query()->create([
                'attendance_sheet_id' => $lockedSheet->getKey(),
                'learner_attendance_id' => $record->getKey(),
                'before_status' => $before,
                'after_status' => $status,
                'reason' => trim($reason),
                'corrected_by' => $actor->getKey(),
            ]);
            $record->update(['status' => $status, 'recorded_by' => $actor->getKey(), 'recorded_at' => $this->clock->now()]);
            $this->audit->record('attendance.corrected', $actor, $this->tenant->organization(), $lockedSheet, [
                'learner_uuid' => $record->learner->uuid,
                'before' => $before->value,
                'after' => $status->value,
                'reason' => trim($reason),
            ]);

            return $record->refresh();
        });
    }
}
