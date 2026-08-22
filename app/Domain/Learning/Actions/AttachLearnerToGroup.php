<?php

declare(strict_types=1);

namespace App\Domain\Learning\Actions;

use App\Domain\Learner\Enums\LearnerStatus;
use App\Domain\Learner\Models\Learner;
use App\Domain\Learning\Models\Group;
use App\Domain\Learning\Models\GroupLearnerAssignment;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class AttachLearnerToGroup
{
    public function __construct(private AuditLogger $audit, private TenantContext $tenant, private Clock $clock) {}

    public function execute(Group $group, Learner $learner, User $actor): GroupLearnerAssignment
    {
        return DB::transaction(function () use ($group, $learner, $actor): GroupLearnerAssignment {
            $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->getKey());
            $lockedLearner = Learner::query()->lockForUpdate()->findOrFail($learner->getKey());
            if ($lockedGroup->isArchived()) {
                throw ValidationException::withMessages(['learner_uuid' => 'Un groupe archivé ne peut pas recevoir d’apprenant.']);
            }
            if ($lockedLearner->status !== LearnerStatus::Active) {
                throw ValidationException::withMessages(['learner_uuid' => 'Seul un apprenant actif peut être affecté.']);
            }
            if ((string) $lockedLearner->getRawOriginal('language') !== (string) $lockedGroup->getRawOriginal('language')
                || $lockedLearner->current_level !== $lockedGroup->level) {
                throw ValidationException::withMessages(['learner_uuid' => 'La langue et le niveau de l’apprenant doivent correspondre au groupe.']);
            }
            if (GroupLearnerAssignment::query()->where('learner_id', $lockedLearner->getKey())->whereNull('detached_at')->exists()) {
                throw ValidationException::withMessages(['learner_uuid' => 'Cet apprenant appartient déjà à un groupe actif.']);
            }
            if ($lockedGroup->activeAssignments()->count() >= $lockedGroup->capacity) {
                throw ValidationException::withMessages(['learner_uuid' => 'La capacité du groupe est atteinte.']);
            }

            $assignment = GroupLearnerAssignment::query()->create([
                'group_id' => $lockedGroup->getKey(), 'learner_id' => $lockedLearner->getKey(),
                'assigned_at' => $this->clock->now(), 'assigned_by' => $actor->getKey(),
            ]);
            $this->audit->record('group.learner_attached', $actor, $this->tenant->organization(), $lockedGroup, [
                'learner_uuid' => $lockedLearner->uuid, 'assignment_uuid' => $assignment->uuid,
            ]);

            return $assignment;
        });
    }
}
