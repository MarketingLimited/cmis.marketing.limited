<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\InteractsWithRLS;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class CampaignAPITest extends TestCase
{
    use RefreshDatabase, CreatesTestData, InteractsWithRLS;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_list_campaigns_for_organization()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        // Create test campaigns
        $this->createTestCampaign($org->org_id, ['name' => 'Campaign 1']);
        $this->createTestCampaign($org->org_id, ['name' => 'Campaign 2']);
        $this->createTestCampaign($org->org_id, ['name' => 'Campaign 3']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/orgs/{$org->org_id}/campaigns");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'campaign_id',
                        'name',
                        'objective',
                        'status',
                        'budget',
                        'start_date',
                        'end_date',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/orgs/{org_id}/campaigns',
            'campaigns_count' => 3,
        ]);
    }

    /** @test */
    public function it_can_create_a_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $campaignData = [
            'name' => 'API Created Campaign',
            'objective' => 'conversions',
            'status' => 'draft',
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'budget' => 5000.00,
            'currency' => 'BHD',
            'description' => 'Created via API test',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orgs/{$org->org_id}/campaigns", $campaignData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'campaign_id',
                    'name',
                    'objective',
                    'status',
                ],
            ])
            ->assertJson([
                'data' => [
                    'name' => 'API Created Campaign',
                    'objective' => 'conversions',
                ],
            ]);

        $this->assertDatabaseHasWithRLS('cmis.campaigns', [
            'name' => 'API Created Campaign',
            'org_id' => $org->org_id,
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/orgs/{org_id}/campaigns',
            'campaign_name' => 'API Created Campaign',
        ]);
    }

    /** @test */
    public function it_can_view_a_specific_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'Viewable Campaign',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/orgs/{$org->org_id}/campaigns/{$campaign->campaign_id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'campaign_id' => $campaign->campaign_id,
                    'name' => 'Viewable Campaign',
                ],
            ]);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/orgs/{org_id}/campaigns/{campaign_id}',
            'campaign_id' => $campaign->campaign_id,
        ]);
    }

    /** @test */
    public function it_can_update_a_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'Original Name',
            'status' => 'draft',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'status' => 'active',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/orgs/{$org->org_id}/campaigns/{$campaign->campaign_id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                    'status' => 'active',
                ],
            ]);

        $this->assertDatabaseHasWithRLS('cmis.campaigns', [
            'campaign_id' => $campaign->campaign_id,
            'name' => 'Updated Name',
            'status' => 'active',
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'PUT /api/orgs/{org_id}/campaigns/{campaign_id}',
            'updated_fields' => ['name', 'status'],
        ]);
    }

    /** @test */
    public function it_can_delete_a_campaign()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $campaign = $this->createTestCampaign($org->org_id);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/orgs/{$org->org_id}/campaigns/{$campaign->campaign_id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('cmis.campaigns', [
            'campaign_id' => $campaign->campaign_id,
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'DELETE /api/orgs/{org_id}/campaigns/{campaign_id}',
            'soft_delete' => 'verified',
        ]);
    }

    /** @test */
    public function it_enforces_org_isolation_for_campaigns()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $campaign = $this->createTestCampaign($setup1['org']->org_id);

        // User from org2 tries to access org1's campaign
        $response = $this->actingAs($setup2['user'], 'sanctum')
            ->getJson("/api/orgs/{$setup2['org']->org_id}/campaigns/{$campaign->campaign_id}");

        $response->assertStatus(404);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/orgs/{org_id}/campaigns/{campaign_id}',
            'org_isolation' => 'enforced',
            'expected_status' => 404,
        ]);
    }

    /** @test */
    public function it_validates_campaign_creation_data()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $invalidData = [
            // Missing required fields
            'objective' => 'awareness',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orgs/{$org->org_id}/campaigns", $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/orgs/{org_id}/campaigns',
            'validation' => 'enforced',
        ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $response = $this->getJson("/api/orgs/{$org->org_id}/campaigns");

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/orgs/{org_id}/campaigns',
            'authentication' => 'required',
        ]);
    }

    /** @test */
    public function it_can_filter_campaigns_by_status()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->createTestCampaign($org->org_id, ['status' => 'draft']);
        $this->createTestCampaign($org->org_id, ['status' => 'active']);
        $this->createTestCampaign($org->org_id, ['status' => 'active']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/orgs/{$org->org_id}/campaigns?status=active");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/orgs/{org_id}/campaigns?status=active',
            'filter' => 'status',
            'filtered_count' => 2,
        ]);
    }

    /** @test */
    public function it_can_search_campaigns()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->createTestCampaign($org->org_id, ['name' => 'Summer Sale 2024']);
        $this->createTestCampaign($org->org_id, ['name' => 'Winter Campaign']);
        $this->createTestCampaign($org->org_id, ['name' => 'Summer Brand Launch']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/orgs/{$org->org_id}/campaigns?search=summer");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/orgs/{org_id}/campaigns?search=summer',
            'search_term' => 'summer',
            'results_count' => 2,
        ]);
    }
}
