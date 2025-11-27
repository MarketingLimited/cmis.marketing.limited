<?php

namespace Tests\Feature\Analytics;

use App\Models\Analytics\DataExportConfig;
use App\Models\Analytics\DataExportLog;
use App\Models\Core\APIToken;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataExportsControllerTest extends TestCase
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

        Storage::fake('local');
    }

    /** @test */
    public function it_can_list_export_configurations()
    {
        DataExportConfig::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/exports/configs");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'configs' => [
                'data' => [
                    '*' => [
                        'config_id',
                        'name',
                        'export_type',
                        'format',
                        'delivery_method',
                    ]
                ],
            ],
        ]);
    }

    /** @test */
    public function it_can_filter_configs_by_export_type()
    {
        DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'export_type' => 'analytics',
        ]);

        DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'export_type' => 'campaigns',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/exports/configs?export_type=analytics");

        $response->assertOk();
    }

    /** @test */
    public function it_can_filter_configs_by_format()
    {
        DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'format' => 'json',
        ]);

        DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'format' => 'csv',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/exports/configs?format=csv");

        $response->assertOk();
    }

    /** @test */
    public function it_can_create_an_export_configuration()
    {
        $data = [
            'name' => 'Daily Analytics Export',
            'description' => 'Export analytics data daily',
            'export_type' => 'analytics',
            'format' => 'csv',
            'delivery_method' => 'webhook',
            'data_config' => [
                'metrics' => ['spend', 'impressions', 'clicks'],
                'date_range' => 'last_7_days',
            ],
            'delivery_config' => [
                'webhook_url' => 'https://example.com/webhook',
            ],
            'schedule' => [
                'frequency' => 'daily',
                'time' => '08:00',
            ],
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/exports/configs", $data);

        $response->assertStatus(201);
        $response->assertJsonPath('config.name', 'Daily Analytics Export');
        $response->assertJsonPath('config.org_id', $this->org->org_id);
    }

    /** @test */
    public function it_validates_export_configuration_creation()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/exports/configs", [
                // Missing required fields
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'export_type', 'format', 'delivery_method', 'data_config', 'delivery_config']);
    }

    /** @test */
    public function it_validates_export_type_values()
    {
        $data = [
            'name' => 'Test Export',
            'export_type' => 'invalid_type',
            'format' => 'csv',
            'delivery_method' => 'download',
            'data_config' => [],
            'delivery_config' => [],
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/exports/configs", $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['export_type']);
    }

    /** @test */
    public function it_validates_format_values()
    {
        $data = [
            'name' => 'Test Export',
            'export_type' => 'analytics',
            'format' => 'invalid_format',
            'delivery_method' => 'download',
            'data_config' => [],
            'delivery_config' => [],
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/exports/configs", $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['format']);
    }

    /** @test */
    public function it_can_show_an_export_configuration()
    {
        $config = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/exports/configs/{$config->config_id}");

        $response->assertOk();
        $response->assertJsonPath('config.config_id', $config->config_id);
        $response->assertJsonPath('config.name', $config->name);
    }

    /** @test */
    public function it_can_update_an_export_configuration()
    {
        $config = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
            'name' => 'Original Name',
            'format' => 'json',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/orgs/{$this->org->org_id}/exports/configs/{$config->config_id}", [
                'name' => 'Updated Name',
                'format' => 'csv',
            ]);

        $response->assertOk();
        $response->assertJsonPath('config.name', 'Updated Name');
        $response->assertJsonPath('config.format', 'csv');
    }

    /** @test */
    public function it_can_delete_an_export_configuration()
    {
        $config = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/orgs/{$this->org->org_id}/exports/configs/{$config->config_id}");

        $response->assertOk();

        $this->assertSoftDeleted('cmis_analytics.data_export_configs', [
            'config_id' => $config->config_id,
        ]);
    }

    /** @test */
    public function it_can_execute_export_with_existing_config()
    {
        $config = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/exports/execute", [
                'config_id' => $config->config_id,
                'async' => true,
            ]);

        $response->assertStatus(202);
        $response->assertJsonPath('message', 'Export queued for processing');
    }

    /** @test */
    public function it_can_execute_manual_export()
    {
        $data = [
            'export_type' => 'campaigns',
            'format' => 'json',
            'data_config' => [
                'campaign_ids' => ['11111111-1111-1111-1111-111111111111'],
            ],
            'async' => false,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/exports/execute", $data);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'log',
            'download_url',
        ]);
    }

    /** @test */
    public function it_can_get_export_logs()
    {
        DataExportLog::factory()->count(15)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/exports/logs");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'logs' => [
                'data' => [
                    '*' => [
                        'log_id',
                        'config_id',
                        'status',
                        'format',
                        'started_at',
                    ]
                ],
            ],
        ]);
    }

    /** @test */
    public function it_can_filter_logs_by_status()
    {
        DataExportLog::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'completed',
        ]);

        DataExportLog::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'failed',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/exports/logs?status=completed");

        $response->assertOk();
    }

    /** @test */
    public function it_can_filter_logs_by_config_id()
    {
        $config = DataExportConfig::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        DataExportLog::factory()->create([
            'org_id' => $this->org->org_id,
            'config_id' => $config->config_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/exports/logs?config_id={$config->config_id}");

        $response->assertOk();
    }

    /** @test */
    public function it_can_download_export_file()
    {
        Storage::put('exports/test.json', '{"data": "test"}');

        $log = DataExportLog::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'completed',
            'file_path' => 'exports/test.json',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/api/orgs/{$this->org->org_id}/exports/download/{$log->log_id}");

        $response->assertOk();
        $response->assertDownload('test.json');
    }

    /** @test */
    public function it_returns_error_for_missing_export_file()
    {
        $log = DataExportLog::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'completed',
            'file_path' => 'exports/nonexistent.json',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/exports/download/{$log->log_id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_list_api_tokens()
    {
        APIToken::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/api-tokens");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'tokens' => [
                'data' => [
                    '*' => [
                        'token_id',
                        'name',
                        'scopes',
                        'is_active',
                    ]
                ],
            ],
        ]);
    }

    /** @test */
    public function it_can_filter_tokens_by_active_status()
    {
        APIToken::factory()->create([
            'org_id' => $this->org->org_id,
            'is_active' => true,
        ]);

        APIToken::factory()->create([
            'org_id' => $this->org->org_id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/api-tokens?active=true");

        $response->assertOk();
    }

    /** @test */
    public function it_can_create_an_api_token()
    {
        $data = [
            'name' => 'Analytics API Token',
            'scopes' => ['analytics:read', 'exports:read'],
            'rate_limits' => [
                'requests_per_minute' => 60,
            ],
            'expires_at' => now()->addYear()->toDateTimeString(),
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/api-tokens", $data);

        $response->assertStatus(201);
        $response->assertJsonPath('token.name', 'Analytics API Token');
        $response->assertJsonPath('token.org_id', $this->org->org_id);
        $response->assertJsonStructure([
            'success',
            'token',
            'plaintext_token',
            'message',
        ]);
    }

    /** @test */
    public function it_validates_api_token_creation()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/api-tokens", [
                // Missing required fields
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'scopes']);
    }

    /** @test */
    public function it_validates_token_scopes()
    {
        $data = [
            'name' => 'Test Token',
            'scopes' => ['invalid:scope'],
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/orgs/{$this->org->org_id}/api-tokens", $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['scopes.0']);
    }

    /** @test */
    public function it_can_revoke_an_api_token()
    {
        $token = APIToken::factory()->create([
            'org_id' => $this->org->org_id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/orgs/{$this->org->org_id}/api-tokens/{$token->token_id}");

        $response->assertOk();

        $this->assertDatabaseHas('cmis.api_tokens', [
            'token_id' => $token->token_id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_can_get_export_statistics()
    {
        DataExportConfig::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'is_active' => true,
        ]);

        DataExportLog::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
            'status' => 'completed',
        ]);

        DataExportLog::factory()->count(2)->create([
            'org_id' => $this->org->org_id,
            'status' => 'failed',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/exports/stats?days=30");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'stats' => [
                'total_configs',
                'active_configs',
                'total_exports',
                'successful_exports',
                'failed_exports',
                'total_records_exported',
                'total_data_size',
                'active_tokens',
                'by_format',
                'recent_exports',
            ],
            'period_days',
        ]);
    }

    /** @test */
    public function it_enforces_multi_tenancy()
    {
        $otherOrg = Org::factory()->create();

        DataExportConfig::factory()->count(5)->create([
            'org_id' => $otherOrg->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/orgs/{$this->org->org_id}/exports/configs");

        $response->assertOk();
        // Should return paginated result with 0 configs
    }

    /** @test */
    public function it_supports_all_export_formats()
    {
        $formats = ['json', 'csv', 'xlsx', 'parquet'];

        foreach ($formats as $format) {
            $data = [
                'name' => "Test Export {$format}",
                'export_type' => 'analytics',
                'format' => $format,
                'delivery_method' => 'download',
                'data_config' => ['metrics' => ['spend']],
                'delivery_config' => [],
            ];

            $response = $this->actingAs($this->user)
                ->postJson("/api/orgs/{$this->org->org_id}/exports/configs", $data);

            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_supports_all_delivery_methods()
    {
        $methods = ['download', 'webhook', 'sftp', 's3'];

        foreach ($methods as $method) {
            $data = [
                'name' => "Test Export {$method}",
                'export_type' => 'analytics',
                'format' => 'json',
                'delivery_method' => $method,
                'data_config' => ['metrics' => ['spend']],
                'delivery_config' => [],
            ];

            $response = $this->actingAs($this->user)
                ->postJson("/api/orgs/{$this->org->org_id}/exports/configs", $data);

            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_supports_scheduled_exports()
    {
        $schedules = [
            ['frequency' => 'hourly'],
            ['frequency' => 'daily', 'time' => '08:00'],
            ['frequency' => 'weekly', 'day_of_week' => 1, 'time' => '09:00'],
            ['frequency' => 'monthly', 'day_of_month' => 1, 'time' => '10:00'],
        ];

        foreach ($schedules as $schedule) {
            $data = [
                'name' => 'Scheduled Export',
                'export_type' => 'analytics',
                'format' => 'csv',
                'delivery_method' => 'webhook',
                'data_config' => [],
                'delivery_config' => ['webhook_url' => 'https://example.com'],
                'schedule' => $schedule,
            ];

            $response = $this->actingAs($this->user)
                ->postJson("/api/orgs/{$this->org->org_id}/exports/configs", $data);

            $response->assertStatus(201);
        }
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson("/api/orgs/{$this->org->org_id}/exports/configs");

        $response->assertStatus(401);
    }
}
