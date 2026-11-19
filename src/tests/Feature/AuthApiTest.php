<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
