<?php

namespace Tests\Integration\Sync;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Sync\MetaSyncService;
use App\Services\Sync\TikTokSyncService;
use App\Services\Sync\TwitterSyncService;
use App\Services\Sync\LinkedInSyncService;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * All Platforms Sync Integration Tests
 */
class AllPlatformsSyncTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_syncs_facebook_page_data()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'data' => [
                'id' => 'page_123',
                'name' => 'Test Page',
                'followers_count' => 10000,
                'likes' => 9500,
            ],
        ]);

        $syncService = app(MetaSyncService::class);
        $result = $syncService->syncPageData($integration);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.social_accounts', [
            'org_id' => $org->org_id,
            'platform' => 'facebook',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'platform_sync',
            'platform' => 'facebook',
            'type' => 'page_data',
        ]);
    }

    #[Test]
    public function it_syncs_facebook_posts()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'id' => 'post_123',
                    'message' => 'Test Facebook post',
                    'created_time' => '2024-01-01T00:00:00+0000',
                    'likes' => ['summary' => ['total_count' => 100]],
                    'comments' => ['summary' => ['total_count' => 10]],
                ],
            ],
        ]);

        $syncService = app(MetaSyncService::class);
        $result = $syncService->syncPosts($integration, now()->subDays(7));

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.social_posts', [
            'platform' => 'facebook',
            'post_external_id' => 'post_123',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'platform_sync',
            'platform' => 'facebook',
            'type' => 'posts',
        ]);
    }

    #[Test]
    public function it_syncs_facebook_insights()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'data' => [
                [
                    'name' => 'page_impressions',
                    'period' => 'day',
                    'values' => [
                        ['value' => 5000, 'end_time' => '2024-01-01T00:00:00+0000'],
                    ],
                ],
            ],
        ]);

        $syncService = app(MetaSyncService::class);
        $result = $syncService->syncInsights($integration);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'platform_sync',
            'platform' => 'facebook',
            'type' => 'insights',
        ]);
    }

    #[Test]
    public function it_syncs_tiktok_account_info()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        $this->mockTikTokAPI('success', [
            'data' => [
                'user' => [
                    'id' => 'tiktok_123',
                    'display_name' => 'Test TikTok',
                    'follower_count' => 50000,
                    'following_count' => 100,
                ],
            ],
        ]);

        $syncService = app(TikTokSyncService::class);
        $result = $syncService->syncAccountInfo($integration);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'platform_sync',
            'platform' => 'tiktok',
            'type' => 'account_info',
        ]);
    }

    #[Test]
    public function it_syncs_tiktok_videos()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        $this->mockTikTokAPI('success', [
            'data' => [
                'videos' => [
                    [
                        'id' => 'video_123',
                        'create_time' => time(),
                        'title' => 'Test TikTok video',
                        'like_count' => 1000,
                        'comment_count' => 50,
                        'share_count' => 25,
                        'view_count' => 50000,
                    ],
                ],
            ],
        ]);

        $syncService = app(TikTokSyncService::class);
        $result = $syncService->syncVideos($integration);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'platform_sync',
            'platform' => 'tiktok',
            'type' => 'videos',
        ]);
    }

    #[Test]
    public function it_syncs_twitter_profile()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'twitter_123',
                'username' => 'testuser',
                'name' => 'Test User',
                'public_metrics' => [
                    'followers_count' => 5000,
                    'following_count' => 500,
                    'tweet_count' => 1000,
                ],
            ],
        ]);

        $syncService = app(TwitterSyncService::class);
        $result = $syncService->syncProfile($integration);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'platform_sync',
            'platform' => 'twitter',
            'type' => 'profile',
        ]);
    }

    #[Test]
    public function it_syncs_twitter_tweets()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $this->mockTwitterAPI('success', [
            'data' => [
                [
                    'id' => 'tweet_123',
                    'text' => 'Test tweet',
                    'created_at' => '2024-01-01T00:00:00.000Z',
                    'public_metrics' => [
                        'retweet_count' => 10,
                        'reply_count' => 5,
                        'like_count' => 50,
                    ],
                ],
            ],
        ]);

        $syncService = app(TwitterSyncService::class);
        $result = $syncService->syncTweets($integration);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'platform_sync',
            'platform' => 'twitter',
            'type' => 'tweets',
        ]);
    }

    #[Test]
    public function it_syncs_linkedin_company_page()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $this->mockLinkedInAPI('success', [
            'id' => 'urn:li:organization:123',
            'name' => 'Test Company',
            'followerCount' => 1000,
        ]);

        $syncService = app(LinkedInSyncService::class);
        $result = $syncService->syncCompanyPage($integration);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'platform_sync',
            'platform' => 'linkedin',
            'type' => 'company_page',
        ]);
    }

    #[Test]
    public function it_syncs_linkedin_posts()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $this->mockLinkedInAPI('success', [
            'elements' => [
                [
                    'id' => 'urn:li:share:123',
                    'created' => [
                        'time' => time() * 1000,
                    ],
                    'text' => [
                        'text' => 'Test LinkedIn post',
                    ],
                ],
            ],
        ]);

        $syncService = app(LinkedInSyncService::class);
        $result = $syncService->syncPosts($integration);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'platform_sync',
            'platform' => 'linkedin',
            'type' => 'posts',
        ]);
    }

    #[Test]
    public function it_handles_rate_limiting_gracefully()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('rate_limit');

        $syncService = app(MetaSyncService::class);
        $result = $syncService->syncPosts($integration, now()->subDays(7));

        $this->assertFalse($result['success']);
        $this->assertEquals('rate_limit', $result['error_type']);

        $this->logTestResult('passed', [
            'workflow' => 'platform_sync',
            'test' => 'rate_limiting',
        ]);
    }

    #[Test]
    public function it_retries_failed_sync_operations()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        // Mock temporary failure then success
        $this->mockMetaAPI('error');

        $syncService = app(MetaSyncService::class);
        $result = $syncService->syncPageData($integration);

        $this->assertFalse($result['success']);

        // Now mock success
        $this->mockMetaAPI('success');

        $retryResult = $syncService->syncPageData($integration);
        $this->assertTrue($retryResult['success']);

        $this->logTestResult('passed', [
            'workflow' => 'platform_sync',
            'test' => 'retry_logic',
        ]);
    }

    #[Test]
    public function it_syncs_multiple_platforms_simultaneously()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integrations = [
            $this->createTestIntegration($org->org_id, 'facebook'),
            $this->createTestIntegration($org->org_id, 'instagram'),
            $this->createTestIntegration($org->org_id, 'twitter'),
        ];

        $this->mockAllAPIs();

        $results = [];
        foreach ($integrations as $integration) {
            if ($integration->platform === 'facebook' || $integration->platform === 'instagram') {
                $service = app(MetaSyncService::class);
                $results[] = $service->syncPageData($integration);
            } elseif ($integration->platform === 'twitter') {
                $service = app(TwitterSyncService::class);
                $results[] = $service->syncProfile($integration);
            }
        }

        foreach ($results as $result) {
            $this->assertTrue($result['success']);
        }

        $this->logTestResult('passed', [
            'workflow' => 'platform_sync',
            'test' => 'multi_platform',
            'platforms' => ['facebook', 'instagram', 'twitter'],
        ]);
    }
}
