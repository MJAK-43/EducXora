<?php

declare(strict_types=1);

namespace App\Domain\Learner\Actions;

use App\Domain\Learner\Enums\LearnerStatus;
use App\Domain\Learner\Models\Learner;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

final readonly class ArchiveLearner
{
    public function __construct(private TenantContext $tenant, private AuditLogger $audit) {}

    public function execute(Learner $learner, User $actor): Learner
    {
        if ($learner->isArchived()) {
            return $learner;
        }

        return DB::transaction(function () use ($learner, $actor): Learner {
            $learner->update([
                'status' => LearnerStatus::Archived,
                'archived_at' => now(),
                'archived_by' => $actor->getKey(),
            ]);
            $this->audit->record('learner.archived', $actor, $this->tenant->organization(), $learner, [
                'before' => ['status' => LearnerStatus::Active->value],
                'after' => ['status' => LearnerStatus::Archived->value],
            ]);

            return $learner->refresh();
        });
    }
}
