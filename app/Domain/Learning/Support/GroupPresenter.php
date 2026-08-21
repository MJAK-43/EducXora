<?php

declare(strict_types=1);

namespace App\Domain\Learning\Support;

use App\Domain\Learning\Models\Group;
use App\Domain\Learning\Models\GroupLearnerAssignment;

final class GroupPresenter
{
    /** @return array<string, mixed> */
    public static function summary(Group $group): array
    {
        return [
            'uuid' => $group->uuid,
            'name' => $group->name,
            'language' => $group->language->value,
            'language_label' => $group->language->label(),
            'level' => $group->level->value,
            'capacity' => $group->capacity,
            'members_count' => (int) ($group->active_assignments_count ?? $group->activeAssignments()->count()),
            'teacher_membership_uuid' => $group->teacherMembership->uuid,
            'teacher_name' => $group->teacherMembership->user->name,
            'status' => $group->status->value,
        ];
    }

    /** @return array<string, mixed> */
    public static function detail(Group $group): array
    {
        return [
            ...self::summary($group),
            'archived_at' => $group->archived_at?->toIso8601String(),
            'members' => $group->activeAssignments->map(
                fn (GroupLearnerAssignment $assignment): array => [
                    'assignment_uuid' => $assignment->uuid,
                    'uuid' => $assignment->learner->uuid,
                    'full_name' => $assignment->learner->fullName(),
                    'phone' => $assignment->learner->phone,
                    'assigned_at_label' => $assignment->assigned_at->format('d/m/Y'),
                ],
            )->values(),
        ];
    }
}
