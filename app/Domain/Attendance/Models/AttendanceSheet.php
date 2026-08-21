<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\Attendance\Enums\AttendanceSheetStatus;
use App\Domain\Learning\Models\Group;
use App\Domain\Scheduling\Models\CourseSession;
use App\Models\Concerns\BelongsToTenant;
use App\Models\OrganizationMembership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $organization_id
 * @property int $course_session_id
 * @property int $group_id
 * @property int $teacher_membership_id
 * @property AttendanceSheetStatus $status
 * @property int|null $created_by
 * @property CarbonImmutable|null $validated_at
 * @property int|null $validated_by
 * @property-read CourseSession $courseSession
 * @property-read Group $group
 * @property-read OrganizationMembership $teacherMembership
 * @property-read Collection<int, LearnerAttendance> $learnerAttendances
 * @property-read TeacherAttendance|null $teacherAttendance
 */
#[Fillable(['course_session_id', 'group_id', 'teacher_membership_id', 'status', 'created_by', 'validated_at', 'validated_by'])]
final class AttendanceSheet extends Model
{
    use BelongsToTenant;

    protected static function booted(): void
    {
        self::creating(fn (AttendanceSheet $sheet) => $sheet->uuid ??= (string) Str::uuid7());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<CourseSession, $this> */
    public function courseSession(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class);
    }

    /** @return BelongsTo<Group, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /** @return BelongsTo<OrganizationMembership, $this> */
    public function teacherMembership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'teacher_membership_id');
    }

    /** @return HasMany<LearnerAttendance, $this> */
    public function learnerAttendances(): HasMany
    {
        return $this->hasMany(LearnerAttendance::class);
    }

    /** @return HasOne<TeacherAttendance, $this> */
    public function teacherAttendance(): HasOne
    {
        return $this->hasOne(TeacherAttendance::class);
    }

    /** @return HasMany<AttendanceCorrection, $this> */
    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    /** @return BelongsTo<User, $this> */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValidated(): bool
    {
        return $this->status === AttendanceSheetStatus::Validated;
    }

    protected function casts(): array
    {
        return ['status' => AttendanceSheetStatus::class, 'validated_at' => 'immutable_datetime'];
    }
}
