<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Consolidated Multi-Tenancy Middleware
 *
 * This middleware sets the organization context for Row-Level Security (RLS).
 * It replaces three previous middleware implementations:
 * - SetRLSContext
 * - SetDatabaseContext
 * - SetOrgContextMiddleware
 *
 * Usage:
 *   Route::middleware(['auth:sanctum', 'org.context'])->group(function () {
 *       // Your routes here
 *   });
 *
 * How it works:
 * 1. Extracts org_id from authenticated user's current_org_id or org_id property
 * 2. Calls cmis.init_transaction_context(user_id, org_id) to set PostgreSQL session variables
 * 3. RLS policies automatically filter all queries based on the set org_id
 * 4. Cleans up context after request completes
 */
class SetOrganizationContext
{
    /**
     * Handle an incoming request and set organization context for RLS.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // RACE CONDITION DETECTION: Check if context was already set by another middleware
        try {
            $existingContext = DB::selectOne(
                "SELECT current_setting('app.current_org_id', true) as org_id"
            );

            if ($existingContext && $existingContext->org_id) {
                Log::warning('SetOrganizationContext: Context already set by another middleware - RACE CONDITION DETECTED', [
                    'existing_org_id' => $existingContext->org_id,
                    'route' => $request->path(),
                    'middleware_stack' => get_class($this)
                ]);

                // Return error to prevent data leakage
                return response()->json([
                    'error' => 'Multiple context middleware detected',
                    'message' => 'A race condition was detected. Please use only org.context middleware.'
                ], 500);
            }
        } catch (\Exception $e) {
            // Context not set yet - this is expected
        }

        // Skip context setup if no authenticated user
        if (!$user) {
            Log::debug('SetOrganizationContext: No authenticated user, skipping context setup');
            return $next($request);
        }

        // Determine org_id from user
        // Priority: current_org_id (for multi-org users) > org_id (default org)
        $orgId = $user->current_org_id ?? $user->org_id ?? null;

        if (!$orgId) {
            Log::error('SetOrganizationContext: User has no organization', [
                'user_id' => $user->id,
                'user_email' => $user->email ?? 'unknown'
            ]);

            return response()->json([
                'error' => 'No organization assigned',
                'message' => 'Your user account is not associated with any organization.'
            ], 403);
        }

        // Validate org_id format (must be valid UUID)
        if (!$this->isValidUuid($orgId)) {
            Log::error('SetOrganizationContext: Invalid organization ID format', [
                'user_id' => $user->id,
                'org_id' => $orgId
            ]);

            return response()->json([
                'error' => 'Invalid organization',
                'message' => 'The organization ID format is invalid.'
            ], 400);
        }

        try {
            // Initialize database context with user_id and org_id
            // This sets PostgreSQL session variables that RLS policies use
            DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
                $user->id,
                $orgId
            ]);

            // Verify context was set correctly
            $currentOrg = DB::selectOne(
                "SELECT current_setting('app.current_org_id', true) as org_id"
            );

            if (!$currentOrg || $currentOrg->org_id !== $orgId) {
                Log::error('SetOrganizationContext: Context mismatch after initialization', [
                    'user_id' => $user->id,
                    'expected_org_id' => $orgId,
                    'actual_org_id' => $currentOrg->org_id ?? 'null'
                ]);

                return response()->json([
                    'error' => 'Context initialization failed',
                    'message' => 'Failed to set organization context correctly.'
                ], 500);
            }

            // Store org_id in request for easy access in controllers/services
            $request->merge(['_org_id' => $orgId]);
            $request->attributes->set('current_org_id', $orgId);

            Log::debug('SetOrganizationContext: Context set successfully', [
                'user_id' => $user->id,
                'org_id' => $orgId,
                'route' => $request->path()
            ]);

        } catch (\Exception $e) {
            Log::error('SetOrganizationContext: Failed to initialize context', [
                'user_id' => $user->id,
                'org_id' => $orgId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to initialize context',
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'An internal error occurred while setting organization context.'
            ], 500);
        }

        // Process the request
        $response = $next($request);

        // Clean up context after request (optional, terminate() also does this)
        try {
            DB::statement('SELECT cmis.clear_transaction_context()');
            Log::debug('SetOrganizationContext: Context cleared after request');
        } catch (\Exception $e) {
            Log::warning('SetOrganizationContext: Failed to clear context in handle()', [
                'error' => $e->getMessage()
            ]);
        }

        return $response;
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     *
     * This ensures context is cleaned up even if handle() cleanup didn't execute
     * (e.g., due to early return or exception).
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            DB::statement('SELECT cmis.clear_transaction_context()');
            Log::debug('SetOrganizationContext: Context cleared in terminate()');
        } catch (\Exception $e) {
            // Silently fail - database connection might already be closed
            Log::debug('SetOrganizationContext: Could not clear context in terminate()', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate UUID format
     *
     * @param string $uuid
     * @return bool
     */
    private function isValidUuid(string $uuid): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $uuid
        ) === 1;
    }
}
