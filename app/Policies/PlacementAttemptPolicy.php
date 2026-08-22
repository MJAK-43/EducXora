<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Learning\Models\GroupLearnerAssignment;
use App\Domain\Pedagogy\Models\PlacementAttempt;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;

final readonly class PlacementAttemptPolicy
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer) {}

    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'placement_tests.view');
    }

    public function view(User $user, PlacementAttempt $attempt): bool
    {
        if ((int) $attempt->organization_id !== $this->tenant->id() || ! $this->viewAny($user)) {
            return false;
        }
        if ($this->authorizer->allows($user, 'placement_tests.start') || $this->authorizer->allows($user, 'placement_tests.review')) {
            return true;
        }

        return $this->teacherOwnsLearner($attempt->learner_id);
    }

    public function answer(User $user, PlacementAttempt $attempt): bool
    {
        return (int) $attempt->organization_id === $this->tenant->id()
            && $this->authorizer->allows($user, 'placement_tests.complete');
    }

    public function complete(User $user, PlacementAttempt $attempt): bool
    {
        return $this->answer($user, $attempt);
    }

    public function review(User $user, PlacementAttempt $attempt): bool
    {
        return (int) $attempt->organization_id === $this->tenant->id()
            && $this->authorizer->allows($user, 'placement_tests.review');
    }

    private function teacherOwnsLearner(int $learnerId): bool
    {
        $membershipId = $this->tenant->membership()?->getKey();
        if ($membershipId === null) {
            return false;
        }

        return GroupLearnerAssignment::query()
            ->where('learner_id', $learnerId)
            ->whereNull('detached_at')
            ->whereHas('group', fn ($query) => $query->where('teacher_membership_id', $membershipId)->where('status', 'active'))
            ->exists();
    }
}
