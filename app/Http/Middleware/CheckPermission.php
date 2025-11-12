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
     * @param  bool  $requireAll  If multiple permissions, require all (default) or any
     */
    public function handle(Request $request, Closure $next, string $permission, bool $requireAll = true): Response
    {
        $user = $request->user();

        if (!$user) {
            Log::warning('Permission check without authenticated user', [
                'permission' => $permission,
                'route' => $request->route()?->getName()
            ]);

            return $this->unauthorized($request);
        }

        // Check if multiple permissions (separated by |)
        $permissions = explode('|', $permission);

        if (count($permissions) > 1) {
            $hasPermission = $requireAll
                ? $this->permissionService->hasAll($user, $permissions)
                : $this->permissionService->hasAny($user, $permissions);
        } else {
            $hasPermission = $this->permissionService->check($user, $permission);
        }

        if (!$hasPermission) {
            Log::warning('Permission denied', [
                'user_id' => $user->user_id,
                'permission' => $permission,
                'route' => $request->route()?->getName(),
                'org_id' => session('current_org_id')
            ]);

            return $this->unauthorized($request);
        }

        return $next($request);
    }

    /**
     * Handle unauthorized access
     */
    protected function unauthorized(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'You do not have permission to perform this action.',
                'error' => 'Unauthorized'
            ], 403);
        }

        abort(403, 'You do not have permission to perform this action.');
    }
}
