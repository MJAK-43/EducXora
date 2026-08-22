<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Models;

use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learner\Models\Learner;
use App\Domain\Pedagogy\Enums\LevelChangeSource;
use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

/** @property int $id
 * @property string $uuid
 * @property int $organization_id
 * @property int $learner_id
 * @property LearnerLevel $from_level
 * @property LearnerLevel $to_level
 * @property LevelChangeSource $source
 * @property string|null $reason
 * @property CarbonImmutable $occurred_at
 */
#[Fillable(['learner_id', 'from_level', 'to_level', 'source', 'placement_attempt_id', 'reason', 'changed_by', 'occurred_at'])]
final class LearnerLevelHistory extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        self::creating(fn (LearnerLevelHistory $history) => $history->uuid ??= (string) Str::uuid7());
        self::updating(fn () => throw new LogicException('Learner level history is append-only.'));
        self::deleting(fn () => throw new LogicException('Learner level history is append-only.'));
    }

    /** @return BelongsTo<Learner, $this> */
    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class);
    }

    /** @return BelongsTo<PlacementAttempt, $this> */
    public function placementAttempt(): BelongsTo
    {
        return $this->belongsTo(PlacementAttempt::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected function casts(): array
    {
        return [
            'from_level' => LearnerLevel::class,
            'to_level' => LearnerLevel::class,
            'source' => LevelChangeSource::class,
            'occurred_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
