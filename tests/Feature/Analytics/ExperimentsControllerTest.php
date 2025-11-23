<?php

namespace Tests\Feature\Analytics;

use App\Models\Analytics\Experiment;
use App\Models\Analytics\ExperimentVariant;
use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExperimentsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Org $org;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create([
            'org_id' => $this->org->org_id
        ]);

        Sanctum::actingAs($this->user);

        // Set RLS context
        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $this->user->user_id,
            $this->org->org_id
        ]);
    }

    /** @test */
    public function it_lists_experiments()
    {
        Experiment::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/experiments");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'experiments' => [
                    'data' => [
                        '*' => [
                            'experiment_id',
                            'name',
                            'status',
                            'experiment_type',
                        ]
                    ]
                ]
            ]);

        $this->assertTrue($response->json('success'));
    }

    /** @test */
    public function it_filters_experiments_by_status()
    {
        Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
        ]);

        Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/experiments?status=running");

        $response->assertOk();
        $experiments = $response->json('experiments.data');

        $this->assertCount(1, $experiments);
        $this->assertEquals('running', $experiments[0]['status']);
    }

    /** @test */
    public function it_creates_experiment()
    {
        $data = [
            'name' => 'New Campaign Experiment',
            'description' => 'Testing ad creatives',
            'experiment_type' => 'campaign',
            'metric' => 'conversion_rate',
            'hypothesis' => 'New creative will improve conversions',
            'duration_days' => 14,
            'confidence_level' => 95.00,
            'control_config' => ['creative_id' => 'original'],
        ];

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/experiments", $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'experiment' => [
                    'experiment_id',
                    'name',
                    'status',
                    'variants',
                ],
                'message'
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('New Campaign Experiment', $response->json('experiment.name'));
        $this->assertEquals('draft', $response->json('experiment.status'));

        // Verify control variant was created
        $variants = $response->json('experiment.variants');
        $this->assertCount(1, $variants);
        $this->assertEquals('Control', $variants[0]['name']);
    }

    /** @test */
    public function it_validates_experiment_creation()
    {
        $data = [
            'name' => '', // Invalid: empty name
            'experiment_type' => 'invalid', // Invalid type
        ];

        $response = $this->postJson("/api/orgs/{$this->org->org_id}/experiments", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'experiment_type', 'metric']);
    }

    /** @test */
    public function it_shows_experiment_details()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        ExperimentVariant::factory()->count(2)->create([
            'experiment_id' => $experiment->experiment_id,
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/experiments/{$experiment->experiment_id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'experiment' => [
                    'experiment_id',
                    'name',
                    'variants',
                    'results',
                ],
                'performance',
            ]);
    }

    /** @test */
    public function it_updates_draft_experiment()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
            'name' => 'Original Name',
        ]);

        $response = $this->putJson(
            "/api/orgs/{$this->org->org_id}/experiments/{$experiment->experiment_id}",
            ['name' => 'Updated Name']
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'experiment' => [
                    'name' => 'Updated Name',
                ]
            ]);
    }

    /** @test */
    public function it_prevents_updating_running_experiment()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
        ]);

        $response = $this->putJson(
            "/api/orgs/{$this->org->org_id}/experiments/{$experiment->experiment_id}",
            ['name' => 'New Name']
        );

        $response->assertStatus(422);
    }

    /** @test */
    public function it_deletes_experiment()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
        ]);

        $response = $this->deleteJson("/api/orgs/{$this->org->org_id}/experiments/{$experiment->experiment_id}");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('cmis.experiments', [
            'experiment_id' => $experiment->experiment_id,
        ]);
    }

    /** @test */
    public function it_prevents_deleting_running_experiment()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
        ]);

        $response = $this->deleteJson("/api/orgs/{$this->org->org_id}/experiments/{$experiment->experiment_id}");

        $response->assertStatus(422);
    }

    /** @test */
    public function it_adds_variant_to_experiment()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
        ]);

        ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'is_control' => true,
        ]);

        $variantData = [
            'name' => 'Variant A',
            'description' => 'New creative',
            'traffic_percentage' => 50.00,
            'config' => ['creative_id' => 'new_design'],
        ];

        $response = $this->postJson(
            "/api/orgs/{$this->org->org_id}/experiments/{$experiment->experiment_id}/variants",
            $variantData
        );

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'variant' => [
                    'name' => 'Variant A',
                ]
            ]);
    }

    /** @test */
    public function it_updates_variant()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $variant = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'name' => 'Original',
        ]);

        $response = $this->putJson(
            "/api/orgs/{$this->org->org_id}/experiments/{$experiment->experiment_id}/variants/{$variant->variant_id}",
            ['name' => 'Updated Variant']
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'variant' => [
                    'name' => 'Updated Variant',
                ]
            ]);
    }

    /** @test */
    public function it_starts_experiment()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
        ]);

        // Create control and test variant
        ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'is_control' => true,
        ]);

        ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'is_control' => false,
        ]);

        $response = $this->postJson(
            "/api/orgs/{$this->org->org_id}/experiments/{$experiment->experiment_id}/start"
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'experiment' => [
                    'status' => 'running',
                ]
            ]);

        $this->assertNotNull($response->json('experiment.started_at'));
    }

    /** @test */
    public function it_pauses_running_experiment()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $response = $this->postJson(
            "/api/orgs/{$this->org->org_id}/experiments/{$experiment->experiment_id}/pause"
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'experiment' => [
                    'status' => 'paused',
                ]
            ]);
    }

    /** @test */
    public function it_resumes_paused_experiment()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'paused',
        ]);

        $response = $this->postJson(
            "/api/orgs/{$this->org->org_id}/experiments/{$experiment->experiment_id}/resume"
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'experiment' => [
                    'status' => 'running',
                ]
            ]);
    }

    /** @test */
    public function it_completes_experiment_and_determines_winner()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
        ]);

        // Control: 10% conversion
        $control = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'is_control' => true,
            'impressions' => 1000,
            'conversions' => 100,
        ]);

        // Variant: 15% conversion (winner)
        $variant = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
            'is_control' => false,
            'impressions' => 1000,
            'conversions' => 150,
        ]);

        $response = $this->postJson(
            "/api/orgs/{$this->org->org_id}/experiments/{$experiment->experiment_id}/complete"
        );

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'experiment' => [
                    'status',
                    'winner_variant_id',
                ],
                'winner',
                'significance_results',
            ]);

        $this->assertEquals('completed', $response->json('experiment.status'));
        $this->assertNotNull($response->json('winner'));
    }

    /** @test */
    public function it_records_experiment_event()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
        ]);

        $variant = ExperimentVariant::factory()->create([
            'experiment_id' => $experiment->experiment_id,
        ]);

        $eventData = [
            'variant_id' => $variant->variant_id,
            'event_type' => 'conversion',
            'user_id' => 'test-user-123',
            'value' => 50.00,
        ];

        $response = $this->postJson(
            "/api/orgs/{$this->org->org_id}/experiments/{$experiment->experiment_id}/events",
            $eventData
        );

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'event' => [
                    'event_type' => 'conversion',
                ]
            ]);
    }

    /** @test */
    public function it_gets_experiment_results()
    {
        $experiment = Experiment::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
        ]);

        ExperimentVariant::factory()->count(2)->create([
            'experiment_id' => $experiment->experiment_id,
        ]);

        $response = $this->getJson(
            "/api/orgs/{$this->org->org_id}/experiments/{$experiment->experiment_id}/results"
        );

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'performance',
                'time_series',
                'statistical_significance',
            ]);
    }

    /** @test */
    public function it_gets_experiment_statistics()
    {
        Experiment::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
        ]);

        Experiment::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
        ]);

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/experiments/stats");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'stats' => [
                    'total_experiments',
                    'running_experiments',
                    'completed_experiments',
                    'draft_experiments',
                    'recent_experiments',
                ]
            ]);

        $stats = $response->json('stats');
        $this->assertEquals(8, $stats['total_experiments']);
        $this->assertEquals(3, $stats['running_experiments']);
        $this->assertEquals(5, $stats['draft_experiments']);
    }

    /** @test */
    public function it_enforces_multi_tenancy()
    {
        // Create experiment for another org
        $otherOrg = Org::factory()->create();
        $otherExperiment = Experiment::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        // Try to access other org's experiment
        $response = $this->getJson("/api/orgs/{$this->org->org_id}/experiments/{$otherExperiment->experiment_id}");

        $response->assertNotFound();
    }

    /** @test */
    public function it_requires_authentication()
    {
        auth()->logout();

        $response = $this->getJson("/api/orgs/{$this->org->org_id}/experiments");

        $response->assertUnauthorized();
    }
}
