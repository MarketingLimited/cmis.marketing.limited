<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Core\Integration;
use Illuminate\Support\Facades\Log;

class RefreshExpiredTokens
{
    /**
     * Handle an incoming request.
     *
     * Automatically refresh expired or expiring platform integration tokens
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if request has integration_id parameter
        $integrationId = $request->route('integration') ?? $request->input('integration_id');

        if ($integrationId) {
            $integration = Integration::find($integrationId);

            if ($integration && $integration->needsTokenRefresh()) {
                Log::info("Auto-refreshing token for integration {$integration->integration_id}");

                $refreshed = $integration->refreshAccessToken();

                if (!$refreshed) {
                    // Token refresh failed - return error response
                    return response()->json([
                        'error' => 'Integration token expired and refresh failed',
                        'message' => 'Please re-authenticate your account',
                        'integration_id' => $integration->integration_id,
                        'provider' => $integration->provider,
                    ], 401);
                }
            }
        }

        // Also check all active integrations for the current org
        $orgId = $request->route('org') ?? $request->input('org_id');

        if ($orgId) {
            Integration::where('org_id', $orgId)
                ->where('is_active', true)
                ->get()
                ->each(function ($integration) {
                    if ($integration->needsTokenRefresh()) {
                        Log::info("Background token refresh for integration {$integration->integration_id}");
                        $integration->refreshAccessToken();
                    }
                });
        }

        return $next($request);
    }
}
