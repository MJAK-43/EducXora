<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserInvitation;
use App\Services\Audit\AuditLogger;
use App\Services\Invitations\InvitationService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\Concerns\BuildsPhaseTwoTenancy;
use Tests\TestCase;

final class InvitationAndAuditTest extends TestCase
{
    use BuildsPhaseTwoTenancy;
    use RefreshDatabase;

    public function test_invitation_token_is_hashed_expires_and_is_single_use(): void
    {
        [$organization, $inviter] = $this->tenantWithUser();
        app(TenantContext::class)->set($organization);
        $token = 'known-secret-token';
        UserInvitation::query()->create([
            'email' => 'new.member@example.test', 'token_hash' => hash('sha256', $token),
            'role_id' => $organization->roles()->where('name', 'Staff')->value('id'),
            'expires_at' => now()->addHour(), 'invited_by' => $inviter->getKey(),
        ]);
        self::assertDatabaseMissing('user_invitations', ['token_hash' => $token]);
        $user = app(InvitationService::class)->accept($token, [
            'first_name' => 'New', 'last_name' => 'Member', 'password' => 'VerySecure!42',
        ]);
        self::assertInstanceOf(User::class, $user);
        self::assertDatabaseHas('organization_user', ['organization_id' => $organization->getKey(), 'user_id' => $user->getKey()]);
        $this->expectException(ValidationException::class);
        app(InvitationService::class)->accept($token, [], $user);
    }

    public function test_expired_invitation_is_rejected(): void
    {
        [$organization, $inviter] = $this->tenantWithUser();
        app(TenantContext::class)->set($organization);
        UserInvitation::query()->create([
            'email' => 'late@example.test', 'token_hash' => hash('sha256', 'expired'),
            'role_id' => $organization->roles()->where('name', 'Staff')->value('id'),
            'expires_at' => now()->subMinute(), 'invited_by' => $inviter->getKey(),
        ]);
        $this->expectException(ValidationException::class);
        app(InvitationService::class)->findUsable('expired');
    }

    public function test_audit_logs_are_append_only_and_never_contain_passwords(): void
    {
        [$organization, $user] = $this->tenantWithUser();
        $log = app(AuditLogger::class)->record('test.action', $user, $organization, metadata: ['password' => 'secret', 'safe' => 'value']);
        self::assertSame(['safe' => 'value'], $log->metadata);
        $this->expectException(LogicException::class);
        $log->delete();
    }
}
