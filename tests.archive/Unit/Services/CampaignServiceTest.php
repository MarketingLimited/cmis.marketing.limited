<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CampaignService;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Mockery;

use PHPUnit\Framework\Attributes\Test;
class CampaignServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected CampaignService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CampaignService::class);
    }

    #[Test]
    public function it_can_create_campaign_with_context()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaignData = [
            'name' => 'Service Test Campaign',
            'objective' => 'awareness',
            'status' => 'draft',
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'budget' => 2000.00,
            'currency' => 'BHD',
            'description' => 'Test campaign created via service',
        ];

        $campaign = $this->service->createWithContext($campaignData);

        $this->assertNotNull($campaign);
        $this->assertEquals('Service Test Campaign', $campaign->name);
        $this->assertEquals($org->org_id, $campaign->org_id);

        $this->assertDatabaseHasWithRLS('cmis.campaigns', [
            'campaign_id' => $campaign->campaign_id,
            'name' => 'Service Test Campaign',
        ]);

        $this->logTestResult('passed', [
            'campaign_id' => $campaign->campaign_id,
            'service_method' => 'createWithContext',
        ]);
    }

    #[Test]
    public function it_can_update_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'Original Name',
            'status' => 'draft',
        ]);

        $updated = $this->service->update($campaign->campaign_id, [
            'name' => 'Updated Name',
            'status' => 'active',
        ]);

        $this->assertTrue($updated);

        $campaign = $campaign->fresh();

        $this->assertEquals('Updated Name', $campaign->name);
        $this->assertEquals('active', $campaign->status);

        $this->logTestResult('passed', [
            'campaign_id' => $campaign->campaign_id,
            'updated_fields' => ['name', 'status'],
        ]);
    }

    #[Test]
    public function it_can_find_related_campaigns()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign1 = $this->createTestCampaign($org->org_id, [
            'name' => 'Product Launch Q1',
            'objective' => 'conversions',
        ]);

        $campaign2 = $this->createTestCampaign($org->org_id, [
            'name' => 'Product Launch Q2',
            'objective' => 'conversions',
        ]);

        $relatedCampaigns = $this->service->findRelatedCampaigns($campaign1->campaign_id);

        $this->assertIsArray($relatedCampaigns);

        $this->logTestResult('passed', [
            'source_campaign' => $campaign1->campaign_id,
            'related_found' => count($relatedCampaigns),
        ]);
    }

    #[Test]
    public function it_can_get_campaign_contexts()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);

        $contexts = $this->service->getCampaignContexts($campaign->campaign_id);

        $this->assertIsArray($contexts);

        $this->logTestResult('passed', [
            'campaign_id' => $campaign->campaign_id,
            'contexts_retrieved' => count($contexts),
        ]);
    }

    #[Test]
    public function it_enforces_org_isolation()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        // Create campaign in org1
        $this->actingAsUserInOrg($setup1['user'], $setup1['org']);
        $campaign = $this->createTestCampaign($setup1['org']->org_id);

        // Try to access from org2
        $this->clearTransactionContext();
        $this->actingAsUserInOrg($setup2['user'], $setup2['org']);

        $result = $this->service->getCampaign($campaign->campaign_id);

        // Should return null or throw exception due to RLS
        $this->assertNull($result);

        $this->logTestResult('passed', [
            'org_isolation' => 'enforced',
            'test_type' => 'cross_org_access_denied',
        ]);
    }

    #[Test]
    public function it_handles_campaign_deletion()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $campaign = $this->createTestCampaign($org->org_id);
        $campaignId = $campaign->campaign_id;

        $deleted = $this->service->delete($campaignId);

        $this->assertTrue($deleted);

        $this->assertSoftDeleted('cmis.campaigns', [
            'campaign_id' => $campaignId,
        ]);

        $this->logTestResult('passed', [
            'campaign_id' => $campaignId,
            'soft_delete' => 'verified',
        ]);
    }
}
