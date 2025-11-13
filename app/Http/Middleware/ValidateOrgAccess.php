<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateOrgAccess
{
    /**
     * Handle an incoming request and validate organization access.
     *
     * This middleware verifies that the authenticated user is an active member
     * of the organization they're trying to access by checking the cmis.user_orgs table.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to access this resource'
            ], 401);
        }

        $orgId = $request->route('org_id');

        if (!$orgId) {
            return response()->json([
                'error' => 'Bad Request',
                'message' => 'Organization ID is required'
            ], 400);
        }

        $userId = $user->user_id ?? $user->id;

        try {
            $hasAccess = DB::table('cmis.user_orgs')
                ->where('user_id', $userId)
                ->where('org_id', $orgId)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->exists();

            if (!$hasAccess) {
                Log::warning('Unauthorized organization access attempt', [
                    'user_id' => $userId,
                    'org_id' => $orgId,
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'You do not have access to this organization'
                ], 403);
            }

            Log::debug('Organization access validated', [
                'user_id' => $userId,
                'org_id' => $orgId
            ]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Failed to validate organization access', [
                'user_id' => $userId,
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => config('app.debug') ? $e->getMessage() : 'Failed to validate access'
            ], 500);
        }
    }
}
