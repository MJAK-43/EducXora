<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Learner\Models\Learner;
use App\Enums\OrganizationStatus;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'slug', 'email', 'phone', 'country_code', 'timezone', 'currency', 'locale',
    'status', 'settings',
])]
final class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (Organization $organization): void {
            $organization->uuid ??= (string) Str::uuid7();
            $organization->slug = Str::slug($organization->slug ?: $organization->name);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsToMany<User, $this, OrganizationMembership, 'pivot'> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(OrganizationMembership::class)
            ->withPivot(['id', 'status', 'joined_at'])
            ->withTimestamps();
    }

    /** @return HasMany<OrganizationMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    /** @return HasMany<Role, $this> */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /** @return HasMany<Learner, $this> */
    public function learners(): HasMany
    {
        return $this->hasMany(Learner::class);
    }

    public function isActive(): bool
    {
        return $this->getAttribute('status') === OrganizationStatus::Active;
    }

    public function statusValue(): string
    {
        $status = $this->getAttribute('status');

        return $status instanceof OrganizationStatus ? $status->value : (string) $status;
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'status' => OrganizationStatus::class,
        ];
    }
}
