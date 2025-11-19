<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Social\LinkedInService;

use PHPUnit\Framework\Attributes\Test;
/**
 * LinkedIn Service Unit Tests
 */
class LinkedInServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected LinkedInService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LinkedInService::class);
    }

    #[Test]
    public function it_can_publish_post()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $this->mockLinkedInAPI('success', [
            'id' => 'linkedin_post_123',
        ]);

        $result = $this->service->publishPost($integration, [
            'text' => 'منشور تجريبي على لينكد إن',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('linkedin_post_123', $result['post_id']);

        $this->logTestResult('passed', [
            'service' => 'LinkedInService',
            'method' => 'publishPost',
        ]);
    }

    #[Test]
    public function it_can_publish_article()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $this->mockLinkedInAPI('success', [
            'id' => 'article_123',
        ]);

        $result = $this->service->publishArticle($integration, [
            'title' => 'مقال عن التسويق الرقمي',
            'content' => 'محتوى المقال الطويل...',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'LinkedInService',
            'method' => 'publishArticle',
        ]);
    }

    #[Test]
    public function it_can_publish_image_post()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $this->mockLinkedInAPI('success', [
            'id' => 'image_post_456',
        ]);

        $result = $this->service->publishImagePost($integration, [
            'text' => 'منشور مع صورة',
            'image_url' => 'https://example.com/image.jpg',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'LinkedInService',
            'method' => 'publishImagePost',
        ]);
    }

    #[Test]
    public function it_can_publish_video_post()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $this->mockLinkedInAPI('success', [
            'id' => 'video_post_789',
        ]);

        $result = $this->service->publishVideoPost($integration, [
            'text' => 'فيديو احترافي',
            'video_url' => 'https://example.com/video.mp4',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'LinkedInService',
            'method' => 'publishVideoPost',
        ]);
    }

    #[Test]
    public function it_can_get_organization_statistics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $this->mockLinkedInAPI('success', [
            'elements' => [
                [
                    'totalShareStatistics' => [
                        'impressionCount' => 50000,
                        'clickCount' => 2500,
                        'engagement' => 750,
                    ],
                ],
            ],
        ]);

        $result = $this->service->getOrganizationStatistics($integration);

        $this->assertTrue($result['success']);
        $this->assertEquals(50000, $result['data']['impressionCount']);

        $this->logTestResult('passed', [
            'service' => 'LinkedInService',
            'method' => 'getOrganizationStatistics',
        ]);
    }

    #[Test]
    public function it_can_get_post_statistics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $this->mockLinkedInAPI('success', [
            'impressionCount' => 5000,
            'clickCount' => 250,
            'likeCount' => 150,
            'commentCount' => 35,
            'shareCount' => 25,
        ]);

        $result = $this->service->getPostStatistics($integration, 'post_123');

        $this->assertTrue($result['success']);
        $this->assertEquals(5000, $result['data']['impressionCount']);
        $this->assertEquals(150, $result['data']['likeCount']);

        $this->logTestResult('passed', [
            'service' => 'LinkedInService',
            'method' => 'getPostStatistics',
        ]);
    }

    #[Test]
    public function it_can_get_follower_demographics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $this->mockLinkedInAPI('success', [
            'elements' => [
                [
                    'followerCounts' => [
                        'organicFollowerCount' => 10000,
                        'paidFollowerCount' => 500,
                    ],
                ],
            ],
        ]);

        $result = $this->service->getFollowerDemographics($integration);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'LinkedInService',
            'method' => 'getFollowerDemographics',
        ]);
    }

    #[Test]
    public function it_can_get_comments()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $this->mockLinkedInAPI('success', [
            'elements' => [
                [
                    'id' => 'comment_1',
                    'message' => ['text' => 'تعليق رائع'],
                    'actor' => 'urn:li:person:123',
                ],
            ],
        ]);

        $result = $this->service->getComments($integration, 'post_123');

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);

        $this->logTestResult('passed', [
            'service' => 'LinkedInService',
            'method' => 'getComments',
        ]);
    }

    #[Test]
    public function it_can_delete_post()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $this->mockLinkedInAPI('success', []);

        $result = $this->service->deletePost($integration, 'post_123');

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'LinkedInService',
            'method' => 'deletePost',
        ]);
    }

    #[Test]
    public function it_validates_post_content()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $result = $this->service->publishPost($integration, []);

        $this->assertFalse($result['success']);

        $this->logTestResult('passed', [
            'service' => 'LinkedInService',
            'test' => 'validation',
        ]);
    }

    #[Test]
    public function it_handles_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $this->mockLinkedInAPI('error');

        $result = $this->service->publishPost($integration, [
            'text' => 'Test',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'service' => 'LinkedInService',
            'test' => 'error_handling',
        ]);
    }
}
