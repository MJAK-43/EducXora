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

final readonly class CreateGroup
{
    public function __construct(private TeacherMembershipQuery $teachers, private AuditLogger $audit, private TenantContext $tenant) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes, User $actor): Group
    {
        $teacher = $this->teachers->find((string) $attributes['teacher_membership_uuid']);

        return DB::transaction(function () use ($attributes, $teacher, $actor): Group {
            $group = Group::query()->create([
                ...$attributes,
                'teacher_membership_id' => $teacher->getKey(),
                'status' => GroupStatus::Active,
                'created_by' => $actor->getKey(),
            ]);
            $this->audit->record('group.created', $actor, $this->tenant->organization(), $group, [
                'level' => $group->level->value,
                'teacher_membership_uuid' => $teacher->uuid,
            ]);

            return $group->refresh();
        });
    }
}
