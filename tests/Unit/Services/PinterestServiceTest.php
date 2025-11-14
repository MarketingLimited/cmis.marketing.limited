<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Social\PinterestService;

/**
 * Pinterest Service Unit Tests
 */
class PinterestServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected PinterestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->service = app(PinterestService::class);
    }

    /** @test */
    public function it_can_create_pin()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'pinterest');

        $this->mockPinterestAPI('success', [
            'id' => 'pin_123',
            'link' => 'https://pinterest.com/pin/123',
        ]);

        $result = $this->service->createPin($integration, [
            'board_id' => 'board_456',
            'title' => 'منتج صيفي جديد',
            'description' => 'اكتشف مجموعتنا الصيفية الجديدة',
            'media_url' => 'https://example.com/image.jpg',
            'link' => 'https://example.com/products/summer',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('pin_123', $result['pin_id']);

        $this->logTestResult('passed', [
            'service' => 'PinterestService',
            'method' => 'createPin',
        ]);
    }

    /** @test */
    public function it_can_create_story_pin()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'pinterest');

        $this->mockPinterestAPI('success', [
            'id' => 'story_pin_789',
        ]);

        $result = $this->service->createStoryPin($integration, [
            'board_id' => 'board_456',
            'title' => 'قصة منتجنا',
            'pages' => [
                [
                    'image_url' => 'https://example.com/page1.jpg',
                    'title' => 'الصفحة الأولى',
                ],
                [
                    'image_url' => 'https://example.com/page2.jpg',
                    'title' => 'الصفحة الثانية',
                ],
            ],
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'PinterestService',
            'method' => 'createStoryPin',
        ]);
    }

    /** @test */
    public function it_can_create_board()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'pinterest');

        $this->mockPinterestAPI('success', [
            'id' => 'board_new_123',
            'name' => 'لوحة جديدة',
        ]);

        $result = $this->service->createBoard($integration, [
            'name' => 'لوحة جديدة',
            'description' => 'وصف اللوحة',
            'privacy' => 'PUBLIC',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('board_new_123', $result['board_id']);

        $this->logTestResult('passed', [
            'service' => 'PinterestService',
            'method' => 'createBoard',
        ]);
    }

    /** @test */
    public function it_can_get_board_pins()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'pinterest');

        $this->mockPinterestAPI('success', [
            'items' => [
                ['id' => 'pin_1', 'title' => 'Pin 1'],
                ['id' => 'pin_2', 'title' => 'Pin 2'],
                ['id' => 'pin_3', 'title' => 'Pin 3'],
            ],
        ]);

        $result = $this->service->getBoardPins($integration, 'board_456');

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['pins']);

        $this->logTestResult('passed', [
            'service' => 'PinterestService',
            'method' => 'getBoardPins',
        ]);
    }

    /** @test */
    public function it_can_get_pin_analytics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'pinterest');

        $this->mockPinterestAPI('success', [
            'all' => [
                'daily_metrics' => [
                    [
                        'date' => '2024-01-15',
                        'metrics' => [
                            'IMPRESSION' => 5000,
                            'PIN_CLICK' => 250,
                            'SAVE' => 120,
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->service->getPinAnalytics($integration, 'pin_123', [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('analytics', $result);

        $this->logTestResult('passed', [
            'service' => 'PinterestService',
            'method' => 'getPinAnalytics',
        ]);
    }

    /** @test */
    public function it_can_get_user_analytics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'pinterest');

        $this->mockPinterestAPI('success', [
            'all' => [
                'daily_metrics' => [
                    [
                        'date' => '2024-01-15',
                        'metrics' => [
                            'IMPRESSION' => 50000,
                            'ENGAGEMENTS' => 2500,
                            'OUTBOUND_CLICK' => 1200,
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->service->getUserAnalytics($integration, [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'PinterestService',
            'method' => 'getUserAnalytics',
        ]);
    }

    /** @test */
    public function it_can_search_pins()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'pinterest');

        $this->mockPinterestAPI('success', [
            'items' => [
                ['id' => 'pin_search_1', 'title' => 'نتيجة 1'],
                ['id' => 'pin_search_2', 'title' => 'نتيجة 2'],
            ],
        ]);

        $result = $this->service->searchPins($integration, [
            'query' => 'ديكور منزلي',
            'limit' => 20,
        ]);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, count($result['pins']));

        $this->logTestResult('passed', [
            'service' => 'PinterestService',
            'method' => 'searchPins',
        ]);
    }

    /** @test */
    public function it_can_delete_pin()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'pinterest');

        $this->mockPinterestAPI('success', []);

        $result = $this->service->deletePin($integration, 'pin_123');

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'PinterestService',
            'method' => 'deletePin',
        ]);
    }

    /** @test */
    public function it_can_update_board()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'pinterest');

        $this->mockPinterestAPI('success', [
            'id' => 'board_456',
            'name' => 'اسم محدث',
        ]);

        $result = $this->service->updateBoard($integration, 'board_456', [
            'name' => 'اسم محدث',
            'description' => 'وصف محدث',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'PinterestService',
            'method' => 'updateBoard',
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'pinterest');

        $result = $this->service->createPin($integration, [
            'title' => 'Test Pin',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'service' => 'PinterestService',
            'test' => 'validation',
        ]);
    }

    /** @test */
    public function it_handles_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'pinterest');

        $this->mockPinterestAPI('error');

        $result = $this->service->createPin($integration, [
            'board_id' => 'board_456',
            'title' => 'Test',
            'media_url' => 'https://example.com/image.jpg',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'service' => 'PinterestService',
            'test' => 'error_handling',
        ]);
    }

    /** @test */
    public function it_handles_rate_limiting()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'pinterest');

        Http::fake([
            'api.pinterest.com/*' => Http::response([
                'code' => 429,
                'message' => 'Rate limit exceeded',
            ], 429),
        ]);

        $result = $this->service->createPin($integration, [
            'board_id' => 'board_456',
            'title' => 'Test',
            'media_url' => 'https://example.com/image.jpg',
        ]);

        $this->assertFalse($result['success']);

        $this->logTestResult('passed', [
            'service' => 'PinterestService',
            'test' => 'rate_limiting',
        ]);
    }
}
