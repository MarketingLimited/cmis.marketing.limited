<?php

namespace Tests\Feature\Intelligence;

use App\Models\Intelligence\Forecast;
use App\Models\Intelligence\PredictionModel;
use App\Models\Campaign\Campaign;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForecastControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;
    protected Campaign $campaign;
    protected PredictionModel $model;

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

        // Create prediction model
        $this->model = PredictionModel::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        // Set session org context
        session(['current_org_id' => $this->org->org_id]);
    }

    /** @test */
    public function it_can_list_forecasts()
    {
        // Create forecasts
        Forecast::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'model_id' => $this->model->model_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('forecasts.index'));

        $response->assertOk();
        $response->assertViewIs('intelligence.forecasts.index');
        $response->assertViewHas('forecasts');
    }

    /** @test */
    public function it_can_show_a_forecast()
    {
        $forecast = Forecast::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'model_id' => $this->model->model_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('forecasts.show', $forecast->forecast_id));

        $response->assertOk();
        $response->assertViewIs('intelligence.forecasts.show');
        $response->assertViewHas('forecast');
    }

    /** @test */
    public function it_can_create_a_forecast()
    {
        $data = [
            'model_id' => $this->model->model_id,
            'campaign_id' => $this->campaign->campaign_id,
            'metric_name' => 'impressions',
            'forecast_date' => now()->addDays(7)->toDateString(),
            'predicted_value' => 1000,
            'confidence_lower' => 900,
            'confidence_upper' => 1100,
            'confidence_level' => 0.95,
            'forecast_horizon' => 7,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('forecasts.store'), $data);

        $this->assertDatabaseHas('cmis_intelligence.forecasts', [
            'org_id' => $this->org->org_id,
            'metric_name' => 'impressions',
            'predicted_value' => 1000,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_update_a_forecast()
    {
        $forecast = Forecast::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'model_id' => $this->model->model_id,
            'predicted_value' => 1000,
        ]);

        $data = [
            'predicted_value' => 1500,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('forecasts.update', $forecast->forecast_id), $data);

        $this->assertDatabaseHas('cmis_intelligence.forecasts', [
            'forecast_id' => $forecast->forecast_id,
            'predicted_value' => 1500,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_delete_a_forecast()
    {
        $forecast = Forecast::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'model_id' => $this->model->model_id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('forecasts.destroy', $forecast->forecast_id));

        $this->assertSoftDeleted('cmis_intelligence.forecasts', [
            'forecast_id' => $forecast->forecast_id,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_generate_forecasts()
    {
        $data = [
            'campaign_id' => $this->campaign->campaign_id,
            'metrics' => ['impressions', 'clicks'],
            'forecast_horizon' => 30,
            'model_id' => $this->model->model_id,
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('forecasts.generate'), $data);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
    }

    /** @test */
    public function it_can_record_actuals()
    {
        $forecast = Forecast::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'model_id' => $this->model->model_id,
            'predicted_value' => 1000,
            'actuals' => null,
        ]);

        $data = ['actuals' => 950];

        $response = $this->actingAs($this->user)
            ->postJson(route('forecasts.recordActuals', $forecast->forecast_id), $data);

        $this->assertDatabaseHas('cmis_intelligence.forecasts', [
            'forecast_id' => $forecast->forecast_id,
            'actuals' => 950,
        ]);

        $response->assertOk();
    }

    /** @test */
    public function it_can_get_forecast_accuracy_report()
    {
        // Create forecasts with actuals
        Forecast::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'model_id' => $this->model->model_id,
            'predicted_value' => 1000,
            'actuals' => 950,
            'accuracy' => 0.95,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('forecasts.accuracy'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'total_forecasts',
                'forecasts_with_actuals',
                'average_accuracy',
                'mae',
                'rmse',
                'mape',
            ],
        ]);
    }

    /** @test */
    public function it_can_get_analytics_dashboard_data()
    {
        Forecast::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'model_id' => $this->model->model_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('forecasts.analytics'));

        $response->assertOk();
        $response->assertViewIs('intelligence.forecasts.analytics');
        $response->assertViewHas('analytics');
    }

    /** @test */
    public function it_enforces_multi_tenancy()
    {
        // Create another org
        $otherOrg = Org::factory()->create();

        // Create forecast in other org
        $otherForecast = Forecast::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        // Try to access other org's forecast
        $response = $this->actingAs($this->user)
            ->get(route('forecasts.show', $otherForecast->forecast_id));

        $response->assertNotFound();
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $data = [];

        $response = $this->actingAs($this->user)
            ->postJson(route('forecasts.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['model_id', 'metric_name', 'forecast_date', 'predicted_value']);
    }
}
