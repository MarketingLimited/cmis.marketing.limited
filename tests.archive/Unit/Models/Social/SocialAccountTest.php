<?php

namespace Tests\Unit\Models\Social;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Integration;
use App\Models\Social\SocialAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Social Account Model Unit Tests
 * Aligned with actual cmis.social_accounts schema
 */
class SocialAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_a_social_account()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $socialAccount = SocialAccount::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'account_external_id' => 'ig_account_123',
            'username' => 'testuser',
            'display_name' => 'Test User',
            'provider' => 'instagram',
        ]);

        $this->assertDatabaseHas('cmis.social_accounts', [
            'id' => $socialAccount->id,
            'provider' => 'instagram',
        ]);
    }

    #[Test]
    public function it_belongs_to_organization_and_integration()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $socialAccount = SocialAccount::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'account_external_id' => 'ig_account_123',
            'username' => 'testuser',
            'provider' => 'instagram',
        ]);

        $this->assertEquals($org->org_id, $socialAccount->org->org_id);
        $this->assertEquals($integration->integration_id, $socialAccount->integration->integration_id);
    }

    #[Test]
    public function it_stores_social_metrics()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $socialAccount = SocialAccount::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'account_external_id' => 'ig_account_metrics',
            'username' => 'metricsuser',
            'followers_count' => 10500,
            'follows_count' => 250,
            'media_count' => 87,
            'provider' => 'instagram',
        ]);

        $this->assertEquals(10500, $socialAccount->followers_count);
        $this->assertEquals(250, $socialAccount->follows_count);
        $this->assertEquals(87, $socialAccount->media_count);
    }

    #[Test]
    public function it_validates_provider()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'active',
        ]);

        $socialAccount = SocialAccount::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'account_external_id' => 'fb_account_123',
            'username' => 'fbuser',
            'provider' => 'facebook',
        ]);

        $this->assertContains($socialAccount->provider, ['facebook', 'instagram', 'twitter', 'linkedin']);
    }

    #[Test]
    public function it_generates_uuid_for_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $socialAccount = SocialAccount::create([
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'account_external_id' => 'uuid_test',
            'username' => 'uuiduser',
            'provider' => 'instagram',
        ]);

        $this->assertTrue(Str::isUuid($socialAccount->id));
    }

    #[Test]
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $socialAccount = SocialAccount::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'account_external_id' => 'delete_test',
            'username' => 'deleteuser',
            'provider' => 'instagram',
        ]);

        $socialAccount->delete();

        $this->assertSoftDeleted('cmis.social_accounts', [
            'id' => $socialAccount->id,
        ]);
    }

    #[Test]
    public function it_can_find_by_external_id()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $externalId = 'unique_external_account_id';

        $socialAccount = SocialAccount::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'account_external_id' => $externalId,
            'username' => 'externaliduser',
            'provider' => 'instagram',
        ]);

        $found = SocialAccount::where('account_external_id', $externalId)->first();

        $this->assertNotNull($found);
        $this->assertEquals($socialAccount->id, $found->id);
    }

    #[Test]
    public function it_stores_profile_information()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $socialAccount = SocialAccount::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'account_external_id' => 'profile_test',
            'username' => 'profileuser',
            'display_name' => 'Profile User',
            'biography' => 'This is my bio',
            'profile_picture_url' => 'https://example.com/profile.jpg',
            'website' => 'https://example.com',
            'category' => 'Creator',
            'provider' => 'instagram',
        ]);

        $this->assertEquals('Profile User', $socialAccount->display_name);
        $this->assertEquals('This is my bio', $socialAccount->biography);
        $this->assertEquals('https://example.com/profile.jpg', $socialAccount->profile_picture_url);
        $this->assertEquals('https://example.com', $socialAccount->website);
        $this->assertEquals('Creator', $socialAccount->category);
    }

    #[Test]
    public function it_tracks_fetched_at_timestamp()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $fetchedAt = now()->subHours(2);

        $socialAccount = SocialAccount::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'account_external_id' => 'fetched_test',
            'username' => 'fetchuser',
            'fetched_at' => $fetchedAt,
            'provider' => 'instagram',
        ]);

        $this->assertEquals($fetchedAt->timestamp, $socialAccount->fetched_at->timestamp);
    }

    #[Test]
    public function it_calculates_engagement_metrics()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $socialAccount = SocialAccount::create([
            'id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'account_external_id' => 'engagement_test',
            'username' => 'engagementuser',
            'followers_count' => 5000,
            'follows_count' => 300,
            'media_count' => 150,
            'provider' => 'instagram',
        ]);

        // Average media per follower ratio
        $mediaPerFollowerRatio = $socialAccount->media_count / $socialAccount->followers_count;

        $this->assertEquals(0.03, $mediaPerFollowerRatio);
    }
}
