<?php

declare(strict_types=1);

namespace App\Domain\Learning\Actions;

use App\Domain\Learning\Models\Group;
use App\Domain\Learning\Models\GroupLearnerAssignment;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class DetachLearnerFromGroup
{
    public function __construct(private AuditLogger $audit, private TenantContext $tenant, private Clock $clock) {}

    public function execute(Group $group, string $learnerUuid, User $actor): GroupLearnerAssignment
    {
        if ($group->isArchived()) {
            throw ValidationException::withMessages(['group' => 'Les affectations d’un groupe archivé sont figées.']);
        }

        return DB::transaction(function () use ($group, $learnerUuid, $actor): GroupLearnerAssignment {
            $assignment = GroupLearnerAssignment::query()->with('learner')
                ->where('group_id', $group->getKey())->whereNull('detached_at')
                ->whereHas('learner', fn ($query) => $query->where('uuid', $learnerUuid))
                ->lockForUpdate()->firstOrFail();
            $assignment->update(['detached_at' => $this->clock->now(), 'detached_by' => $actor->getKey()]);
            $this->audit->record('group.learner_detached', $actor, $this->tenant->organization(), $group, [
                'learner_uuid' => $learnerUuid, 'assignment_uuid' => $assignment->uuid,
            ]);

            return $assignment->refresh();
        });
    }
}
