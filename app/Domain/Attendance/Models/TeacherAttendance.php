<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\OrganizationMembership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** @property int $id
 * @property string $uuid
 * @property int $organization_id
 * @property int $attendance_sheet_id
 * @property int $teacher_membership_id
 * @property AttendanceStatus $status
 * @property int|null $recorded_by
 * @property CarbonImmutable $recorded_at
 */
#[Fillable(['attendance_sheet_id', 'teacher_membership_id', 'status', 'recorded_by', 'recorded_at'])]
final class TeacherAttendance extends Model
{
    use BelongsToTenant;

    protected static function booted(): void
    {
        self::creating(fn (TeacherAttendance $attendance) => $attendance->uuid ??= (string) Str::uuid7());
    }

    /** @return BelongsTo<AttendanceSheet, $this> */
    public function sheet(): BelongsTo
    {
        return $this->belongsTo(AttendanceSheet::class, 'attendance_sheet_id');
    }

    /** @return BelongsTo<OrganizationMembership, $this> */
    public function teacherMembership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'teacher_membership_id');
    }

    /** @return HasMany<AttendanceCorrection, $this> */
    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected function casts(): array
    {
        return ['status' => AttendanceStatus::class, 'recorded_at' => 'immutable_datetime'];
    }
}
