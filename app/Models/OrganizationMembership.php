<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

final class OrganizationMembership extends Pivot
{
    protected $table = 'organization_user';

    public $incrementing = true;

    protected $fillable = ['organization_id', 'user_id', 'status', 'joined_at'];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'membership_role', 'membership_id', 'role_id')
            ->withPivot('organization_id')
            ->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->getAttribute('status') === MembershipStatus::Active;
    }

    public function statusValue(): string
    {
        $status = $this->getAttribute('status');

        return $status instanceof MembershipStatus ? $status->value : (string) $status;
    }

    protected function casts(): array
    {
        return [
            'status' => MembershipStatus::class,
            'joined_at' => 'immutable_datetime',
        ];
    }
}
