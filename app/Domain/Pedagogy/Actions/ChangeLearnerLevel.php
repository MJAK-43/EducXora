<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Actions;

use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learner\Models\Learner;
use App\Domain\Pedagogy\Enums\LevelChangeSource;
use App\Domain\Pedagogy\Models\LearnerLevelHistory;
use App\Domain\Pedagogy\Models\PlacementAttempt;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

final readonly class ChangeLearnerLevel
{
    public function __construct(private AuditLogger $audit, private TenantContext $tenant, private Clock $clock) {}

    public function execute(
        Learner $learner,
        LearnerLevel $toLevel,
        LevelChangeSource $source,
        User $actor,
        ?string $reason = null,
        ?PlacementAttempt $attempt = null,
    ): Learner {
        return DB::transaction(function () use ($learner, $toLevel, $source, $actor, $reason, $attempt): Learner {
            $locked = Learner::query()->lockForUpdate()->findOrFail($learner->getKey());
            $fromLevel = $locked->current_level;
            if ($fromLevel === $toLevel) {
                return $locked;
            }
            $locked->update(['current_level' => $toLevel]);
            LearnerLevelHistory::query()->create([
                'learner_id' => $locked->getKey(),
                'from_level' => $fromLevel,
                'to_level' => $toLevel,
                'source' => $source,
                'placement_attempt_id' => $attempt?->getKey(),
                'reason' => $reason,
                'changed_by' => $actor->getKey(),
                'occurred_at' => $this->clock->now(),
            ]);
            $this->audit->record('learner.level_changed', $actor, $this->tenant->organization(), $locked, [
                'learner_uuid' => $locked->uuid,
                'from_level' => $fromLevel->value,
                'to_level' => $toLevel->value,
                'source' => $source->value,
                'attempt_uuid' => $attempt?->uuid,
                'reason' => $reason,
            ]);

            return $locked;
        });
    }
}
