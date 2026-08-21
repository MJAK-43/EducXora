<?php

declare(strict_types=1);

namespace App\Domain\Learning\Models;

use App\Domain\Learner\Models\Learner;
use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $organization_id
 * @property int $group_id
 * @property int $learner_id
 * @property CarbonImmutable $assigned_at
 * @property int|null $assigned_by
 * @property CarbonImmutable|null $detached_at
 * @property int|null $detached_by
 * @property-read Learner $learner
 */
#[Fillable(['group_id', 'learner_id', 'assigned_at', 'assigned_by', 'detached_at', 'detached_by'])]
final class GroupLearnerAssignment extends Model
{
    use BelongsToTenant;

    protected static function booted(): void
    {
        self::creating(fn (GroupLearnerAssignment $assignment) => $assignment->uuid ??= (string) Str::uuid7());
    }

    /** @return BelongsTo<Group, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /** @return BelongsTo<Learner, $this> */
    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function active(): bool
    {
        return $this->detached_at === null;
    }

    protected function casts(): array
    {
        return ['assigned_at' => 'immutable_datetime', 'detached_at' => 'immutable_datetime'];
    }
}
