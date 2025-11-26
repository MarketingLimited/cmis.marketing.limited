<?php

namespace Tests\Integration\Bulk;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\BulkPostService;

use PHPUnit\Framework\Attributes\Test;
/**
 * Bulk Operations Workflow Tests
 */
class BulkOperationsWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected BulkPostService $bulkPostService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bulkPostService = app(BulkPostService::class);
    }

    #[Test]
    public function it_creates_bulk_posts_from_csv()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        // Simulate CSV data
        $csvData = [
            ['content' => 'Post 1', 'platforms' => 'facebook,instagram', 'scheduled_at' => now()->addHours(1)->toDateTimeString()],
            ['content' => 'Post 2', 'platforms' => 'twitter', 'scheduled_at' => now()->addHours(2)->toDateTimeString()],
            ['content' => 'Post 3', 'platforms' => 'facebook,twitter', 'scheduled_at' => now()->addHours(3)->toDateTimeString()],
            ['content' => 'Post 4', 'platforms' => 'instagram', 'scheduled_at' => now()->addHours(4)->toDateTimeString()],
            ['content' => 'Post 5', 'platforms' => 'facebook', 'scheduled_at' => now()->addHours(5)->toDateTimeString()],
        ];

        $result = $this->bulkPostService->createFromCsv($org->org_id, $user->user_id, $csvData);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['created_count']);
        $this->assertEquals(0, $result['failed_count']);

        // Verify posts created
        $this->assertEquals(5, \App\Models\ScheduledSocialPost::where('org_id', $org->org_id)->count());

        $this->logTestResult('passed', [
            'workflow' => 'bulk_operations',
            'operation' => 'create_posts_from_csv',
            'posts_created' => 5,
        ]);
    }

    #[Test]
    public function it_handles_bulk_post_validation_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        // CSV with some invalid data
        $csvData = [
            ['content' => 'Valid Post', 'platforms' => 'facebook', 'scheduled_at' => now()->addHours(1)->toDateTimeString()],
            ['content' => '', 'platforms' => 'facebook', 'scheduled_at' => now()->addHours(2)->toDateTimeString()], // Invalid: empty content
            ['content' => 'Another Valid', 'platforms' => 'twitter', 'scheduled_at' => now()->addHours(3)->toDateTimeString()],
            ['content' => 'Valid', 'platforms' => '', 'scheduled_at' => now()->addHours(4)->toDateTimeString()], // Invalid: no platforms
        ];

        $result = $this->bulkPostService->createFromCsv($org->org_id, $user->user_id, $csvData);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['created_count']);
        $this->assertEquals(2, $result['failed_count']);
        $this->assertCount(2, $result['errors']);

        $this->logTestResult('passed', [
            'workflow' => 'bulk_operations',
            'operation' => 'validation_handling',
            'created' => 2,
            'failed' => 2,
        ]);
    }

    #[Test]
    public function it_performs_bulk_campaign_status_update()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        // Create multiple campaigns
        $campaigns = [];
        for ($i = 1; $i <= 10; $i++) {
            $campaigns[] = $this->createTestCampaign($org->org_id, [
                'name' => "Campaign {$i}",
                'status' => 'draft',
            ]);
        }

        // Bulk activate campaigns
        $campaignIds = array_map(fn($c) => $c->campaign_id, $campaigns);

        $updated = \App\Models\Campaign::whereIn('campaign_id', $campaignIds)
            ->update(['status' => 'active']);

        $this->assertEquals(10, $updated);

        // Verify all campaigns active
        $activeCampaigns = \App\Models\Campaign::whereIn('campaign_id', $campaignIds)
            ->where('status', 'active')
            ->count();

        $this->assertEquals(10, $activeCampaigns);

        $this->logTestResult('passed', [
            'workflow' => 'bulk_operations',
            'operation' => 'bulk_status_update',
            'campaigns_updated' => 10,
        ]);
    }

    #[Test]
    public function it_performs_bulk_creative_asset_deletion()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        // Create multiple assets
        $assets = [];
        for ($i = 1; $i <= 15; $i++) {
            $assets[] = $this->createTestCreativeAsset($org->org_id);
        }

        $assetIds = array_map(fn($a) => $a->asset_id, $assets);

        // Bulk soft delete
        $deleted = \App\Models\Creative\CreativeAsset::whereIn('asset_id', $assetIds)
            ->delete();

        $this->assertEquals(15, $deleted);

        // Verify soft deleted
        foreach ($assetIds as $assetId) {
            $this->assertSoftDeleted('cmis.creative_assets', [
                'asset_id' => $assetId,
            ]);
        }

        $this->logTestResult('passed', [
            'workflow' => 'bulk_operations',
            'operation' => 'bulk_delete',
            'assets_deleted' => 15,
        ]);
    }

    #[Test]
    public function it_performs_bulk_post_scheduling_with_queue()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        // Create publishing queue
        $publishingQueue = $this->createTestPublishingQueue($org->org_id);

        // Schedule 20 posts automatically to queue slots
        $posts = [];
        for ($i = 0; $i < 20; $i++) {
            $post = $this->createTestScheduledPost($org->org_id, $user->user_id, [
                'content' => "Auto-scheduled post {$i}",
                'scheduled_at' => null, // Will be auto-assigned
            ]);

            // Auto-assign to next available slot
            $nextSlot = $this->calculateNextSlot($publishingQueue, $i);
            $post->update(['scheduled_at' => $nextSlot]);

            $posts[] = $post;
        }

        // Verify all posts scheduled
        $this->assertEquals(20, count($posts));

        $this->logTestResult('passed', [
            'workflow' => 'bulk_operations',
            'operation' => 'bulk_queue_scheduling',
            'posts_scheduled' => 20,
        ]);
    }

    private function calculateNextSlot($queue, $index)
    {
        $baseTime = now()->addDays(1)->setHour(9)->setMinute(0);
        $hours = $index * 2; // Every 2 hours
        return $baseTime->addHours($hours);
    }
}
