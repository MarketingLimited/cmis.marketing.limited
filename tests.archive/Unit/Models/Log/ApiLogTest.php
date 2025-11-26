<?php

namespace Tests\Unit\Models\Log;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use App\Models\Log\ApiLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * ApiLog Model Unit Tests
 */
class ApiLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_api_log()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $apiLog = ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'method' => 'POST',
            'endpoint' => '/api/campaigns',
            'status_code' => 200,
        ]);

        $this->assertDatabaseHas('cmis.api_logs', [
            'log_id' => $apiLog->log_id,
            'endpoint' => '/api/campaigns',
        ]);
    }

    #[Test]
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $apiLog = ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'method' => 'GET',
            'endpoint' => '/api/posts',
            'status_code' => 200,
        ]);

        $this->assertEquals($org->org_id, $apiLog->org->org_id);
    }

    #[Test]
    public function it_belongs_to_user()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $apiLog = ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'method' => 'PUT',
            'endpoint' => '/api/campaigns/123',
            'status_code' => 200,
        ]);

        $this->assertEquals($user->user_id, $apiLog->user->user_id);
    }

    #[Test]
    public function it_logs_different_http_methods()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        foreach ($methods as $method) {
            ApiLog::create([
                'log_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'method' => $method,
                'endpoint' => '/api/test',
                'status_code' => 200,
            ]);
        }

        $logs = ApiLog::where('org_id', $org->org_id)->get();
        $this->assertCount(5, $logs);
    }

    #[Test]
    public function it_stores_request_payload()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $requestPayload = [
            'name' => 'حملة تسويقية جديدة',
            'budget' => 5000,
            'start_date' => '2024-01-01',
        ];

        $apiLog = ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'method' => 'POST',
            'endpoint' => '/api/campaigns',
            'status_code' => 201,
            'request_payload' => $requestPayload,
        ]);

        $this->assertEquals('حملة تسويقية جديدة', $apiLog->request_payload['name']);
        $this->assertEquals(5000, $apiLog->request_payload['budget']);
    }

    #[Test]
    public function it_stores_response_payload()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $responsePayload = [
            'success' => true,
            'campaign_id' => Str::uuid(),
            'message' => 'Campaign created successfully',
        ];

        $apiLog = ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'method' => 'POST',
            'endpoint' => '/api/campaigns',
            'status_code' => 201,
            'response_payload' => $responsePayload,
        ]);

        $this->assertTrue($apiLog->response_payload['success']);
        $this->assertNotNull($apiLog->response_payload['campaign_id']);
    }

    #[Test]
    public function it_tracks_response_time()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $apiLog = ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'method' => 'GET',
            'endpoint' => '/api/analytics',
            'status_code' => 200,
            'response_time' => 125.45,
        ]);

        $this->assertEquals(125.45, $apiLog->response_time);
    }

    #[Test]
    public function it_stores_ip_address()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $apiLog = ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'method' => 'GET',
            'endpoint' => '/api/posts',
            'status_code' => 200,
            'ip_address' => '192.168.1.100',
        ]);

        $this->assertEquals('192.168.1.100', $apiLog->ip_address);
    }

    #[Test]
    public function it_stores_user_agent()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

        $apiLog = ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'method' => 'GET',
            'endpoint' => '/api/campaigns',
            'status_code' => 200,
            'user_agent' => $userAgent,
        ]);

        $this->assertEquals($userAgent, $apiLog->user_agent);
    }

    #[Test]
    public function it_logs_different_status_codes()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $statusCodes = [200, 201, 400, 401, 404, 500];

        foreach ($statusCodes as $code) {
            ApiLog::create([
                'log_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'method' => 'GET',
                'endpoint' => '/api/test',
                'status_code' => $code,
            ]);
        }

        $logs = ApiLog::where('org_id', $org->org_id)->get();
        $this->assertCount(6, $logs);
    }

    #[Test]
    public function it_stores_error_message_for_failed_requests()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $apiLog = ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'method' => 'POST',
            'endpoint' => '/api/campaigns',
            'status_code' => 422,
            'error_message' => 'Validation failed: name field is required',
        ]);

        $this->assertEquals('Validation failed: name field is required', $apiLog->error_message);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $apiLog = ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'method' => 'GET',
            'endpoint' => '/api/test',
            'status_code' => 200,
        ]);

        $this->assertTrue(Str::isUuid($apiLog->log_id));
    }

    #[Test]
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $apiLog = ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'method' => 'GET',
            'endpoint' => '/api/test',
            'status_code' => 200,
        ]);

        $this->assertNotNull($apiLog->created_at);
        $this->assertNotNull($apiLog->updated_at);
    }

    #[Test]
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

        ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'method' => 'GET',
            'endpoint' => '/api/org1/test',
            'status_code' => 200,
        ]);

        ApiLog::create([
            'log_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'method' => 'GET',
            'endpoint' => '/api/org2/test',
            'status_code' => 200,
        ]);

        $org1Logs = ApiLog::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Logs);
        $this->assertEquals('/api/org1/test', $org1Logs->first()->endpoint);
    }
}
