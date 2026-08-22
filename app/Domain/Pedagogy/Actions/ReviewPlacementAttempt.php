<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Actions;

use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learning\Actions\AttachLearnerToGroup;
use App\Domain\Learning\Enums\GroupStatus;
use App\Domain\Learning\Models\Group;
use App\Domain\Learning\Models\GroupLearnerAssignment;
use App\Domain\Pedagogy\Enums\LevelChangeSource;
use App\Domain\Pedagogy\Enums\PlacementAttemptStatus;
use App\Domain\Pedagogy\Models\PlacementAttempt;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ReviewPlacementAttempt
{
    public function __construct(
        private ChangeLearnerLevel $changeLevel,
        private AttachLearnerToGroup $attachLearner,
        private AuditLogger $audit,
        private TenantContext $tenant,
        private Clock $clock,
    ) {}

    public function execute(PlacementAttempt $attempt, LearnerLevel $level, ?Group $group, ?string $reason, User $actor): PlacementAttempt
    {
        return DB::transaction(function () use ($attempt, $level, $group, $reason, $actor): PlacementAttempt {
            $locked = PlacementAttempt::query()->with('learner')->lockForUpdate()->findOrFail($attempt->getKey());
            if ($locked->status !== PlacementAttemptStatus::Completed) {
                throw ValidationException::withMessages(['test' => 'Seul un test terminé et non encore revu peut être validé.']);
            }
            $isOverride = $locked->suggested_level !== $level || $locked->suggested_group_id !== $group?->getKey();
            if ($isOverride && ($reason === null || mb_strlen(trim($reason)) < 10)) {
                throw ValidationException::withMessages(['reason' => 'Une justification d’au moins 10 caractères est requise pour modifier la suggestion.']);
            }
            $lockedGroup = null;
            if ($group !== null) {
                $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->getKey());
                if ($lockedGroup->status !== GroupStatus::Active
                    || (string) $lockedGroup->getRawOriginal('language') !== (string) $locked->getRawOriginal('language')
                    || $lockedGroup->level !== $level) {
                    throw ValidationException::withMessages(['group_uuid' => 'Le groupe validé doit être actif et correspondre à la langue et au niveau retenus.']);
                }
                $activeAssignment = GroupLearnerAssignment::query()
                    ->where('learner_id', $locked->learner_id)
                    ->whereNull('detached_at')
                    ->lockForUpdate()
                    ->first();
                if ($activeAssignment !== null && $activeAssignment->group_id !== $lockedGroup->getKey()) {
                    throw ValidationException::withMessages(['group_uuid' => 'L’apprenant doit être retiré de son groupe actif avant une nouvelle affectation.']);
                }
            }

            $this->changeLevel->execute(
                $locked->learner,
                $level,
                $isOverride ? LevelChangeSource::DirectorOverride : LevelChangeSource::PlacementTest,
                $actor,
                $reason,
                $locked,
            );
            if ($lockedGroup !== null && ! GroupLearnerAssignment::query()->where('learner_id', $locked->learner_id)->whereNull('detached_at')->exists()) {
                $this->attachLearner->execute($lockedGroup, $locked->learner, $actor);
            }
            $locked->update([
                'status' => PlacementAttemptStatus::Reviewed,
                'validated_level' => $level,
                'validated_group_id' => $lockedGroup?->getKey(),
                'review_reason' => $reason,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => $this->clock->now(),
            ]);
            $this->audit->record('placement_test.reviewed', $actor, $this->tenant->organization(), $locked, [
                'attempt_uuid' => $locked->uuid,
                'learner_uuid' => $locked->learner->uuid,
                'suggested_level' => $locked->suggested_level?->value,
                'validated_level' => $level->value,
                'suggested_group_uuid' => $locked->suggestedGroup?->uuid,
                'validated_group_uuid' => $lockedGroup?->uuid,
                'override' => $isOverride,
                'reason' => $reason,
            ]);

            return $locked->refresh();
        });
    }
}
