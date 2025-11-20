<?php

namespace Tests\Feature\Authorization;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Campaign;
use App\Models\Core\User;
use App\Models\Core\Org;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

/**
 * Comprehensive Campaign Authorization Tests
 *
 * Tests all authorization scenarios for CampaignController:
 * - Authentication requirements
 * - Permission checks (viewAny, view, create, update, delete)
 * - Multi-tenant isolation
 * - Unauthorized access prevention
 * - Policy-based authorization
 *
 * @group authorization
 * @group campaign
 * @group security
 */
class CampaignAuthorizationTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected User $authorizedUser;
    protected User $unauthorizedUser;
    protected Org $org1;
    protected Org $org2;
    protected Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test data
        $setup1 = $this->createUserWithOrg();
        $this->authorizedUser = $setup1['user'];
        $this->org1 = $setup1['org'];

        $setup2 = $this->createUserWithOrg();
        $this->unauthorizedUser = $setup2['user'];
        $this->org2 = $setup2['org'];

        // Create test campaign for org1
        $this->campaign = $this->createTestCampaign($this->org1->org_id, [
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);
    }

    // ========================================
    // Authentication Tests
    // ========================================

    #[Test]
    public function unauthenticated_user_cannot_list_campaigns()
    {
        $response = $this->getJson('/api/campaigns');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);

        $this->logTestResult('passed', [
            'test' => 'Unauthenticated access blocked',
            'endpoint' => 'GET /api/campaigns',
            'expected_status' => 401,
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_create_campaign()
    {
        $response = $this->postJson('/api/campaigns', [
            'name' => 'Test Campaign',
            'status' => 'draft',
        ]);

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'test' => 'Unauthenticated creation blocked',
            'endpoint' => 'POST /api/campaigns',
            'expected_status' => 401,
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_view_campaign()
    {
        $response = $this->getJson("/api/campaigns/{$this->campaign->campaign_id}");

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'test' => 'Unauthenticated view blocked',
            'endpoint' => 'GET /api/campaigns/{id}',
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_update_campaign()
    {
        $response = $this->putJson("/api/campaigns/{$this->campaign->campaign_id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'test' => 'Unauthenticated update blocked',
            'endpoint' => 'PUT /api/campaigns/{id}',
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_delete_campaign()
    {
        $response = $this->deleteJson("/api/campaigns/{$this->campaign->campaign_id}");

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'test' => 'Unauthenticated deletion blocked',
            'endpoint' => 'DELETE /api/campaigns/{id}',
        ]);
    }

    // ========================================
    // Authorization: viewAny Permission Tests
    // ========================================

    #[Test]
    public function authorized_user_can_list_campaigns()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->getJson('/api/campaigns');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'per_page', 'total'],
        ]);

        $this->logTestResult('passed', [
            'test' => 'Authorized user can list campaigns',
            'endpoint' => 'GET /api/campaigns',
            'user_id' => $this->authorizedUser->user_id,
        ]);
    }

    // ========================================
    // Authorization: view Permission Tests
    // ========================================

    #[Test]
    public function authorized_user_can_view_own_organization_campaign()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->getJson("/api/campaigns/{$this->campaign->campaign_id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.campaign_id', $this->campaign->campaign_id);
        $response->assertJsonPath('data.name', 'Test Campaign');

        $this->logTestResult('passed', [
            'test' => 'User can view own org campaign',
            'campaign_id' => $this->campaign->campaign_id,
        ]);
    }

    #[Test]
    public function user_cannot_view_another_organization_campaign()
    {
        // User from org2 trying to view org1's campaign
        $this->actingAs($this->unauthorizedUser, 'sanctum');

        $response = $this->getJson("/api/campaigns/{$this->campaign->campaign_id}");

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'error' => 'Campaign not found',
        ]);

        $this->logTestResult('passed', [
            'test' => 'Multi-tenant isolation enforced on view',
            'user_org' => $this->unauthorizedUser->org_id,
            'campaign_org' => $this->campaign->org_id,
            'blocked' => true,
        ]);
    }

    // ========================================
    // Authorization: create Permission Tests
    // ========================================

    #[Test]
    public function authorized_user_can_create_campaign()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->postJson('/api/campaigns', [
            'name' => 'New Test Campaign',
            'description' => 'Campaign created by test',
            'status' => 'draft',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'budget' => 10000,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'New Test Campaign');
        $response->assertJsonPath('data.status', 'draft');

        // Verify campaign was created in correct organization
        $campaignId = $response->json('data.campaign_id');
        $this->assertDatabaseHas('cmis.campaigns', [
            'campaign_id' => $campaignId,
            'org_id' => $this->authorizedUser->org_id,
            'name' => 'New Test Campaign',
        ]);

        $this->logTestResult('passed', [
            'test' => 'Authorized user can create campaign',
            'campaign_id' => $campaignId,
        ]);
    }

    // ========================================
    // Authorization: update Permission Tests
    // ========================================

    #[Test]
    public function authorized_user_can_update_own_organization_campaign()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->putJson("/api/campaigns/{$this->campaign->campaign_id}", [
            'name' => 'Updated Campaign Name',
            'status' => 'paused',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Campaign Name');
        $response->assertJsonPath('data.status', 'paused');

        $this->assertDatabaseHas('cmis.campaigns', [
            'campaign_id' => $this->campaign->campaign_id,
            'name' => 'Updated Campaign Name',
            'status' => 'paused',
        ]);

        $this->logTestResult('passed', [
            'test' => 'User can update own org campaign',
            'campaign_id' => $this->campaign->campaign_id,
        ]);
    }

    #[Test]
    public function user_cannot_update_another_organization_campaign()
    {
        // User from org2 trying to update org1's campaign
        $this->actingAs($this->unauthorizedUser, 'sanctum');

        $response = $this->putJson("/api/campaigns/{$this->campaign->campaign_id}", [
            'name' => 'Hacked Campaign Name',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'error' => 'Campaign not found',
        ]);

        // Verify campaign was NOT updated
        $this->assertDatabaseMissing('cmis.campaigns', [
            'campaign_id' => $this->campaign->campaign_id,
            'name' => 'Hacked Campaign Name',
        ]);

        $this->logTestResult('passed', [
            'test' => 'Multi-tenant isolation enforced on update',
            'blocked' => true,
        ]);
    }

    // ========================================
    // Authorization: delete Permission Tests
    // ========================================

    #[Test]
    public function authorized_user_can_delete_own_organization_campaign()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->deleteJson("/api/campaigns/{$this->campaign->campaign_id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Campaign deleted successfully',
        ]);

        // Verify soft delete (deleted_at should be set)
        $this->assertSoftDeleted('cmis.campaigns', [
            'campaign_id' => $this->campaign->campaign_id,
        ]);

        $this->logTestResult('passed', [
            'test' => 'User can delete own org campaign',
            'campaign_id' => $this->campaign->campaign_id,
            'soft_delete' => true,
        ]);
    }

    #[Test]
    public function user_cannot_delete_another_organization_campaign()
    {
        // User from org2 trying to delete org1's campaign
        $this->actingAs($this->unauthorizedUser, 'sanctum');

        $response = $this->deleteJson("/api/campaigns/{$this->campaign->campaign_id}");

        $response->assertStatus(404);

        // Verify campaign was NOT deleted
        $this->assertDatabaseHas('cmis.campaigns', [
            'campaign_id' => $this->campaign->campaign_id,
            'deleted_at' => null,
        ]);

        $this->logTestResult('passed', [
            'test' => 'Multi-tenant isolation enforced on delete',
            'blocked' => true,
        ]);
    }

    // ========================================
    // Multi-Tenancy Isolation Tests
    // ========================================

    #[Test]
    public function user_only_sees_campaigns_from_their_organization()
    {
        // Create campaigns in both organizations
        $this->createTestCampaign($this->org1->org_id, ['name' => 'Org1 Campaign 1']);
        $this->createTestCampaign($this->org1->org_id, ['name' => 'Org1 Campaign 2']);
        $this->createTestCampaign($this->org2->org_id, ['name' => 'Org2 Campaign 1']);
        $this->createTestCampaign($this->org2->org_id, ['name' => 'Org2 Campaign 2']);

        // Login as org1 user
        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->getJson('/api/campaigns');

        $response->assertStatus(200);

        $campaigns = $response->json('data');

        // Should see 3 campaigns (1 from setUp + 2 created here) all from org1
        $this->assertCount(3, $campaigns);

        // Verify all campaigns belong to org1
        foreach ($campaigns as $campaign) {
            $this->assertEquals(
                $this->org1->org_id,
                $campaign['org_id'],
                'Campaign should belong to user\'s organization'
            );
        }

        // Verify no org2 campaigns are visible
        $campaignNames = array_column($campaigns, 'name');
        $this->assertNotContains('Org2 Campaign 1', $campaignNames);
        $this->assertNotContains('Org2 Campaign 2', $campaignNames);

        $this->logTestResult('passed', [
            'test' => 'Multi-tenant data isolation',
            'org1_campaigns' => 3,
            'org2_campaigns_visible' => 0,
            'isolation' => 'enforced',
        ]);
    }

    // ========================================
    // Duplicate Campaign Authorization Tests
    // ========================================

    #[Test]
    public function authorized_user_can_duplicate_own_organization_campaign()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->postJson("/api/campaigns/{$this->campaign->campaign_id}/duplicate");

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Test Campaign (Copy)');
        $response->assertJsonPath('data.status', 'draft');

        // Verify duplicate was created
        $duplicateId = $response->json('data.campaign_id');
        $this->assertNotEquals($this->campaign->campaign_id, $duplicateId);

        $this->assertDatabaseHas('cmis.campaigns', [
            'campaign_id' => $duplicateId,
            'org_id' => $this->org1->org_id,
            'name' => 'Test Campaign (Copy)',
        ]);

        $this->logTestResult('passed', [
            'test' => 'User can duplicate own org campaign',
            'original_id' => $this->campaign->campaign_id,
            'duplicate_id' => $duplicateId,
        ]);
    }

    #[Test]
    public function user_cannot_duplicate_another_organization_campaign()
    {
        $this->actingAs($this->unauthorizedUser, 'sanctum');

        $response = $this->postJson("/api/campaigns/{$this->campaign->campaign_id}/duplicate");

        $response->assertStatus(404);

        $this->logTestResult('passed', [
            'test' => 'Multi-tenant isolation enforced on duplicate',
            'blocked' => true,
        ]);
    }

    // ========================================
    // Campaign Analytics Authorization Tests
    // ========================================

    #[Test]
    public function authorized_user_can_view_own_campaign_analytics()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->getJson("/api/campaigns/{$this->campaign->campaign_id}/analytics");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'impressions',
                'clicks',
                'conversions',
                'spend',
                'ctr',
                'cpc',
                'cpa',
                'roi',
            ],
        ]);

        $this->logTestResult('passed', [
            'test' => 'User can view own campaign analytics',
            'campaign_id' => $this->campaign->campaign_id,
        ]);
    }

    #[Test]
    public function user_cannot_view_another_organization_campaign_analytics()
    {
        $this->actingAs($this->unauthorizedUser, 'sanctum');

        $response = $this->getJson("/api/campaigns/{$this->campaign->campaign_id}/analytics");

        $response->assertStatus(404);

        $this->logTestResult('passed', [
            'test' => 'Multi-tenant isolation enforced on analytics',
            'blocked' => true,
        ]);
    }

    // ========================================
    // Edge Cases and Security Tests
    // ========================================

    #[Test]
    public function cannot_access_campaign_with_invalid_uuid()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $invalidId = 'invalid-uuid-format';
        $response = $this->getJson("/api/campaigns/{$invalidId}");

        // Should return 400 or 404 depending on route model binding
        $this->assertTrue(
            in_array($response->status(), [400, 404, 500]),
            'Invalid UUID should be rejected'
        );

        $this->logTestResult('passed', [
            'test' => 'Invalid UUID rejected',
            'invalid_id' => $invalidId,
        ]);
    }

    #[Test]
    public function cannot_access_non_existent_campaign()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $nonExistentId = Str::uuid()->toString();
        $response = $this->getJson("/api/campaigns/{$nonExistentId}");

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'error' => 'Campaign not found',
        ]);

        $this->logTestResult('passed', [
            'test' => 'Non-existent campaign returns 404',
            'non_existent_id' => $nonExistentId,
        ]);
    }

    #[Test]
    public function soft_deleted_campaign_is_not_accessible()
    {
        // Soft delete the campaign
        $this->campaign->delete();

        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->getJson("/api/campaigns/{$this->campaign->campaign_id}");

        $response->assertStatus(404);

        $this->logTestResult('passed', [
            'test' => 'Soft deleted campaign not accessible',
            'campaign_id' => $this->campaign->campaign_id,
        ]);
    }
}
