<?php

namespace Tests\Integration\Social;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Social\InstagramSyncService;
use App\Jobs\SyncInstagramDataJob;

/**
 * Instagram Sync Complete Workflow Test
 */
class InstagramSyncWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected InstagramSyncService $syncService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->syncService = app(InstagramSyncService::class);
    }

    /** @test */
    public function it_syncs_instagram_account_profile()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success');

        $result = $this->syncService->syncAccount($integration);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.social_accounts', [
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'instagram_sync',
            'step' => 'account_profile',
        ]);
    }

    /** @test */
    public function it_syncs_instagram_media_posts()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'id' => 'ig_post_123',
                    'caption' => 'Test Instagram post',
                    'media_type' => 'IMAGE',
                    'media_url' => 'https://example.com/image.jpg',
                    'timestamp' => '2024-01-01T00:00:00+0000',
                    'like_count' => 150,
                    'comments_count' => 25,
                ],
            ],
        ]);

        $result = $this->syncService->syncPosts($integration, now()->subDays(7));

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.social_posts', [
            'org_id' => $org->org_id,
            'post_external_id' => 'ig_post_123',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'instagram_sync',
            'step' => 'media_posts',
        ]);
    }

    /** @test */
    public function it_syncs_instagram_insights_metrics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');
        $socialAccount = $this->createTestSocialAccount($org->org_id, $integration->integration_id);

        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'name' => 'impressions',
                    'period' => 'day',
                    'values' => [
                        ['value' => 5000, 'end_time' => '2024-01-01T00:00:00+0000'],
                    ],
                ],
                [
                    'name' => 'reach',
                    'period' => 'day',
                    'values' => [
                        ['value' => 3500, 'end_time' => '2024-01-01T00:00:00+0000'],
                    ],
                ],
            ],
        ]);

        $result = $this->syncService->syncInsights($integration);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'instagram_sync',
            'step' => 'insights_metrics',
        ]);
    }

    /** @test */
    public function it_syncs_instagram_stories()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'id' => 'story_456',
                    'media_type' => 'IMAGE',
                    'media_url' => 'https://example.com/story.jpg',
                    'timestamp' => '2024-01-01T12:00:00+0000',
                ],
            ],
        ]);

        $result = $this->syncService->syncStories($integration);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'instagram_sync',
            'step' => 'stories',
        ]);
    }

    /** @test */
    public function it_handles_instagram_api_token_expiration()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('error', [
            'error' => [
                'message' => 'Invalid OAuth access token.',
                'type' => 'OAuthException',
                'code' => 190,
            ],
        ]);

        $result = $this->syncService->syncAccount($integration);

        $this->assertFalse($result['success']);
        $this->assertEquals('token_expired', $result['error_type']);

        $this->logTestResult('passed', [
            'workflow' => 'instagram_sync',
            'step' => 'token_expiration_handling',
        ]);
    }

    /** @test */
    public function it_syncs_instagram_comments()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'id' => 'comment_789',
                    'text' => 'Great post!',
                    'username' => 'test_user',
                    'timestamp' => '2024-01-01T13:00:00+0000',
                ],
            ],
        ]);

        $result = $this->syncService->syncComments($integration);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'instagram_sync',
            'step' => 'comments',
        ]);
    }
}
