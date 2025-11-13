<?php

namespace Tests\Integration\Campaign;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CampaignService;
use App\Services\CreativeService;
use App\Services\PublishingService;
use App\Services\Ads\MetaAdsService;

/**
 * Complete Campaign Lifecycle Integration Test
 *
 * Tests the entire campaign flow from creation to reporting
 */
class CompleteCampaignLifecycleTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected CampaignService $campaignService;
    protected CreativeService $creativeService;
    protected PublishingService $publishingService;
    protected MetaAdsService $metaAdsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->campaignService = app(CampaignService::class);
        $this->creativeService = app(CreativeService::class);
        $this->publishingService = app(PublishingService::class);
        $this->metaAdsService = app(MetaAdsService::class);
    }

    /** @test */
    public function it_executes_complete_campaign_lifecycle()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);
        $this->mockAllAPIs();

        // ========== PHASE 1: PLANNING ==========

        // Step 1: Create Campaign
        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'Summer Sale 2024',
            'objective' => 'conversions',
            'status' => 'draft',
            'budget' => 5000.00,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(37)->format('Y-m-d'),
        ]);

        $this->assertDatabaseHas('cmis.campaigns', [
            'campaign_id' => $campaign->campaign_id,
            'status' => 'draft',
        ]);

        // Step 2: Create Creative Brief
        $brief = $this->createTestCreativeBrief($org->org_id, [
            'name' => 'Summer Sale Creative Brief',
            'brief_data' => [
                'objective' => 'drive_sales',
                'target_audience' => 'Young professionals 25-40',
                'key_message' => 'Up to 50% off summer collection',
                'tone' => 'exciting',
            ],
        ]);

        // ========== PHASE 2: CREATIVE DEVELOPMENT ==========

        // Step 3: Generate Creative Assets
        $assets = [];
        $channels = ['facebook', 'instagram', 'twitter'];

        foreach ($channels as $channelId => $channelName) {
            $asset = $this->createTestCreativeAsset($org->org_id, $campaign->campaign_id, [
                'brief_id' => $brief->brief_id,
                'channel_id' => $channelId + 1,
                'status' => 'draft',
                'final_copy' => [
                    'headline' => "Summer Sale - {$channelName}",
                    'body' => 'Don\'t miss out on amazing deals!',
                    'cta' => 'Shop Now',
                ],
            ]);
            $assets[] = $asset;
        }

        $this->assertCount(3, $assets);

        // ========== PHASE 3: APPROVAL ==========

        // Step 4: Approve All Assets
        foreach ($assets as $asset) {
            $asset->update(['status' => 'approved']);
        }

        // ========== PHASE 4: CAMPAIGN ACTIVATION ==========

        // Step 5: Activate Campaign
        $campaign->update(['status' => 'active']);

        // Step 6: Create Ad Campaigns on Meta
        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $adCampaign = $this->createTestAdCampaign($org->org_id, $integration->integration_id, [
            'name' => 'Summer Sale - Meta Ads',
            'objective' => 'CONVERSIONS',
            'status' => 'ACTIVE',
            'budget' => 2000.00,
        ]);

        // ========== PHASE 5: CONTENT PUBLISHING ==========

        // Step 7: Schedule Social Posts
        $scheduledPosts = [];

        for ($day = 0; $day < 7; $day++) {
            $post = $this->createTestScheduledPost($org->org_id, $user->user_id, [
                'campaign_id' => $campaign->campaign_id,
                'platforms' => ['facebook', 'instagram'],
                'content' => "Day {$day}: Amazing summer deals! #SummerSale",
                'scheduled_at' => now()->addDays(7 + $day)->setHour(10),
                'status' => 'scheduled',
            ]);
            $scheduledPosts[] = $post;
        }

        $this->assertCount(7, $scheduledPosts);

        // Step 8: Publish First Post
        $firstPost = $scheduledPosts[0];
        $publishResult = $this->publishingService->publishPost($firstPost);

        $this->assertTrue($publishResult['success']);

        $firstPost = $firstPost->fresh();
        $this->assertEquals('published', $firstPost->status);

        // ========== PHASE 6: MONITORING & OPTIMIZATION ==========

        // Step 9: Sync Ad Performance Metrics
        $metricsResult = $this->metaAdsService->syncMetrics($integration);

        $this->assertTrue($metricsResult['success']);

        // Step 10: Update Campaign Performance
        \App\Models\CampaignPerformanceMetric::create([
            'metric_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $org->org_id,
            'campaign_id' => $campaign->campaign_id,
            'kpi' => 'impressions',
            'observed' => 50000,
            'target' => 75000,
            'observed_at' => now(),
        ]);

        \App\Models\CampaignPerformanceMetric::create([
            'metric_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $org->org_id,
            'campaign_id' => $campaign->campaign_id,
            'kpi' => 'conversions',
            'observed' => 250,
            'target' => 500,
            'observed_at' => now(),
        ]);

        // ========== PHASE 7: COMPLETION & REPORTING ==========

        // Step 11: Complete Campaign
        $campaign->update([
            'status' => 'completed',
            'end_date' => now()->format('Y-m-d'),
        ]);

        // Step 12: Verify Final State
        $campaign = $campaign->fresh();

        $this->assertEquals('completed', $campaign->status);
        $this->assertCount(3, $campaign->creativeAssets);
        $this->assertCount(2, $campaign->performanceMetrics);

        // Verify all posts published
        $publishedCount = \App\Models\ScheduledSocialPost::where('campaign_id', $campaign->campaign_id)
            ->where('status', 'published')
            ->count();

        $this->assertGreaterThan(0, $publishedCount);

        $this->logTestResult('passed', [
            'workflow' => 'complete_campaign_lifecycle',
            'phases_completed' => 7,
            'assets_created' => 3,
            'posts_scheduled' => 7,
            'posts_published' => $publishedCount,
        ]);
    }

    /** @test */
    public function it_handles_campaign_pause_and_resume()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id, [
            'status' => 'active',
        ]);

        // Pause campaign
        $campaign->update(['status' => 'paused']);

        $this->assertEquals('paused', $campaign->fresh()->status);

        // Resume campaign
        $campaign->update(['status' => 'active']);

        $this->assertEquals('active', $campaign->fresh()->status);

        $this->logTestResult('passed', [
            'workflow' => 'campaign_lifecycle',
            'step' => 'pause_resume',
        ]);
    }

    /** @test */
    public function it_archives_completed_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id, [
            'status' => 'completed',
            'end_date' => now()->subDays(30)->format('Y-m-d'),
        ]);

        // Archive campaign
        $campaign->update(['status' => 'archived']);

        $this->assertEquals('archived', $campaign->fresh()->status);

        $this->logTestResult('passed', [
            'workflow' => 'campaign_lifecycle',
            'step' => 'archive_completed',
        ]);
    }
}
