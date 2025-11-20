<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Services\OAuth\OAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * OAuth Controller
 *
 * Handles OAuth authentication flows for all supported platforms
 */
class OAuthController extends Controller
{
    protected OAuthService $oauthService;

    public function __construct(OAuthService $oauthService)
    {
        $this->oauthService = $oauthService;
    }

    /**
     * Redirect to platform authorization URL
     *
     * @param string $platform Platform name (meta, google, tiktok, etc.)
     * @return RedirectResponse
     */
    public function redirect(string $platform): RedirectResponse
    {
        try {
            // Generate CSRF state token
            $state = Str::random(40);
            session(['oauth_state' => $state, 'oauth_platform' => $platform]);

            // Get authorization URL
            $authUrl = $this->oauthService->getAuthorizationUrl($platform, $state);

            Log::info('OAuth redirect initiated', [
                'platform' => $platform,
                'state' => $state,
            ]);

            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('OAuth redirect failed', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('integrations.index')
                ->with('error', 'Failed to initiate OAuth: ' . $e->getMessage());
        }
    }

    /**
     * Handle OAuth callback
     *
     * @param string $platform Platform name
     * @param Request $request Callback request
     * @return RedirectResponse
     */
    public function callback(string $platform, Request $request): RedirectResponse
    {
        try {
            // Validate state for CSRF protection
            $state = $request->get('state');
            $expectedState = session('oauth_state');
            $sessionPlatform = session('oauth_platform');

            if (!$state || !$expectedState || !hash_equals($expectedState, $state)) {
                throw new \Exception('Invalid state parameter - possible CSRF attack');
            }

            if ($platform !== $sessionPlatform) {
                throw new \Exception('Platform mismatch');
            }

            // Check for error in callback
            if ($request->has('error')) {
                $error = $request->get('error');
                $errorDescription = $request->get('error_description', $error);
                throw new \Exception("OAuth error: $errorDescription");
            }

            // Get authorization code
            $code = $request->get('code');
            if (!$code) {
                throw new \Exception('Authorization code not provided');
            }

            // Exchange code for access token and create integration
            $integration = $this->oauthService->handleCallback(
                $platform,
                $code,
                auth()->user()
            );

            Log::info('OAuth callback successful', [
                'platform' => $platform,
                'integration_id' => $integration->integration_id,
                'user_id' => auth()->id(),
            ]);

            // Clear session data
            session()->forget(['oauth_state', 'oauth_platform']);

            return redirect()->route('integrations.show', $integration)
                ->with('success', ucfirst($platform) . ' integration connected successfully!');
        } catch (\Exception $e) {
            Log::error('OAuth callback failed', [
                'platform' => $platform,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            session()->forget(['oauth_state', 'oauth_platform']);

            return redirect()->route('integrations.index')
                ->with('error', 'OAuth failed: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect/revoke OAuth integration
     *
     * @param string $platform Platform name
     * @param Request $request Request
     * @return RedirectResponse
     */
    public function disconnect(string $platform, Request $request): RedirectResponse
    {
        try {
            $integrationId = $request->get('integration_id');

            if (!$integrationId) {
                throw new \Exception('Integration ID required');
            }

            $this->oauthService->revokeIntegration($integrationId, auth()->user());

            Log::info('OAuth integration disconnected', [
                'platform' => $platform,
                'integration_id' => $integrationId,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('integrations.index')
                ->with('success', ucfirst($platform) . ' integration disconnected');
        } catch (\Exception $e) {
            Log::error('OAuth disconnect failed', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to disconnect: ' . $e->getMessage());
        }
    }
}
