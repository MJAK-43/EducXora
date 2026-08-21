<?php

declare(strict_types=1);

namespace App\Domain\Learning\Models;

use App\Domain\Learner\Enums\LearnerLanguage;
use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learning\Enums\GroupStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\OrganizationMembership;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $organization_id
 * @property string $name
 * @property LearnerLanguage $language
 * @property LearnerLevel $level
 * @property int $capacity
 * @property int $teacher_membership_id
 * @property GroupStatus $status
 * @property int|null $created_by
 * @property CarbonImmutable|null $archived_at
 * @property int|null $archived_by
 * @property int|null $active_assignments_count
 * @property-read OrganizationMembership $teacherMembership
 * @property-read Collection<int, GroupLearnerAssignment> $activeAssignments
 */
#[Fillable(['name', 'language', 'level', 'capacity', 'teacher_membership_id', 'status', 'created_by', 'archived_at', 'archived_by'])]
final class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use BelongsToTenant, HasFactory;

    protected static function booted(): void
    {
        self::creating(fn (Group $group) => $group->uuid ??= (string) Str::uuid7());
    }

    protected static function newFactory(): GroupFactory
    {
        return GroupFactory::new();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<OrganizationMembership, $this> */
    public function teacherMembership(): BelongsTo
    {
        return $this->belongsTo(OrganizationMembership::class, 'teacher_membership_id');
    }

    /** @return HasMany<GroupLearnerAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(GroupLearnerAssignment::class);
    }

    /** @return HasMany<GroupLearnerAssignment, $this> */
    public function activeAssignments(): HasMany
    {
        return $this->assignments()->whereNull('detached_at');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isArchived(): bool
    {
        return $this->status === GroupStatus::Archived;
    }

    protected function casts(): array
    {
        return [
            'language' => LearnerLanguage::class,
            'level' => LearnerLevel::class,
            'status' => GroupStatus::class,
            'capacity' => 'integer',
            'archived_at' => 'immutable_datetime',
        ];
    }
}
