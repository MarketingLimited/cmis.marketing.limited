<?php

namespace Tests\Integration\AdPlatform;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AdPlatform\LinkedInAdsService;
use App\Models\AdPlatform\AdCampaign;
use App\Models\AdPlatform\AdSet;
use App\Models\AdPlatform\Ad;
use Illuminate\Support\Str;

/**
 * LinkedIn Ads Platform Integration Tests
 */
class LinkedInAdsWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_creates_linkedin_ad_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin_ads');

        $this->mockLinkedInAPI('success', [
            'id' => 123456789,
            'name' => 'B2B Lead Generation Campaign',
            'status' => 'ACTIVE',
            'type' => 'SPONSORED_UPDATES',
        ]);

        $campaignData = [
            'account' => 'urn:li:sponsoredAccount:123456',
            'name' => 'B2B Lead Generation Campaign',
            'type' => 'SPONSORED_UPDATES',
            'status' => 'ACTIVE',
            'objective_type' => 'LEAD_GENERATION',
            'cost_type' => 'CPM',
            'daily_budget' => [
                'amount' => '100',
                'currencyCode' => 'USD',
            ],
        ];

        $service = app(LinkedInAdsService::class);
        $result = $service->createCampaign($integration, $campaignData);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.ad_campaigns', [
            'org_id' => $org->org_id,
            'platform' => 'linkedin',
            'external_campaign_id' => '123456789',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'linkedin_ads',
            'action' => 'create_campaign',
        ]);
    }

    /** @test */
    public function it_creates_linkedin_sponsored_content()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'linkedin',
            'external_campaign_id' => '123456789',
            'name' => 'B2B Lead Generation Campaign',
            'objective' => 'lead_generation',
            'status' => 'active',
        ]);

        $this->mockLinkedInAPI('success', [
            'id' => 'urn:li:sponsoredCreative:987654321',
            'campaign' => 'urn:li:sponsoredCampaign:123456789',
        ]);

        $adData = [
            'campaign' => 'urn:li:sponsoredCampaign:123456789',
            'creative' => [
                'type' => 'SPONSORED_STATUS_UPDATE',
                'reference' => 'urn:li:share:share_123',
                'variables' => [
                    'clickUri' => 'https://example.com/landing-page',
                    'data' => [
                        'com.linkedin.ads.SponsoredUpdateCreativeVariables' => [
                            'activity' => 'urn:li:activity:activity_123',
                        ],
                    ],
                ],
            ],
        ];

        $service = app(LinkedInAdsService::class);
        $result = $service->createSponsoredContent($integration, $adData);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('cmis.ads', [
            'org_id' => $org->org_id,
            'platform' => 'linkedin',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'linkedin_ads',
            'action' => 'create_sponsored_content',
        ]);
    }

    /** @test */
    public function it_creates_linkedin_lead_gen_form()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin_ads');

        $this->mockLinkedInAPI('success', [
            'id' => 123456,
            'name' => 'Download Whitepaper Form',
            'locale' => [
                'language' => 'en',
                'country' => 'US',
            ],
        ]);

        $formData = [
            'account' => 'urn:li:sponsoredAccount:123456',
            'name' => 'Download Whitepaper Form',
            'locale' => [
                'language' => 'en',
                'country' => 'US',
            ],
            'privacyPolicyUrl' => 'https://example.com/privacy',
            'headline' => 'Get Your Free Marketing Guide',
            'description' => 'Download our comprehensive marketing guide for 2024',
            'submitButtonText' => 'Download Now',
            'fields' => [
                ['type' => 'FIRST_NAME'],
                ['type' => 'LAST_NAME'],
                ['type' => 'EMAIL'],
                ['type' => 'COMPANY'],
                ['type' => 'TITLE'],
            ],
        ];

        $service = app(LinkedInAdsService::class);
        $result = $service->createLeadGenForm($integration, $formData);

        $this->assertTrue($result['success']);
        $this->assertEquals(123456, $result['form_id']);

        $this->logTestResult('passed', [
            'workflow' => 'linkedin_ads',
            'action' => 'create_lead_gen_form',
        ]);
    }

    /** @test */
    public function it_creates_linkedin_audience_targeting()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin_ads');

        $this->mockLinkedInAPI('success', [
            'id' => 'targeting_123',
        ]);

        $targetingData = [
            'includedTargetingFacets' => [
                'locations' => ['urn:li:geo:102095887'], // California
                'industries' => ['urn:li:industry:4'], // Computer Software
                'seniorities' => [
                    'urn:li:seniority:5', // Manager
                    'urn:li:seniority:6', // Director
                ],
                'jobFunctions' => ['urn:li:function:3'], // Marketing
                'companySize' => [
                    'urn:li:companySize:C', // 51-200 employees
                    'urn:li:companySize:D', // 201-500 employees
                ],
            ],
            'excludedTargetingFacets' => [
                'jobFunctions' => ['urn:li:function:25'], // Student
            ],
        ];

        $service = app(LinkedInAdsService::class);
        $result = $service->createAudienceTargeting($integration, $targetingData);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'linkedin_ads',
            'action' => 'create_audience_targeting',
        ]);
    }

    /** @test */
    public function it_fetches_linkedin_campaign_analytics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'linkedin',
            'external_campaign_id' => '123456789',
            'name' => 'B2B Lead Generation Campaign',
            'objective' => 'lead_generation',
            'status' => 'active',
        ]);

        $this->mockLinkedInAPI('success', [
            'elements' => [
                [
                    'campaignId' => 123456789,
                    'impressions' => 50000,
                    'clicks' => 1500,
                    'costInLocalCurrency' => 300.50,
                    'leads' => 75,
                    'externalWebsiteConversions' => 120,
                    'dateRange' => [
                        'start' => [
                            'year' => 2024,
                            'month' => 6,
                            'day' => 1,
                        ],
                        'end' => [
                            'year' => 2024,
                            'month' => 6,
                            'day' => 30,
                        ],
                    ],
                ],
            ],
        ]);

        $service = app(LinkedInAdsService::class);
        $result = $service->getCampaignAnalytics($integration, '123456789');

        $this->assertTrue($result['success']);
        $this->assertEquals(50000, $result['data']['impressions']);
        $this->assertEquals(75, $result['data']['leads']);

        $this->logTestResult('passed', [
            'workflow' => 'linkedin_ads',
            'action' => 'fetch_analytics',
        ]);
    }

    /** @test */
    public function it_fetches_linkedin_lead_gen_submissions()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin_ads');

        $this->mockLinkedInAPI('success', [
            'elements' => [
                [
                    'id' => 'lead_submission_123',
                    'formId' => 123456,
                    'submittedAt' => 1622505600000,
                    'formResponse' => [
                        'firstName' => 'أحمد',
                        'lastName' => 'محمد',
                        'email' => 'ahmed@example.com',
                        'company' => 'Tech Solutions',
                        'title' => 'Marketing Manager',
                    ],
                ],
            ],
        ]);

        $service = app(LinkedInAdsService::class);
        $result = $service->getLeadGenSubmissions($integration, 123456);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['leads']);
        $this->assertEquals('ahmed@example.com', $result['leads'][0]['email']);

        $this->logTestResult('passed', [
            'workflow' => 'linkedin_ads',
            'action' => 'fetch_lead_submissions',
        ]);
    }

    /** @test */
    public function it_creates_linkedin_text_ad()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin_ads');

        $this->mockLinkedInAPI('success', [
            'id' => 'text_ad_123',
            'campaign' => 'urn:li:sponsoredCampaign:123456789',
        ]);

        $textAdData = [
            'campaign' => 'urn:li:sponsoredCampaign:123456789',
            'creative' => [
                'type' => 'TEXT_AD',
                'variables' => [
                    'data' => [
                        'com.linkedin.ads.TextAdCreativeVariables' => [
                            'text' => 'احصل على دليل التسويق المجاني',
                            'title' => 'دليل التسويق الرقمي 2024',
                        ],
                    ],
                    'clickUri' => 'https://example.com/download',
                ],
            ],
        ];

        $service = app(LinkedInAdsService::class);
        $result = $service->createTextAd($integration, $textAdData);

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'workflow' => 'linkedin_ads',
            'action' => 'create_text_ad',
        ]);
    }

    /** @test */
    public function it_pauses_linkedin_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'linkedin',
            'external_campaign_id' => '123456789',
            'name' => 'B2B Lead Generation Campaign',
            'objective' => 'lead_generation',
            'status' => 'active',
        ]);

        $this->mockLinkedInAPI('success', [
            'id' => 123456789,
            'status' => 'PAUSED',
        ]);

        $service = app(LinkedInAdsService::class);
        $result = $service->pauseCampaign($integration, '123456789');

        $this->assertTrue($result['success']);

        $campaign->refresh();
        $this->assertEquals('paused', $campaign->status);

        $this->logTestResult('passed', [
            'workflow' => 'linkedin_ads',
            'action' => 'pause_campaign',
        ]);
    }

    /** @test */
    public function it_updates_linkedin_campaign_budget()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin_ads');

        $campaign = AdCampaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'linkedin',
            'external_campaign_id' => '123456789',
            'name' => 'B2B Lead Generation Campaign',
            'objective' => 'lead_generation',
            'status' => 'active',
            'daily_budget' => 100.00,
        ]);

        $this->mockLinkedInAPI('success', [
            'id' => 123456789,
            'dailyBudget' => [
                'amount' => '150',
                'currencyCode' => 'USD',
            ],
        ]);

        $service = app(LinkedInAdsService::class);
        $result = $service->updateCampaignBudget($integration, '123456789', 150.00);

        $this->assertTrue($result['success']);

        $campaign->refresh();
        $this->assertEquals(150.00, $campaign->daily_budget);

        $this->logTestResult('passed', [
            'workflow' => 'linkedin_ads',
            'action' => 'update_budget',
        ]);
    }

    /** @test */
    public function it_handles_linkedin_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin_ads');

        $this->mockLinkedInAPI('error');

        $service = app(LinkedInAdsService::class);
        $result = $service->createCampaign($integration, [
            'name' => 'Test Campaign',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        $this->logTestResult('passed', [
            'workflow' => 'linkedin_ads',
            'test' => 'error_handling',
        ]);
    }
}
