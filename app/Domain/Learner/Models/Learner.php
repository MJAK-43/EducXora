<?php

declare(strict_types=1);

namespace App\Domain\Learner\Models;

use App\Domain\Attendance\Models\LearnerAttendance;
use App\Domain\Learner\Enums\LearnerLanguage;
use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learner\Enums\LearnerStatus;
use App\Domain\Pedagogy\Models\LearnerLevelHistory;
use App\Domain\Pedagogy\Models\PlacementAttempt;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Organization;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\LearnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $organization_id
 * @property string $first_name
 * @property string $last_name
 * @property CarbonImmutable $birth_date
 * @property string $phone
 * @property string|null $email
 * @property LearnerLanguage $language
 * @property LearnerLevel $initial_level
 * @property LearnerLevel $current_level
 * @property CarbonImmutable $registered_on
 * @property string|null $photo_path
 * @property LearnerStatus $status
 * @property int|null $created_by
 * @property CarbonImmutable|null $archived_at
 * @property int|null $archived_by
 * @property-read Organization $organization
 */
#[Fillable([
    'first_name', 'last_name', 'birth_date', 'phone', 'email', 'language',
    'initial_level', 'current_level', 'registered_on', 'photo_path', 'status', 'created_by',
    'archived_at', 'archived_by',
])]
final class Learner extends Model
{
    /** @use HasFactory<LearnerFactory> */
    use BelongsToTenant, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (Learner $learner): void {
            $learner->uuid ??= (string) Str::uuid7();
            $learner->current_level ??= $learner->initial_level;
        });
    }

    protected static function newFactory(): LearnerFactory
    {
        return LearnerFactory::new();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<LearnerAttendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(LearnerAttendance::class);
    }

    /** @return HasMany<PlacementAttempt, $this> */
    public function placementAttempts(): HasMany
    {
        return $this->hasMany(PlacementAttempt::class);
    }

    /** @return HasMany<LearnerLevelHistory, $this> */
    public function levelHistory(): HasMany
    {
        return $this->hasMany(LearnerLevelHistory::class)->orderByDesc('occurred_at');
    }

    /** @return BelongsTo<User, $this> */
    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function isArchived(): bool
    {
        return $this->status === LearnerStatus::Archived;
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'immutable_date',
            'registered_on' => 'immutable_date',
            'archived_at' => 'immutable_datetime',
            'language' => LearnerLanguage::class,
            'initial_level' => LearnerLevel::class,
            'current_level' => LearnerLevel::class,
            'status' => LearnerStatus::class,
        ];
    }
}
