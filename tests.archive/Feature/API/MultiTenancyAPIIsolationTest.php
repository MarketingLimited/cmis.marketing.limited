<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\InteractsWithRLS;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

/**
 * Multi-Tenancy API Isolation Tests
 *
 * Verifies that API endpoints properly enforce Row-Level Security (RLS)
 * and prevent cross-organization data access.
 *
 * These tests ensure that:
 * 1. Users can only access data from their own organization
 * 2. API endpoints respect RLS policies
 * 3. Unauthorized access attempts are properly blocked
 * 4. Data isolation is maintained across all endpoints
 */
#[Group('multi-tenancy')]
#[Group('api')]
#[Group('integration')]
class MultiTenancyAPIIsolationTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, InteractsWithRLS;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function campaigns_api_enforces_organization_isolation()
    {
        // Create two organizations
        $org1 = $this->createUserWithOrg();
        $org2 = $this->createUserWithOrg();

        // Create campaigns for each org
        $campaign1 = $this->createTestCampaign($org1['org']->org_id, [
            'name' => 'Org1 Campaign',
            'budget' => 5000,
        ]);

        $campaign2 = $this->createTestCampaign($org2['org']->org_id, [
            'name' => 'Org2 Campaign',
            'budget' => 8000,
        ]);

        // Test 1: Org1 user should only see org1 campaigns
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->getJson("/api/orgs/{$org1['org']->org_id}/campaigns");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Org1 Campaign');

        // Test 2: Org2 user should only see org2 campaigns
        $response = $this->actingAs($org2['user'], 'sanctum')
            ->getJson("/api/orgs/{$org2['org']->org_id}/campaigns");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Org2 Campaign');

        // Test 3: Org1 user cannot access org2's campaign by ID
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->getJson("/api/orgs/{$org1['org']->org_id}/campaigns/{$campaign2->campaign_id}");

        $response->assertStatus(404); // Should not find org2's campaign

        // Test 4: Org1 user cannot access org2's campaigns endpoint
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->getJson("/api/orgs/{$org2['org']->org_id}/campaigns");

        $response->assertStatus(403); // Forbidden - not member of org2

        $this->logTestResult('passed', [
            'test' => 'Campaigns API RLS isolation',
            'endpoints_tested' => 3,
        ]);
    }

    #[Test]
    public function content_plans_api_enforces_organization_isolation()
    {
        $org1 = $this->createUserWithOrg();
        $org2 = $this->createUserWithOrg();

        // Create campaigns and content plans
        $campaign1 = $this->createTestCampaign($org1['org']->org_id);
        $campaign2 = $this->createTestCampaign($org2['org']->org_id);

        $plan1 = $this->createTestContentPlan($campaign1->campaign_id, $org1['org']->org_id, [
            'name' => 'Org1 Content Plan',
        ]);

        $plan2 = $this->createTestContentPlan($campaign2->campaign_id, $org2['org']->org_id, [
            'name' => 'Org2 Content Plan',
        ]);

        // Test 1: Org1 user can only see their content plans
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->getJson("/api/orgs/{$org1['org']->org_id}/campaigns/{$campaign1->campaign_id}/content-plans");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Org1 Content Plan');

        // Test 2: Org1 user cannot access org2's content plan
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->getJson("/api/orgs/{$org1['org']->org_id}/content-plans/{$plan2->plan_id}");

        $response->assertStatus(404);

        $this->logTestResult('passed', [
            'test' => 'Content Plans API RLS isolation',
        ]);
    }

    #[Test]
    public function ad_accounts_api_enforces_organization_isolation()
    {
        $org1 = $this->createUserWithOrg();
        $org2 = $this->createUserWithOrg();

        // Create integrations and ad accounts
        $integration1 = $this->createTestIntegration($org1['org']->org_id, 'facebook');
        $integration2 = $this->createTestIntegration($org2['org']->org_id, 'google');

        $adAccount1 = $this->createTestAdAccount($integration1->integration_id, $org1['org']->org_id, [
            'name' => 'Org1 Ad Account',
            'account_external_id' => 'act_111',
        ]);

        $adAccount2 = $this->createTestAdAccount($integration2->integration_id, $org2['org']->org_id, [
            'name' => 'Org2 Ad Account',
            'account_external_id' => 'act_222',
        ]);

        // Test 1: Org1 user can only see their ad accounts
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->getJson("/api/orgs/{$org1['org']->org_id}/ad-accounts");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Org1 Ad Account');

        // Test 2: Org1 user cannot access org2's ad account
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->getJson("/api/orgs/{$org1['org']->org_id}/ad-accounts/{$adAccount2->id}");

        $response->assertStatus(404);

        $this->logTestResult('passed', [
            'test' => 'Ad Accounts API RLS isolation',
        ]);
    }

    #[Test]
    public function social_posts_api_enforces_organization_isolation()
    {
        $org1 = $this->createUserWithOrg();
        $org2 = $this->createUserWithOrg();

        // Create integrations and social posts
        $integration1 = $this->createTestIntegration($org1['org']->org_id, 'instagram');
        $integration2 = $this->createTestIntegration($org2['org']->org_id, 'twitter');

        $post1 = $this->createTestSocialPost($integration1->integration_id, $org1['org']->org_id, [
            'content' => 'Org1 Social Post',
            'platform' => 'instagram',
        ]);

        $post2 = $this->createTestSocialPost($integration2->integration_id, $org2['org']->org_id, [
            'content' => 'Org2 Social Post',
            'platform' => 'twitter',
        ]);

        // Test 1: Org1 user can only see their social posts
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->getJson("/api/orgs/{$org1['org']->org_id}/social-posts");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Org1 Social Post');

        // Test 2: Org1 user cannot access org2's social post
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->getJson("/api/orgs/{$org1['org']->org_id}/social-posts/{$post2->post_id}");

        $response->assertStatus(404);

        $this->logTestResult('passed', [
            'test' => 'Social Posts API RLS isolation',
        ]);
    }

    #[Test]
    public function creative_assets_api_enforces_organization_isolation()
    {
        $org1 = $this->createUserWithOrg();
        $org2 = $this->createUserWithOrg();

        // Create creative assets
        $asset1 = $this->createTestCreativeAsset($org1['org']->org_id, [
            'final_copy' => ['headline' => 'Org1 Asset'],
            'status' => 'approved',
        ]);

        $asset2 = $this->createTestCreativeAsset($org2['org']->org_id, [
            'final_copy' => ['headline' => 'Org2 Asset'],
            'status' => 'approved',
        ]);

        // Test 1: Org1 user can only see their creative assets
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->getJson("/api/orgs/{$org1['org']->org_id}/creative-assets");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Test 2: Org1 user cannot access org2's creative asset
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->getJson("/api/orgs/{$org1['org']->org_id}/creative-assets/{$asset2->asset_id}");

        $response->assertStatus(404);

        $this->logTestResult('passed', [
            'test' => 'Creative Assets API RLS isolation',
        ]);
    }

    #[Test]
    public function analytics_api_enforces_organization_isolation()
    {
        $org1 = $this->createUserWithOrg();
        $org2 = $this->createUserWithOrg();

        // Create campaigns with metrics
        $campaign1 = $this->createTestCampaign($org1['org']->org_id, [
            'name' => 'Org1 Campaign with Metrics',
            'budget' => 10000,
        ]);

        $campaign2 = $this->createTestCampaign($org2['org']->org_id, [
            'name' => 'Org2 Campaign with Metrics',
            'budget' => 15000,
        ]);

        // Test 1: Org1 user can only access their analytics
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->getJson("/api/orgs/{$org1['org']->org_id}/analytics/campaigns");

        $response->assertStatus(200);
        $data = $response->json('data');

        // Verify no data from org2 is included
        if (!empty($data)) {
            foreach ($data as $item) {
                $this->assertEquals($org1['org']->org_id, $item['org_id']);
            }
        }

        // Test 2: Org1 user cannot access org2's campaign analytics
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->getJson("/api/orgs/{$org2['org']->org_id}/analytics/campaigns/{$campaign2->campaign_id}");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'test' => 'Analytics API RLS isolation',
        ]);
    }

    #[Test]
    public function create_operations_respect_organization_context()
    {
        $org1 = $this->createUserWithOrg();
        $org2 = $this->createUserWithOrg();

        // Test 1: User from org1 creates a campaign
        $campaignData = [
            'name' => 'New Campaign',
            'objective' => 'awareness',
            'status' => 'draft',
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'budget' => 5000.00,
            'currency' => 'BHD',
        ];

        $response = $this->actingAs($org1['user'], 'sanctum')
            ->postJson("/api/orgs/{$org1['org']->org_id}/campaigns", $campaignData);

        $response->assertStatus(201);
        $campaignId = $response->json('data.campaign_id');

        // Test 2: Verify org2 user cannot see the campaign created by org1
        $response = $this->actingAs($org2['user'], 'sanctum')
            ->getJson("/api/orgs/{$org2['org']->org_id}/campaigns/{$campaignId}");

        $response->assertStatus(404);

        // Test 3: Verify org1 user can see their created campaign
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->getJson("/api/orgs/{$org1['org']->org_id}/campaigns/{$campaignId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Campaign');

        $this->logTestResult('passed', [
            'test' => 'Create operations RLS isolation',
        ]);
    }

    #[Test]
    public function update_operations_respect_organization_boundaries()
    {
        $org1 = $this->createUserWithOrg();
        $org2 = $this->createUserWithOrg();

        // Create campaign in org1
        $campaign = $this->createTestCampaign($org1['org']->org_id, [
            'name' => 'Original Name',
            'budget' => 5000,
        ]);

        // Test 1: Org2 user cannot update org1's campaign
        $updateData = ['name' => 'Hacked Name', 'budget' => 99999];

        $response = $this->actingAs($org2['user'], 'sanctum')
            ->putJson("/api/orgs/{$org2['org']->org_id}/campaigns/{$campaign->campaign_id}", $updateData);

        $response->assertStatus(404); // Should not find the campaign

        // Test 2: Verify campaign was not modified
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->getJson("/api/orgs/{$org1['org']->org_id}/campaigns/{$campaign->campaign_id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Original Name')
            ->assertJsonPath('data.budget', 5000);

        // Test 3: Org1 user can update their own campaign
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->putJson("/api/orgs/{$org1['org']->org_id}/campaigns/{$campaign->campaign_id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->logTestResult('passed', [
            'test' => 'Update operations RLS isolation',
        ]);
    }

    #[Test]
    public function delete_operations_respect_organization_boundaries()
    {
        $org1 = $this->createUserWithOrg();
        $org2 = $this->createUserWithOrg();

        // Create campaigns
        $campaign1 = $this->createTestCampaign($org1['org']->org_id, ['name' => 'Org1 Campaign']);
        $campaign2 = $this->createTestCampaign($org2['org']->org_id, ['name' => 'Org2 Campaign']);

        // Test 1: Org1 user cannot delete org2's campaign
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->deleteJson("/api/orgs/{$org1['org']->org_id}/campaigns/{$campaign2->campaign_id}");

        $response->assertStatus(404);

        // Test 2: Verify org2's campaign still exists
        $response = $this->actingAs($org2['user'], 'sanctum')
            ->getJson("/api/orgs/{$org2['org']->org_id}/campaigns/{$campaign2->campaign_id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Org2 Campaign');

        // Test 3: Org1 user can delete their own campaign
        $response = $this->actingAs($org1['user'], 'sanctum')
            ->deleteJson("/api/orgs/{$org1['org']->org_id}/campaigns/{$campaign1->campaign_id}");

        $response->assertStatus(204);

        $this->logTestResult('passed', [
            'test' => 'Delete operations RLS isolation',
        ]);
    }

    #[Test]
    public function batch_operations_respect_organization_isolation()
    {
        $org1 = $this->createUserWithOrg();
        $org2 = $this->createUserWithOrg();

        // Create multiple campaigns for each org
        $org1Campaigns = collect([
            $this->createTestCampaign($org1['org']->org_id, ['name' => 'Org1 Campaign 1']),
            $this->createTestCampaign($org1['org']->org_id, ['name' => 'Org1 Campaign 2']),
        ]);

        $org2Campaign = $this->createTestCampaign($org2['org']->org_id, ['name' => 'Org2 Campaign']);

        // Test: Org1 user tries to batch update including org2's campaign
        $batchData = [
            'campaign_ids' => $org1Campaigns->pluck('campaign_id')->push($org2Campaign->campaign_id)->toArray(),
            'updates' => ['status' => 'paused'],
        ];

        $response = $this->actingAs($org1['user'], 'sanctum')
            ->putJson("/api/orgs/{$org1['org']->org_id}/campaigns/batch", $batchData);

        // Should either:
        // 1. Return 200 but only update org1 campaigns, or
        // 2. Return partial success with error for org2 campaign
        if ($response->status() === 200) {
            $updated = $response->json('updated_count');
            $this->assertEquals(2, $updated); // Only org1's campaigns updated
        }

        // Verify org2's campaign was not modified
        $response = $this->actingAs($org2['user'], 'sanctum')
            ->getJson("/api/orgs/{$org2['org']->org_id}/campaigns/{$org2Campaign->campaign_id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', $org2Campaign->status); // Status unchanged

        $this->logTestResult('passed', [
            'test' => 'Batch operations RLS isolation',
        ]);
    }
}
