<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Support;

use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AttendanceCorrection;
use App\Domain\Attendance\Models\AttendanceSheet;
use App\Domain\Attendance\Models\LearnerAttendance;
use App\Domain\Scheduling\Models\CourseSession;

final class AttendancePresenter
{
    /** @return array<string, mixed> */
    public static function session(CourseSession $session, string $timezone): array
    {
        $startsAt = $session->starts_at->setTimezone($timezone);

        return [
            'uuid' => $session->uuid,
            'group_name' => $session->group->name,
            'teacher_name' => $session->teacherMembership->user->name,
            'date' => $startsAt->format('Y-m-d'),
            'date_label' => $startsAt->format('d/m/Y'),
            'start_time' => $startsAt->format('H:i'),
            'end_time' => $session->ends_at->setTimezone($timezone)->format('H:i'),
            'session_status' => $session->status->value,
            'attendance_uuid' => $session->attendanceSheet?->uuid,
            'attendance_status' => $session->attendanceSheet?->status->value ?? 'non_pointed',
        ];
    }

    /** @return array<string, mixed> */
    public static function sheet(AttendanceSheet $sheet, string $timezone): array
    {
        $session = $sheet->courseSession;
        $startsAt = $session->starts_at->setTimezone($timezone);

        return [
            'uuid' => $sheet->uuid,
            'status' => $sheet->status->value,
            'session' => [
                'uuid' => $session->uuid,
                'date' => $startsAt->format('Y-m-d'),
                'date_label' => $startsAt->format('d/m/Y'),
                'start_time' => $startsAt->format('H:i'),
                'end_time' => $session->ends_at->setTimezone($timezone)->format('H:i'),
                'status' => $session->status->value,
            ],
            'group' => ['uuid' => $sheet->group->uuid, 'name' => $sheet->group->name],
            'teacher' => [
                'uuid' => $sheet->teacherMembership->uuid,
                'name' => $sheet->teacherMembership->user->name,
                'status' => $sheet->teacherAttendance?->status->value,
                'status_label' => $sheet->teacherAttendance?->status->label() ?? 'Non pointé',
            ],
            'learners' => $sheet->learnerAttendances->map(
                fn (LearnerAttendance $attendance): array => self::learner($attendance),
            )->values()->all(),
            'created_by' => $sheet->creator?->name,
            'validated_by' => $sheet->validator?->name,
            'validated_at_label' => $sheet->validated_at?->setTimezone($timezone)->format('d/m/Y H:i'),
            'corrections' => $sheet->corrections->map(
                fn (AttendanceCorrection $correction): array => self::correction($correction, $timezone),
            )->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private static function learner(LearnerAttendance $attendance): array
    {
        return [
            'uuid' => $attendance->uuid,
            'learner_uuid' => $attendance->learner->uuid,
            'full_name' => $attendance->learner->fullName(),
            'status' => $attendance->status?->value,
            'status_label' => $attendance->status?->label() ?? 'Non pointé',
        ];
    }

    /** @return array<string, mixed> */
    private static function correction(AttendanceCorrection $correction, string $timezone): array
    {
        $subject = $correction->learnerAttendance?->learner->fullName()
            ?? $correction->teacherAttendance?->teacherMembership->user->name
            ?? 'Inconnu';

        return [
            'uuid' => $correction->uuid,
            'subject' => $subject,
            'subject_type' => $correction->learner_attendance_id ? 'learner' : 'teacher',
            'before' => $correction->before_status->value,
            'before_label' => $correction->before_status->label(),
            'after' => $correction->after_status->value,
            'after_label' => $correction->after_status->label(),
            'reason' => $correction->reason,
            'actor' => $correction->corrector->name,
            'created_at_label' => $correction->created_at->setTimezone($timezone)->format('d/m/Y H:i'),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function statusOptions(): array
    {
        return array_map(
            fn (AttendanceStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
            AttendanceStatus::cases(),
        );
    }
}
