<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @deprecated This middleware is deprecated. Use SetOrganizationContext ('org.context') instead.
 *             Kept for backward compatibility only.
 */
class SetRLSContext
{
    public function handle(Request $request, Closure $next)
    {
        \Illuminate\Support\Facades\Log::warning(
            '⚠️  DEPRECATED MIDDLEWARE IN USE: SetRLSContext is deprecated. ' .
            'Please update your routes to use org.context middleware instead. ' .
            'Using multiple context middleware can cause race conditions and data leakage.',
            [
                'route' => $request->path(),
                'middleware' => 'SetRLSContext',
                'replacement' => 'SetOrganizationContext (alias: org.context)'
            ]
        );

        $user = $request->user();

        if ($user && $user->current_org_id) {
            DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
                $user->user_id,
                $user->current_org_id
            ]);
        }

        $response = $next($request);

        if ($user) {
            DB::statement('SELECT cmis.clear_transaction_context()');
        }

        return $response;
    }

    /**
     * Perform cleanup tasks after the response is sent.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     * @return void
     */
    public function terminate(Request $request, $response): void
    {
        // Ensure context is cleared even if handle() cleanup didn't execute
        try {
            DB::statement('SELECT cmis.clear_transaction_context()');
        } catch (\Exception $e) {
            // Silently fail - database might already be cleaned up
        }
    }
}
