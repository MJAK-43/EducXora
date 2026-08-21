<?php

declare(strict_types=1);

namespace App\Domain\Learning\Queries;

use App\Domain\Learning\Enums\GroupStatus;
use App\Domain\Learning\Models\Group;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;

final readonly class GroupIndexQuery
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer) {}

    /** @param array<string, mixed> $filters
     * @return Builder<Group>
     */
    public function build(array $filters, User $user): Builder
    {
        $query = Group::query()
            ->with(['teacherMembership.user'])
            ->withCount('activeAssignments')
            ->orderBy('name');

        if (! $this->authorizer->allows($user, 'group.update')) {
            $query->where('teacher_membership_id', $this->tenant->membership()?->getKey() ?? 0);
        }

        $status = (string) ($filters['status'] ?? GroupStatus::Active->value);
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }
        if (! empty($filters['teacher'])) {
            $query->whereHas('teacherMembership', fn (Builder $teacher) => $teacher->where('uuid', $filters['teacher']));
        }
        if (! empty($filters['search'])) {
            $query->whereRaw('name ILIKE ?', ['%'.trim((string) $filters['search']).'%']);
        }

        return $query;
    }
}
