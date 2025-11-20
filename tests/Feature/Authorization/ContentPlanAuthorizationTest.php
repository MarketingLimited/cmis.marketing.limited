<?php

namespace Tests\Feature\Authorization;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Creative\ContentPlan;
use App\Models\Core\User;
use App\Models\Core\Org;
use App\Models\Campaign;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

/**
 * Comprehensive ContentPlan Authorization Tests
 *
 * Tests all authorization scenarios for ContentPlanController:
 * - Authentication requirements
 * - Permission checks (viewAny, view, create, update, delete)
 * - Multi-tenant isolation
 * - Unauthorized access prevention
 * - Policy-based authorization
 *
 * @group authorization
 * @group content-plan
 * @group security
 */
class ContentPlanAuthorizationTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected User $authorizedUser;
    protected User $unauthorizedUser;
    protected Org $org1;
    protected Org $org2;
    protected Campaign $campaign;
    protected ContentPlan $contentPlan;

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
            'name' => 'Test Campaign for Content Plan',
        ]);

        // Create test content plan
        $this->contentPlan = ContentPlan::create([
            'plan_id' => Str::uuid()->toString(),
            'org_id' => $this->org1->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'name' => 'Test Content Plan',
            'status' => 'draft',
            'created_by' => $this->authorizedUser->user_id,
        ]);
    }

    // ========================================
    // Authentication Tests
    // ========================================

    #[Test]
    public function unauthenticated_user_cannot_list_content_plans()
    {
        $response = $this->getJson('/api/content-plans');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);

        $this->logTestResult('passed', [
            'test' => 'Unauthenticated access blocked',
            'endpoint' => 'GET /api/content-plans',
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_create_content_plan()
    {
        $response = $this->postJson('/api/content-plans', [
            'name' => 'Test Plan',
            'campaign_id' => $this->campaign->campaign_id,
        ]);

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'test' => 'Unauthenticated creation blocked',
            'endpoint' => 'POST /api/content-plans',
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_view_content_plan()
    {
        $response = $this->getJson("/api/content-plans/{$this->contentPlan->plan_id}");

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'test' => 'Unauthenticated view blocked',
            'endpoint' => 'GET /api/content-plans/{id}',
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_update_content_plan()
    {
        $response = $this->putJson("/api/content-plans/{$this->contentPlan->plan_id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'test' => 'Unauthenticated update blocked',
            'endpoint' => 'PUT /api/content-plans/{id}',
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_delete_content_plan()
    {
        $response = $this->deleteJson("/api/content-plans/{$this->contentPlan->plan_id}");

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'test' => 'Unauthenticated deletion blocked',
            'endpoint' => 'DELETE /api/content-plans/{id}',
        ]);
    }

    // ========================================
    // Authorization: viewAny Permission Tests
    // ========================================

    #[Test]
    public function authorized_user_can_list_content_plans()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->getJson('/api/content-plans');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'meta' => ['current_page', 'per_page', 'total'],
        ]);

        $this->logTestResult('passed', [
            'test' => 'Authorized user can list content plans',
            'endpoint' => 'GET /api/content-plans',
        ]);
    }

    // ========================================
    // Authorization: view Permission Tests
    // ========================================

    #[Test]
    public function authorized_user_can_view_own_organization_content_plan()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->getJson("/api/content-plans/{$this->contentPlan->plan_id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.plan_id', $this->contentPlan->plan_id);
        $response->assertJsonPath('data.name', 'Test Content Plan');

        $this->logTestResult('passed', [
            'test' => 'User can view own org content plan',
            'plan_id' => $this->contentPlan->plan_id,
        ]);
    }

    #[Test]
    public function user_cannot_view_another_organization_content_plan()
    {
        // User from org2 trying to view org1's content plan
        $this->actingAs($this->unauthorizedUser, 'sanctum');

        $response = $this->getJson("/api/content-plans/{$this->contentPlan->plan_id}");

        // Should return 404 due to organization filtering
        $response->assertStatus(404);

        $this->logTestResult('passed', [
            'test' => 'Multi-tenant isolation enforced on view',
            'user_org' => $this->unauthorizedUser->org_id,
            'plan_org' => $this->contentPlan->org_id,
            'blocked' => true,
        ]);
    }

    // ========================================
    // Authorization: create Permission Tests
    // ========================================

    #[Test]
    public function authorized_user_can_create_content_plan()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->postJson('/api/content-plans', [
            'campaign_id' => $this->campaign->campaign_id,
            'name' => 'New Content Plan',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'draft',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'New Content Plan');
        $response->assertJsonPath('data.status', 'draft');

        // Verify content plan was created in correct organization
        $planId = $response->json('data.plan_id');
        $this->assertDatabaseHas('cmis.content_plans', [
            'plan_id' => $planId,
            'org_id' => $this->authorizedUser->org_id,
            'name' => 'New Content Plan',
        ]);

        $this->logTestResult('passed', [
            'test' => 'Authorized user can create content plan',
            'plan_id' => $planId,
        ]);
    }

    #[Test]
    public function user_cannot_create_content_plan_for_another_organizations_campaign()
    {
        // User from org2 trying to create content plan for org1's campaign
        $this->actingAs($this->unauthorizedUser, 'sanctum');

        $response = $this->postJson('/api/content-plans', [
            'campaign_id' => $this->campaign->campaign_id, // org1's campaign
            'name' => 'Unauthorized Plan',
        ]);

        // Should fail validation (campaign not found in user's org)
        $response->assertStatus(422);

        $this->logTestResult('passed', [
            'test' => 'Cannot create plan for another org\'s campaign',
            'blocked' => true,
        ]);
    }

    // ========================================
    // Authorization: update Permission Tests
    // ========================================

    #[Test]
    public function authorized_user_can_update_own_organization_content_plan()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->putJson("/api/content-plans/{$this->contentPlan->plan_id}", [
            'name' => 'Updated Content Plan Name',
            'status' => 'active',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Content Plan Name');
        $response->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('cmis.content_plans', [
            'plan_id' => $this->contentPlan->plan_id,
            'name' => 'Updated Content Plan Name',
            'status' => 'active',
        ]);

        $this->logTestResult('passed', [
            'test' => 'User can update own org content plan',
            'plan_id' => $this->contentPlan->plan_id,
        ]);
    }

    #[Test]
    public function user_cannot_update_another_organization_content_plan()
    {
        // User from org2 trying to update org1's content plan
        $this->actingAs($this->unauthorizedUser, 'sanctum');

        $response = $this->putJson("/api/content-plans/{$this->contentPlan->plan_id}", [
            'name' => 'Hacked Plan Name',
        ]);

        $response->assertStatus(404);

        // Verify content plan was NOT updated
        $this->assertDatabaseMissing('cmis.content_plans', [
            'plan_id' => $this->contentPlan->plan_id,
            'name' => 'Hacked Plan Name',
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
    public function authorized_user_can_delete_own_organization_content_plan()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->deleteJson("/api/content-plans/{$this->contentPlan->plan_id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Content plan deleted successfully',
        ]);

        // Verify soft delete
        $this->assertSoftDeleted('cmis.content_plans', [
            'plan_id' => $this->contentPlan->plan_id,
        ]);

        $this->logTestResult('passed', [
            'test' => 'User can delete own org content plan',
            'plan_id' => $this->contentPlan->plan_id,
            'soft_delete' => true,
        ]);
    }

    #[Test]
    public function user_cannot_delete_another_organization_content_plan()
    {
        // User from org2 trying to delete org1's content plan
        $this->actingAs($this->unauthorizedUser, 'sanctum');

        $response = $this->deleteJson("/api/content-plans/{$this->contentPlan->plan_id}");

        $response->assertStatus(404);

        // Verify content plan was NOT deleted
        $this->assertDatabaseHas('cmis.content_plans', [
            'plan_id' => $this->contentPlan->plan_id,
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
    public function user_only_sees_content_plans_from_their_organization()
    {
        // Create content plans in both organizations
        $campaign2 = $this->createTestCampaign($this->org2->org_id);

        ContentPlan::create([
            'plan_id' => Str::uuid()->toString(),
            'org_id' => $this->org1->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'name' => 'Org1 Plan 2',
            'created_by' => $this->authorizedUser->user_id,
        ]);

        ContentPlan::create([
            'plan_id' => Str::uuid()->toString(),
            'org_id' => $this->org2->org_id,
            'campaign_id' => $campaign2->campaign_id,
            'name' => 'Org2 Plan 1',
            'created_by' => $this->unauthorizedUser->user_id,
        ]);

        // Login as org1 user
        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->getJson('/api/content-plans');

        $response->assertStatus(200);

        $plans = $response->json('data');

        // Should see 2 plans from org1 only
        $this->assertCount(2, $plans);

        // Verify all plans belong to org1
        foreach ($plans as $plan) {
            $this->assertEquals(
                $this->org1->org_id,
                $plan['org_id'],
                'Content plan should belong to user\'s organization'
            );
        }

        // Verify no org2 plans are visible
        $planNames = array_column($plans, 'name');
        $this->assertNotContains('Org2 Plan 1', $planNames);

        $this->logTestResult('passed', [
            'test' => 'Multi-tenant data isolation',
            'org1_plans' => 2,
            'org2_plans_visible' => 0,
            'isolation' => 'enforced',
        ]);
    }

    // ========================================
    // Edge Cases and Security Tests
    // ========================================

    #[Test]
    public function cannot_access_content_plan_with_invalid_uuid()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $invalidId = 'invalid-uuid-format';
        $response = $this->getJson("/api/content-plans/{$invalidId}");

        // Should return 400 or 404 or 500 depending on route model binding
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
    public function cannot_access_non_existent_content_plan()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        $nonExistentId = Str::uuid()->toString();
        $response = $this->getJson("/api/content-plans/{$nonExistentId}");

        $response->assertStatus(404);

        $this->logTestResult('passed', [
            'test' => 'Non-existent content plan returns 404',
            'non_existent_id' => $nonExistentId,
        ]);
    }

    #[Test]
    public function soft_deleted_content_plan_is_not_accessible()
    {
        // Soft delete the content plan
        $this->contentPlan->delete();

        $this->actingAs($this->authorizedUser, 'sanctum');

        $response = $this->getJson("/api/content-plans/{$this->contentPlan->plan_id}");

        $response->assertStatus(404);

        $this->logTestResult('passed', [
            'test' => 'Soft deleted content plan not accessible',
            'plan_id' => $this->contentPlan->plan_id,
        ]);
    }

    #[Test]
    public function content_plan_list_can_be_filtered_by_campaign()
    {
        $this->actingAs($this->authorizedUser, 'sanctum');

        // Create another campaign and content plan
        $campaign2 = $this->createTestCampaign($this->org1->org_id, ['name' => 'Campaign 2']);
        ContentPlan::create([
            'plan_id' => Str::uuid()->toString(),
            'org_id' => $this->org1->org_id,
            'campaign_id' => $campaign2->campaign_id,
            'name' => 'Plan for Campaign 2',
            'created_by' => $this->authorizedUser->user_id,
        ]);

        // Filter by first campaign
        $response = $this->getJson("/api/content-plans?campaign_id={$this->campaign->campaign_id}");

        $response->assertStatus(200);

        $plans = $response->json('data');

        // Should only see plans for the first campaign
        $this->assertCount(1, $plans);
        $this->assertEquals($this->contentPlan->plan_id, $plans[0]['plan_id']);

        $this->logTestResult('passed', [
            'test' => 'Content plans filtered by campaign',
            'campaign_id' => $this->campaign->campaign_id,
            'plans_found' => 1,
        ]);
    }
}
