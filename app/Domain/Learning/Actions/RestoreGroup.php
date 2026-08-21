<?php

declare(strict_types=1);

namespace App\Domain\Learning\Actions;

use App\Domain\Learning\Enums\GroupStatus;
use App\Domain\Learning\Models\Group;
use App\Domain\Learning\Queries\TeacherMembershipQuery;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

final readonly class RestoreGroup
{
    public function __construct(private TeacherMembershipQuery $teachers, private AuditLogger $audit, private TenantContext $tenant) {}

    public function execute(Group $group, User $actor): Group
    {
        if (! $group->isArchived()) {
            return $group;
        }
        $teacherUuid = (string) $group->teacherMembership()->value('uuid');
        $this->teachers->find($teacherUuid);

        return DB::transaction(function () use ($group, $actor): Group {
            $group->update(['status' => GroupStatus::Active, 'archived_at' => null, 'archived_by' => null]);
            $this->audit->record('group.restored', $actor, $this->tenant->organization(), $group);

            return $group->refresh();
        });
    }
}
