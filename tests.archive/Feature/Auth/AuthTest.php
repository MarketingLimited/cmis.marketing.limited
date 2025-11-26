<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that login requires correct password (CRITICAL SECURITY FIX).
     *
     * @return void
     */
    public function test_login_requires_correct_password()
    {
        // Create a test user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
            'status' => 'active',
        ]);

        // Test with wrong password - should fail
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
        $this->assertStringContainsString('credentials are incorrect', $response->json('errors.email')[0]);

        // Test with correct password - should succeed
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'token_type',
                'user'
            ]
        ]);
        $this->assertTrue($response->json('success'));
    }

    /**
     * Test that login fails with non-existent email.
     *
     * @return void
     */
    public function test_login_fails_with_nonexistent_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'any-password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test that login fails for inactive users.
     *
     * @return void
     */
    public function test_login_fails_for_inactive_users()
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'status' => 'inactive',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Your account is not active. Please contact support.'
        ]);
    }

    /**
     * Test that login validates required fields.
     *
     * @return void
     */
    public function test_login_validates_required_fields()
    {
        // Missing email
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);

        // Missing password
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);

        // Invalid email format
        $response = $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test successful registration.
     *
     * @return void
     */
    public function test_registration_creates_user_and_returns_token()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user',
                'token',
                'token_type'
            ]
        ]);

        $this->assertDatabaseHas('cmis.users', [
            'email' => 'newuser@example.com',
            'name' => 'Test User',
        ]);
    }

    /**
     * Test registration with organization creation.
     *
     * @return void
     */
    public function test_registration_with_org_creates_organization()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'orguser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'org_name' => 'Test Organization',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('cmis.orgs', [
            'name' => 'Test Organization',
        ]);

        $user = User::where('email', 'orguser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertCount(1, $user->orgs);
    }

    /**
     * Test token refresh functionality.
     *
     * @return void
     */
    public function test_token_refresh_works()
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        // Refresh token
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/refresh');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'token_type',
                'expires_in'
            ]
        ]);

        $newToken = $response->json('data.token');
        $this->assertNotEquals($token, $newToken);

        // Old token should no longer work
        $oldTokenResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me');
        $oldTokenResponse->assertStatus(401);

        // New token should work
        $newTokenResponse = $this->withHeader('Authorization', 'Bearer ' . $newToken)
            ->getJson('/api/auth/me');
        $newTokenResponse->assertStatus(200);
    }

    /**
     * Test token refresh requires authentication.
     *
     * @return void
     */
    public function test_token_refresh_requires_authentication()
    {
        $response = $this->postJson('/api/auth/refresh');
        $response->assertStatus(401);
    }

    /**
     * Test logout revokes current token.
     *
     * @return void
     */
    public function test_logout_revokes_current_token()
    {
        $user = User::factory()->create(['status' => 'active']);
        $token = $user->createToken('test-token')->plainTextToken;

        // Logout
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);

        // Token should no longer work
        $meResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me');
        $meResponse->assertStatus(401);
    }

    /**
     * Test logout all revokes all tokens.
     *
     * @return void
     */
    public function test_logout_all_revokes_all_tokens()
    {
        $user = User::factory()->create(['status' => 'active']);

        $token1 = $user->createToken('token-1')->plainTextToken;
        $token2 = $user->createToken('token-2')->plainTextToken;

        // Logout all using first token
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/auth/logout-all');

        $response->assertStatus(200);

        // Both tokens should no longer work
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->getJson('/api/auth/me');
        $response1->assertStatus(401);

        $response2 = $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->getJson('/api/auth/me');
        $response2->assertStatus(401);
    }

    /**
     * Test getting current user profile.
     *
     * @return void
     */
    public function test_authenticated_user_can_get_profile()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'user' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ]
            ]
        ]);
    }

    /**
     * Test updating user profile.
     *
     * @return void
     */
    public function test_authenticated_user_can_update_profile()
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/auth/profile', [
                'name' => 'Updated Name',
                'display_name' => 'Updated Display',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);

        $this->assertDatabaseHas('cmis.users', [
            'user_id' => $user->user_id,
            'name' => 'Updated Name',
            'display_name' => 'Updated Display',
        ]);
    }

    /**
     * Test that password is hashed during registration.
     *
     * @return void
     */
    public function test_password_is_hashed_during_registration()
    {
        $plainPassword = 'password123';

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'hashtest@example.com',
            'password' => $plainPassword,
            'password_confirmation' => $plainPassword,
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'hashtest@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
    }
}
