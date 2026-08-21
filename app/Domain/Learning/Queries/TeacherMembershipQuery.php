<?php

declare(strict_types=1);

namespace App\Domain\Learning\Queries;

use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\OrganizationMembership;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final readonly class TeacherMembershipQuery
{
    public function __construct(private TenantContext $tenant) {}

    /** @return Builder<OrganizationMembership> */
    public function eligible(): Builder
    {
        return OrganizationMembership::query()
            ->where('organization_id', $this->tenant->id())
            ->where('status', MembershipStatus::Active)
            ->whereHas('user', fn (Builder $query) => $query->where('status', UserStatus::Active))
            ->whereHas('roles', fn (Builder $query) => $query->where('roles.name', 'Teacher/Trainer'));
    }

    public function find(string $uuid): OrganizationMembership
    {
        $membership = $this->eligible()->where('uuid', $uuid)->first();

        if (! $membership) {
            throw ValidationException::withMessages([
                'teacher_membership_uuid' => 'L’enseignant sélectionné est invalide ou inactif pour cette organisation.',
            ]);
        }

        return $membership;
    }

    /** @return array<int, array{value: string, label: string}> */
    public function options(): array
    {
        return $this->eligible()->with('user')->get()
            ->sortBy(fn (OrganizationMembership $membership): string => $membership->user->name)
            ->map(fn (OrganizationMembership $membership): array => [
                'value' => $membership->uuid,
                'label' => $membership->user->name,
            ])->values()->all();
    }
}
