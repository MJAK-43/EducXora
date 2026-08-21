<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

final class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'organization_id', 'is_system'];

    /** @param array<string, mixed> $attributes */
    public function __construct(array $attributes = [])
    {
        $attributes['uuid'] ??= (string) Str::uuid7();
        parent::__construct($attributes);
    }

    protected static function booted(): void
    {
        self::creating(function (Role $role): void {
            $role->uuid ??= (string) Str::uuid7();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsToMany<OrganizationMembership, $this> */
    public function memberships(): BelongsToMany
    {
        return $this->belongsToMany(
            OrganizationMembership::class,
            'membership_role',
            'role_id',
            'membership_id',
        )->withPivot('organization_id')->withTimestamps();
    }

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }
}
