<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use Tests\TestHelpers\DatabaseHelpers;
use App\Http\Middleware\CheckAiQuotaMiddleware;
use App\Services\AI\AiQuotaService;
use App\Exceptions\QuotaExceededException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery;

/**
 * Check AI Quota Middleware Test
 *
 * Tests pre-request quota validation and response header injection.
 * Part of Phase 1B weakness remediation (2025-11-21)
 */
class CheckAiQuotaMiddlewareTest extends TestCase
{
    use RefreshDatabase, DatabaseHelpers;

    protected CheckAiQuotaMiddleware $middleware;
    protected AiQuotaService $quotaService;
    protected object $org;
    protected object $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quotaService = Mockery::mock(AiQuotaService::class);
        $this->middleware = new CheckAiQuotaMiddleware($this->quotaService);

        // Create test organization and user
        $this->org = $this->createTestOrg();
        $this->user = $this->createTestUser($this->org->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        $this->cleanupTestOrg($this->org->id);
        parent::tearDown();
    }

    /** @test */
    public function it_allows_request_when_quota_available()
    {
        $request = Request::create('/api/ai/generate', 'POST');
        $request->setUserResolver(fn() => (object)[
            'id' => $this->user->id,
            'org_id' => $this->org->id,
        ]);

        // Mock quota check to pass
        $this->quotaService
            ->shouldReceive('checkQuota')
            ->once()
            ->with($this->org->id, $this->user->id, 'gpt', 1)
            ->andReturn(true);

        $this->quotaService
            ->shouldReceive('getQuotaStatus')
            ->once()
            ->with($this->org->id, $this->user->id)
            ->andReturn([
                'gpt' => [
                    'daily_used' => 3,
                    'daily_limit' => 5,
                    'monthly_used' => 50,
                    'monthly_limit' => 100,
                ],
            ]);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, 'gpt', 1);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Success', $response->content());
    }

    /** @test */
    public function it_blocks_request_when_quota_exceeded()
    {
        $request = Request::create('/api/ai/generate', 'POST');
        $request->setUserResolver(fn() => (object)[
            'id' => $this->user->id,
            'org_id' => $this->org->id,
        ]);

        // Mock quota check to fail
        $this->quotaService
            ->shouldReceive('checkQuota')
            ->once()
            ->with($this->org->id, $this->user->id, 'gpt', 1)
            ->andThrow(new QuotaExceededException(
                'Daily AI quota exceeded',
                ['quota_type' => 'daily']
            ));

        $this->expectException(QuotaExceededException::class);

        $this->middleware->handle($request, function ($req) {
            return new Response('Should not reach here', 200);
        }, 'gpt', 1);
    }

    /** @test */
    public function it_adds_quota_headers_to_response()
    {
        $request = Request::create('/api/ai/generate', 'POST');
        $request->setUserResolver(fn() => (object)[
            'id' => $this->user->id,
            'org_id' => $this->org->id,
        ]);

        $this->quotaService
            ->shouldReceive('checkQuota')
            ->once()
            ->andReturn(true);

        $this->quotaService
            ->shouldReceive('getQuotaStatus')
            ->once()
            ->andReturn([
                'gpt' => [
                    'daily_used' => 2,
                    'daily_limit' => 5,
                    'daily_remaining' => 3,
                    'monthly_used' => 40,
                    'monthly_limit' => 100,
                ],
            ]);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, 'gpt', 1);

        $this->assertTrue($response->headers->has('X-AI-Quota-Daily-Used'));
        $this->assertTrue($response->headers->has('X-AI-Quota-Daily-Limit'));
        $this->assertTrue($response->headers->has('X-AI-Quota-Daily-Remaining'));

        $this->assertEquals('2', $response->headers->get('X-AI-Quota-Daily-Used'));
        $this->assertEquals('5', $response->headers->get('X-AI-Quota-Daily-Limit'));
        $this->assertEquals('3', $response->headers->get('X-AI-Quota-Daily-Remaining'));
    }

    /** @test */
    public function it_handles_custom_requested_amounts()
    {
        $request = Request::create('/api/ai/generate-batch', 'POST');
        $request->setUserResolver(fn() => (object)[
            'id' => $this->user->id,
            'org_id' => $this->org->id,
        ]);

        // Mock quota check with custom amount
        $this->quotaService
            ->shouldReceive('checkQuota')
            ->once()
            ->with($this->org->id, $this->user->id, 'gpt', 5)
            ->andReturn(true);

        $this->quotaService
            ->shouldReceive('getQuotaStatus')
            ->once()
            ->andReturn(['gpt' => []]);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, 'gpt', 5);

        $this->assertEquals(200, $response->status());
    }

    /** @test */
    public function it_works_with_different_ai_services()
    {
        $request = Request::create('/api/ai/embed', 'POST');
        $request->setUserResolver(fn() => (object)[
            'id' => $this->user->id,
            'org_id' => $this->org->id,
        ]);

        // Mock quota check for embeddings service
        $this->quotaService
            ->shouldReceive('checkQuota')
            ->once()
            ->with($this->org->id, $this->user->id, 'embeddings', 1)
            ->andReturn(true);

        $this->quotaService
            ->shouldReceive('getQuotaStatus')
            ->once()
            ->andReturn([
                'embeddings' => [
                    'daily_used' => 10,
                    'daily_limit' => 20,
                ],
            ]);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, 'embeddings', 1);

        $this->assertEquals(200, $response->status());
    }
}
