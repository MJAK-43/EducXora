<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Pedagogy\Models\PlacementQuestion;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;

final readonly class PlacementQuestionPolicy
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer) {}

    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'placement_questions.view');
    }

    public function view(User $user, PlacementQuestion $question): bool
    {
        return $this->viewAny($user) && ($question->organization_id === null || (int) $question->organization_id === $this->tenant->id());
    }

    public function create(User $user): bool
    {
        return $this->authorizer->allows($user, 'placement_questions.create');
    }

    public function update(User $user, PlacementQuestion $question): bool
    {
        return ! $question->isSystem()
            && (int) $question->organization_id === $this->tenant->id()
            && $this->authorizer->allows($user, 'placement_questions.update');
    }

    public function disable(User $user, PlacementQuestion $question): bool
    {
        return ! $question->isSystem()
            && (int) $question->organization_id === $this->tenant->id()
            && $this->authorizer->allows($user, 'placement_questions.disable');
    }

    public function enable(User $user, PlacementQuestion $question): bool
    {
        return $this->disable($user, $question);
    }
}
