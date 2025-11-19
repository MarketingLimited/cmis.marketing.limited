<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Integration\Integration;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Integration Controller Feature Tests
 */
class IntegrationControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_list_all_integrations()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->createTestIntegration($org->org_id, 'facebook');
        $this->createTestIntegration($org->org_id, 'instagram');

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/integrations');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');

        $this->logTestResult('passed', [
            'controller' => 'IntegrationController',
            'endpoint' => 'GET /api/integrations',
        ]);
    }

    #[Test]
    public function it_can_create_new_integration()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/integrations', [
            'platform' => 'facebook',
            'name' => 'Facebook Page Integration',
            'credentials' => [
                'access_token' => 'test_token',
                'page_id' => 'page_123',
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.platform', 'facebook');

        $this->logTestResult('passed', [
            'controller' => 'IntegrationController',
            'endpoint' => 'POST /api/integrations',
        ]);
    }

    #[Test]
    public function it_can_get_single_integration()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/integrations/{$integration->integration_id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.integration_id', $integration->integration_id);

        $this->logTestResult('passed', [
            'controller' => 'IntegrationController',
            'endpoint' => 'GET /api/integrations/{id}',
        ]);
    }

    #[Test]
    public function it_can_update_integration()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->actingAs($user, 'sanctum');

        $response = $this->putJson("/api/integrations/{$integration->integration_id}", [
            'name' => 'Updated Integration Name',
            'is_active' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Integration Name');

        $this->logTestResult('passed', [
            'controller' => 'IntegrationController',
            'endpoint' => 'PUT /api/integrations/{id}',
        ]);
    }

    #[Test]
    public function it_can_delete_integration()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/integrations/{$integration->integration_id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('cmis.integrations', [
            'integration_id' => $integration->integration_id,
        ]);

        $this->logTestResult('passed', [
            'controller' => 'IntegrationController',
            'endpoint' => 'DELETE /api/integrations/{id}',
        ]);
    }

    #[Test]
    public function it_can_test_integration_connection()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/integrations/{$integration->integration_id}/test");

        $response->assertStatus(200);
        $response->assertJsonPath('data.connected', true);

        $this->logTestResult('passed', [
            'controller' => 'IntegrationController',
            'endpoint' => 'POST /api/integrations/{id}/test',
        ]);
    }

    #[Test]
    public function it_can_refresh_integration_token()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $this->mockMetaAPI('success', [
            'access_token' => 'new_refreshed_token',
            'expires_in' => 5184000,
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/integrations/{$integration->integration_id}/refresh-token");

        $response->assertStatus(200);

        $this->logTestResult('passed', [
            'controller' => 'IntegrationController',
            'endpoint' => 'POST /api/integrations/{id}/refresh-token',
        ]);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/integrations', [
            'name' => 'Test Integration',
            // Missing platform
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('platform');

        $this->logTestResult('passed', [
            'controller' => 'IntegrationController',
            'test' => 'validation',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $integration1 = $this->createTestIntegration($setup1['org']->org_id, 'facebook');

        $this->actingAs($setup2['user'], 'sanctum');

        $response = $this->getJson("/api/integrations/{$integration1->integration_id}");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'IntegrationController',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/integrations');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'IntegrationController',
            'test' => 'authentication_required',
        ]);
    }
}
