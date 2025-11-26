<?php

namespace Tests\Feature\Controllers\Analytics;

use App\Models\Analytics\AlertHistory;
use App\Models\Analytics\AlertRule;
use App\Models\Analytics\AlertTemplate;
use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AlertsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Org $org;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create([
            'org_id' => $this->org->org_id,
            'current_org_id' => $this->org->org_id,
        ]);

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_list_alert_rules()
    {
        AlertRule::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/alerts/rules");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data',
                    'current_page',
                    'total',
                ]
            ]);
    }

    /** @test */
    public function it_can_create_alert_rule()
    {
        $data = [
            'name' => 'High CTR Alert',
            'description' => 'Alert when CTR exceeds 5%',
            'entity_type' => 'campaign',
            'metric' => 'ctr',
            'condition' => 'gt',
            'threshold' => 5.0,
            'severity' => 'high',
            'notification_channels' => ['email', 'in_app'],
            'notification_config' => [
                'email' => ['recipients' => ['test@example.com']],
                'in_app' => ['user_ids' => [$this->user->user_id]],
            ],
            'cooldown_minutes' => 60,
            'is_active' => true,
        ];

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/alerts/rules", $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'rule_id',
                    'name',
                    'metric',
                    'threshold',
                ]
            ]);

        $this->assertDatabaseHas('cmis.alert_rules', [
            'name' => 'High CTR Alert',
            'org_id' => $this->org->org_id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_rule()
    {
        $response = $this->postJson("/api/orgs/{$this->org->org_id}/alerts/rules", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'entity_type',
                'metric',
                'condition',
                'threshold',
                'severity',
                'notification_channels',
                'notification_config',
            ]);
    }

    /** @test */
    public function it_can_show_alert_rule()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/alerts/rules/{$rule->rule_id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'rule_id' => $rule->rule_id,
                    'name' => $rule->name,
                ]
            ]);
    }

    /** @test */
    public function it_can_update_alert_rule()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Old Name',
        ]);

        $response = $this->putJson("/api/orgs/{$this->org->org_id}/alerts/rules/{$rule->rule_id}", [
            'name' => 'Updated Name',
            'threshold' => 10.0,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Name',
                ]
            ]);

        $this->assertDatabaseHas('cmis.alert_rules', [
            'rule_id' => $rule->rule_id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function it_can_delete_alert_rule()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->deleteJson("/api/orgs/{$this->org->org_id}/alerts/rules/{$rule->rule_id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('cmis.alert_rules', [
            'rule_id' => $rule->rule_id,
        ]);
    }

    /** @test */
    public function it_can_list_alert_history()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        AlertHistory::factory()->count(5)->create([
            'rule_id' => $rule->rule_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/alerts/history");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data',
                    'total',
                ]
            ]);
    }

    /** @test */
    public function it_can_filter_alert_history_by_status()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        AlertHistory::factory()->count(3)->create([
            'rule_id' => $rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'new',
        ]);

        AlertHistory::factory()->count(2)->create([
            'rule_id' => $rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'resolved',
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/alerts/history?status=new");

        $response->assertOk();
        $data = $response->json('data.data');
        $this->assertCount(3, $data);
    }

    /** @test */
    public function it_can_acknowledge_alert()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $alert = AlertHistory::factory()->create([
            'rule_id' => $rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'new',
        ]);

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/alerts/{$alert->alert_id}/acknowledge", [
            'notes' => 'Working on this',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'acknowledged',
                ]
            ]);

        $this->assertDatabaseHas('cmis.alert_history', [
            'alert_id' => $alert->alert_id,
            'status' => 'acknowledged',
            'acknowledged_by' => $this->user->user_id,
        ]);
    }

    /** @test */
    public function it_can_resolve_alert()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $alert = AlertHistory::factory()->create([
            'rule_id' => $rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'acknowledged',
        ]);

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/alerts/{$alert->alert_id}/resolve", [
            'notes' => 'Issue resolved',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'resolved',
                ]
            ]);
    }

    /** @test */
    public function it_can_snooze_alert()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $alert = AlertHistory::factory()->create([
            'rule_id' => $rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'new',
        ]);

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/alerts/{$alert->alert_id}/snooze", [
            'minutes' => 60,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'snoozed',
                ]
            ]);
    }

    /** @test */
    public function it_can_list_alert_templates()
    {
        AlertTemplate::factory()->count(3)->create([
            'is_public' => true,
        ]);

        $response = $this->getJson('/api/alerts/templates');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }

    /** @test */
    public function it_can_create_rule_from_template()
    {
        $template = AlertTemplate::factory()->create([
            'name' => 'High ROI Template',
            'entity_type' => 'campaign',
            'is_public' => true,
            'default_config' => [
                'metric' => 'roi',
                'condition' => 'lt',
                'threshold' => 100.0,
                'severity' => 'critical',
                'notification_channels' => ['email'],
                'notification_config' => [
                    'email' => ['recipients' => []],
                ],
            ],
        ]);

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/alerts/rules/from-template/{$template->template_id}", [
            'name' => 'My ROI Alert',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'My ROI Alert',
                    'entity_type' => 'campaign',
                ]
            ]);
    }

    /** @test */
    public function it_respects_rls_policies_for_different_orgs()
    {
        // Create another org with different data
        $otherOrg = Org::factory()->create();
        $otherUser = User::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        AlertRule::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
        ]);

        AlertRule::factory()->count(2)->create([
            'org_id' => $otherOrg->org_id,
        ]);

        // User should only see their org's rules
        $response = $this->getJson("/api/orgs/{$this->org->org_id}/alerts/rules");

        $response->assertOk();
        $data = $response->json('data.data');

        // Should only see 3 rules (this org's rules)
        $this->assertCount(3, $data);
    }

    /** @test */
    public function it_prevents_access_to_other_orgs_alert_rules()
    {
        $otherOrg = Org::factory()->create();
        $rule = AlertRule::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/alerts/rules/{$rule->rule_id}");

        // Should return 404 because RLS filters out the rule
        $response->assertNotFound();
    }
}
