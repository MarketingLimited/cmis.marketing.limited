<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Core\Org;
use App\Models\Core\UserOrg;
use App\Models\Strategic\Campaign;
use App\Models\Creative\ContentPlan;
use App\Models\Knowledge\KnowledgeBase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class RowLevelSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that RLS prevents cross-tenant data access for campaigns.
     *
     * @return void
     */
    public function test_rls_prevents_cross_tenant_campaign_access()
    {
        // Create two organizations
        $org1 = Org::factory()->create(['name' => 'Organization 1']);
        $org2 = Org::factory()->create(['name' => 'Organization 2']);

        // Create users for each organization
        $user1 = User::factory()->create(['current_org_id' => $org1->org_id, 'status' => 'active']);
        $user2 = User::factory()->create(['current_org_id' => $org2->org_id, 'status' => 'active']);

        // Associate users with their orgs
        UserOrg::create([
            'user_id' => $user1->user_id,
            'org_id' => $org1->org_id,
            'is_active' => true,
        ]);

        UserOrg::create([
            'user_id' => $user2->user_id,
            'org_id' => $org2->org_id,
            'is_active' => true,
        ]);

        // Create campaigns for each organization
        $campaign1 = Campaign::factory()->create([
            'org_id' => $org1->org_id,
            'name' => 'Campaign for Org 1',
        ]);

        $campaign2 = Campaign::factory()->create([
            'org_id' => $org2->org_id,
            'name' => 'Campaign for Org 2',
        ]);

        // User 1 should only see their org's campaigns
        $this->actingAs($user1, 'sanctum');
        $campaigns = Campaign::all();

        $this->assertCount(1, $campaigns);
        $this->assertEquals($campaign1->id, $campaigns->first()->id);
        $this->assertEquals('Campaign for Org 1', $campaigns->first()->name);

        // User 2 should only see their org's campaigns
        $this->actingAs($user2, 'sanctum');
        $campaigns = Campaign::all();

        $this->assertCount(1, $campaigns);
        $this->assertEquals($campaign2->id, $campaigns->first()->id);
        $this->assertEquals('Campaign for Org 2', $campaigns->first()->name);
    }

    /**
     * Test that RLS prevents direct database access to other org's data.
     *
     * @return void
     */
    public function test_rls_blocks_direct_database_queries_across_tenants()
    {
        $org1 = Org::factory()->create();
        $org2 = Org::factory()->create();

        $user1 = User::factory()->create(['current_org_id' => $org1->org_id, 'status' => 'active']);
        $user2 = User::factory()->create(['current_org_id' => $org2->org_id, 'status' => 'active']);

        Campaign::factory()->create(['org_id' => $org1->org_id]);
        Campaign::factory()->create(['org_id' => $org2->org_id]);

        // Authenticate as user1
        $this->actingAs($user1, 'sanctum');

        // Try to query all campaigns directly
        $campaigns = DB::table('cmis.campaigns')->get();

        // Should only see org1's campaigns due to RLS
        $this->assertCount(1, $campaigns);
    }

    /**
     * Test RLS for content plans.
     *
     * @return void
     */
    public function test_rls_isolates_content_plans()
    {
        $org1 = Org::factory()->create();
        $org2 = Org::factory()->create();

        $user1 = User::factory()->create(['current_org_id' => $org1->org_id, 'status' => 'active']);
        $user2 = User::factory()->create(['current_org_id' => $org2->org_id, 'status' => 'active']);

        $campaign1 = Campaign::factory()->create(['org_id' => $org1->org_id]);
        $campaign2 = Campaign::factory()->create(['org_id' => $org2->org_id]);

        ContentPlan::factory()->create([
            'org_id' => $org1->org_id,
            'campaign_id' => $campaign1->id,
        ]);

        ContentPlan::factory()->create([
            'org_id' => $org2->org_id,
            'campaign_id' => $campaign2->id,
        ]);

        // User 1 should only see their content plans
        $this->actingAs($user1, 'sanctum');
        $plans = ContentPlan::all();
        $this->assertCount(1, $plans);

        // User 2 should only see their content plans
        $this->actingAs($user2, 'sanctum');
        $plans = ContentPlan::all();
        $this->assertCount(1, $plans);
    }

    /**
     * Test RLS for knowledge base.
     *
     * @return void
     */
    public function test_rls_isolates_knowledge_base()
    {
        $org1 = Org::factory()->create();
        $org2 = Org::factory()->create();

        $user1 = User::factory()->create(['current_org_id' => $org1->org_id, 'status' => 'active']);
        $user2 = User::factory()->create(['current_org_id' => $org2->org_id, 'status' => 'active']);

        KnowledgeBase::factory()->create([
            'org_id' => $org1->org_id,
            'title' => 'Org 1 Knowledge',
        ]);

        KnowledgeBase::factory()->create([
            'org_id' => $org2->org_id,
            'title' => 'Org 2 Knowledge',
        ]);

        // User 1 should only see their knowledge
        $this->actingAs($user1, 'sanctum');
        $knowledge = KnowledgeBase::all();
        $this->assertCount(1, $knowledge);
        $this->assertEquals('Org 1 Knowledge', $knowledge->first()->title);

        // User 2 should only see their knowledge
        $this->actingAs($user2, 'sanctum');
        $knowledge = KnowledgeBase::all();
        $this->assertCount(1, $knowledge);
        $this->assertEquals('Org 2 Knowledge', $knowledge->first()->title);
    }

    /**
     * Test that RLS allows updates only to own org's data.
     *
     * @return void
     */
    public function test_rls_prevents_updates_to_other_org_data()
    {
        $org1 = Org::factory()->create();
        $org2 = Org::factory()->create();

        $user1 = User::factory()->create(['current_org_id' => $org1->org_id, 'status' => 'active']);

        $campaign2 = Campaign::factory()->create([
            'org_id' => $org2->org_id,
            'name' => 'Original Name',
        ]);

        // Authenticate as user1 (org1)
        $this->actingAs($user1, 'sanctum');

        // Try to update org2's campaign - should fail due to RLS
        try {
            DB::table('cmis.campaigns')
                ->where('id', $campaign2->id)
                ->update(['name' => 'Hacked Name']);

            // If we get here, RLS is not working
            $this->fail('RLS should have prevented this update');
        } catch (\Exception $e) {
            // Expected - RLS should block this
            $this->assertTrue(true);
        }

        // Verify the campaign was not updated
        $campaign = Campaign::withoutGlobalScopes()->find($campaign2->id);
        $this->assertEquals('Original Name', $campaign->name);
    }

    /**
     * Test that RLS prevents deletes of other org's data.
     *
     * @return void
     */
    public function test_rls_prevents_deletes_of_other_org_data()
    {
        $org1 = Org::factory()->create();
        $org2 = Org::factory()->create();

        $user1 = User::factory()->create(['current_org_id' => $org1->org_id, 'status' => 'active']);

        $campaign2 = Campaign::factory()->create(['org_id' => $org2->org_id]);

        // Authenticate as user1 (org1)
        $this->actingAs($user1, 'sanctum');

        // Try to delete org2's campaign - should fail due to RLS
        $deleted = DB::table('cmis.campaigns')
            ->where('id', $campaign2->id)
            ->delete();

        // RLS should prevent deletion
        $this->assertEquals(0, $deleted);

        // Verify the campaign still exists
        $campaign = Campaign::withoutGlobalScopes()->find($campaign2->id);
        $this->assertNotNull($campaign);
    }

    /**
     * Test that unauthenticated requests see no data.
     *
     * @return void
     */
    public function test_rls_blocks_unauthenticated_access()
    {
        $org = Org::factory()->create();
        Campaign::factory()->count(5)->create(['org_id' => $org->org_id]);

        // No authentication
        $campaigns = Campaign::all();

        // Should see no campaigns
        $this->assertCount(0, $campaigns);
    }

    /**
     * Test that RLS function returns correct org_id.
     *
     * @return void
     */
    public function test_rls_function_returns_correct_org_id()
    {
        $org = Org::factory()->create();
        $user = User::factory()->create(['current_org_id' => $org->org_id, 'status' => 'active']);

        $this->actingAs($user, 'sanctum');

        // Trigger the RLS context setting
        Campaign::all();

        // Query the function directly
        $result = DB::select("SELECT cmis.current_org_id() as org_id");

        $this->assertNotEmpty($result);
        $this->assertEquals($org->org_id, $result[0]->org_id);
    }

    /**
     * Test that changing current_org_id switches context.
     *
     * @return void
     */
    public function test_rls_context_switches_with_org_change()
    {
        $org1 = Org::factory()->create();
        $org2 = Org::factory()->create();

        $user = User::factory()->create(['current_org_id' => $org1->org_id, 'status' => 'active']);

        // Associate user with both orgs
        UserOrg::create(['user_id' => $user->user_id, 'org_id' => $org1->org_id, 'is_active' => true]);
        UserOrg::create(['user_id' => $user->user_id, 'org_id' => $org2->org_id, 'is_active' => true]);

        Campaign::factory()->create(['org_id' => $org1->org_id, 'name' => 'Org 1 Campaign']);
        Campaign::factory()->create(['org_id' => $org2->org_id, 'name' => 'Org 2 Campaign']);

        // View as org1
        $this->actingAs($user, 'sanctum');
        $campaigns = Campaign::all();
        $this->assertCount(1, $campaigns);
        $this->assertEquals('Org 1 Campaign', $campaigns->first()->name);

        // Switch to org2
        $user->current_org_id = $org2->org_id;
        $user->save();

        // Re-authenticate to refresh context
        $this->actingAs($user->fresh(), 'sanctum');
        $campaigns = Campaign::all();
        $this->assertCount(1, $campaigns);
        $this->assertEquals('Org 2 Campaign', $campaigns->first()->name);
    }

    /**
     * Test that RLS works with joins.
     *
     * @return void
     */
    public function test_rls_works_with_joins()
    {
        $org1 = Org::factory()->create();
        $org2 = Org::factory()->create();

        $user1 = User::factory()->create(['current_org_id' => $org1->org_id, 'status' => 'active']);

        $campaign1 = Campaign::factory()->create(['org_id' => $org1->org_id]);
        $campaign2 = Campaign::factory()->create(['org_id' => $org2->org_id]);

        ContentPlan::factory()->create(['org_id' => $org1->org_id, 'campaign_id' => $campaign1->id]);
        ContentPlan::factory()->create(['org_id' => $org2->org_id, 'campaign_id' => $campaign2->id]);

        $this->actingAs($user1, 'sanctum');

        // Query with join
        $results = DB::table('cmis.campaigns')
            ->join('cmis.content_plans', 'campaigns.id', '=', 'content_plans.campaign_id')
            ->select('campaigns.*')
            ->get();

        // Should only see org1's data
        $this->assertCount(1, $results);
    }
}
