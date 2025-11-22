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
                'success' => false,
                'message' => 'You must be authenticated to access this resource',
                'code' => 'UNAUTHENTICATED'
            ], 401);
        }

        $orgId = $request->route('org_id');

        if (!$orgId) {
            return response()->json([
                'success' => false,
                'message' => 'Organization ID is required',
                'code' => 'ORG_ID_REQUIRED'
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
                // Check if org exists at all to give better error message
                $orgExists = DB::table('cmis.orgs')
                    ->where('org_id', $orgId)
                    ->whereNull('deleted_at')
                    ->exists();

                Log::warning('Unauthorized organization access attempt', [
                    'user_id' => $userId,
                    'org_id' => $orgId,
                    'org_exists' => $orgExists,
                    'ip' => $request->ip()
                ]);

                if (!$orgExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The requested organization does not exist or has been deleted',
                        'code' => 'ORG_NOT_FOUND',
                        'errors' => [
                            'org_id' => [$orgId]
                        ]
                    ], 404);
                }

                // Get org admin contact info for helpful error
                $orgAdmin = DB::table('cmis.user_orgs')
                    ->join('cmis.users', 'cmis.users.user_id', '=', 'cmis.user_orgs.user_id')
                    ->where('cmis.user_orgs.org_id', $orgId)
                    ->where('cmis.user_orgs.role', 'admin')
                    ->where('cmis.user_orgs.status', 'active')
                    ->select('cmis.users.email', 'cmis.users.name')
                    ->first();

                $helpfulMessage = 'You do not have access to this organization. ';
                if ($orgAdmin) {
                    $helpfulMessage .= "Contact your organization administrator ({$orgAdmin->name}) to request access.";
                } else {
                    $helpfulMessage .= 'Contact your organization administrator to request access.';
                }

                return response()->json([
                    'success' => false,
                    'message' => $helpfulMessage,
                    'code' => 'ORG_ACCESS_DENIED',
                    'errors' => [
                        'required_permission' => ['org:access'],
                        'contact' => $orgAdmin ? [$orgAdmin->email] : []
                    ]
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

            // Never expose stack traces in production (Issue #30)
            $errorMessage = config('app.debug')
                ? $e->getMessage()
                : 'An unexpected error occurred while validating access. Please try again.';

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }
}
