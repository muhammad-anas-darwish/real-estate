<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Entities\OtpCode;
use Modules\Auth\Entities\User;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Notifications\OtpNotification;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_otp_returns_user_id_on_valid_credentials()
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        Notification::fake();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['user_id']]);
        $this->assertDatabaseHas('otp_codes', [
            'user_id' => $user->id,
            'purpose' => OtpPurpose::LOGIN->value,
        ]);
    }

    public function test_send_otp_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_send_otp_fails_with_missing_fields()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_send_otp_rate_limits()
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertStatus(200);
        }

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(429);
    }

    public function test_verify_otp_returns_token_on_valid_code()
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $otp = OtpCode::create([
            'user_id' => $user->id,
            'code' => '123456',
            'purpose' => OtpPurpose::LOGIN,
            'expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'user_id' => $user->id,
            'code' => '123456',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['token', 'user']]);
        $this->assertDatabaseHas('otp_codes', [
            'id' => $otp->id,
            'verified_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_verify_otp_fails_with_invalid_code()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/verify-otp', [
            'user_id' => $user->id,
            'code' => '000000',
        ]);

        $response->assertStatus(422);
    }

    public function test_verify_otp_fails_with_expired_code()
    {
        $user = User::factory()->create();

        OtpCode::create([
            'user_id' => $user->id,
            'code' => '123456',
            'purpose' => OtpPurpose::LOGIN,
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'user_id' => $user->id,
            'code' => '123456',
        ]);

        $response->assertStatus(422);
    }

    public function test_verify_otp_fails_with_nonexistent_user()
    {
        $response = $this->postJson('/api/auth/verify-otp', [
            'user_id' => 9999,
            'code' => '123456',
        ]);

        $response->assertStatus(422);
    }

    public function test_send_otp_sends_notification()
    {
        Notification::fake();
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        Notification::assertSentTo($user, OtpNotification::class);
    }

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['token', 'user']]);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/logout');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_user_can_get_own_info()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJsonPath('id', $user->id);
    }

    public function test_user_info_requires_auth()
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }

    public function test_logout_requires_auth()
    {
        $response = $this->postJson('/api/auth/logout');
        $response->assertStatus(401);
    }

    public function test_user_can_request_password_reset_link()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);
    }

    public function test_password_reset_link_fails_for_nonexistent_email()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_update_password()
    {
        $user = User::factory()->create(['password' => bcrypt('currentpass')]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/auth/user/password', [
                'current_password' => 'currentpass',
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ]);

        $response->assertStatus(200);
    }

    public function test_register_validates_required_fields()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_validates_unique_email()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Another User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }
}
