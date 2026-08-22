<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Learner\Models\Learner;
use App\Domain\Learning\Models\GroupLearnerAssignment;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;

final readonly class LearnerPolicy
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer) {}

    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'learners.view');
    }

    public function view(User $user, Learner $learner): bool
    {
        return $this->sameTenant($learner) && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->authorizer->allows($user, 'learners.create');
    }

    public function update(User $user, Learner $learner): bool
    {
        return $this->sameTenant($learner) && $this->authorizer->allows($user, 'learners.update');
    }

    public function archive(User $user, Learner $learner): bool
    {
        return $this->sameTenant($learner) && $this->authorizer->allows($user, 'learners.archive');
    }

    public function restore(User $user, Learner $learner): bool
    {
        return $this->sameTenant($learner) && $this->authorizer->allows($user, 'learners.restore');
    }

    public function export(User $user): bool
    {
        return $this->authorizer->allows($user, 'learners.export');
    }

    public function viewPedagogy(User $user, Learner $learner): bool
    {
        if (! $this->sameTenant($learner) || ! $this->authorizer->allows($user, 'learner_levels.view')) {
            return false;
        }
        if ($this->authorizer->allows($user, 'placement_tests.start') || $this->authorizer->allows($user, 'placement_tests.review')) {
            return true;
        }

        return $this->teacherOwnsLearner($learner);
    }

    public function updateLevel(User $user, Learner $learner): bool
    {
        if (! $this->sameTenant($learner) || ! $this->authorizer->allows($user, 'learner_levels.update')) {
            return false;
        }
        if ($this->authorizer->allows($user, 'placement_tests.review')) {
            return true;
        }

        return $this->teacherOwnsLearner($learner);
    }

    private function teacherOwnsLearner(Learner $learner): bool
    {
        $membershipId = $this->tenant->membership()?->getKey();
        if ($membershipId === null) {
            return false;
        }

        return GroupLearnerAssignment::query()
            ->where('learner_id', $learner->getKey())
            ->whereNull('detached_at')
            ->whereHas('group', fn ($query) => $query->where('teacher_membership_id', $membershipId)->where('status', 'active'))
            ->exists();
    }

    private function sameTenant(Learner $learner): bool
    {
        return $this->tenant->resolved() && (int) $learner->organization_id === $this->tenant->id();
    }
}
