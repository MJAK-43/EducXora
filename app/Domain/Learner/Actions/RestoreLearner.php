<?php

declare(strict_types=1);

namespace App\Domain\Learner\Actions;

use App\Domain\Learner\Enums\LearnerStatus;
use App\Domain\Learner\Models\Learner;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

final readonly class RestoreLearner
{
    public function __construct(private TenantContext $tenant, private AuditLogger $audit) {}

    public function execute(Learner $learner, User $actor): Learner
    {
        if (! $learner->isArchived()) {
            return $learner;
        }

        return DB::transaction(function () use ($learner, $actor): Learner {
            $learner->update([
                'status' => LearnerStatus::Active,
                'archived_at' => null,
                'archived_by' => null,
            ]);
            $this->audit->record('learner.restored', $actor, $this->tenant->organization(), $learner, [
                'before' => ['status' => LearnerStatus::Archived->value],
                'after' => ['status' => LearnerStatus::Active->value],
            ]);

            return $learner->refresh();
        });
    }
}
