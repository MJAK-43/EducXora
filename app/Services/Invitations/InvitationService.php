<?php

declare(strict_types=1);

namespace App\Services\Invitations;

use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\OrganizationInvitationNotification;
use App\Services\Audit\AuditLogger;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class InvitationService
{
    public function __construct(
        private TenantContext $tenant,
        private MembershipAuthorizer $authorizer,
        private AuditLogger $audit,
    ) {}

    public function invite(string $email, Role $role, User $actor): UserInvitation
    {
        if ((int) $role->organization_id !== $this->tenant->id()) {
            throw ValidationException::withMessages(['role_id' => 'Le rôle ne correspond pas à cette organisation.']);
        }

        $token = Str::random(64);
        $invitation = DB::transaction(function () use ($email, $role, $actor, $token): UserInvitation {
            UserInvitation::query()->where('email', strtolower($email))->delete();
            $invitation = UserInvitation::query()->create([
                'email' => strtolower($email), 'token_hash' => hash('sha256', $token),
                'role_id' => $role->getKey(), 'expires_at' => now()->addHours(72),
                'invited_by' => $actor->getKey(),
            ]);
            $this->audit->record('invitation.created', $actor, $this->tenant->organization(), $invitation, ['email' => strtolower($email), 'role' => $role->name]);

            return $invitation;
        });
        Notification::route('mail', strtolower($email))
            ->notify(new OrganizationInvitationNotification($this->tenant->organization(), $token));

        return $invitation;
    }

    public function findUsable(string $token): UserInvitation
    {
        $invitation = UserInvitation::withoutGlobalScope(TenantScope::class)
            ->with(['organization', 'role'])->where('token_hash', hash('sha256', $token))->first();
        if (! $invitation || ! $invitation->isUsable()) {
            throw ValidationException::withMessages(['token' => 'Cette invitation est invalide ou expirée.']);
        }

        return $invitation;
    }

    /** @param array<string, mixed> $attributes */
    public function accept(string $token, array $attributes, ?User $authenticated = null): User
    {
        return DB::transaction(function () use ($token, $attributes, $authenticated): User {
            $invitation = $this->findUsable($token);
            $user = $authenticated ?? User::query()->where('email', $invitation->email)->first();
            if ($user && strtolower($user->email) !== strtolower($invitation->email)) {
                throw ValidationException::withMessages(['email' => 'Connectez-vous avec l’adresse invitée.']);
            }
            if (! $user) {
                $user = User::query()->create([
                    'first_name' => $attributes['first_name'], 'last_name' => $attributes['last_name'],
                    'name' => trim($attributes['first_name'].' '.$attributes['last_name']),
                    'email' => $invitation->email, 'password' => $attributes['password'],
                    'status' => UserStatus::Active, 'email_verified_at' => now(),
                ]);
                event(new Verified($user));
            }
            $membership = OrganizationMembership::query()->firstOrCreate(
                ['organization_id' => $invitation->organization_id, 'user_id' => $user->getKey()],
                ['status' => MembershipStatus::Active, 'joined_at' => now()],
            );
            $membership->update(['status' => MembershipStatus::Active, 'joined_at' => $membership->joined_at ?? now()]);
            $this->authorizer->syncRoles($membership, [$invitation->role_id]);
            $invitation->forceFill(['accepted_at' => now()])->save();
            $this->audit->record('invitation.accepted', $user, $invitation->organization, $membership, ['invitation_uuid' => $invitation->uuid]);

            return $user;
        });
    }
}
