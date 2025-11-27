<?php

namespace Tests\Feature\Intelligence;

use App\Models\Intelligence\Anomaly;
use App\Models\Campaign\Campaign;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnomalyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;
    protected Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        // Create organization and user
        $this->org = Org::factory()->create();
        $this->user = User::factory()->create();

        // Associate user with organization
        $this->user->orgs()->attach($this->org->org_id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create campaign
        $this->campaign = Campaign::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        // Set session org context
        session(['current_org_id' => $this->org->org_id]);
    }

    /** @test */
    public function it_can_list_anomalies()
    {
        // Create anomalies
        Anomaly::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $this->campaign->campaign_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('anomalies.index'));

        $response->assertOk();
        $response->assertViewIs('intelligence.anomalies.index');
        $response->assertViewHas('anomalies');
    }

    /** @test */
    public function it_can_show_an_anomaly()
    {
        $anomaly = Anomaly::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $this->campaign->campaign_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('anomalies.show', $anomaly->anomaly_id));

        $response->assertOk();
        $response->assertViewIs('intelligence.anomalies.show');
        $response->assertViewHas('anomaly');
    }

    /** @test */
    public function it_can_detect_anomalies()
    {
        $data = [
            'entity_type' => 'campaign',
            'entity_id' => $this->campaign->campaign_id,
            'metrics' => ['impressions', 'clicks', 'ctr'],
            'date_from' => now()->subDays(30)->toDateString(),
            'date_to' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('anomalies.detect'), $data);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
    }

    /** @test */
    public function it_can_resolve_an_anomaly()
    {
        $anomaly = Anomaly::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $this->campaign->campaign_id,
            'status' => Anomaly::STATUS_DETECTED,
        ]);

        $data = ['resolution' => 'Caused by seasonal traffic spike'];

        $response = $this->actingAs($this->user)
            ->postJson(route('anomalies.resolve', $anomaly->anomaly_id), $data);

        $this->assertDatabaseHas('cmis_intelligence.anomalies', [
            'anomaly_id' => $anomaly->anomaly_id,
            'status' => Anomaly::STATUS_RESOLVED,
            'resolution' => 'Caused by seasonal traffic spike',
        ]);

        $response->assertOk();
    }

    /** @test */
    public function it_can_mark_anomaly_as_false_positive()
    {
        $anomaly = Anomaly::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $this->campaign->campaign_id,
            'status' => Anomaly::STATUS_DETECTED,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('anomalies.markFalsePositive', $anomaly->anomaly_id));

        $this->assertDatabaseHas('cmis_intelligence.anomalies', [
            'anomaly_id' => $anomaly->anomaly_id,
            'status' => Anomaly::STATUS_FALSE_POSITIVE,
        ]);

        $response->assertOk();
    }

    /** @test */
    public function it_can_mark_anomaly_as_investigating()
    {
        $anomaly = Anomaly::factory()->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $this->campaign->campaign_id,
            'status' => Anomaly::STATUS_DETECTED,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('anomalies.investigate', $anomaly->anomaly_id));

        $this->assertDatabaseHas('cmis_intelligence.anomalies', [
            'anomaly_id' => $anomaly->anomaly_id,
            'status' => Anomaly::STATUS_INVESTIGATING,
        ]);

        $response->assertOk();
    }

    /** @test */
    public function it_can_filter_anomalies_by_severity()
    {
        // Create anomalies with different severities
        Anomaly::factory()->create([
            'org_id' => $this->org->org_id,
            'severity' => Anomaly::SEVERITY_CRITICAL,
        ]);

        Anomaly::factory()->create([
            'org_id' => $this->org->org_id,
            'severity' => Anomaly::SEVERITY_LOW,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('anomalies.index', ['severity' => Anomaly::SEVERITY_CRITICAL]));

        $response->assertOk();
    }

    /** @test */
    public function it_can_filter_anomalies_by_status()
    {
        // Create resolved and unresolved anomalies
        Anomaly::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => Anomaly::STATUS_RESOLVED,
        ]);

        Anomaly::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => Anomaly::STATUS_DETECTED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('anomalies.index', ['status' => Anomaly::STATUS_DETECTED]));

        $response->assertOk();
    }

    /** @test */
    public function it_can_get_analytics_dashboard_data()
    {
        Anomaly::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $this->campaign->campaign_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('anomalies.analytics'));

        $response->assertOk();
        $response->assertViewIs('intelligence.anomalies.analytics');
        $response->assertViewHas('analytics');
    }

    /** @test */
    public function it_can_get_anomaly_summary()
    {
        Anomaly::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'entity_type' => 'campaign',
            'entity_id' => $this->campaign->campaign_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('anomalies.summary'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'period_days',
                'total_anomalies',
                'by_severity',
                'by_status',
            ],
        ]);
    }

    /** @test */
    public function it_enforces_multi_tenancy()
    {
        // Create another org
        $otherOrg = Org::factory()->create();

        // Create anomaly in other org
        $otherAnomaly = Anomaly::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        // Try to access other org's anomaly
        $response = $this->actingAs($this->user)
            ->get(route('anomalies.show', $otherAnomaly->anomaly_id));

        $response->assertNotFound();
    }

    /** @test */
    public function it_validates_required_fields_on_detection()
    {
        $data = [];

        $response = $this->actingAs($this->user)
            ->postJson(route('anomalies.detect'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['entity_type', 'entity_id', 'metrics']);
    }

    /** @test */
    public function it_validates_resolution_field()
    {
        $anomaly = Anomaly::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => Anomaly::STATUS_DETECTED,
        ]);

        $data = []; // Missing resolution

        $response = $this->actingAs($this->user)
            ->postJson(route('anomalies.resolve', $anomaly->anomaly_id), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['resolution']);
    }
}
