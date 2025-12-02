<?php

namespace App\Http\Middleware;

use App\Services\Marketplace\MarketplaceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check App Enabled Middleware
 *
 * Verifies that an app is enabled for the organization before allowing access.
 * If the app is disabled, redirects to the marketplace or returns a JSON error.
 *
 * Usage in routes:
 * Route::middleware('app.enabled:campaigns')->...
 */
class CheckAppEnabled
{
    protected MarketplaceService $marketplace;

    public function __construct(MarketplaceService $marketplace)
    {
        $this->marketplace = $marketplace;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $appSlug  The app slug to check
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $appSlug): Response
    {
        $orgId = $request->route('org');

        // If no org in route, let other middleware handle it
        if (!$orgId) {
            return $next($request);
        }

        // Check if app is enabled
        if (!$this->marketplace->isAppEnabled($orgId, $appSlug)) {
            $message = __('marketplace.app_not_enabled');

            // JSON response for API/AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'redirect' => route('orgs.marketplace.index', ['org' => $orgId]),
                    'app_required' => $appSlug,
                ], 403);
            }

            // Redirect for web requests
            return redirect()
                ->route('orgs.marketplace.index', ['org' => $orgId])
                ->with('error', $message)
                ->with('app_required', $appSlug);
        }

        return $next($request);
    }
}
