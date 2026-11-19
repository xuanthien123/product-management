<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_and_revoke_their_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'secret-password',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'secret-password',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonMissingPath('data.user.password');

        $token = $response->json('data.token');
        $this->assertIsString($token);

        $this->withToken($token)->getJson('/api/products')->assertOk();
        $this->withToken($token)->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'secret-password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'incorrect-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_login_requires_an_email_and_password(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_current_user_endpoint_reports_non_admin_role(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('user')->plainTextToken)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.is_admin', false)
            ->assertJsonPath('data.user.email', $user->email);
    }

    public function test_current_user_endpoint_reports_admin_role(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $admin->roles()->attach($adminRole);

        $this->assertDatabaseHas('role_user', [
            'user_id' => $admin->id,
            'role_id' => $adminRole->id,
        ]);

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.is_admin', true);
    }

    public function test_current_user_endpoint_returns_personal_details(): void
    {
        $user = User::factory()->create([
            'address' => '123 Example Street',
            'date_of_birth' => '1990-04-12',
            'sex' => 'female',
        ]);

        $response = $this->withToken($user->createToken('user')->plainTextToken)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.user.address', '123 Example Street')
            ->assertJsonPath('data.user.sex', 'female')
            ->assertJsonMissingPath('data.user.password');

        $this->assertSame(
            '1990-04-12',
            Carbon::parse($response->json('data.user.date_of_birth'))->toDateString(),
        );
    }

    public function test_authenticated_user_can_update_their_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->withToken($user->createToken('user')->plainTextToken)
            ->putJson('/api/update-profile', [
                'name' => 'Updated Name',
                'address' => '456 New Street',
                'date_of_birth' => '1995-06-20',
                'sex' => 'female',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.address', '456 New Street')
            ->assertJsonPath('data.sex', 'female')
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'address' => '456 New Street',
            'sex' => 'female',
        ]);
        $this->assertSame(
            '1995-06-20',
            Carbon::parse($response->json('data.date_of_birth'))->toDateString(),
        );
    }

    public function test_forgot_password_does_not_reveal_if_an_account_exists(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'If the account exists, a password reset link will be sent.');

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use ($user): bool {
                $url = $notification->toMail($user)->actionUrl;
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

                if (! isset($query['token']) || isset($query['email'])) {
                    return false;
                }

                $resetData = json_decode(
                    Crypt::decryptString($query['token']),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );

                return parse_url($url, PHP_URL_PATH) === '/reset-password'
                    && $resetData === [
                        'email' => $user->email,
                        'token' => $notification->token,
                    ];
            },
        );

        $this->postJson('/api/forgot-password', ['email' => 'unknown@example.com'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'If the account exists, a password reset link will be sent.');
    }

    public function test_password_can_be_reset_and_existing_access_tokens_are_revoked(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $token = Password::createToken($user);
        $user->createToken('existing-session');

        $this->postJson('/api/reset-password', [
            'token' => $this->makeResetLinkToken($user, $token),
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Password reset successfully.');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_invalid_password_reset_token_returns_unprocessable_response(): void
    {
        $user = User::factory()->create();
        Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $this->makeResetLinkToken($user, 'invalid-token'),
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The password reset link is invalid or expired.');
    }

    public function test_tampered_password_reset_link_token_is_rejected(): void
    {
        $this->postJson('/api/reset-password', [
            'token' => 'tampered-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The password reset link is invalid or expired.');
    }

    public function test_authenticated_user_can_change_password_and_other_tokens_are_revoked(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $currentAccessToken = $user->createToken('current-session');
        $otherAccessToken = $user->createToken('other-session');

        $this->withToken($currentAccessToken->plainTextToken)
            ->putJson('/api/password', [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Password changed successfully.');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $currentAccessToken->accessToken->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otherAccessToken->accessToken->id]);

        Auth::forgetGuards();
        $this->withToken($currentAccessToken->plainTextToken)->getJson('/api/me')->assertOk();
        Auth::forgetGuards();
        $this->withToken($otherAccessToken->plainTextToken)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_password_change_rejects_an_incorrect_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $token = $user->createToken('current-session')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/password', [
                'current_password' => 'incorrect-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Current password is incorrect.');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    private function makeResetLinkToken(User $user, string $passwordBrokerToken): string
    {
        return Crypt::encryptString(json_encode([
            'email' => $user->email,
            'token' => $passwordBrokerToken,
        ], JSON_THROW_ON_ERROR));
    }
}
