<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\PublishToInstagramJob;
use App\Jobs\PublishToFacebookJob;
use App\Jobs\PublishToTwitterJob;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Publishing API Feature Tests
 */
class PublishingAPITest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    #[Test]
    public function it_can_schedule_instagram_post()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $campaign = $this->createTestCampaign($org->org_id);
        $content = $this->createTestContent($campaign->campaign_id, [
            'platform' => 'instagram',
            'content_type' => 'feed',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/publishing/schedule', [
            'content_id' => $content->content_id,
            'platform' => 'instagram',
            'scheduled_at' => now()->addHours(2)->toDateTimeString(),
            'caption' => 'اختبار النشر على إنستقرام',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.platform', 'instagram');
        $response->assertJsonPath('data.status', 'scheduled');

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/publishing/schedule',
            'platform' => 'instagram',
        ]);
    }

    #[Test]
    public function it_can_publish_immediately_to_facebook()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $campaign = $this->createTestCampaign($org->org_id);
        $content = $this->createTestContent($campaign->campaign_id, [
            'platform' => 'facebook',
            'content_type' => 'post',
        ]);

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'id' => 'fb_post_123',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/publishing/publish-now', [
            'content_id' => $content->content_id,
            'platform' => 'facebook',
            'message' => 'نشر فوري على فيسبوك',
        ]);

        $response->assertStatus(200);
        Queue::assertPushed(PublishToFacebookJob::class);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/publishing/publish-now',
            'platform' => 'facebook',
        ]);
    }

    #[Test]
    public function it_can_schedule_multiple_platforms()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $campaign = $this->createTestCampaign($org->org_id);
        $content = $this->createTestContent($campaign->campaign_id);

        $this->createTestIntegration($org->org_id, 'instagram');
        $this->createTestIntegration($org->org_id, 'facebook');
        $this->createTestIntegration($org->org_id, 'twitter');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/publishing/schedule-multi', [
            'content_id' => $content->content_id,
            'platforms' => ['instagram', 'facebook', 'twitter'],
            'scheduled_at' => now()->addHours(2)->toDateTimeString(),
            'caption' => 'نشر متعدد المنصات',
        ]);

        $response->assertStatus(201);
        $response->assertJsonCount(3, 'data');

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/publishing/schedule-multi',
            'platforms_count' => 3,
        ]);
    }

    #[Test]
    public function it_can_cancel_scheduled_post()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $campaign = $this->createTestCampaign($org->org_id);
        $content = $this->createTestContent($campaign->campaign_id, [
            'status' => 'scheduled',
            'scheduled_at' => now()->addHours(2),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/publishing/cancel/{$content->content_id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'cancelled');

        $this->logTestResult('passed', [
            'endpoint' => 'DELETE /api/publishing/cancel',
            'content_id' => $content->content_id,
        ]);
    }

    #[Test]
    public function it_can_get_publishing_queue()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $campaign = $this->createTestCampaign($org->org_id);

        $this->createTestContent($campaign->campaign_id, [
            'status' => 'scheduled',
            'scheduled_at' => now()->addHours(1),
        ]);

        $this->createTestContent($campaign->campaign_id, [
            'status' => 'scheduled',
            'scheduled_at' => now()->addHours(3),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/publishing/queue');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['content_id', 'scheduled_at', 'platform', 'status'],
            ],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/publishing/queue',
        ]);
    }

    #[Test]
    public function it_can_retry_failed_publishing()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $campaign = $this->createTestCampaign($org->org_id);
        $content = $this->createTestContent($campaign->campaign_id, [
            'status' => 'failed',
            'platform' => 'instagram',
        ]);

        $this->mockMetaAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/publishing/retry/{$content->content_id}");

        $response->assertStatus(200);
        Queue::assertPushed(PublishToInstagramJob::class);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/publishing/retry',
            'content_id' => $content->content_id,
        ]);
    }

    #[Test]
    public function it_validates_scheduling_time()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $campaign = $this->createTestCampaign($org->org_id);
        $content = $this->createTestContent($campaign->campaign_id);

        $this->actingAs($user, 'sanctum');

        // Try to schedule in the past
        $response = $this->postJson('/api/publishing/schedule', [
            'content_id' => $content->content_id,
            'platform' => 'instagram',
            'scheduled_at' => now()->subHours(1)->toDateTimeString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('scheduled_at');

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/publishing/schedule',
            'validation' => 'past_time_rejected',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $campaign1 = $this->createTestCampaign($setup1['org']->org_id);
        $content1 = $this->createTestContent($campaign1->campaign_id);

        $this->actingAs($setup2['user'], 'sanctum');

        $response = $this->postJson('/api/publishing/schedule', [
            'content_id' => $content1->content_id,
            'platform' => 'instagram',
            'scheduled_at' => now()->addHours(2)->toDateTimeString(),
        ]);

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/publishing/schedule',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_can_bulk_schedule_posts()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $campaign = $this->createTestCampaign($org->org_id);

        $content1 = $this->createTestContent($campaign->campaign_id);
        $content2 = $this->createTestContent($campaign->campaign_id);
        $content3 = $this->createTestContent($campaign->campaign_id);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/publishing/bulk-schedule', [
            'content_ids' => [
                $content1->content_id,
                $content2->content_id,
                $content3->content_id,
            ],
            'platform' => 'instagram',
            'start_time' => now()->addHours(1)->toDateTimeString(),
            'interval_hours' => 2,
        ]);

        $response->assertStatus(201);
        $response->assertJsonCount(3, 'data');

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/publishing/bulk-schedule',
            'scheduled_count' => 3,
        ]);
    }

    #[Test]
    public function it_can_get_publishing_history()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $campaign = $this->createTestCampaign($org->org_id);

        $this->createTestContent($campaign->campaign_id, [
            'status' => 'published',
            'published_at' => now()->subDays(1),
        ]);

        $this->createTestContent($campaign->campaign_id, [
            'status' => 'published',
            'published_at' => now()->subDays(2),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/publishing/history');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['content_id', 'published_at', 'platform', 'status'],
            ],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/publishing/history',
        ]);
    }

    #[Test]
    public function it_can_preview_post_before_publishing()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $campaign = $this->createTestCampaign($org->org_id);
        $content = $this->createTestContent($campaign->campaign_id, [
            'caption' => 'اختبار المعاينة',
            'platform' => 'instagram',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/publishing/preview/{$content->content_id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['caption', 'platform', 'preview_url'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/publishing/preview',
        ]);
    }
}
