<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Learner\Models\Learner;
use App\Models\Concerns\BelongsToTenant;
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
 * @property int $learner_id
 * @property AttendanceStatus|null $status
 * @property int|null $recorded_by
 * @property CarbonImmutable|null $recorded_at
 * @property-read Learner $learner
 */
#[Fillable(['attendance_sheet_id', 'learner_id', 'status', 'recorded_by', 'recorded_at'])]
final class LearnerAttendance extends Model
{
    use BelongsToTenant;

    protected static function booted(): void
    {
        self::creating(fn (LearnerAttendance $attendance) => $attendance->uuid ??= (string) Str::uuid7());
    }

    /** @return BelongsTo<AttendanceSheet, $this> */
    public function sheet(): BelongsTo
    {
        return $this->belongsTo(AttendanceSheet::class, 'attendance_sheet_id');
    }

    /** @return BelongsTo<Learner, $this> */
    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class);
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
