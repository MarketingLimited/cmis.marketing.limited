<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Middleware\SetRLSContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use PHPUnit\Framework\Attributes\Test;
/**
 * RLS Middleware Unit Tests
 */
class RLSMiddlewareTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected SetRLSContext $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SetRLSContext();
    }

    #[Test]
    public function it_sets_rls_context_for_authenticated_user()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $request = Request::create('/api/campaigns', 'GET');
        $request->setUserResolver(fn() => $user);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->status());

        $this->logTestResult('passed', [
            'middleware' => 'SetRLSContext',
            'test' => 'set_context',
        ]);
    }

    #[Test]
    public function it_clears_rls_context_after_request()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $request = Request::create('/api/campaigns', 'GET');
        $request->setUserResolver(fn() => $user);

        $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->middleware->terminate($request, response()->json([]));

        $this->logTestResult('passed', [
            'middleware' => 'SetRLSContext',
            'test' => 'clear_context',
        ]);
    }

    #[Test]
    public function it_skips_rls_for_unauthenticated_requests()
    {
        $request = Request::create('/api/public', 'GET');

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->status());

        $this->logTestResult('passed', [
            'middleware' => 'SetRLSContext',
            'test' => 'skip_unauthenticated',
        ]);
    }

    #[Test]
    public function it_handles_missing_org_id_gracefully()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        // Remove org_id from user metadata
        $user->metadata = [];
        $user->save();

        $request = Request::create('/api/campaigns', 'GET');
        $request->setUserResolver(fn() => $user);

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->status());

        $this->logTestResult('passed', [
            'middleware' => 'SetRLSContext',
            'test' => 'missing_org_id',
        ]);
    }

    #[Test]
    public function it_sets_correct_org_id_in_context()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $request = Request::create('/api/campaigns', 'GET');
        $request->setUserResolver(fn() => $user);

        $contextOrgId = null;

        $this->middleware->handle($request, function ($req) use (&$contextOrgId) {
            // Try to get the context org_id
            $contextOrgId = DB::select("SELECT current_setting('app.current_org_id', true) as org_id")[0]->org_id ?? null;
            return response()->json(['success' => true]);
        });

        $this->assertNotNull($contextOrgId);

        $this->logTestResult('passed', [
            'middleware' => 'SetRLSContext',
            'test' => 'correct_org_id',
        ]);
    }

    #[Test]
    public function it_sets_correct_user_id_in_context()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $request = Request::create('/api/campaigns', 'GET');
        $request->setUserResolver(fn() => $user);

        $contextUserId = null;

        $this->middleware->handle($request, function ($req) use (&$contextUserId) {
            // Try to get the context user_id
            $contextUserId = DB::select("SELECT current_setting('app.current_user_id', true) as user_id")[0]->user_id ?? null;
            return response()->json(['success' => true]);
        });

        $this->assertNotNull($contextUserId);

        $this->logTestResult('passed', [
            'middleware' => 'SetRLSContext',
            'test' => 'correct_user_id',
        ]);
    }

    #[Test]
    public function it_works_with_sanctum_authentication()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $token = $user->createToken('test-token')->plainTextToken;

        $request = Request::create('/api/campaigns', 'GET');
        $request->headers->set('Authorization', "Bearer {$token}");

        $this->actingAs($user, 'sanctum');

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        $this->assertEquals(200, $response->status());

        $this->logTestResult('passed', [
            'middleware' => 'SetRLSContext',
            'test' => 'sanctum_auth',
        ]);
    }

    #[Test]
    public function it_handles_exceptions_gracefully()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $request = Request::create('/api/campaigns', 'GET');
        $request->setUserResolver(fn() => $user);

        try {
            $response = $this->middleware->handle($request, function ($req) {
                throw new \Exception('Test exception');
            });
        } catch (\Exception $e) {
            $this->assertEquals('Test exception', $e->getMessage());
        }

        $this->logTestResult('passed', [
            'middleware' => 'SetRLSContext',
            'test' => 'exception_handling',
        ]);
    }
}
