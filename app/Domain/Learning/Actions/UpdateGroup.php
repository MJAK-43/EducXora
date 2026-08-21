<?php

declare(strict_types=1);

namespace App\Domain\Learning\Actions;

use App\Domain\Learning\Models\Group;
use App\Domain\Learning\Queries\TeacherMembershipQuery;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateGroup
{
    public function __construct(private TeacherMembershipQuery $teachers, private AuditLogger $audit, private TenantContext $tenant) {}

    /** @param array<string, mixed> $attributes */
    public function execute(Group $group, array $attributes, User $actor): Group
    {
        return DB::transaction(function () use ($group, $attributes, $actor): Group {
            $lockedGroup = Group::query()->lockForUpdate()->findOrFail($group->getKey());
            if ($lockedGroup->isArchived()) {
                throw ValidationException::withMessages(['group' => 'Un groupe archivé ne peut pas être modifié.']);
            }
            $teacher = $this->teachers->find((string) $attributes['teacher_membership_uuid']);
            $memberCount = $lockedGroup->activeAssignments()->count();
            if ((int) $attributes['capacity'] < $memberCount) {
                throw ValidationException::withMessages(['capacity' => "La capacité ne peut pas être inférieure aux {$memberCount} apprenants affectés."]);
            }

            if ($lockedGroup->activeAssignments()->whereHas(
                'learner',
                fn (Builder $query) => $query->where('initial_level', '!=', $attributes['level']),
            )->exists()) {
                throw ValidationException::withMessages(['level' => 'Le niveau doit rester compatible avec les apprenants affectés.']);
            }
            if ($lockedGroup->activeAssignments()->whereHas(
                'learner',
                fn (Builder $query) => $query->where('language', '!=', $attributes['language']),
            )->exists()) {
                throw ValidationException::withMessages(['language' => 'La langue doit rester compatible avec les apprenants affectés.']);
            }

            $before = $lockedGroup->only(['name', 'language', 'level', 'capacity', 'teacher_membership_id']);
            $teacherChanged = (int) $lockedGroup->teacher_membership_id !== (int) $teacher->getKey();
            $lockedGroup->update([...$attributes, 'teacher_membership_id' => $teacher->getKey()]);
            $this->audit->record('group.updated', $actor, $this->tenant->organization(), $lockedGroup, [
                'before' => $before,
                'after' => $lockedGroup->only(['name', 'language', 'level', 'capacity', 'teacher_membership_id']),
            ]);
            if ($teacherChanged) {
                $this->audit->record('group.teacher_changed', $actor, $this->tenant->organization(), $lockedGroup, [
                    'from_membership_id' => $before['teacher_membership_id'],
                    'to_membership_uuid' => $teacher->uuid,
                ]);
            }

            return $lockedGroup->refresh();
        });
    }
}
