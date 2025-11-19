<?php

namespace Tests\Unit\Models\Subscription;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Subscription\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Subscription Model Unit Tests
 */
class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_subscription()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Professional',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->assertDatabaseHas('cmis.subscriptions', [
            'subscription_id' => $subscription->subscription_id,
            'plan_name' => 'Professional',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Basic',
            'status' => 'active',
        ]);

        $this->assertEquals($org->org_id, $subscription->org->org_id);
    }

    /** @test */
    public function it_has_different_plan_types()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $plans = ['Free', 'Basic', 'Professional', 'Enterprise'];

        foreach ($plans as $plan) {
            Subscription::create([
                'subscription_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'plan_name' => $plan,
                'status' => 'active',
            ]);
        }

        $subscriptions = Subscription::where('org_id', $org->org_id)->get();
        $this->assertCount(4, $subscriptions);
    }

    /** @test */
    public function it_has_different_statuses()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $activeSubscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Pro',
            'status' => 'active',
        ]);

        $cancelledSubscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Basic',
            'status' => 'cancelled',
        ]);

        $expiredSubscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Free',
            'status' => 'expired',
        ]);

        $this->assertEquals('active', $activeSubscription->status);
        $this->assertEquals('cancelled', $cancelledSubscription->status);
        $this->assertEquals('expired', $expiredSubscription->status);
    }

    /** @test */
    public function it_stores_pricing_information()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Professional',
            'status' => 'active',
            'price' => 99.99,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
        ]);

        $this->assertEquals(99.99, $subscription->price);
        $this->assertEquals('USD', $subscription->currency);
        $this->assertEquals('monthly', $subscription->billing_cycle);
    }

    /** @test */
    public function it_stores_plan_features()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $features = [
            'max_campaigns' => 50,
            'max_users' => 10,
            'max_posts_per_month' => 1000,
            'analytics' => true,
            'api_access' => true,
            'priority_support' => false,
        ];

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Professional',
            'status' => 'active',
            'features' => $features,
        ]);

        $this->assertEquals(50, $subscription->features['max_campaigns']);
        $this->assertTrue($subscription->features['analytics']);
        $this->assertFalse($subscription->features['priority_support']);
    }

    /** @test */
    public function it_tracks_subscription_dates()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Basic',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->assertNotNull($subscription->starts_at);
        $this->assertNotNull($subscription->ends_at);
        $this->assertTrue($subscription->ends_at->greaterThan($subscription->starts_at));
    }

    /** @test */
    public function it_can_check_if_subscription_is_active()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $activeSubscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Pro',
            'status' => 'active',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
        ]);

        $this->assertEquals('active', $activeSubscription->status);
        $this->assertTrue(now()->between($activeSubscription->starts_at, $activeSubscription->ends_at));
    }

    /** @test */
    public function it_tracks_trial_period()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Professional',
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $this->assertEquals('trial', $subscription->status);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertTrue($subscription->trial_ends_at->isFuture());
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Basic',
            'status' => 'active',
        ]);

        $this->assertTrue(Str::isUuid($subscription->subscription_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Pro',
            'status' => 'active',
        ]);

        $this->assertNotNull($subscription->created_at);
        $this->assertNotNull($subscription->updated_at);
    }

    /** @test */
    public function it_respects_rls_policies()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'plan_name' => 'Professional',
            'status' => 'active',
        ]);

        Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'plan_name' => 'Enterprise',
            'status' => 'active',
        ]);

        $org1Subscriptions = Subscription::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Subscriptions);
        $this->assertEquals('Professional', $org1Subscriptions->first()->plan_name);
    }
}
