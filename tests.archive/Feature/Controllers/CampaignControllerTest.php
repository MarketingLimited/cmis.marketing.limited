<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Core\Campaign;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Campaign Controller Feature Tests
 */
class CampaignControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_list_all_campaigns()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->createTestCampaign($org->org_id);
        $this->createTestCampaign($org->org_id);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/campaigns');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'endpoint' => 'GET /api/campaigns',
        ]);
    }

    #[Test]
    public function it_can_create_campaign()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/campaigns', [
            'name' => 'حملة الصيف 2024',
            'description' => 'حملة ترويجية لفصل الصيف',
            'status' => 'draft',
            'start_date' => '2024-06-01',
            'end_date' => '2024-08-31',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'حملة الصيف 2024');

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'endpoint' => 'POST /api/campaigns',
        ]);
    }

    #[Test]
    public function it_can_get_single_campaign()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'Test Campaign',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/campaigns/{$campaign->campaign_id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Test Campaign');

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'endpoint' => 'GET /api/campaigns/{id}',
        ]);
    }

    #[Test]
    public function it_can_update_campaign()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'Original Name',
            'status' => 'draft',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->putJson("/api/campaigns/{$campaign->campaign_id}", [
            'name' => 'Updated Name',
            'status' => 'active',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Name');
        $response->assertJsonPath('data.status', 'active');

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'endpoint' => 'PUT /api/campaigns/{id}',
        ]);
    }

    #[Test]
    public function it_can_delete_campaign()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);

        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/campaigns/{$campaign->campaign_id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('cmis.campaigns', [
            'campaign_id' => $campaign->campaign_id,
        ]);

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'endpoint' => 'DELETE /api/campaigns/{id}',
        ]);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/campaigns', [
            'description' => 'Campaign without name',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'test' => 'validation',
        ]);
    }

    #[Test]
    public function it_can_filter_campaigns_by_status()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->createTestCampaign($org->org_id, ['status' => 'active']);
        $this->createTestCampaign($org->org_id, ['status' => 'active']);
        $this->createTestCampaign($org->org_id, ['status' => 'draft']);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/campaigns?status=active');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'test' => 'status_filtering',
        ]);
    }

    #[Test]
    public function it_can_search_campaigns()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->createTestCampaign($org->org_id, ['name' => 'Summer Sale Campaign']);
        $this->createTestCampaign($org->org_id, ['name' => 'Winter Sale Campaign']);
        $this->createTestCampaign($org->org_id, ['name' => 'Back to School']);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/campaigns?search=Summer');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'test' => 'search',
        ]);
    }

    #[Test]
    public function it_can_get_campaign_analytics()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/campaigns/{$campaign->campaign_id}/analytics");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'impressions',
                'clicks',
                'conversions',
            ],
        ]);

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'endpoint' => 'GET /api/campaigns/{id}/analytics',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $campaign1 = $this->createTestCampaign($setup1['org']->org_id);

        $this->actingAs($setup2['user'], 'sanctum');

        $response = $this->getJson("/api/campaigns/{$campaign1->campaign_id}");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/campaigns');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_can_duplicate_campaign()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'Original Campaign',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/campaigns/{$campaign->campaign_id}/duplicate");

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Original Campaign (Copy)');

        $this->logTestResult('passed', [
            'controller' => 'CampaignController',
            'endpoint' => 'POST /api/campaigns/{id}/duplicate',
        ]);
    }
}
