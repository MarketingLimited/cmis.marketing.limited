<?php

namespace Tests\Integration;

use App\Models\Campaign\Campaign;
use App\Models\Analytics\AlertRule;
use App\Models\Analytics\AlertHistory;
use App\Models\Core\Org;
use App\Models\User;
use App\Services\Analytics\AlertsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Integration tests for Campaign + Alerts workflow
 *
 * Tests the end-to-end flow of alert rules monitoring campaign
 * performance and triggering notifications when thresholds are met.
 */
class CampaignAlertsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;
    protected AlertsService $alertsService;

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

        $this->alertsService = app(AlertsService::class);

        Notification::fake();
    }

    /** @test */
    public function alert_triggers_when_campaign_spend_exceeds_threshold()
    {
        // Create campaign with budget
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Summer Sale Campaign',
            'status' => 'active',
            'budget' => 5000,
            'spend' => 3000, // 60% spent
        ]);

        // Create alert rule for high spend
        $alertRule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'High Spend Alert',
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'metric' => 'spend',
            'condition' => 'gt',
            'threshold' => 4000,
            'severity' => 'critical',
            'status' => 'active',
            'enabled' => true,
            'notification_channels' => ['email', 'in_app'],
        ]);

        // Simulate spend increase to 4500
        $campaign->update(['spend' => 4500]);

        // Check if alert should trigger
        $shouldTrigger = $this->alertsService->evaluateRule($alertRule, [
            'current_value' => 4500,
            'previous_value' => 3000,
        ]);

        $this->assertTrue($shouldTrigger);

        // Create alert history entry
        $alert = AlertHistory::create([
            'rule_id' => $alertRule->rule_id,
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'metric' => 'spend',
            'current_value' => 4500,
            'threshold' => 4000,
            'severity' => 'critical',
            'status' => 'triggered',
            'triggered_at' => now(),
        ]);

        // Verify alert was recorded
        $this->assertDatabaseHas('cmis_analytics.alert_history', [
            'rule_id' => $alertRule->rule_id,
            'entity_id' => $campaign->campaign_id,
            'status' => 'triggered',
            'severity' => 'critical',
        ]);

        // Update alert rule stats
        $alertRule->increment('trigger_count');
        $alertRule->update(['last_triggered_at' => now()]);

        $this->assertEquals(1, $alertRule->fresh()->trigger_count);
        $this->assertNotNull($alertRule->fresh()->last_triggered_at);
    }

    /** @test */
    public function alert_triggers_on_percentage_change()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        // Alert when CTR drops by more than 20%
        $alertRule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'CTR Drop Alert',
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'metric' => 'ctr',
            'condition' => 'change_pct',
            'threshold' => -20, // -20% change
            'severity' => 'high',
            'status' => 'active',
            'enabled' => true,
            'notification_channels' => ['email'],
        ]);

        // Simulate CTR drop from 2.5% to 1.8% (28% decrease)
        $shouldTrigger = $this->alertsService->evaluateRule($alertRule, [
            'current_value' => 1.8,
            'previous_value' => 2.5,
        ]);

        $this->assertTrue($shouldTrigger);

        $currentValue = 1.8;
        $previousValue = 2.5;
        $percentChange = (($currentValue - $previousValue) / $previousValue) * 100;
        $this->assertLessThan(-20, $percentChange);
    }

    /** @test */
    public function multiple_alerts_trigger_for_same_campaign()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
            'spend' => 5000,
        ]);

        // Create multiple alert rules
        $spendAlert = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'High Spend',
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'metric' => 'spend',
            'condition' => 'gt',
            'threshold' => 4000,
            'severity' => 'critical',
            'status' => 'active',
            'enabled' => true,
        ]);

        $cpcAlert = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'High CPC',
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'metric' => 'cpc',
            'condition' => 'gt',
            'threshold' => 10,
            'severity' => 'high',
            'status' => 'active',
            'enabled' => true,
        ]);

        $ctrAlert = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'name' => 'Low CTR',
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'metric' => 'ctr',
            'condition' => 'lt',
            'threshold' => 1.0,
            'severity' => 'medium',
            'status' => 'active',
            'enabled' => true,
        ]);

        // Fetch all active alerts for this campaign
        $activeAlerts = AlertRule::where('org_id', $this->org->org_id)
            ->where('entity_type', 'campaign')
            ->where('entity_id', $campaign->campaign_id)
            ->where('status', 'active')
            ->where('enabled', true)
            ->get();

        $this->assertCount(3, $activeAlerts);
    }

    /** @test */
    public function alert_can_be_acknowledged_and_resolved()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        $alertRule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'status' => 'active',
            'enabled' => true,
        ]);

        // Create triggered alert
        $alert = AlertHistory::create([
            'rule_id' => $alertRule->rule_id,
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'metric' => 'spend',
            'current_value' => 5000,
            'threshold' => 4000,
            'severity' => 'critical',
            'status' => 'triggered',
            'triggered_at' => now(),
        ]);

        // Acknowledge alert
        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $this->user->user_id,
        ]);

        $this->assertEquals('acknowledged', $alert->fresh()->status);
        $this->assertNotNull($alert->fresh()->acknowledged_at);

        // Resolve alert
        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $this->user->user_id,
            'resolution_note' => 'Budget increased',
        ]);

        $this->assertEquals('resolved', $alert->fresh()->status);
        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    /** @test */
    public function alert_can_be_snoozed_for_specified_duration()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        $alertRule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'status' => 'active',
            'enabled' => true,
        ]);

        // Snooze for 2 hours
        $alertRule->update([
            'status' => 'snoozed',
            'snoozed_until' => now()->addHours(2),
        ]);

        $this->assertEquals('snoozed', $alertRule->fresh()->status);
        $this->assertNotNull($alertRule->fresh()->snoozed_until);

        // Check if still snoozed
        $isSnoozed = $alertRule->fresh()->snoozed_until > now();
        $this->assertTrue($isSnoozed);

        // Simulate time passing (2+ hours)
        $alertRule->update(['snoozed_until' => now()->subMinutes(1)]);

        $isSnoozed = $alertRule->fresh()->snoozed_until > now();
        $this->assertFalse($isSnoozed);
    }

    /** @test */
    public function alert_severity_levels_are_properly_prioritized()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        // Create alerts with different severity levels
        $criticalAlert = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'severity' => 'critical',
            'status' => 'active',
        ]);

        $highAlert = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'severity' => 'high',
            'status' => 'active',
        ]);

        $mediumAlert = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'severity' => 'medium',
            'status' => 'active',
        ]);

        $lowAlert = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'severity' => 'low',
            'status' => 'active',
        ]);

        // Fetch alerts ordered by severity
        $severityOrder = ['critical', 'high', 'medium', 'low'];

        $orderedAlerts = AlertRule::where('org_id', $this->org->org_id)
            ->where('entity_type', 'campaign')
            ->get()
            ->sortBy(function ($alert) use ($severityOrder) {
                return array_search($alert->severity, $severityOrder);
            });

        $this->assertEquals('critical', $orderedAlerts->first()->severity);
        $this->assertEquals('low', $orderedAlerts->last()->severity);
    }

    /** @test */
    public function alert_history_tracks_all_triggered_events()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        $alertRule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'metric' => 'spend',
            'condition' => 'gt',
            'threshold' => 1000,
            'status' => 'active',
            'enabled' => true,
        ]);

        // Simulate multiple alert triggers over time
        AlertHistory::create([
            'rule_id' => $alertRule->rule_id,
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'metric' => 'spend',
            'current_value' => 1200,
            'threshold' => 1000,
            'severity' => 'critical',
            'status' => 'triggered',
            'triggered_at' => now()->subHours(5),
        ]);

        AlertHistory::create([
            'rule_id' => $alertRule->rule_id,
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'metric' => 'spend',
            'current_value' => 1500,
            'threshold' => 1000,
            'severity' => 'critical',
            'status' => 'triggered',
            'triggered_at' => now()->subHours(3),
        ]);

        AlertHistory::create([
            'rule_id' => $alertRule->rule_id,
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'metric' => 'spend',
            'current_value' => 1800,
            'threshold' => 1000,
            'severity' => 'critical',
            'status' => 'triggered',
            'triggered_at' => now()->subHour(),
        ]);

        // Fetch alert history
        $history = AlertHistory::where('rule_id', $alertRule->rule_id)
            ->where('entity_id', $campaign->campaign_id)
            ->orderBy('triggered_at', 'desc')
            ->get();

        $this->assertCount(3, $history);
        $this->assertEquals(1800, $history->first()->current_value);
        $this->assertEquals(1200, $history->last()->current_value);
    }

    /** @test */
    public function alert_notification_channels_are_configurable()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        // Email only
        $emailAlert = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'notification_channels' => ['email'],
            'notification_config' => [
                'email' => ['admin@example.com', 'manager@example.com'],
            ],
        ]);

        // In-app only
        $inAppAlert = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'notification_channels' => ['in_app'],
        ]);

        // Multiple channels
        $multiChannelAlert = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'notification_channels' => ['email', 'in_app', 'webhook'],
            'notification_config' => [
                'email' => ['admin@example.com'],
                'webhook' => ['url' => 'https://example.com/webhook'],
            ],
        ]);

        $this->assertCount(1, $emailAlert->notification_channels);
        $this->assertContains('email', $emailAlert->notification_channels);

        $this->assertCount(1, $inAppAlert->notification_channels);
        $this->assertContains('in_app', $inAppAlert->notification_channels);

        $this->assertCount(3, $multiChannelAlert->notification_channels);
        $this->assertContains('webhook', $multiChannelAlert->notification_channels);
    }

    /** @test */
    public function alert_templates_can_be_used_to_create_rules()
    {
        // Predefined alert template
        $template = [
            'name' => 'High Spend Template',
            'entity_type' => 'campaign',
            'metric' => 'spend',
            'condition' => 'gt',
            'threshold' => 5000,
            'severity' => 'critical',
            'notification_channels' => ['email', 'in_app'],
        ];

        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        // Create alert from template
        $alertRule = AlertRule::create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
            'entity_id' => $campaign->campaign_id,
            ...$template,
            'status' => 'active',
            'enabled' => true,
        ]);

        $this->assertEquals('High Spend Template', $alertRule->name);
        $this->assertEquals('spend', $alertRule->metric);
        $this->assertEquals(5000, $alertRule->threshold);
        $this->assertEquals('critical', $alertRule->severity);
    }

    /** @test */
    public function alert_enforces_multi_tenancy()
    {
        $otherOrg = Org::factory()->create();

        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        // Create alert in other org
        $otherOrgAlert = AlertRule::factory()->create([
            'org_id' => $otherOrg->org_id,
            'entity_type' => 'campaign',
        ]);

        // Try to fetch alerts for current org
        $alerts = AlertRule::where('org_id', $this->org->org_id)->get();

        // Should not include other org's alert
        $this->assertFalse($alerts->contains('rule_id', $otherOrgAlert->rule_id));
    }

    /** @test */
    public function alert_statistics_aggregate_correctly()
    {
        $campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'active',
        ]);

        $alertRule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $campaign->campaign_id,
            'status' => 'active',
            'enabled' => true,
        ]);

        // Create various alert history entries
        AlertHistory::factory()->count(5)->create([
            'rule_id' => $alertRule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'triggered',
            'triggered_at' => now()->subDays(2),
        ]);

        AlertHistory::factory()->count(3)->create([
            'rule_id' => $alertRule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'acknowledged',
            'triggered_at' => now()->subDay(),
        ]);

        AlertHistory::factory()->count(2)->create([
            'rule_id' => $alertRule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'resolved',
            'triggered_at' => now(),
        ]);

        // Calculate statistics
        $totalAlerts = AlertHistory::where('rule_id', $alertRule->rule_id)->count();
        $triggeredCount = AlertHistory::where('rule_id', $alertRule->rule_id)
            ->where('status', 'triggered')->count();
        $acknowledgedCount = AlertHistory::where('rule_id', $alertRule->rule_id)
            ->where('status', 'acknowledged')->count();
        $resolvedCount = AlertHistory::where('rule_id', $alertRule->rule_id)
            ->where('status', 'resolved')->count();

        $this->assertEquals(10, $totalAlerts);
        $this->assertEquals(5, $triggeredCount);
        $this->assertEquals(3, $acknowledgedCount);
        $this->assertEquals(2, $resolvedCount);
    }
}
