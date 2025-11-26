<?php

namespace Tests\Integration;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\InteractsWithRLS;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\Campaign;
use App\Models\Social\SocialPost;
use App\Models\CreativeAsset;
use PHPUnit\Framework\Attributes\Test;

/**
 * Multi-Tenancy Integration Tests
 *
 * Verifies Row-Level Security (RLS) and data isolation between organizations.
 */
class MultiTenancyTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, InteractsWithRLS;

    #[Test]
    public function it_enforces_rls_for_campaigns()
    {
        // Create two organizations with campaigns
        $org1 = $this->createUserWithOrg();
        $org2 = $this->createUserWithOrg();

        // Set context for org1 and create campaign
        $this->initTransactionContext($org1['user']->user_id, $org1['org']->org_id);
        $campaign1 = Campaign::create([
            'campaign_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $org1['org']->org_id,
            'name' => 'Org1 Campaign',
            'objective' => 'awareness',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'budget' => 1000,
            'currency' => 'BHD',
        ]);

        // Set context for org2 and create campaign
        $this->clearTransactionContext();
        $this->initTransactionContext($org2['user']->user_id, $org2['org']->org_id);
        $campaign2 = Campaign::create([
            'campaign_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $org2['org']->org_id,
            'name' => 'Org2 Campaign',
            'objective' => 'conversions',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'budget' => 2000,
            'currency' => 'USD',
        ]);

        // Verify org1 can only see its campaign
        $this->clearTransactionContext();
        $this->initTransactionContext($org1['user']->user_id, $org1['org']->org_id);
        $org1Campaigns = Campaign::all();
        $this->assertCount(1, $org1Campaigns);
        $this->assertEquals('Org1 Campaign', $org1Campaigns->first()->name);

        // Verify org2 can only see its campaign
        $this->clearTransactionContext();
        $this->initTransactionContext($org2['user']->user_id, $org2['org']->org_id);
        $org2Campaigns = Campaign::all();
        $this->assertCount(1, $org2Campaigns);
        $this->assertEquals('Org2 Campaign', $org2Campaigns->first()->name);

        $this->clearTransactionContext();

        $this->logTestResult('passed', [
            'test' => 'RLS campaign isolation',
            'org1_campaigns' => 1,
            'org2_campaigns' => 1,
        ]);
    }

    #[Test]
    public function it_prevents_cross_org_data_access()
    {
        $org1 = $this->createUserWithOrg();
        $org2 = $this->createUserWithOrg();

        // Create campaign in org1
        $this->initTransactionContext($org1['user']->user_id, $org1['org']->org_id);
        $campaign = Campaign::create([
            'campaign_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $org1['org']->org_id,
            'name' => 'Secret Campaign',
            'objective' => 'awareness',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'budget' => 5000,
            'currency' => 'BHD',
        ]);

        // Try to access from org2 context
        $this->clearTransactionContext();
        $this->initTransactionContext($org2['user']->user_id, $org2['org']->org_id);

        // Should not find the campaign
        $foundCampaign = Campaign::where('campaign_id', $campaign->campaign_id)->first();
        $this->assertNull($foundCampaign);

        $this->clearTransactionContext();

        $this->logTestResult('passed', [
            'test' => 'Cross-org access prevention',
            'campaign_id' => $campaign->campaign_id,
        ]);
    }

    #[Test]
    public function it_isolates_social_posts_by_organization()
    {
        $org1 = $this->createUserWithOrg();
        $org2 = $this->createUserWithOrg();

        // Create posts for both orgs
        $this->initTransactionContext($org1['user']->user_id, $org1['org']->org_id);
        $integration1 = $this->createTestIntegration($org1['org']->org_id);
        $post1 = SocialPost::create([
            'post_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $org1['org']->org_id,
            'integration_id' => $integration1->integration_id,
            'platform' => 'facebook',
            'content' => 'Org1 post',
            'status' => 'published',
        ]);

        $this->clearTransactionContext();
        $this->initTransactionContext($org2['user']->user_id, $org2['org']->org_id);
        $integration2 = $this->createTestIntegration($org2['org']->org_id);
        $post2 = SocialPost::create([
            'post_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $org2['org']->org_id,
            'integration_id' => $integration2->integration_id,
            'platform' => 'instagram',
            'content' => 'Org2 post',
            'status' => 'published',
        ]);

        // Verify isolation
        $this->clearTransactionContext();
        $this->initTransactionContext($org1['user']->user_id, $org1['org']->org_id);
        $org1Posts = SocialPost::all();
        $this->assertCount(1, $org1Posts);
        $this->assertEquals('Org1 post', $org1Posts->first()->content);

        $this->clearTransactionContext();
        $this->initTransactionContext($org2['user']->user_id, $org2['org']->org_id);
        $org2Posts = SocialPost::all();
        $this->assertCount(1, $org2Posts);
        $this->assertEquals('Org2 post', $org2Posts->first()->content);

        $this->clearTransactionContext();

        $this->logTestResult('passed', [
            'test' => 'Social posts isolation',
            'org1_posts' => 1,
            'org2_posts' => 1,
        ]);
    }

    #[Test]
    public function it_verifies_rls_context_initialization()
    {
        $org = $this->createUserWithOrg();

        // Before setting context
        $this->clearTransactionContext();

        // Should get NULL for org context
        $currentOrg = DB::selectOne("SELECT current_setting('app.current_org_id', true) as org_id");
        $this->assertNull($currentOrg->org_id);

        // After setting context
        $this->initTransactionContext($org['user']->user_id, $org['org']->org_id);

        // Should get the org ID
        $currentOrg = DB::selectOne("SELECT current_setting('app.current_org_id', true) as org_id");
        $this->assertEquals($org['org']->org_id, $currentOrg->org_id);

        $this->clearTransactionContext();

        $this->logTestResult('passed', [
            'test' => 'RLS context initialization',
            'org_id' => $org['org']->org_id,
        ]);
    }

    #[Test]
    public function it_handles_multi_user_access_within_same_org()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        // Create second user in same org
        $user2Data = $this->createUserWithOrg([
            'name' => 'User 2',
            'email' => 'user2@' . str_replace(' ', '', strtolower($org->name)) . '.test',
        ], [
            'name' => $org->name,
            'org_id' => $org->org_id,
        ], 'creator');
        $user2 = $user2Data['user'];

        // User 1 creates campaign
        $this->initTransactionContext($setup['user']->user_id, $org->org_id);
        $campaign = Campaign::create([
            'campaign_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Shared Campaign',
            'objective' => 'engagement',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'budget' => 3000,
            'currency' => 'BHD',
        ]);

        // User 2 should see same campaign
        $this->clearTransactionContext();
        $this->initTransactionContext($user2->user_id, $org->org_id);
        $foundCampaign = Campaign::where('campaign_id', $campaign->campaign_id)->first();
        $this->assertNotNull($foundCampaign);
        $this->assertEquals('Shared Campaign', $foundCampaign->name);

        $this->clearTransactionContext();

        $this->logTestResult('passed', [
            'test' => 'Multi-user same org access',
            'users' => 2,
            'shared_campaign' => true,
        ]);
    }

    #[Test]
    public function it_enforces_rls_on_creative_assets()
    {
        $org1 = $this->createUserWithOrg();
        $org2 = $this->createUserWithOrg();

        // Create assets for both orgs
        $this->initTransactionContext($org1['user']->user_id, $org1['org']->org_id);
        $asset1 = CreativeAsset::create([
            'asset_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $org1['org']->org_id,
            'channel_id' => 1,
            'format_id' => 1,
            'variation_tag' => 'v1',
            'status' => 'approved',
            'final_copy' => ['headline' => 'Org1 Asset'],
        ]);

        $this->clearTransactionContext();
        $this->initTransactionContext($org2['user']->user_id, $org2['org']->org_id);
        $asset2 = CreativeAsset::create([
            'asset_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $org2['org']->org_id,
            'channel_id' => 1,
            'format_id' => 1,
            'variation_tag' => 'v1',
            'status' => 'approved',
            'final_copy' => ['headline' => 'Org2 Asset'],
        ]);

        // Verify org1 can only see its asset
        $this->clearTransactionContext();
        $this->initTransactionContext($org1['user']->user_id, $org1['org']->org_id);
        $org1Assets = CreativeAsset::all();
        $this->assertCount(1, $org1Assets);

        // Verify org2 can only see its asset
        $this->clearTransactionContext();
        $this->initTransactionContext($org2['user']->user_id, $org2['org']->org_id);
        $org2Assets = CreativeAsset::all();
        $this->assertCount(1, $org2Assets);

        $this->clearTransactionContext();

        $this->logTestResult('passed', [
            'test' => 'Creative assets isolation',
            'org1_assets' => 1,
            'org2_assets' => 1,
        ]);
    }

    #[Test]
    public function it_prevents_rls_bypass_attempts()
    {
        $org1 = $this->createUserWithOrg();
        $org2 = $this->createUserWithOrg();

        // Create campaign in org1
        $this->initTransactionContext($org1['user']->user_id, $org1['org']->org_id);
        $campaign = Campaign::create([
            'campaign_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $org1['org']->org_id,
            'name' => 'Protected Campaign',
            'objective' => 'awareness',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'budget' => 10000,
            'currency' => 'BHD',
        ]);

        // Switch to org2 context
        $this->clearTransactionContext();
        $this->initTransactionContext($org2['user']->user_id, $org2['org']->org_id);

        // Attempt to query by org_id (should still be blocked by RLS)
        $foundCampaign = Campaign::where('org_id', $org1['org']->org_id)->first();
        $this->assertNull($foundCampaign);

        // Attempt raw query with org_id filter (should still be blocked)
        $rawResult = DB::select("SELECT * FROM cmis.campaigns WHERE org_id = ?", [$org1['org']->org_id]);
        $this->assertEmpty($rawResult);

        $this->clearTransactionContext();

        $this->logTestResult('passed', [
            'test' => 'RLS bypass prevention',
            'attempted_bypass' => 'failed_as_expected',
        ]);
    }
}
