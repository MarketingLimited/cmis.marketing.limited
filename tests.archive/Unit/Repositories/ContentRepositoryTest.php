<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Repositories\CMIS\ContentRepository;
use App\Repositories\Contracts\ContentRepositoryInterface;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Content Repository Unit Tests
 */
class ContentRepositoryTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected ContentRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(ContentRepositoryInterface::class);
    }

    #[Test]
    public function it_can_create_content_with_context()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $contentData = [
            'campaign_id' => $campaign->campaign_id,
            'title' => 'Test Content',
            'content_type' => 'image',
            'platform' => 'instagram',
            'caption' => 'Test caption for Instagram post',
            'status' => 'draft',
        ];

        $content = $this->repository->createContentWithContext($contentData);

        $this->assertNotNull($content);
        $this->assertEquals('Test Content', $content['title']);
        $this->assertEquals($org->org_id, $content['org_id']);

        $this->logTestResult('passed', [
            'content_id' => $content['content_id'] ?? null,
            'function' => 'createContentWithContext',
        ]);
    }

    #[Test]
    public function it_can_get_content_for_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $this->createTestContent($campaign->campaign_id, [
            'title' => 'Content 1',
            'status' => 'published',
        ]);

        $this->createTestContent($campaign->campaign_id, [
            'title' => 'Content 2',
            'status' => 'draft',
        ]);

        $content = $this->repository->getContentForCampaign($campaign->campaign_id);

        $this->assertIsArray($content);
        $this->assertCount(2, $content);

        $this->logTestResult('passed', [
            'campaign_id' => $campaign->campaign_id,
            'content_count' => count($content),
        ]);
    }

    #[Test]
    public function it_can_filter_content_by_status()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $this->createTestContent($campaign->campaign_id, [
            'title' => 'Published Content',
            'status' => 'published',
        ]);

        $this->createTestContent($campaign->campaign_id, [
            'title' => 'Draft Content',
            'status' => 'draft',
        ]);

        $publishedContent = $this->repository->getContentByStatus($campaign->campaign_id, 'published');

        $this->assertIsArray($publishedContent);
        $this->assertCount(1, $publishedContent);
        $this->assertEquals('Published Content', $publishedContent[0]['title']);

        $this->logTestResult('passed', [
            'status_filter' => 'published',
            'filtered_count' => count($publishedContent),
        ]);
    }

    #[Test]
    public function it_can_get_scheduled_content()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $this->createTestContent($campaign->campaign_id, [
            'title' => 'Scheduled Content',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(1),
        ]);

        $this->createTestContent($campaign->campaign_id, [
            'title' => 'Draft Content',
            'status' => 'draft',
        ]);

        $scheduledContent = $this->repository->getScheduledContent($org->org_id);

        $this->assertIsArray($scheduledContent);
        $this->assertGreaterThanOrEqual(1, count($scheduledContent));

        $this->logTestResult('passed', [
            'org_id' => $org->org_id,
            'scheduled_count' => count($scheduledContent),
        ]);
    }

    #[Test]
    public function it_can_search_content_by_keyword()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $this->createTestContent($campaign->campaign_id, [
            'title' => 'Summer Sale Post',
            'caption' => 'خصومات الصيف حتى 50%',
        ]);

        $this->createTestContent($campaign->campaign_id, [
            'title' => 'Winter Collection',
            'caption' => 'مجموعة الشتاء الجديدة',
        ]);

        $results = $this->repository->searchContent($org->org_id, 'صيف');

        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(1, count($results));

        $this->logTestResult('passed', [
            'search_query' => 'صيف',
            'results_count' => count($results),
        ]);
    }

    #[Test]
    public function it_can_get_content_by_platform()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $this->createTestContent($campaign->campaign_id, [
            'title' => 'Instagram Content',
            'platform' => 'instagram',
        ]);

        $this->createTestContent($campaign->campaign_id, [
            'title' => 'Facebook Content',
            'platform' => 'facebook',
        ]);

        $this->createTestContent($campaign->campaign_id, [
            'title' => 'Instagram Content 2',
            'platform' => 'instagram',
        ]);

        $instagramContent = $this->repository->getContentByPlatform($campaign->campaign_id, 'instagram');

        $this->assertIsArray($instagramContent);
        $this->assertCount(2, $instagramContent);

        $this->logTestResult('passed', [
            'platform' => 'instagram',
            'content_count' => count($instagramContent),
        ]);
    }

    #[Test]
    public function it_can_find_similar_content()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $content1 = $this->createTestContent($campaign->campaign_id, [
            'title' => 'Summer Sale Post',
            'caption' => 'خصومات الصيف',
        ]);

        $content2 = $this->createTestContent($campaign->campaign_id, [
            'title' => 'Summer Promotion',
            'caption' => 'عروض الصيف',
        ]);

        $similarContent = $this->repository->findSimilarContent($content1->content_id, 5);

        $this->assertIsArray($similarContent);

        $this->logTestResult('passed', [
            'source_content_id' => $content1->content_id,
            'similar_count' => count($similarContent),
        ]);
    }

    #[Test]
    public function it_respects_transaction_context()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        // Create content in org1
        $this->actingAsUserInOrg($setup1['user'], $setup1['org']);
        $campaign1 = $this->createTestCampaign($setup1['org']->org_id);
        $content1 = $this->createTestContent($campaign1->campaign_id, [
            'title' => 'Org 1 Content',
        ]);

        // Switch to org2 context
        $this->clearTransactionContext();
        $this->actingAsUserInOrg($setup2['user'], $setup2['org']);

        // Create content in org2
        $campaign2 = $this->createTestCampaign($setup2['org']->org_id);
        $content2 = $this->createTestContent($campaign2->campaign_id, [
            'title' => 'Org 2 Content',
        ]);

        // Verify org2 user can only see their own content
        $allContent = $this->repository->getAllContent($setup2['org']->org_id);

        $contentIds = array_column($allContent, 'content_id');

        $this->assertContains($content2->content_id, $contentIds);
        $this->assertNotContains($content1->content_id, $contentIds);

        $this->logTestResult('passed', [
            'transaction_context' => 'enforced',
        ]);
    }

    #[Test]
    public function it_can_get_content_performance()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $content = $this->createTestContent($campaign->campaign_id, [
            'title' => 'Performance Content',
            'status' => 'published',
            'metrics' => [
                'views' => 10000,
                'likes' => 500,
                'comments' => 50,
            ],
        ]);

        $performance = $this->repository->getContentPerformance($content->content_id);

        $this->assertIsArray($performance);
        $this->assertArrayHasKey('metrics', $performance);

        $this->logTestResult('passed', [
            'content_id' => $content->content_id,
            'has_performance_data' => !empty($performance['metrics']),
        ]);
    }

    #[Test]
    public function it_can_bulk_update_content_status()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $content1 = $this->createTestContent($campaign->campaign_id, [
            'title' => 'Content 1',
            'status' => 'draft',
        ]);

        $content2 = $this->createTestContent($campaign->campaign_id, [
            'title' => 'Content 2',
            'status' => 'draft',
        ]);

        $result = $this->repository->bulkUpdateStatus([
            $content1->content_id,
            $content2->content_id,
        ], 'approved');

        $this->assertTrue($result);

        $this->logTestResult('passed', [
            'updated_count' => 2,
            'new_status' => 'approved',
        ]);
    }
}
