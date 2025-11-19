<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Repositories\CMIS\CampaignRepository;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use Illuminate\Support\Str;

class CampaignRepositoryTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected CampaignRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(CampaignRepositoryInterface::class);
    }

    /** @test */
    public function it_can_create_campaign_with_context()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaignData = [
            'name' => 'Test Campaign',
            'objective' => 'awareness',
            'status' => 'draft',
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'budget' => 1000.00,
            'currency' => 'BHD',
        ];

        $campaign = $this->repository->createCampaignWithContext($campaignData);

        $this->assertNotNull($campaign);
        $this->assertEquals('Test Campaign', $campaign['name']);
        $this->assertEquals($org->org_id, $campaign['org_id']);

        $this->logTestResult('passed', [
            'campaign_id' => $campaign['campaign_id'] ?? null,
            'function' => 'createCampaignWithContext',
        ]);
    }

    /** @test */
    public function it_can_find_related_campaigns()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        // Create multiple campaigns
        $campaign1 = $this->createTestCampaign($org->org_id, [
            'name' => 'Summer Sale Campaign',
            'objective' => 'conversions',
        ]);

        $campaign2 = $this->createTestCampaign($org->org_id, [
            'name' => 'Summer Brand Awareness',
            'objective' => 'awareness',
        ]);

        $campaign3 = $this->createTestCampaign($org->org_id, [
            'name' => 'Winter Sale Campaign',
            'objective' => 'conversions',
        ]);

        // Find campaigns related to campaign1
        $relatedCampaigns = $this->repository->findRelatedCampaigns(
            $campaign1->campaign_id,
            10
        );

        $this->assertIsArray($relatedCampaigns);

        $this->logTestResult('passed', [
            'source_campaign' => $campaign1->campaign_id,
            'related_count' => count($relatedCampaigns),
        ]);
    }

    /** @test */
    public function it_can_get_campaign_contexts()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $contexts = $this->repository->getCampaignContexts(
            $campaign->campaign_id,
            true
        );

        $this->assertIsArray($contexts);

        $this->logTestResult('passed', [
            'campaign_id' => $campaign->campaign_id,
            'contexts_count' => count($contexts),
        ]);
    }

    /** @test */
    public function it_respects_transaction_context()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        // Create campaign in org1
        $this->actingAsUserInOrg($setup1['user'], $setup1['org']);
        $campaign1 = $this->createTestCampaign($setup1['org']->org_id);

        // Switch to org2 context
        $this->clearTransactionContext();
        $this->actingAsUserInOrg($setup2['user'], $setup2['org']);

        // Create campaign in org2
        $campaign2 = $this->createTestCampaign($setup2['org']->org_id);

        // Verify org2 user can only see their own campaign
        $campaigns = $this->repository->getCampaignsForOrg($setup2['org']->org_id);

        $campaignIds = array_column($campaigns, 'campaign_id');

        $this->assertContains($campaign2->campaign_id, $campaignIds);
        $this->assertNotContains($campaign1->campaign_id, $campaignIds);

        $this->logTestResult('passed', [
            'transaction_context' => 'enforced',
        ]);
    }
}
