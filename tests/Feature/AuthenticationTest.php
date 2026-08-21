<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_atomically_creates_identity_organization_and_admin_membership(): void
    {
        Notification::fake();
        $response = $this->post('/register', [
            'organization_name' => 'Institut Horizon', 'first_name' => 'Amina', 'last_name' => 'Njoya',
            'email' => 'amina@example.test', 'phone' => '+237600000001',
            'password' => 'VerySecure!42', 'password_confirmation' => 'VerySecure!42',
        ]);

        $response->assertRedirect('/verify-email');
        $user = User::query()->where('email', 'amina@example.test')->sole();
        $organization = Organization::query()->where('name', 'Institut Horizon')->sole();
        $membership = OrganizationMembership::query()->whereBelongsTo($user)->whereBelongsTo($organization)->sole();
        self::assertNotEmpty($user->uuid);
        self::assertNotEmpty($organization->uuid);
        self::assertTrue(Hash::check('VerySecure!42', $user->password));
        self::assertSame(['Organization Admin'], $membership->roles()->pluck('name')->all());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_login_regenerates_session_and_rejects_inactive_users_generically(): void
    {
        $active = User::factory()->create(['email' => 'active@example.test', 'password' => 'VerySecure!42']);
        $this->post('/login', ['email' => $active->email, 'password' => 'VerySecure!42'])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('authenticated_at');
        $this->post('/logout')->assertRedirect('/login');

        $inactive = User::factory()->create(['email' => 'inactive@example.test', 'password' => 'VerySecure!42', 'status' => UserStatus::Inactive]);
        $this->post('/login', ['email' => $inactive->email, 'password' => 'VerySecure!42'])
            ->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_password_reset_request_does_not_disclose_account_existence(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $message = 'Si ce compte existe, un lien de réinitialisation a été envoyé.';
        $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status', $message);
        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'absent@example.test'])->assertSessionHas('status', $message);
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_absolute_session_lifetime_is_enforced(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['authenticated_at' => now()->subDay()->subSecond()->timestamp])
            ->get('/organizations/select')->assertRedirect('/login');
        $this->assertGuest();
    }
}
