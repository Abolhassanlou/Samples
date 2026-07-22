<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Fatemeh',
            'last_name' => 'Abolhassanlou',
            'preferred_name' => 'Hellen',
            'email' => 'hellen@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'device_name' => 'phpunit',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.user.email',
                'hellen@example.com'
            )
            ->assertJsonPath(
                'data.user.first_name',
                'Fatemeh'
            )
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'first_name',
                        'last_name',
                        'preferred_name',
                        'email',
                    ],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'hellen@example.com',
            'first_name' => 'Fatemeh',
            'last_name' => 'Abolhassanlou',
        ]);

        $user = User::query()
            ->where('email', 'hellen@example.com')
            ->firstOrFail();

        $this->assertTrue(
            Hash::check('Password123', $user->password)
        );

        $this->assertNotSame(
            'Password123',
            $user->password
        );
    }

    public function test_registration_requires_valid_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => '',
            'last_name' => '',
            'email' => 'not-an-email',
            'password' => 'weak',
            'password_confirmation' => 'different',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'email',
                'password',
            ]);
    }

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'hellen@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'hellen@example.com',
            'password' => 'Password123',
            'device_name' => 'phpunit',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Login successful.'
            )
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'token',
                ],
            ]);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'hellen@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'hellen@example.com',
            'password' => 'WrongPassword123',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);
    }

    public function test_authenticated_user_can_view_their_profile(): void
    {
        $user = User::factory()->create();

        $token = $user
            ->createToken('phpunit')
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.user.id',
                $user->id
            )
            ->assertJsonPath(
                'data.user.email',
                $user->email
            );
    }

    public function test_unauthenticated_user_cannot_view_profile(): void
    {
        $this
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $newToken = $user->createToken('phpunit');
        $plainTextToken = $newToken->plainTextToken;
        $tokenId = $newToken->accessToken->id;

        $this
            ->withToken($plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Logout successful.'
            );

        $this->assertDatabaseMissing(
            'personal_access_tokens',
            [
                'id' => $tokenId,
            ]
        );
        $this->app['auth']->forgetGuards();
        $this
            ->withToken($plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }
}
