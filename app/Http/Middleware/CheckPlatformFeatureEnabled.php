<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\FeatureToggle\FeatureFlagService;
use App\Exceptions\FeatureDisabledException;

/**
 * Middleware to check if a platform-specific feature is enabled
 *
 * Usage in routes:
 * Route::post('campaigns/{platform}/paid', [Controller::class, 'method'])
 *      ->middleware(['auth', CheckPlatformFeatureEnabled::class . ':paid_campaigns']);
 */
class CheckPlatformFeatureEnabled
{
    public function __construct(
        protected FeatureFlagService $featureFlags
    ) {}

    /**
     * Handle an incoming request
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string $featureCategory (e.g., 'paid_campaigns', 'scheduling', 'analytics')
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $featureCategory)
    {
        // Extract platform from route parameter
        $platform = $request->route('platform');

        if (!$platform) {
            return $this->respondFeatureDisabled($request, 'Platform not specified');
        }

        // Build feature key
        $featureKey = "{$featureCategory}.{$platform}.enabled";

        // Check if feature is enabled
        if (!$this->featureFlags->isEnabled($featureKey)) {
            return $this->respondFeatureDisabled(
                $request,
                "Feature '{$featureCategory}' is not available for platform '{$platform}'",
                $featureCategory,
                $platform
            );
        }

        // Feature is enabled, proceed
        return $next($request);
    }

    /**
     * Respond with feature disabled error
     */
    protected function respondFeatureDisabled(
        Request $request,
        string $message,
        ?string $featureCategory = null,
        ?string $platform = null
    ) {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => $message,
                'feature' => $featureCategory,
                'platform' => $platform,
                'available_platforms' => $featureCategory
                    ? $this->featureFlags->getEnabledPlatforms($featureCategory)
                    : [],
            ], 403);
        }

        // For web requests, redirect with error
        return redirect()->back()->with('error', $message);
    }
}
