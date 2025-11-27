<?php

namespace Tests\Feature\Analytics;

use App\Models\Analytics\AlertRule;
use App\Models\Analytics\AlertHistory;
use App\Models\Analytics\AlertTemplate;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create();

        $this->user->orgs()->attach($this->org->org_id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        session(['current_org_id' => $this->org->org_id]);
    }

    /** @test */
    public function it_can_list_alert_rules()
    {
        AlertRule::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/alerts/rules");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data' => [
                    '*' => [
                        'rule_id',
                        'name',
                        'entity_type',
                        'metric',
                        'condition',
                        'threshold',
                        'severity',
                    ]
                ],
                'current_page',
                'total',
            ],
        ]);
    }

    /** @test */
    public function it_can_filter_alert_rules_by_entity_type()
    {
        AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
        ]);

        AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'ad_set',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/alerts/rules?entity_type=campaign");

        $response->assertOk();
    }

    /** @test */
    public function it_can_filter_alert_rules_by_severity()
    {
        AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'severity' => 'critical',
        ]);

        AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'severity' => 'low',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/alerts/rules?severity=critical");

        $response->assertOk();
    }

    /** @test */
    public function it_can_filter_alert_rules_by_active_status()
    {
        AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'is_active' => true,
        ]);

        AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/alerts/rules?active=true");

        $response->assertOk();
    }

    /** @test */
    public function it_can_create_an_alert_rule()
    {
        $data = [
            'name' => 'High Spend Alert',
            'description' => 'Alert when campaign spend exceeds threshold',
            'entity_type' => 'campaign',
            'entity_id' => '11111111-1111-1111-1111-111111111111',
            'metric' => 'spend',
            'condition' => 'gt',
            'threshold' => 1000,
            'time_window_minutes' => 60,
            'severity' => 'critical',
            'notification_channels' => ['email', 'in_app'],
            'notification_config' => [
                'email' => ['test@example.com'],
                'in_app' => true,
            ],
            'cooldown_minutes' => 60,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/alerts/rules", $data);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'High Spend Alert');
        $response->assertJsonPath('data.org_id', $this->org->org_id);
        $response->assertJsonPath('data.created_by', $this->user->user_id);
    }

    /** @test */
    public function it_validates_alert_rule_creation()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/alerts/rules", [
                // Missing required fields
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'entity_type', 'metric', 'condition', 'threshold', 'severity', 'notification_channels', 'notification_config']);
    }

    /** @test */
    public function it_validates_condition_values()
    {
        $data = [
            'name' => 'Test Alert',
            'entity_type' => 'campaign',
            'metric' => 'spend',
            'condition' => 'invalid_condition',
            'threshold' => 100,
            'severity' => 'medium',
            'notification_channels' => ['email'],
            'notification_config' => ['email' => ['test@example.com']],
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/alerts/rules", $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['condition']);
    }

    /** @test */
    public function it_validates_severity_values()
    {
        $data = [
            'name' => 'Test Alert',
            'entity_type' => 'campaign',
            'metric' => 'spend',
            'condition' => 'gt',
            'threshold' => 100,
            'severity' => 'invalid_severity',
            'notification_channels' => ['email'],
            'notification_config' => ['email' => ['test@example.com']],
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/alerts/rules", $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['severity']);
    }

    /** @test */
    public function it_can_show_an_alert_rule()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/alerts/rules/{$rule->rule_id}");

        $response->assertOk();
        $response->assertJsonPath('data.rule_id', $rule->rule_id);
        $response->assertJsonPath('data.name', $rule->name);
    }

    /** @test */
    public function it_can_update_an_alert_rule()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Original Name',
            'threshold' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/orgs/{$this->org->org_id}/alerts/rules/{$rule->rule_id}", [
                'name' => 'Updated Name',
                'threshold' => 500,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Updated Name');
        $response->assertJsonPath('data.threshold', 500);
    }

    /** @test */
    public function it_can_delete_an_alert_rule()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/orgs/{$this->org->org_id}/alerts/rules/{$rule->rule_id}");

        $response->assertOk();

        $this->assertSoftDeleted('cmis_analytics.alert_rules', [
            'rule_id' => $rule->rule_id,
        ]);
    }

    /** @test */
    public function it_can_get_alert_history()
    {
        AlertHistory::factory()->count(15)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/alerts/history");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data' => [
                    '*' => [
                        'alert_id',
                        'rule_id',
                        'status',
                        'severity',
                        'triggered_at',
                    ]
                ],
            ],
        ]);
    }

    /** @test */
    public function it_can_filter_alert_history_by_status()
    {
        AlertHistory::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'triggered',
        ]);

        AlertHistory::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'acknowledged',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/alerts/history?status=triggered");

        $response->assertOk();
    }

    /** @test */
    public function it_can_filter_alert_history_by_severity()
    {
        AlertHistory::factory()->create([
            'org_id' => $this->org->org_id,
            'severity' => 'critical',
        ]);

        AlertHistory::factory()->create([
            'org_id' => $this->org->org_id,
            'severity' => 'low',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/alerts/history?severity=critical");

        $response->assertOk();
    }

    /** @test */
    public function it_can_filter_alert_history_by_rule_id()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        AlertHistory::factory()->create([
            'org_id' => $this->org->org_id,
            'rule_id' => $rule->rule_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/alerts/history?rule_id={$rule->rule_id}");

        $response->assertOk();
    }

    /** @test */
    public function it_can_acknowledge_an_alert()
    {
        $alert = AlertHistory::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'triggered',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/alerts/{$alert->alert_id}/acknowledge", [
                'notes' => 'Investigating the issue',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'acknowledged');
    }

    /** @test */
    public function it_can_resolve_an_alert()
    {
        $alert = AlertHistory::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'acknowledged',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/alerts/{$alert->alert_id}/resolve", [
                'notes' => 'Issue resolved by adjusting campaign budget',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'resolved');
    }

    /** @test */
    public function it_can_snooze_an_alert()
    {
        $alert = AlertHistory::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'triggered',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/alerts/{$alert->alert_id}/snooze", [
                'minutes' => 60,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'snoozed');
    }

    /** @test */
    public function it_validates_snooze_duration()
    {
        $alert = AlertHistory::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/alerts/{$alert->alert_id}/snooze", [
                'minutes' => 10, // Too short
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['minutes']);
    }

    /** @test */
    public function it_can_test_an_alert_rule()
    {
        $rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/alerts/rules/{$rule->rule_id}/test");

        $response->assertOk();
        $response->assertJsonPath('message', 'Alert rule evaluation queued');
    }

    /** @test */
    public function it_can_get_alert_templates()
    {
        AlertTemplate::factory()->count(5)->create([
            'is_public' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/alerts/templates");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'template_id',
                    'name',
                    'category',
                    'entity_type',
                    'default_config',
                ]
            ],
        ]);
    }

    /** @test */
    public function it_can_filter_templates_by_category()
    {
        AlertTemplate::factory()->create([
            'is_public' => true,
            'category' => 'budget',
        ]);

        AlertTemplate::factory()->create([
            'is_public' => true,
            'category' => 'performance',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/alerts/templates?category=budget");

        $response->assertOk();
    }

    /** @test */
    public function it_can_filter_templates_by_entity_type()
    {
        AlertTemplate::factory()->create([
            'is_public' => true,
            'entity_type' => 'campaign',
        ]);

        AlertTemplate::factory()->create([
            'is_public' => true,
            'entity_type' => 'ad_set',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/alerts/templates?entity_type=campaign");

        $response->assertOk();
    }

    /** @test */
    public function it_can_create_rule_from_template()
    {
        $template = AlertTemplate::factory()->create([
            'is_public' => true,
            'entity_type' => 'campaign',
            'default_config' => [
                'metric' => 'spend',
                'condition' => 'gt',
                'threshold' => 1000,
                'severity' => 'high',
                'notification_channels' => ['email'],
                'notification_config' => ['email' => ['admin@example.com']],
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/alerts/rules/from-template/{$template->template_id}", [
                'name' => 'My Custom Alert from Template',
                'entity_id' => '11111111-1111-1111-1111-111111111111',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'My Custom Alert from Template');
        $response->assertJsonPath('data.entity_type', 'campaign');
    }

    /** @test */
    public function it_can_override_template_config()
    {
        $template = AlertTemplate::factory()->create([
            'is_public' => true,
            'entity_type' => 'campaign',
            'default_config' => [
                'metric' => 'spend',
                'condition' => 'gt',
                'threshold' => 1000,
                'severity' => 'high',
                'notification_channels' => ['email'],
                'notification_config' => ['email' => ['admin@example.com']],
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/alerts/rules/from-template/{$template->template_id}", [
                'name' => 'Custom Alert',
                'config_overrides' => [
                    'threshold' => 2000,
                    'severity' => 'critical',
                ],
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.threshold', 2000);
        $response->assertJsonPath('data.severity', 'critical');
    }

    /** @test */
    public function it_enforces_multi_tenancy()
    {
        $otherOrg = Org::factory()->create();

        AlertRule::factory()->count(5)->create([
            'org_id' => $otherOrg->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/alerts/rules");

        $response->assertOk();
        $response->assertJsonPath('data.total', 0);
    }

    /** @test */
    public function it_supports_all_condition_types()
    {
        $conditions = ['gt', 'gte', 'lt', 'lte', 'eq', 'ne', 'change_pct'];

        foreach ($conditions as $condition) {
            $data = [
                'name' => "Test Alert {$condition}",
                'entity_type' => 'campaign',
                'metric' => 'spend',
                'condition' => $condition,
                'threshold' => 100,
                'severity' => 'medium',
                'notification_channels' => ['email'],
                'notification_config' => ['email' => ['test@example.com']],
            ];

            $response = $this->actingAs($this->user)
                ->postJson("/api/orgs/{$this->org->org_id}/alerts/rules", $data);

            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_supports_all_severity_levels()
    {
        $severities = ['critical', 'high', 'medium', 'low'];

        foreach ($severities as $severity) {
            $data = [
                'name' => "Test Alert {$severity}",
                'entity_type' => 'campaign',
                'metric' => 'spend',
                'condition' => 'gt',
                'threshold' => 100,
                'severity' => $severity,
                'notification_channels' => ['email'],
                'notification_config' => ['email' => ['test@example.com']],
            ];

            $response = $this->actingAs($this->user)
                ->postJson("/api/orgs/{$this->org->org_id}/alerts/rules", $data);

            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_supports_multiple_notification_channels()
    {
        $data = [
            'name' => 'Multi-Channel Alert',
            'entity_type' => 'campaign',
            'metric' => 'spend',
            'condition' => 'gt',
            'threshold' => 100,
            'severity' => 'critical',
            'notification_channels' => ['email', 'in_app', 'slack', 'webhook'],
            'notification_config' => [
                'email' => ['admin@example.com'],
                'in_app' => true,
                'slack' => ['channel' => '#alerts'],
                'webhook' => ['url' => 'https://example.com/webhook'],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/alerts/rules", $data);

        $response->assertStatus(201);
        $response->assertJsonPath('data.notification_channels', ['email', 'in_app', 'slack', 'webhook']);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson("/api/orgs/{$this->org->org_id}/alerts/rules");

        $response->assertStatus(401);
    }
}
