<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Social\GoogleBusinessService;

/**
 * Google My Business Service Unit Tests
 */
class GoogleBusinessServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected GoogleBusinessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->service = app(GoogleBusinessService::class);
    }

    /** @test */
    public function it_can_create_local_post()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_business');

        $this->mockGoogleAPI('success', [
            'name' => 'locations/123/localPosts/456',
            'state' => 'LIVE',
        ]);

        $result = $this->service->createLocalPost($integration, [
            'location_id' => 'locations/123',
            'summary' => 'عرض خاص لهذا الأسبوع! خصم 30%',
            'event' => [
                'title' => 'حدث خاص',
                'schedule' => [
                    'start_date' => '2024-06-01',
                    'end_date' => '2024-06-07',
                ],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('localPosts', $result['post_id']);

        $this->logTestResult('passed', [
            'service' => 'GoogleBusinessService',
            'method' => 'createLocalPost',
        ]);
    }

    /** @test */
    public function it_can_create_offer_post()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_business');

        $this->mockGoogleAPI('success', [
            'name' => 'locations/123/localPosts/789',
            'state' => 'LIVE',
        ]);

        $result = $this->service->createOfferPost($integration, [
            'location_id' => 'locations/123',
            'summary' => 'خصم 50% على جميع المنتجات',
            'offer' => [
                'coupon_code' => 'SUMMER50',
                'redeem_online_url' => 'https://example.com/offers',
                'terms_conditions' => 'ينتهي العرض في 31 يوليو',
            ],
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'GoogleBusinessService',
            'method' => 'createOfferPost',
        ]);
    }

    /** @test */
    public function it_can_get_location_insights()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_business');

        $this->mockGoogleAPI('success', [
            'locationMetrics' => [
                [
                    'metricValues' => [
                        ['metric' => 'QUERIES_DIRECT', 'totalValue' => ['value' => '1500']],
                        ['metric' => 'QUERIES_INDIRECT', 'totalValue' => ['value' => '3200']],
                        ['metric' => 'VIEWS_MAPS', 'totalValue' => ['value' => '2800']],
                        ['metric' => 'VIEWS_SEARCH', 'totalValue' => ['value' => '4500']],
                    ],
                ],
            ],
        ]);

        $result = $this->service->getLocationInsights($integration, 'locations/123', [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('metrics', $result);

        $this->logTestResult('passed', [
            'service' => 'GoogleBusinessService',
            'method' => 'getLocationInsights',
        ]);
    }

    /** @test */
    public function it_can_get_reviews()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_business');

        $this->mockGoogleAPI('success', [
            'reviews' => [
                [
                    'reviewId' => 'review_123',
                    'reviewer' => ['displayName' => 'أحمد'],
                    'starRating' => 'FIVE',
                    'comment' => 'خدمة ممتازة!',
                    'createTime' => '2024-01-15T10:30:00Z',
                ],
                [
                    'reviewId' => 'review_456',
                    'reviewer' => ['displayName' => 'فاطمة'],
                    'starRating' => 'FOUR',
                    'comment' => 'جيد جداً',
                    'createTime' => '2024-01-20T14:20:00Z',
                ],
            ],
        ]);

        $result = $this->service->getReviews($integration, 'locations/123');

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['reviews']);

        $this->logTestResult('passed', [
            'service' => 'GoogleBusinessService',
            'method' => 'getReviews',
        ]);
    }

    /** @test */
    public function it_can_reply_to_review()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_business');

        $this->mockGoogleAPI('success', [
            'comment' => 'شكراً لك على تقييمك الإيجابي!',
        ]);

        $result = $this->service->replyToReview($integration, [
            'location_id' => 'locations/123',
            'review_id' => 'review_123',
            'comment' => 'شكراً لك على تقييمك الإيجابي!',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'GoogleBusinessService',
            'method' => 'replyToReview',
        ]);
    }

    /** @test */
    public function it_can_update_business_hours()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_business');

        $this->mockGoogleAPI('success', [
            'name' => 'locations/123',
        ]);

        $result = $this->service->updateBusinessHours($integration, 'locations/123', [
            'periods' => [
                [
                    'openDay' => 'SUNDAY',
                    'openTime' => '09:00',
                    'closeDay' => 'SUNDAY',
                    'closeTime' => '22:00',
                ],
            ],
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'GoogleBusinessService',
            'method' => 'updateBusinessHours',
        ]);
    }

    /** @test */
    public function it_can_get_location_questions()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_business');

        $this->mockGoogleAPI('success', [
            'questions' => [
                [
                    'name' => 'locations/123/questions/q1',
                    'author' => ['displayName' => 'محمد'],
                    'text' => 'هل لديكم خدمة التوصيل؟',
                    'createTime' => '2024-01-10T12:00:00Z',
                ],
            ],
        ]);

        $result = $this->service->getQuestions($integration, 'locations/123');

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, count($result['questions']));

        $this->logTestResult('passed', [
            'service' => 'GoogleBusinessService',
            'method' => 'getQuestions',
        ]);
    }

    /** @test */
    public function it_can_answer_question()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_business');

        $this->mockGoogleAPI('success', [
            'name' => 'locations/123/questions/q1/answers/a1',
            'text' => 'نعم، نوفر خدمة التوصيل المجاني',
        ]);

        $result = $this->service->answerQuestion($integration, [
            'question_name' => 'locations/123/questions/q1',
            'answer_text' => 'نعم، نوفر خدمة التوصيل المجاني',
        ]);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'service' => 'GoogleBusinessService',
            'method' => 'answerQuestion',
        ]);
    }

    /** @test */
    public function it_validates_location_id()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_business');

        $result = $this->service->createLocalPost($integration, [
            'summary' => 'Test post',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('location', strtolower($result['error']));

        $this->logTestResult('passed', [
            'service' => 'GoogleBusinessService',
            'test' => 'validation',
        ]);
    }

    /** @test */
    public function it_handles_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'google_business');

        $this->mockGoogleAPI('error');

        $result = $this->service->createLocalPost($integration, [
            'location_id' => 'locations/123',
            'summary' => 'Test',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'service' => 'GoogleBusinessService',
            'test' => 'error_handling',
        ]);
    }
}
