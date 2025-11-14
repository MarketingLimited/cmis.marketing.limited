<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Integration API Feature Tests
 */
class IntegrationAPITest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_list_all_integrations()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->createTestIntegration($org->org_id, 'instagram');
        $this->createTestIntegration($org->org_id, 'facebook');

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/integrations');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['integration_id', 'platform', 'status'],
            ],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/integrations',
        ]);
    }

    /** @test */
    public function it_can_get_integration_by_id()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/integrations/{$integration->integration_id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.platform', 'instagram');

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/integrations/{id}',
        ]);
    }

    /** @test */
    public function it_can_connect_instagram_integration()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->mockMetaAPI('success', [
            'data' => [
                'id' => 'ig_account_123',
                'username' => 'testuser',
            ],
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/integrations/connect', [
            'platform' => 'instagram',
            'access_token' => 'test_access_token_123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.platform', 'instagram');
        $response->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('cmis.integrations', [
            'org_id' => $org->org_id,
            'platform' => 'instagram',
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/integrations/connect',
            'platform' => 'instagram',
        ]);
    }

    /** @test */
    public function it_can_disconnect_integration()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/integrations/{$integration->integration_id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('cmis.integrations', [
            'integration_id' => $integration->integration_id,
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'DELETE /api/integrations/{id}',
        ]);
    }

    /** @test */
    public function it_can_refresh_integration_token()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success', [
            'access_token' => 'new_refreshed_token_123',
            'expires_in' => 5184000,
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/integrations/{$integration->integration_id}/refresh");

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'active');

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/integrations/{id}/refresh',
        ]);
    }

    /** @test */
    public function it_can_test_integration_connection()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'data' => ['id' => 'fb_page_123', 'name' => 'Test Page'],
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/integrations/{$integration->integration_id}/test");

        $response->assertStatus(200);
        $response->assertJsonPath('data.connection_status', 'successful');

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/integrations/{id}/test',
        ]);
    }

    /** @test */
    public function it_can_get_integration_permissions()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram', [
            'metadata' => [
                'scopes' => ['instagram_basic', 'instagram_content_publish', 'pages_read_engagement'],
            ],
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/integrations/{$integration->integration_id}/permissions");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['scopes'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/integrations/{id}/permissions',
        ]);
    }

    /** @test */
    public function it_can_sync_integration_data()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->mockMetaAPI('success', [
            'data' => [
                ['id' => 'post_1', 'caption' => 'Test post 1'],
                ['id' => 'post_2', 'caption' => 'Test post 2'],
            ],
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/integrations/{$integration->integration_id}/sync");

        $response->assertStatus(200);
        $response->assertJsonPath('data.sync_status', 'completed');

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/integrations/{id}/sync',
        ]);
    }

    /** @test */
    public function it_can_get_integration_activity_log()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/integrations/{$integration->integration_id}/activity");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['action', 'timestamp', 'status'],
            ],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/integrations/{id}/activity',
        ]);
    }

    /** @test */
    public function it_validates_platform_on_connect()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/integrations/connect', [
            'platform' => 'invalid_platform',
            'access_token' => 'test_token',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('platform');

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/integrations/connect',
            'validation' => 'invalid_platform_rejected',
        ]);
    }

    /** @test */
    public function it_respects_org_isolation()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $integration1 = $this->createTestIntegration($setup1['org']->org_id, 'instagram');

        $this->actingAs($setup2['user'], 'sanctum');

        $response = $this->getJson("/api/integrations/{$integration1->integration_id}");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/integrations/{id}',
            'test' => 'org_isolation',
        ]);
    }

    /** @test */
    public function it_can_configure_webhook_for_integration()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/integrations/{$integration->integration_id}/webhooks", [
            'webhook_url' => 'https://api.example.com/webhooks/instagram',
            'events' => ['messages', 'comments', 'mentions'],
        ]);

        $response->assertStatus(201);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/integrations/{id}/webhooks',
        ]);
    }

    /** @test */
    public function it_can_get_available_integrations()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/integrations/available');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['platform', 'name', 'description', 'features'],
            ],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/integrations/available',
        ]);
    }
}
