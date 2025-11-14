<?php

namespace Tests\Unit\Models\Operations;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use App\Models\Operations\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * AuditLog Model Unit Tests
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_create_audit_log()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $auditLog = AuditLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'action' => 'created',
            'entity_type' => 'Campaign',
            'entity_id' => Str::uuid(),
            'ip_address' => '192.168.1.1',
        ]);

        $this->assertDatabaseHas('cmis.audit_logs', [
            'log_id' => $auditLog->log_id,
            'action' => 'created',
        ]);

        $this->logTestResult('passed', [
            'model' => 'AuditLog',
            'test' => 'create',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $auditLog = AuditLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'action' => 'updated',
            'entity_type' => 'Content',
        ]);

        $this->assertEquals($org->org_id, $auditLog->org->org_id);

        $this->logTestResult('passed', [
            'model' => 'AuditLog',
            'test' => 'belongs_to_org',
        ]);
    }

    /** @test */
    public function it_belongs_to_user()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Audit User',
            'email' => 'audit@example.com',
            'password' => bcrypt('password'),
        ]);

        $auditLog = AuditLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'action' => 'deleted',
            'entity_type' => 'Lead',
        ]);

        $this->assertEquals($user->user_id, $auditLog->user->user_id);

        $this->logTestResult('passed', [
            'model' => 'AuditLog',
            'test' => 'belongs_to_user',
        ]);
    }

    /** @test */
    public function it_tracks_action_type()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $actions = ['created', 'updated', 'deleted', 'viewed', 'exported'];

        foreach ($actions as $action) {
            $auditLog = AuditLog::create([
                'log_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'action' => $action,
                'entity_type' => 'Campaign',
            ]);

            $this->assertEquals($action, $auditLog->action);
        }

        $this->logTestResult('passed', [
            'model' => 'AuditLog',
            'test' => 'action_types',
        ]);
    }

    /** @test */
    public function it_stores_entity_information()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $entityId = Str::uuid();

        $auditLog = AuditLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'action' => 'updated',
            'entity_type' => 'Campaign',
            'entity_id' => $entityId,
        ]);

        $this->assertEquals('Campaign', $auditLog->entity_type);
        $this->assertEquals($entityId, $auditLog->entity_id);

        $this->logTestResult('passed', [
            'model' => 'AuditLog',
            'test' => 'entity_info',
        ]);
    }

    /** @test */
    public function it_stores_old_and_new_values()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $oldValues = [
            'name' => 'Old Campaign Name',
            'status' => 'draft',
        ];

        $newValues = [
            'name' => 'New Campaign Name',
            'status' => 'active',
        ];

        $auditLog = AuditLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'action' => 'updated',
            'entity_type' => 'Campaign',
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);

        $this->assertEquals('Old Campaign Name', $auditLog->old_values['name']);
        $this->assertEquals('New Campaign Name', $auditLog->new_values['name']);

        $this->logTestResult('passed', [
            'model' => 'AuditLog',
            'test' => 'old_new_values',
        ]);
    }

    /** @test */
    public function it_tracks_ip_address()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $auditLog = AuditLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'action' => 'created',
            'entity_type' => 'Lead',
            'ip_address' => '203.0.113.42',
        ]);

        $this->assertEquals('203.0.113.42', $auditLog->ip_address);

        $this->logTestResult('passed', [
            'model' => 'AuditLog',
            'test' => 'ip_address',
        ]);
    }

    /** @test */
    public function it_tracks_user_agent()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';

        $auditLog = AuditLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'action' => 'viewed',
            'entity_type' => 'Report',
            'user_agent' => $userAgent,
        ]);

        $this->assertEquals($userAgent, $auditLog->user_agent);

        $this->logTestResult('passed', [
            'model' => 'AuditLog',
            'test' => 'user_agent',
        ]);
    }

    /** @test */
    public function it_stores_additional_metadata()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metadata = [
            'browser' => 'Chrome',
            'device' => 'Desktop',
            'location' => 'Riyadh, Saudi Arabia',
        ];

        $auditLog = AuditLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'action' => 'exported',
            'entity_type' => 'Analytics',
            'metadata' => $metadata,
        ]);

        $this->assertEquals('Chrome', $auditLog->metadata['browser']);
        $this->assertEquals('Riyadh, Saudi Arabia', $auditLog->metadata['location']);

        $this->logTestResult('passed', [
            'model' => 'AuditLog',
            'test' => 'metadata',
        ]);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $auditLog = AuditLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'action' => 'created',
            'entity_type' => 'Content',
        ]);

        $this->assertTrue(Str::isUuid($auditLog->log_id));

        $this->logTestResult('passed', [
            'model' => 'AuditLog',
            'test' => 'uuid_primary_key',
        ]);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $auditLog = AuditLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'action' => 'updated',
            'entity_type' => 'Campaign',
        ]);

        $this->assertNotNull($auditLog->created_at);

        $this->logTestResult('passed', [
            'model' => 'AuditLog',
            'test' => 'timestamps',
        ]);
    }

    /** @test */
    public function it_respects_rls_policies()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        AuditLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'action' => 'created',
            'entity_type' => 'Campaign',
        ]);

        AuditLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'action' => 'created',
            'entity_type' => 'Campaign',
        ]);

        $org1Logs = AuditLog::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Logs);

        $this->logTestResult('passed', [
            'model' => 'AuditLog',
            'test' => 'rls_isolation',
        ]);
    }

    /** @test */
    public function it_can_query_by_entity_type()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        AuditLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'action' => 'created',
            'entity_type' => 'Campaign',
        ]);

        AuditLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'action' => 'created',
            'entity_type' => 'Lead',
        ]);

        $campaignLogs = AuditLog::where('entity_type', 'Campaign')->get();
        $this->assertCount(1, $campaignLogs);

        $this->logTestResult('passed', [
            'model' => 'AuditLog',
            'test' => 'query_by_entity',
        ]);
    }
}
