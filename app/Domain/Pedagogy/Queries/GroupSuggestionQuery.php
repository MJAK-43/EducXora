<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Queries;

use App\Domain\Learner\Enums\LearnerLanguage;
use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learning\Enums\GroupStatus;
use App\Domain\Learning\Models\Group;

final class GroupSuggestionQuery
{
    public function firstAvailable(LearnerLevel $level, LearnerLanguage $language): ?Group
    {
        return Group::query()
            ->where('status', GroupStatus::Active)
            ->where('language', $language)
            ->where('level', $level)
            ->withCount('activeAssignments')
            ->whereRaw('(SELECT COUNT(*) FROM group_learner_assignments WHERE group_learner_assignments.group_id = groups.id AND group_learner_assignments.detached_at IS NULL) < groups.capacity')
            ->orderBy('active_assignments_count')
            ->orderBy('name')
            ->first();
    }
}
