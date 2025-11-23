<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlatformIntegrationsApiTest extends TestCase
{
    use RefreshDatabase;

    protected string $orgId;
    protected string $userId;
    protected string $integrationId;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test organization and user
        $this->orgId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->userId = \Ramsey\Uuid\Uuid::uuid4()->toString();

        // Will be used for mocking authenticated requests
        $this->integrationId = \Ramsey\Uuid\Uuid::uuid4()->toString();
    }

    /** @test */
    public function it_requires_authentication_for_tiktok_endpoints()
    {
        $response = $this->getJson("/api/orgs/{$this->orgId}/tiktok-ads/campaigns");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_authentication_for_linkedin_endpoints()
    {
        $response = $this->getJson("/api/orgs/{$this->orgId}/linkedin-ads/campaigns");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_requires_authentication_for_automation_endpoints()
    {
        $response = $this->getJson("/api/orgs/{$this->orgId}/automation/rules");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_required_fields_for_tiktok_campaign_creation()
    {
        // Mock authentication
        $this->actingAs($this->createAuthenticatedUser());

        $response = $this->postJson("/api/orgs/{$this->orgId}/tiktok-ads/campaigns", [
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['integration_id', 'name', 'objective', 'budget']);
    }

    /** @test */
    public function it_validates_required_fields_for_linkedin_campaign_creation()
    {
        $this->actingAs($this->createAuthenticatedUser());

        $response = $this->postJson("/api/orgs/{$this->orgId}/linkedin-ads/campaigns", [
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['integration_id', 'name', 'objective']);
    }

    /** @test */
    public function it_validates_required_fields_for_automation_rule_creation()
    {
        $this->actingAs($this->createAuthenticatedUser());

        $response = $this->postJson("/api/orgs/{$this->orgId}/automation/rules", [
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'condition', 'action']);
    }

    /** @test */
    public function it_validates_tiktok_campaign_objectives()
    {
        $this->actingAs($this->createAuthenticatedUser());

        $response = $this->postJson("/api/orgs/{$this->orgId}/tiktok-ads/campaigns", [
            'integration_id' => $this->integrationId,
            'name' => 'Test Campaign',
            'objective' => 'INVALID_OBJECTIVE',
            'budget' => 100.00
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['objective']);
    }

    /** @test */
    public function it_validates_linkedin_b2b_objectives()
    {
        $this->actingAs($this->createAuthenticatedUser());

        $response = $this->postJson("/api/orgs/{$this->orgId}/linkedin-ads/campaigns", [
            'integration_id' => $this->integrationId,
            'name' => 'Test Campaign',
            'objective' => 'INVALID_OBJECTIVE'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['objective']);
    }

    /** @test */
    public function it_validates_automation_rule_structure()
    {
        $this->actingAs($this->createAuthenticatedUser());

        $response = $this->postJson("/api/orgs/{$this->orgId}/automation/rules", [
            'name' => 'Test Rule',
            'condition' => [
                'metric' => 'cpa',
                // Missing operator and value
            ],
            'action' => [
                // Missing type
            ]
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_validates_automation_rule_metrics()
    {
        $this->actingAs($this->createAuthenticatedUser());

        $response = $this->postJson("/api/orgs/{$this->orgId}/automation/rules", [
            'name' => 'Test Rule',
            'condition' => [
                'metric' => 'invalid_metric',
                'operator' => '>',
                'value' => 50
            ],
            'action' => [
                'type' => 'pause_underperforming'
            ]
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['condition.metric']);
    }

    /** @test */
    public function it_validates_automation_rule_operators()
    {
        $this->actingAs($this->createAuthenticatedUser());

        $response = $this->postJson("/api/orgs/{$this->orgId}/automation/rules", [
            'name' => 'Test Rule',
            'condition' => [
                'metric' => 'cpa',
                'operator' => '!=', // Invalid operator
                'value' => 50
            ],
            'action' => [
                'type' => 'pause_underperforming'
            ]
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['condition.operator']);
    }

    /** @test */
    public function it_validates_automation_action_types()
    {
        $this->actingAs($this->createAuthenticatedUser());

        $response = $this->postJson("/api/orgs/{$this->orgId}/automation/rules", [
            'name' => 'Test Rule',
            'condition' => [
                'metric' => 'cpa',
                'operator' => '>',
                'value' => 50
            ],
            'action' => [
                'type' => 'invalid_action'
            ]
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['action.type']);
    }

    /** @test */
    public function it_validates_budget_minimums_for_tiktok()
    {
        $this->actingAs($this->createAuthenticatedUser());

        $response = $this->postJson("/api/orgs/{$this->orgId}/tiktok-ads/campaigns", [
            'integration_id' => $this->integrationId,
            'name' => 'Test Campaign',
            'objective' => 'TRAFFIC',
            'budget' => 5.00 // Below minimum
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['budget']);
    }

    /** @test */
    public function it_validates_budget_minimums_for_linkedin()
    {
        $this->actingAs($this->createAuthenticatedUser());

        $response = $this->postJson("/api/orgs/{$this->orgId}/linkedin-ads/campaigns", [
            'integration_id' => $this->integrationId,
            'name' => 'Test Campaign',
            'objective' => 'BRAND_AWARENESS',
            'daily_budget' => 5.00 // Below minimum
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['daily_budget']);
    }

    /** @test */
    public function it_returns_automation_rule_templates()
    {
        $this->actingAs($this->createAuthenticatedUser());

        $response = $this->getJson("/api/orgs/{$this->orgId}/automation/rules/templates");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'templates' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'condition',
                        'action'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_validates_date_ranges_for_campaign_metrics()
    {
        $this->actingAs($this->createAuthenticatedUser());

        $response = $this->getJson("/api/orgs/{$this->orgId}/tiktok-ads/campaigns/123/metrics?" . http_build_query([
            'integration_id' => $this->integrationId,
            'start_date' => '2024-12-31',
            'end_date' => '2024-01-01' // End before start
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    /** @test */
    public function it_prevents_future_dates_in_metrics_requests()
    {
        $this->actingAs($this->createAuthenticatedUser());

        $futureDate = now()->addDays(10)->format('Y-m-d');

        $response = $this->getJson("/api/orgs/{$this->orgId}/linkedin-ads/campaigns/123/metrics?" . http_build_query([
            'integration_id' => $this->integrationId,
            'start_date' => $futureDate
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    /**
     * Helper method to create an authenticated user
     */
    protected function createAuthenticatedUser()
    {
        return new class($this->userId, $this->orgId) {
            public $id;
            public $org_id;

            public function __construct($id, $orgId)
            {
                $this->id = $id;
                $this->org_id = $orgId;
            }

            public function getAuthIdentifierName()
            {
                return 'id';
            }

            public function getAuthIdentifier()
            {
                return $this->id;
            }

            public function getAuthPassword()
            {
                return '';
            }

            public function getRememberToken()
            {
                return '';
            }

            public function setRememberToken($value)
            {
                //
            }

            public function getRememberTokenName()
            {
                return '';
            }
        };
    }
}
