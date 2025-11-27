<?php

namespace Tests\Feature\Dashboard;

use App\Models\Dashboard\Dashboard;
use App\Models\Dashboard\DashboardWidget;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;

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

        // Set session org context
        session(['current_org_id' => $this->org->org_id]);
    }

    /** @test */
    public function it_can_list_dashboards()
    {
        // Create dashboards
        Dashboard::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('dashboards.index'));

        $response->assertOk();
        $response->assertViewIs('dashboards.index');
        $response->assertViewHas('dashboards');
    }

    /** @test */
    public function it_can_show_a_dashboard()
    {
        $dashboard = Dashboard::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
        ]);

        // Create some widgets
        DashboardWidget::factory()->count(3)->create([
            'dashboard_id' => $dashboard->dashboard_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('dashboards.show', $dashboard->dashboard_id));

        $response->assertOk();
        $response->assertViewIs('dashboards.show');
        $response->assertViewHas('dashboard');
    }

    /** @test */
    public function it_can_create_a_dashboard()
    {
        $data = [
            'name' => 'My Dashboard',
            'description' => 'A test dashboard',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('dashboards.store'), $data);

        $this->assertDatabaseHas('cmis_dashboard.dashboards', [
            'org_id' => $this->org->org_id,
            'name' => 'My Dashboard',
            'created_by' => $this->user->user_id,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_update_a_dashboard()
    {
        $dashboard = Dashboard::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Original Name',
        ]);

        $data = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('dashboards.update', $dashboard->dashboard_id), $data);

        $this->assertDatabaseHas('cmis_dashboard.dashboards', [
            'dashboard_id' => $dashboard->dashboard_id,
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_delete_a_dashboard()
    {
        $dashboard = Dashboard::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('dashboards.destroy', $dashboard->dashboard_id));

        $this->assertSoftDeleted('cmis_dashboard.dashboards', [
            'dashboard_id' => $dashboard->dashboard_id,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_duplicate_a_dashboard()
    {
        $dashboard = Dashboard::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Original Dashboard',
        ]);

        // Create widgets
        DashboardWidget::factory()->count(3)->create([
            'dashboard_id' => $dashboard->dashboard_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('dashboards.duplicate', $dashboard->dashboard_id));

        // Check that a new dashboard was created
        $this->assertDatabaseHas('cmis_dashboard.dashboards', [
            'org_id' => $this->org->org_id,
            'name' => 'Original Dashboard (Copy)',
        ]);

        // Check that widgets were duplicated
        $duplicated = Dashboard::where('name', 'Original Dashboard (Copy)')->first();
        $this->assertEquals(3, $duplicated->widgets()->count());

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_share_a_dashboard()
    {
        $dashboard = Dashboard::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $userIds = [
            '11111111-1111-1111-1111-111111111111',
            '22222222-2222-2222-2222-222222222222',
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('dashboards.share', $dashboard->dashboard_id), [
                'user_ids' => $userIds,
                'permission' => 'view',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
    }

    /** @test */
    public function it_can_export_a_dashboard()
    {
        $dashboard = Dashboard::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        DashboardWidget::factory()->count(2)->create([
            'dashboard_id' => $dashboard->dashboard_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('dashboards.export', $dashboard->dashboard_id));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'name',
                'description',
                'widgets',
                'exported_at',
            ],
        ]);
    }

    /** @test */
    public function it_can_import_a_dashboard()
    {
        $config = [
            'name' => 'Imported Dashboard',
            'description' => 'An imported dashboard',
            'widgets' => [
                [
                    'widget_type' => 'metric_card',
                    'name' => 'Widget 1',
                    'config' => ['metric' => 'total_spend'],
                    'position_x' => 0,
                    'position_y' => 0,
                    'width' => 4,
                    'height' => 3,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('dashboards.import'), [
                'config' => json_encode($config),
            ]);

        $this->assertDatabaseHas('cmis_dashboard.dashboards', [
            'org_id' => $this->org->org_id,
            'name' => 'Imported Dashboard',
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_can_get_dashboard_templates()
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('dashboards.templates'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
    }

    /** @test */
    public function it_can_create_dashboard_from_template()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('dashboards.createFromTemplate'), [
                'template_id' => 'campaign_overview',
                'name' => 'My Campaign Dashboard',
            ]);

        $this->assertDatabaseHas('cmis_dashboard.dashboards', [
            'org_id' => $this->org->org_id,
            'name' => 'My Campaign Dashboard',
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function it_can_update_dashboard_layout()
    {
        $dashboard = Dashboard::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $layout = [
            'columns' => 12,
            'row_height' => 100,
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('dashboards.updateLayout', $dashboard->dashboard_id), [
                'layout' => $layout,
            ]);

        $this->assertDatabaseHas('cmis_dashboard.dashboards', [
            'dashboard_id' => $dashboard->dashboard_id,
            'layout' => json_encode($layout),
        ]);

        $response->assertOk();
    }

    /** @test */
    public function it_can_get_dashboard_analytics()
    {
        Dashboard::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('dashboards.analytics'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'summary',
                'most_used',
                'widget_types',
            ],
        ]);
    }

    /** @test */
    public function it_can_set_default_dashboard()
    {
        $dashboard = Dashboard::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('dashboards.setDefault', $dashboard->dashboard_id));

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_refresh_all_widgets()
    {
        $dashboard = Dashboard::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        DashboardWidget::factory()->count(3)->create([
            'dashboard_id' => $dashboard->dashboard_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('dashboards.refreshWidgets', $dashboard->dashboard_id));

        $response->assertOk();
    }

    /** @test */
    public function it_can_create_dashboard_snapshot()
    {
        $dashboard = Dashboard::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('dashboards.snapshot', $dashboard->dashboard_id), [
                'format' => 'json',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
    }

    /** @test */
    public function it_enforces_multi_tenancy()
    {
        // Create another org
        $otherOrg = Org::factory()->create();

        // Create dashboard in other org
        $otherDashboard = Dashboard::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        // Try to access other org's dashboard
        $response = $this->actingAs($this->user)
            ->get(route('dashboards.show', $otherDashboard->dashboard_id));

        $response->assertNotFound();
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $data = [];

        $response = $this->actingAs($this->user)
            ->postJson(route('dashboards.store'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_filters_dashboards_by_search()
    {
        Dashboard::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Marketing Dashboard',
        ]);

        Dashboard::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Sales Dashboard',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('dashboards.index', ['search' => 'Marketing']));

        $response->assertOk();
    }

    /** @test */
    public function it_filters_dashboards_by_creator()
    {
        Dashboard::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $this->user->user_id,
        ]);

        $otherUser = User::factory()->create();
        Dashboard::factory()->create([
            'org_id' => $this->org->org_id,
            'created_by' => $otherUser->user_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('dashboards.index', ['created_by' => $this->user->user_id]));

        $response->assertOk();
    }
}
