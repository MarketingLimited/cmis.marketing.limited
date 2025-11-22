<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to set organization context for Row-Level Security (RLS)
 *
 * @deprecated This middleware is deprecated. Use SetOrganizationContext ('org.context') instead.
 *             Kept for backward compatibility only.
 *
 * This middleware automatically sets the current org_id in PostgreSQL session variables,
 * which are used by RLS policies to enforce organization data isolation.
 *
 * Usage:
 * - Apply to routes that need automatic org isolation
 * - RLS policies will automatically filter queries based on set org_id
 */
class SetOrgContextMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        \Illuminate\Support\Facades\Log::warning(
            '⚠️  DEPRECATED MIDDLEWARE IN USE: SetOrgContextMiddleware is deprecated. ' .
            'Please update your routes to use org.context middleware instead. ' .
            'Using multiple context middleware can cause race conditions and data leakage.',
            [
                'route' => $request->path(),
                'middleware' => 'SetOrgContextMiddleware',
                'replacement' => 'SetOrganizationContext (alias: org.context)'
            ]
        );

        if (Auth::check() && Auth::user()->org_id) {
            // Set the current org_id in PostgreSQL session variable
            DB::statement(
                "SELECT cmis.set_org_context(?)",
                [Auth::user()->org_id]
            );

            // Store in request for easy access
            $request->attributes->set('current_org_id', Auth::user()->org_id);
        }

        $response = $next($request);

        // Optional: Clear context after request (if needed)
        // DB::statement("SELECT cmis.clear_org_context()");

        return $response;
    }

    /**
     * Handle terminating the middleware.
     */
    public function terminate(Request $request, Response $response): void
    {
        // Clear org context after response is sent
        try {
            DB::statement("SELECT cmis.clear_org_context()");
        } catch (\Exception $e) {
            // Silently fail - connection might be closed
        }
    }
}
