<?php

declare(strict_types=1);

namespace App\Domain\Learning\Actions;

use App\Domain\Learning\Enums\GroupStatus;
use App\Domain\Learning\Models\Group;
use App\Domain\Scheduling\Enums\CourseSessionStatus;
use App\Domain\Scheduling\Models\CourseSession;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ArchiveGroup
{
    public function __construct(private AuditLogger $audit, private TenantContext $tenant, private Clock $clock) {}

    public function execute(Group $group, User $actor): Group
    {
        if ($group->isArchived()) {
            return $group;
        }
        if (CourseSession::query()->where('group_id', $group->getKey())
            ->where('status', CourseSessionStatus::Scheduled)->where('ends_at', '>', $this->clock->now())->exists()) {
            throw ValidationException::withMessages(['group' => 'Annulez d’abord les séances futures de ce groupe.']);
        }

        return DB::transaction(function () use ($group, $actor): Group {
            $group->update(['status' => GroupStatus::Archived, 'archived_at' => $this->clock->now(), 'archived_by' => $actor->getKey()]);
            $this->audit->record('group.archived', $actor, $this->tenant->organization(), $group);

            return $group->refresh();
        });
    }
}
