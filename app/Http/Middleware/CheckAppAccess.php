<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if the current organization's subscription plan
 * has access to a specific marketplace app.
 *
 * Usage in routes:
 *   Route::get('/advertising', ...)->middleware('app.access:advertising');
 *   Route::get('/ai-insights', ...)->middleware('app.access:ai-insights');
 */
class CheckAppAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $appCode  The slug/code of the app to check access for
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $appCode): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->denyAccess($request, $appCode, 'authentication_required');
        }

        // Get the user's current organization
        $org = $user->currentOrganization ?? $user->orgs()->first();

        if (!$org) {
            return $this->denyAccess($request, $appCode, 'no_organization');
        }

        // Get the organization's active subscription
        $subscription = $org->subscription;

        if (!$subscription || !$subscription->isActive()) {
            return $this->denyAccess($request, $appCode, 'no_active_subscription');
        }

        // Get the subscription plan
        $plan = $subscription->plan;

        if (!$plan) {
            return $this->denyAccess($request, $appCode, 'no_plan');
        }

        // Check if the plan has access to this app
        if (!$plan->hasApp($appCode)) {
            return $this->denyAccess($request, $appCode, 'app_not_available');
        }

        return $next($request);
    }

    /**
     * Handle access denial.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $appCode
     * @param  string  $reason
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function denyAccess(Request $request, string $appCode, string $reason): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => 'upgrade_required',
                'message' => __('apps.access_denied.' . $reason),
                'app' => $appCode,
                'reason' => $reason,
                'upgrade_url' => route('settings.billing'),
            ], 403);
        }

        // For web requests, redirect to upgrade page
        return redirect()
            ->route('upgrade.required', ['app' => $appCode])
            ->with('error', __('apps.access_denied.' . $reason))
            ->with('requested_app', $appCode);
    }
}
