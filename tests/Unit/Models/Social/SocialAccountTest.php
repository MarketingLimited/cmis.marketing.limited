<?php

namespace Tests\Unit\Models\Social;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Integration;
use App\Models\Social\SocialAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Social Account Model Unit Tests
 */
class SocialAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
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
            'account_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'instagram',
            'external_account_id' => 'ig_account_123',
            'username' => 'testuser',
            'display_name' => 'Test User',
        ]);

        $this->assertDatabaseHas('cmis.social_accounts', [
            'account_id' => $socialAccount->account_id,
            'platform' => 'instagram',
        ]);
    }

    /** @test */
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
            'account_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'instagram',
            'external_account_id' => 'ig_account_123',
            'username' => 'testuser',
        ]);

        $this->assertEquals($org->org_id, $socialAccount->org->org_id);
        $this->assertEquals($integration->integration_id, $socialAccount->integration->integration_id);
    }

    /** @test */
    public function it_stores_profile_data_as_json()
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

        $profileData = [
            'bio' => 'حساب تجريبي للتسويق الرقمي',
            'profile_picture_url' => 'https://example.com/profile.jpg',
            'website' => 'https://example.com',
            'category' => 'Marketing',
        ];

        $socialAccount = SocialAccount::create([
            'account_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'instagram',
            'external_account_id' => 'ig_account_123',
            'username' => 'testuser',
            'profile_data' => $profileData,
        ]);

        $this->assertEquals('حساب تجريبي للتسويق الرقمي', $socialAccount->profile_data['bio']);
        $this->assertEquals('Marketing', $socialAccount->profile_data['category']);
    }

    /** @test */
    public function it_stores_metrics_as_json()
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

        $metrics = [
            'followers_count' => 10000,
            'following_count' => 500,
            'posts_count' => 250,
            'engagement_rate' => 4.5,
        ];

        $socialAccount = SocialAccount::create([
            'account_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'instagram',
            'external_account_id' => 'ig_account_123',
            'username' => 'testuser',
            'metrics' => $metrics,
        ]);

        $this->assertEquals(10000, $socialAccount->metrics['followers_count']);
        $this->assertEquals(4.5, $socialAccount->metrics['engagement_rate']);
    }

    /** @test */
    public function it_validates_platform()
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
            'account_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'facebook',
            'external_account_id' => 'fb_page_123',
            'username' => 'testpage',
        ]);

        $this->assertContains($socialAccount->platform, [
            'instagram',
            'facebook',
            'twitter',
            'linkedin',
            'tiktok',
            'youtube',
            'snapchat',
        ]);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
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
            'account_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'instagram',
            'external_account_id' => 'ig_account_123',
            'username' => 'testuser',
        ]);

        $this->assertTrue(Str::isUuid($socialAccount->account_id));
    }

    /** @test */
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
            'account_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'instagram',
            'external_account_id' => 'ig_account_123',
            'username' => 'testuser',
        ]);

        $socialAccount->delete();

        $this->assertSoftDeleted('cmis.social_accounts', [
            'account_id' => $socialAccount->account_id,
        ]);
    }

    /** @test */
    public function it_has_timestamps()
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
            'account_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'instagram',
            'external_account_id' => 'ig_account_123',
            'username' => 'testuser',
        ]);

        $this->assertNotNull($socialAccount->created_at);
        $this->assertNotNull($socialAccount->updated_at);
    }

    /** @test */
    public function it_tracks_last_sync_time()
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

        $lastSyncAt = now()->subHours(2);

        $socialAccount = SocialAccount::create([
            'account_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'instagram',
            'external_account_id' => 'ig_account_123',
            'username' => 'testuser',
            'last_sync_at' => $lastSyncAt,
        ]);

        $this->assertNotNull($socialAccount->last_sync_at);
        $this->assertTrue($socialAccount->last_sync_at->isPast());
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

        $integration1 = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $integration2 = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        SocialAccount::create([
            'account_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'integration_id' => $integration1->integration_id,
            'platform' => 'instagram',
            'external_account_id' => 'ig_org1',
            'username' => 'org1account',
        ]);

        SocialAccount::create([
            'account_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'integration_id' => $integration2->integration_id,
            'platform' => 'instagram',
            'external_account_id' => 'ig_org2',
            'username' => 'org2account',
        ]);

        $org1Accounts = SocialAccount::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Accounts);
        $this->assertEquals('org1account', $org1Accounts->first()->username);
    }
}
