<?php

namespace Tests\Feature\Creative;

use Tests\TestCase;
use App\Models\User;
use App\Models\Core\Org;
use App\Models\Strategic\Campaign;
use App\Models\Creative\ContentPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

use PHPUnit\Framework\Attributes\Test;
class ContentPlanControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Org $org;
    protected Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test organization
        $this->org = Org::factory()->create();

        // Create test user
        $this->user = User::factory()->create([
            'current_org_id' => $this->org->org_id,
        ]);

        // Create test campaign
        $this->campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    #[Test]
    public function it_can_list_content_plans()
    {
        // Create test content plans
        ContentPlan::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
        ]);

        $response = $this->getJson('/api/creative/content-plans');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['plan_id', 'name', 'org_id', 'campaign_id'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJson(['success' => true]);

        $this->assertCount(3, $response->json('data'));
    }

    #[Test]
    public function it_can_filter_content_plans_by_campaign()
    {
        $campaign2 = Campaign::factory()->create(['org_id' => $this->org->org_id]);

        ContentPlan::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
        ]);

        ContentPlan::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $campaign2->campaign_id,
        ]);

        $response = $this->getJson("/api/creative/content-plans?campaign_id={$this->campaign->campaign_id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    #[Test]
    public function it_can_create_a_content_plan()
    {
        $data = [
            'campaign_id' => $this->campaign->campaign_id,
            'name' => 'Test Content Plan',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'channels' => ['facebook', 'instagram'],
            'objectives' => ['awareness', 'engagement'],
        ];

        $response = $this->postJson('/api/creative/content-plans', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['plan_id', 'name', 'org_id', 'campaign_id'],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Test Content Plan',
                    'org_id' => $this->org->org_id,
                ],
            ]);

        $this->assertDatabaseHas('cmis.content_plans', [
            'name' => 'Test Content Plan',
            'org_id' => $this->org->org_id,
        ]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating()
    {
        $response = $this->postJson('/api/creative/content-plans', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['campaign_id', 'name']);
    }

    #[Test]
    public function it_can_show_a_content_plan()
    {
        $plan = ContentPlan::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
        ]);

        $response = $this->getJson("/api/creative/content-plans/{$plan->plan_id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'plan_id' => $plan->plan_id,
                    'name' => $plan->name,
                ],
            ]);
    }

    #[Test]
    public function it_cannot_show_content_plan_from_different_org()
    {
        $otherOrg = Org::factory()->create();
        $plan = ContentPlan::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        $response = $this->getJson("/api/creative/content-plans/{$plan->plan_id}");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_can_update_a_content_plan()
    {
        $plan = ContentPlan::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'name' => 'Original Name',
        ]);

        $response = $this->putJson("/api/creative/content-plans/{$plan->plan_id}", [
            'name' => 'Updated Name',
            'status' => 'active',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Name',
                    'status' => 'active',
                ],
            ]);

        $this->assertDatabaseHas('cmis.content_plans', [
            'plan_id' => $plan->plan_id,
            'name' => 'Updated Name',
        ]);
    }

    #[Test]
    public function it_can_delete_a_content_plan()
    {
        $plan = ContentPlan::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->deleteJson("/api/creative/content-plans/{$plan->plan_id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('cmis.content_plans', [
            'plan_id' => $plan->plan_id,
        ]);
    }

    #[Test]
    public function it_can_get_content_plan_stats()
    {
        ContentPlan::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
        ]);

        ContentPlan::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/creative/content-plans-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['total', 'by_status', 'by_type'],
            ]);

        $this->assertEquals(8, $response->json('data.total'));
    }

    #[Test]
    public function it_requires_authentication()
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/creative/content-plans');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_can_approve_a_content_plan()
    {
        $plan = ContentPlan::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/creative/content-plans/{$plan->plan_id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Content plan approved',
            ]);

        $plan->refresh();
        $this->assertEquals('approved', $plan->status);
    }

    #[Test]
    public function it_can_reject_a_content_plan()
    {
        $plan = ContentPlan::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/creative/content-plans/{$plan->plan_id}/reject", [
            'reason' => 'Content needs revision',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Content plan rejected',
            ]);

        $plan->refresh();
        $this->assertEquals('rejected', $plan->status);
        $this->assertEquals('Content needs revision', $plan->rejection_reason);
    }

    #[Test]
    public function it_requires_reason_when_rejecting()
    {
        $plan = ContentPlan::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->postJson("/api/creative/content-plans/{$plan->plan_id}/reject", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    #[Test]
    public function it_enforces_pagination_limits()
    {
        ContentPlan::factory()->count(150)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->getJson('/api/creative/content-plans?per_page=150');

        $response->assertStatus(200);

        // Should be capped at 100
        $this->assertLessThanOrEqual(100, count($response->json('data')));
    }
}
