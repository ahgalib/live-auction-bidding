<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login(): void
    {
        $register = $this->postJson('/api/register', [
            'name' => 'Galib',
            'email' => 'galib@example.com',
            'password' => 'password123',
        ]);

        $register->assertCreated()
            ->assertJsonPath('user.email', 'galib@example.com');

        $login = $this->postJson('/api/login', [
            'email' => 'galib@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk()
            ->assertJsonPath('token.token_type', 'Bearer');
    }

    public function test_oauth_password_grant_issues_access_token(): void
    {
        User::factory()->create([
            'email' => 'naimur@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/oauth/token', [
            'grant_type' => 'password',
            'client_id' => 1,
            'client_secret' => 'dev-client-secret-change-me',
            'username' => 'naimur@example.com',
            'password' => 'password123',
            'scope' => 'bid:write auction:read',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer');
    }

    public function test_authenticated_user_can_access_me_and_logout_revokes_token(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $tokenResponse = $this->postJson('/api/oauth/token', [
            'grant_type' => 'password',
            'client_id' => 1,
            'client_secret' => 'dev-client-secret-change-me',
            'username' => 'admin@example.com',
            'password' => 'password123',
        ])->assertOk();

        $token = $tokenResponse->json('access_token');
        $tokenId = hash('sha256', $token);

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('email', 'admin@example.com');

        $this->withToken($token)->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $tokenId,
            'revoked' => true,
        ]);
    }
}
