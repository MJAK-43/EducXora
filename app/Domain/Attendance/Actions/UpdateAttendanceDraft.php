<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AttendanceSheet;
use App\Domain\Attendance\Models\LearnerAttendance;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateAttendanceDraft
{
    public function __construct(private AuditLogger $audit, private TenantContext $tenant, private Clock $clock) {}

    /** @param array<int, array{learner_uuid: string, status: string}> $entries */
    public function execute(AttendanceSheet $sheet, array $entries, User $actor): AttendanceSheet
    {
        return DB::transaction(function () use ($sheet, $entries, $actor): AttendanceSheet {
            $lockedSheet = AttendanceSheet::query()->lockForUpdate()->findOrFail($sheet->getKey());
            if ($lockedSheet->isValidated()) {
                throw ValidationException::withMessages(['attendance' => 'Un pointage validé est verrouillé.']);
            }

            $records = LearnerAttendance::query()->with('learner')
                ->where('attendance_sheet_id', $lockedSheet->getKey())->lockForUpdate()->get()
                ->keyBy(fn (LearnerAttendance $record): string => $record->learner->uuid);

            foreach ($entries as $entry) {
                $record = $records->get($entry['learner_uuid']);
                if (! $record) {
                    throw ValidationException::withMessages(['attendances' => 'Un apprenant ne fait pas partie de cette feuille de présence.']);
                }
                $record->update([
                    'status' => AttendanceStatus::from($entry['status']),
                    'recorded_by' => $actor->getKey(),
                    'recorded_at' => $this->clock->now(),
                ]);
            }

            $this->audit->record('attendance.updated', $actor, $this->tenant->organization(), $lockedSheet, [
                'updated_count' => count($entries),
            ]);

            return $lockedSheet->load(['learnerAttendances.learner', 'teacherAttendance']);
        });
    }
}
