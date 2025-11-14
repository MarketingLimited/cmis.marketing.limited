<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Lead\Lead;
use Illuminate\Support\Str;

/**
 * Lead Controller Feature Tests
 */
class LeadControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_list_all_leads()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Lead 1',
            'email' => 'lead1@example.com',
            'status' => 'new',
        ]);

        Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Lead 2',
            'email' => 'lead2@example.com',
            'status' => 'qualified',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/leads');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');

        $this->logTestResult('passed', [
            'controller' => 'LeadController',
            'endpoint' => 'GET /api/leads',
        ]);
    }

    /** @test */
    public function it_can_create_lead()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/leads', [
            'name' => 'عميل محتمل جديد',
            'email' => 'newlead@example.com',
            'phone' => '+966501234567',
            'status' => 'new',
            'source' => 'website',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'عميل محتمل جديد');

        $this->logTestResult('passed', [
            'controller' => 'LeadController',
            'endpoint' => 'POST /api/leads',
        ]);
    }

    /** @test */
    public function it_can_get_single_lead()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Lead',
            'email' => 'test@example.com',
            'status' => 'new',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/leads/{$lead->lead_id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Test Lead');

        $this->logTestResult('passed', [
            'controller' => 'LeadController',
            'endpoint' => 'GET /api/leads/{id}',
        ]);
    }

    /** @test */
    public function it_can_update_lead()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'status' => 'new',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->putJson("/api/leads/{$lead->lead_id}", [
            'name' => 'Updated Name',
            'status' => 'qualified',
            'score' => 85,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Name');
        $response->assertJsonPath('data.status', 'qualified');

        $this->logTestResult('passed', [
            'controller' => 'LeadController',
            'endpoint' => 'PUT /api/leads/{id}',
        ]);
    }

    /** @test */
    public function it_can_delete_lead()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'To Delete',
            'email' => 'delete@example.com',
            'status' => 'new',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/leads/{$lead->lead_id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('cmis.leads', [
            'lead_id' => $lead->lead_id,
        ]);

        $this->logTestResult('passed', [
            'controller' => 'LeadController',
            'endpoint' => 'DELETE /api/leads/{id}',
        ]);
    }

    /** @test */
    public function it_can_filter_leads_by_status()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'New Lead',
            'email' => 'new@example.com',
            'status' => 'new',
        ]);

        Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Qualified Lead',
            'email' => 'qualified@example.com',
            'status' => 'qualified',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/leads?status=qualified');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');

        $this->logTestResult('passed', [
            'controller' => 'LeadController',
            'test' => 'status_filtering',
        ]);
    }

    /** @test */
    public function it_can_convert_lead()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Lead to Convert',
            'email' => 'convert@example.com',
            'status' => 'qualified',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/leads/{$lead->lead_id}/convert");

        $response->assertStatus(200);

        $lead->refresh();
        $this->assertEquals('converted', $lead->status);

        $this->logTestResult('passed', [
            'controller' => 'LeadController',
            'endpoint' => 'POST /api/leads/{id}/convert',
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/leads', [
            'email' => 'test@example.com',
            // Missing name
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');

        $this->logTestResult('passed', [
            'controller' => 'LeadController',
            'test' => 'validation',
        ]);
    }

    /** @test */
    public function it_respects_org_isolation()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $lead1 = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $setup1['org']->org_id,
            'name' => 'Org 1 Lead',
            'email' => 'org1@example.com',
            'status' => 'new',
        ]);

        $this->actingAs($setup2['user'], 'sanctum');

        $response = $this->getJson("/api/leads/{$lead1->lead_id}");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'LeadController',
            'test' => 'org_isolation',
        ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/leads');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'LeadController',
            'test' => 'authentication_required',
        ]);
    }
}
