<?php

namespace Tests\Unit\Models\Analytics;

use App\Models\Analytics\AlertHistory;
use App\Models\Analytics\AlertNotification;
use App\Models\Analytics\AlertRule;
use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected Org $org;
    protected User $user;
    protected AlertRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create([
            'org_id' => $this->org->org_id,
        ]);
        $this->rule = AlertRule::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
        ]);
    }

    /** @test */
    public function it_can_create_alert_history()
    {
        $alert = AlertHistory::create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'triggered_at' => now(),
            'entity_type' => 'campaign',
            'metric' => 'ctr',
            'actual_value' => 6.5,
            'threshold_value' => 5.0,
            'condition' => 'gt',
            'severity' => 'high',
            'message' => 'CTR exceeded threshold',
            'status' => 'new',
        ]);

        $this->assertDatabaseHas('cmis.alert_history', [
            'alert_id' => $alert->alert_id,
            'status' => 'new',
        ]);
    }

    /** @test */
    public function it_belongs_to_rule()
    {
        $alert = AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
        ]);

        $this->assertInstanceOf(AlertRule::class, $alert->rule);
        $this->assertEquals($this->rule->rule_id, $alert->rule->rule_id);
    }

    /** @test */
    public function it_has_many_notifications()
    {
        $alert = AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
        ]);

        AlertNotification::factory()->count(3)->create([
            'alert_id' => $alert->alert_id,
            'org_id' => $this->org->org_id,
        ]);

        $this->assertCount(3, $alert->notifications);
    }

    /** @test */
    public function scope_new_filters_new_alerts()
    {
        AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'new',
        ]);

        AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'resolved',
        ]);

        $this->assertEquals(1, AlertHistory::new()->count());
    }

    /** @test */
    public function scope_active_excludes_resolved()
    {
        AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'new',
        ]);

        AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'acknowledged',
        ]);

        AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'resolved',
        ]);

        $this->assertEquals(2, AlertHistory::active()->count());
    }

    /** @test */
    public function scope_critical_filters_critical_severity()
    {
        AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'severity' => 'critical',
        ]);

        AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'severity' => 'low',
        ]);

        $this->assertEquals(1, AlertHistory::critical()->count());
    }

    /** @test */
    public function acknowledge_updates_status_and_fields()
    {
        $alert = AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'new',
        ]);

        $alert->acknowledge($this->user->user_id, 'Investigating this issue');

        $fresh = $alert->fresh();
        $this->assertEquals('acknowledged', $fresh->status);
        $this->assertEquals($this->user->user_id, $fresh->acknowledged_by);
        $this->assertNotNull($fresh->acknowledged_at);
        $this->assertEquals('Investigating this issue', $fresh->resolution_notes);
    }

    /** @test */
    public function resolve_updates_status_and_fields()
    {
        $alert = AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'acknowledged',
        ]);

        $alert->resolve($this->user->user_id, 'Issue fixed');

        $fresh = $alert->fresh();
        $this->assertEquals('resolved', $fresh->status);
        $this->assertNotNull($fresh->resolved_at);
        $this->assertEquals('Issue fixed', $fresh->resolution_notes);
    }

    /** @test */
    public function snooze_updates_status_and_snooze_time()
    {
        $alert = AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'new',
        ]);

        $alert->snooze(60);

        $fresh = $alert->fresh();
        $this->assertEquals('snoozed', $fresh->status);
        $this->assertNotNull($fresh->snoozed_until);
        $this->assertTrue($fresh->snoozed_until->isFuture());
    }

    /** @test */
    public function unsnooze_clears_snooze_status()
    {
        $alert = AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'snoozed',
            'snoozed_until' => now()->addHour(),
        ]);

        $alert->unsnooze();

        $fresh = $alert->fresh();
        $this->assertEquals('new', $fresh->status);
        $this->assertNull($fresh->snoozed_until);
    }

    /** @test */
    public function is_snoozed_returns_correct_status()
    {
        $alert = AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'snoozed',
            'snoozed_until' => now()->addHour(),
        ]);

        $this->assertTrue($alert->isSnoozed());

        $alert->update(['snoozed_until' => now()->subHour()]);
        $this->assertFalse($alert->fresh()->isSnoozed());
    }

    /** @test */
    public function requires_attention_identifies_critical_and_high_alerts()
    {
        $critical = AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'new',
            'severity' => 'critical',
        ]);

        $low = AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'new',
            'severity' => 'low',
        ]);

        $resolved = AlertHistory::factory()->create([
            'rule_id' => $this->rule->rule_id,
            'org_id' => $this->org->org_id,
            'status' => 'resolved',
            'severity' => 'critical',
        ]);

        $this->assertTrue($critical->requiresAttention());
        $this->assertFalse($low->requiresAttention());
        $this->assertFalse($resolved->requiresAttention());
    }
}
