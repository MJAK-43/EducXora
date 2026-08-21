<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Models;

use App\Domain\Learning\Models\Group;
use App\Domain\Scheduling\Enums\CourseSessionStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\OrganizationMembership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\CourseSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $organization_id
 * @property int $group_id
 * @property int $teacher_membership_id
 * @property string|null $room
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property CourseSessionStatus $status
 * @property int|null $created_by
 * @property CarbonImmutable|null $cancelled_at
 * @property int|null $cancelled_by
 * @property-read Group $group
 * @property-read OrganizationMembership $teacherMembership
 */
#[Fillable(['group_id', 'teacher_membership_id', 'room', 'starts_at', 'ends_at', 'status', 'created_by', 'cancelled_at', 'cancelled_by'])]
final class CourseSession extends Model
{
    /** @use HasFactory<CourseSessionFactory> */
    use BelongsToTenant, HasFactory;

    protected static function booted(): void
    {
        self::creating(fn (CourseSession $session) => $session->uuid ??= (string) Str::uuid7());
    }

    protected static function newFactory(): CourseSessionFactory
    {
        return CourseSessionFactory::new();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
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

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCancelled(): bool
    {
        return $this->status === CourseSessionStatus::Cancelled;
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'status' => CourseSessionStatus::class,
        ];
    }
}
