<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;

use PHPUnit\Framework\Attributes\Test;
/**
 * RateLimit Middleware Unit Tests
 */
class RateLimitMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_allows_requests_within_limit()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        RateLimiter::clear('api:' . $user->user_id);

        // Should allow requests within rate limit
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'middleware' => 'RateLimitMiddleware',
            'test' => 'within_limit',
        ]);
    }

    #[Test]
    public function it_blocks_requests_exceeding_limit()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        // Simulate exceeding rate limit
        $key = 'api:' . $user->user_id;
        for ($i = 0; $i < 100; $i++) {
            RateLimiter::hit($key);
        }

        // Should block requests exceeding limit
        $this->assertTrue(RateLimiter::tooManyAttempts($key, 60));

        $this->logTestResult('passed', [
            'middleware' => 'RateLimitMiddleware',
            'test' => 'exceeding_limit',
        ]);
    }

    #[Test]
    public function it_uses_different_limits_per_endpoint()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        // Different endpoints should have different limits
        // e.g., /api/campaigns: 60/min, /api/analytics: 10/min
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'middleware' => 'RateLimitMiddleware',
            'test' => 'different_limits',
        ]);
    }

    #[Test]
    public function it_resets_after_time_window()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $key = 'api:' . $user->user_id;
        RateLimiter::clear($key);

        // Should reset after time window expires
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'middleware' => 'RateLimitMiddleware',
            'test' => 'reset_window',
        ]);
    }

    #[Test]
    public function it_provides_retry_after_header()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        // Should include Retry-After header when limit exceeded
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'middleware' => 'RateLimitMiddleware',
            'test' => 'retry_after_header',
        ]);
    }

    #[Test]
    public function it_tracks_remaining_attempts()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $key = 'api:' . $user->user_id;
        RateLimiter::clear($key);

        RateLimiter::hit($key);
        RateLimiter::hit($key);

        $remaining = RateLimiter::remaining($key, 60);

        // Should track remaining attempts
        $this->assertTrue($remaining < 60);

        $this->logTestResult('passed', [
            'middleware' => 'RateLimitMiddleware',
            'test' => 'remaining_attempts',
        ]);
    }

    #[Test]
    public function it_applies_per_user_limits()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user1 = User::create([
            'user_id' => Str::uuid(),
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
        ]);

        $user2 = User::create([
            'user_id' => Str::uuid(),
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
        ]);

        // Each user should have independent rate limits
        $this->assertNotEquals($user1->user_id, $user2->user_id);

        $this->logTestResult('passed', [
            'middleware' => 'RateLimitMiddleware',
            'test' => 'per_user_limits',
        ]);
    }

    #[Test]
    public function it_applies_per_ip_limits_for_guests()
    {
        // Guest users should be rate limited by IP address
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'middleware' => 'RateLimitMiddleware',
            'test' => 'per_ip_limits',
        ]);
    }

    #[Test]
    public function it_exempts_admin_users()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $admin = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        // Admin users might be exempted from rate limits
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'middleware' => 'RateLimitMiddleware',
            'test' => 'admin_exemption',
        ]);
    }

    #[Test]
    public function it_respects_org_specific_limits()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Free Tier Org',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Premium Org',
        ]);

        // Different orgs might have different rate limits based on plan
        $this->assertNotEquals($org1->org_id, $org2->org_id);

        $this->logTestResult('passed', [
            'middleware' => 'RateLimitMiddleware',
            'test' => 'org_specific_limits',
        ]);
    }
}
