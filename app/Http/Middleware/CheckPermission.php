<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Check authentication
        if (!auth()->check()) {
            Log::warning('Permission check failed: User not authenticated', [
                'permission' => $permission,
                'route' => $request->path()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'Authentication required'
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Please log in to continue');
        }

        // Check organization context
        if (!session()->has('current_org_id')) {
            Log::warning('Permission check failed: No org context', [
                'user_id' => auth()->id(),
                'permission' => $permission,
                'route' => $request->path()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Organization context not set',
                    'message' => 'Please select an organization'
                ], 400);
            }

            return redirect()->route('orgs.index')->with('error', 'Please select an organization');
        }

        // Check permission using transaction context
        try {
            $hasPermission = $this->permissionService->checkTx($permission);
        } catch (\Exception $e) {
            Log::error('Permission check error', [
                'user_id' => auth()->id(),
                'org_id' => session('current_org_id'),
                'permission' => $permission,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Fallback to direct check
            try {
                $hasPermission = $this->permissionService->check(auth()->user(), $permission);
            } catch (\Exception $fallbackError) {
                Log::error('Fallback permission check also failed', [
                    'error' => $fallbackError->getMessage()
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Permission check failed',
                        'message' => 'An error occurred while checking permissions'
                    ], 500);
                }

                abort(500, 'Permission check failed');
            }
        }

        // Check result
        if (!$hasPermission) {
            Log::warning('Permission denied', [
                'user_id' => auth()->id(),
                'org_id' => session('current_org_id'),
                'permission' => $permission,
                'route' => $request->path(),
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Insufficient permissions',
                    'message' => 'You do not have permission to perform this action',
                    'required_permission' => $permission
                ], 403);
            }

            abort(403, 'You do not have permission to perform this action');
        }

        // Permission granted, continue
        Log::debug('Permission granted', [
            'user_id' => auth()->id(),
            'org_id' => session('current_org_id'),
            'permission' => $permission,
            'route' => $request->path()
        ]);

        return $next($request);
    }
}
