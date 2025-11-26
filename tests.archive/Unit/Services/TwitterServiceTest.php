<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Social\TwitterService;

use PHPUnit\Framework\Attributes\Test;
/**
 * Twitter/X Service Unit Tests
 */
class TwitterServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected TwitterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TwitterService::class);
    }

    #[Test]
    public function it_can_publish_tweet()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'tweet_123',
                'text' => 'تغريدة تجريبية',
            ],
        ]);

        $result = $this->service->publishTweet($integration, [
            'text' => 'تغريدة تجريبية',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('tweet_123', $result['tweet_id']);

        $this->logTestResult('passed', [
            'service' => 'TwitterService',
            'method' => 'publishTweet',
        ]);
    }

    #[Test]
    public function it_can_publish_tweet_with_media()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'tweet_with_media_456',
                'text' => 'تغريدة مع صورة',
            ],
        ]);

        $result = $this->service->publishTweet($integration, [
            'text' => 'تغريدة مع صورة',
            'media_ids' => ['media_123', 'media_456'],
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'TwitterService',
            'method' => 'publishTweetWithMedia',
        ]);
    }

    #[Test]
    public function it_can_publish_thread()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $this->mockTwitterAPI('success', [
            'data' => [
                [
                    'id' => 'tweet_1',
                    'text' => 'التغريدة الأولى',
                ],
                [
                    'id' => 'tweet_2',
                    'text' => 'التغريدة الثانية',
                ],
            ],
        ]);

        $result = $this->service->publishThread($integration, [
            'التغريدة الأولى',
            'التغريدة الثانية',
            'التغريدة الثالثة',
        ]);

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['tweet_ids']);

        $this->logTestResult('passed', [
            'service' => 'TwitterService',
            'method' => 'publishThread',
        ]);
    }

    #[Test]
    public function it_can_get_user_timeline()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $this->mockTwitterAPI('success', [
            'data' => [
                [
                    'id' => 'tweet_1',
                    'text' => 'تغريدة 1',
                    'public_metrics' => [
                        'like_count' => 50,
                        'retweet_count' => 10,
                    ],
                ],
            ],
        ]);

        $result = $this->service->getUserTimeline($integration);

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);

        $this->logTestResult('passed', [
            'service' => 'TwitterService',
            'method' => 'getUserTimeline',
        ]);
    }

    #[Test]
    public function it_can_get_tweet_metrics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'tweet_123',
                'public_metrics' => [
                    'like_count' => 150,
                    'retweet_count' => 45,
                    'reply_count' => 25,
                    'quote_count' => 10,
                ],
            ],
        ]);

        $result = $this->service->getTweetMetrics($integration, 'tweet_123');

        $this->assertTrue($result['success']);
        $this->assertEquals(150, $result['data']['like_count']);
        $this->assertEquals(45, $result['data']['retweet_count']);

        $this->logTestResult('passed', [
            'service' => 'TwitterService',
            'method' => 'getTweetMetrics',
        ]);
    }

    #[Test]
    public function it_can_reply_to_tweet()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'reply_tweet_123',
                'text' => 'شكراً على المشاركة!',
            ],
        ]);

        $result = $this->service->replyToTweet($integration, 'tweet_123', 'شكراً على المشاركة!');

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'TwitterService',
            'method' => 'replyToTweet',
        ]);
    }

    #[Test]
    public function it_can_delete_tweet()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $this->mockTwitterAPI('success', [
            'data' => [
                'deleted' => true,
            ],
        ]);

        $result = $this->service->deleteTweet($integration, 'tweet_123');

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'TwitterService',
            'method' => 'deleteTweet',
        ]);
    }

    #[Test]
    public function it_can_search_tweets()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $this->mockTwitterAPI('success', [
            'data' => [
                [
                    'id' => 'search_result_1',
                    'text' => 'نتيجة البحث',
                ],
            ],
        ]);

        $result = $this->service->searchTweets($integration, 'تسويق رقمي');

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);

        $this->logTestResult('passed', [
            'service' => 'TwitterService',
            'method' => 'searchTweets',
        ]);
    }

    #[Test]
    public function it_can_get_mentions()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $this->mockTwitterAPI('success', [
            'data' => [
                [
                    'id' => 'mention_1',
                    'text' => '@username رائع!',
                ],
            ],
        ]);

        $result = $this->service->getMentions($integration);

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);

        $this->logTestResult('passed', [
            'service' => 'TwitterService',
            'method' => 'getMentions',
        ]);
    }

    #[Test]
    public function it_handles_character_limit()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $longText = str_repeat('a', 300); // More than 280 characters

        $result = $this->service->publishTweet($integration, [
            'text' => $longText,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('character limit', strtolower($result['error']));

        $this->logTestResult('passed', [
            'service' => 'TwitterService',
            'test' => 'character_limit',
        ]);
    }

    #[Test]
    public function it_handles_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $this->mockTwitterAPI('error');

        $result = $this->service->publishTweet($integration, [
            'text' => 'Test',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'service' => 'TwitterService',
            'test' => 'error_handling',
        ]);
    }
}
