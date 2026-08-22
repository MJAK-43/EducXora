<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Models;

use App\Domain\Learner\Enums\LearnerLanguage;
use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learner\Models\Learner;
use App\Domain\Learning\Models\Group;
use App\Domain\Pedagogy\Enums\PlacementAttemptStatus;
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
 * @property int $learner_id
 * @property PlacementAttemptStatus $status
 * @property LearnerLanguage $language
 * @property int $question_count
 * @property string $scoring_version
 * @property int|null $raw_score
 * @property string|null $percentage
 * @property LearnerLevel|null $suggested_level
 * @property int|null $suggested_group_id
 * @property LearnerLevel|null $validated_level
 * @property int|null $validated_group_id
 * @property string|null $review_reason
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $reviewed_at
 */
#[Fillable([
    'learner_id', 'status', 'language', 'question_count', 'scoring_version',
    'raw_score', 'percentage', 'suggested_level', 'suggested_group_id',
    'validated_level', 'validated_group_id', 'review_reason', 'started_by',
    'started_at', 'completed_by', 'completed_at', 'reviewed_by', 'reviewed_at',
])]
final class PlacementAttempt extends Model
{
    use BelongsToTenant;

    protected static function booted(): void
    {
        self::creating(fn (PlacementAttempt $attempt) => $attempt->uuid ??= (string) Str::uuid7());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<Learner, $this> */
    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class);
    }

    /** @return HasMany<PlacementAttemptQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(PlacementAttemptQuestion::class, 'attempt_id')->orderBy('position');
    }

    /** @return BelongsTo<Group, $this> */
    public function suggestedGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'suggested_group_id');
    }

    /** @return BelongsTo<Group, $this> */
    public function validatedGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'validated_group_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isStarted(): bool
    {
        return $this->status === PlacementAttemptStatus::Started;
    }

    public function isReviewed(): bool
    {
        return $this->status === PlacementAttemptStatus::Reviewed;
    }

    protected function casts(): array
    {
        return [
            'status' => PlacementAttemptStatus::class,
            'language' => LearnerLanguage::class,
            'question_count' => 'integer',
            'raw_score' => 'integer',
            'percentage' => 'decimal:2',
            'suggested_level' => LearnerLevel::class,
            'validated_level' => LearnerLevel::class,
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
        ];
    }
}
