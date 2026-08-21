<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** @property int $id
 * @property string $uuid
 * @property int $organization_id
 * @property int $attendance_sheet_id
 * @property int|null $learner_attendance_id
 * @property int|null $teacher_attendance_id
 * @property AttendanceStatus $before_status
 * @property AttendanceStatus $after_status
 * @property string $reason
 * @property int $corrected_by
 * @property CarbonImmutable $created_at
 */
#[Fillable([
    'attendance_sheet_id', 'learner_attendance_id', 'teacher_attendance_id',
    'before_status', 'after_status', 'reason', 'corrected_by',
])]
final class AttendanceCorrection extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        self::creating(fn (AttendanceCorrection $correction) => $correction->uuid ??= (string) Str::uuid7());
        self::updating(fn () => throw new \LogicException('Attendance corrections are append-only.'));
        self::deleting(fn () => throw new \LogicException('Attendance corrections are append-only.'));
    }

    /** @return BelongsTo<AttendanceSheet, $this> */
    public function sheet(): BelongsTo
    {
        return $this->belongsTo(AttendanceSheet::class, 'attendance_sheet_id');
    }

    /** @return BelongsTo<LearnerAttendance, $this> */
    public function learnerAttendance(): BelongsTo
    {
        return $this->belongsTo(LearnerAttendance::class);
    }

    /** @return BelongsTo<TeacherAttendance, $this> */
    public function teacherAttendance(): BelongsTo
    {
        return $this->belongsTo(TeacherAttendance::class);
    }

    /** @return BelongsTo<User, $this> */
    public function corrector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    protected function casts(): array
    {
        return ['before_status' => AttendanceStatus::class, 'after_status' => AttendanceStatus::class, 'created_at' => 'immutable_datetime'];
    }
}
