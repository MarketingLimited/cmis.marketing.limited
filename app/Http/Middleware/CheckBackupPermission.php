<?php

namespace App\Http\Middleware;

use App\Apps\Backup\Services\Limits\PlanLimitsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Backup Permission Middleware
 *
 * Verifies user has required backup permissions and plan limits.
 */
class CheckBackupPermission
{
    protected PlanLimitsService $planLimits;

    public function __construct(PlanLimitsService $planLimits)
    {
        $this->planLimits = $planLimits;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        $user = auth()->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.unauthenticated'),
                ], 401);
            }
            return redirect()->route('login');
        }

        // Check permission if specified
        if ($permission && !$user->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('backup.permission_denied'),
                ], 403);
            }
            abort(403, __('backup.permission_denied'));
        }

        // Get org_id from route
        $orgId = $request->route('org');

        if (!$orgId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('backup.organization_required'),
                ], 400);
            }
            abort(400, __('backup.organization_required'));
        }

        // Check if user belongs to organization
        if (!$user->belongsToOrganization($orgId)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('backup.not_member_of_organization'),
                ], 403);
            }
            abort(403, __('backup.not_member_of_organization'));
        }

        // For backup creation, check plan limits
        if ($permission === 'backup.create' && $request->isMethod('POST')) {
            $limitCheck = $this->planLimits->checkBackupAllowed($orgId);

            if ($limitCheck->isDenied()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $limitCheck->getMessage(),
                        'data' => $limitCheck->getData(),
                    ], 403);
                }

                return redirect()
                    ->back()
                    ->withErrors(['limit' => $limitCheck->getMessage()]);
            }
        }

        return $next($request);
    }
}
