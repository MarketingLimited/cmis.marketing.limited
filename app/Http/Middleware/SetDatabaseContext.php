<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetDatabaseContext
{
    /**
     * Handle an incoming request and set the database context for multi-tenancy.
     *
     * This middleware initializes the PostgreSQL session context using RLS (Row Level Security)
     * by calling the cmis.init_transaction_context() function with the current user_id and org_id.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $userId = $user->id;
        $orgId = $request->route('org_id');

        if ($orgId) {
            try {
                DB::statement(
                    "SELECT cmis.init_transaction_context(?, ?)",
                    [$userId, $orgId]
                );

                Log::debug('Database context set', [
                    'user_id' => $userId,
                    'org_id' => $orgId
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to set database context', [
                    'user_id' => $userId,
                    'org_id' => $orgId,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'error' => 'Failed to initialize database context',
                    'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
                ], 500);
            }
        }

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     *
     * Clean up the database context after the request is complete.
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            DB::statement("SELECT cmis.clear_transaction_context()");
        } catch (\Exception $e) {
            Log::warning('Failed to clear database context', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
