<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Core\Integration;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller for managing platform integrations (OAuth, Connect, Disconnect)
 * Supports all platforms: Meta, Google, TikTok, Twitter, LinkedIn, Snapchat, etc.
 */
class PlatformIntegrationController extends Controller
{
    use ApiResponse;

    /**
     * Get OAuth authorization URL for a platform
     *
     * @param string $platform
     * @param Request $request
     * @return JsonResponse
     */
    public function getAuthUrl(string $platform, Request $request): JsonResponse
    {
        try {
            $connector = ConnectorFactory::make($platform);

            $state = bin2hex(random_bytes(16));
            $request->session()->put("oauth_state_{$platform}", $state);

            $authUrl = $connector->getAuthUrl([
                'state' => $state,
                'org_id' => $request->user()->org_id ?? $request->input('org_id'),
            ]);

            return response()->json([
                'success' => true,
                'auth_url' => $authUrl,
                'platform' => $platform,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get auth URL for {$platform}: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle OAuth callback from platform
     *
     * @param string $platform
     * @param Request $request
     * @return JsonResponse
     */
    public function handleCallback(string $platform, Request $request): JsonResponse
    {
        try {
            $code = $request->input('code');
            $state = $request->input('state');

            // Verify state to prevent CSRF
            $sessionState = $request->session()->get("oauth_state_{$platform}");
            if ($state !== $sessionState) {
                return $this->error(
                    'Invalid OAuth state. Possible CSRF attack detected.',
                    403,
                    null,
                    'INVALID_OAUTH_STATE'
                );
            }

            $connector = ConnectorFactory::make($platform);

            $integration = $connector->connect($code, [
                'org_id' => $request->user()->org_id ?? $request->input('org_id'),
                'created_by' => $request->user()->user_id ?? null,
            ]);

            // Clear state from session
            $request->session()->forget("oauth_state_{$platform}");

            // Issue #72: Test connection immediately after OAuth (Issue #72)
            try {
                $connector->testConnection($integration);
            } catch (\Exception $testException) {
                Log::warning("OAuth succeeded but connection test failed for {$platform}: {$testException->getMessage()}");
                // Don't fail - OAuth succeeded, connection might be slow/delayed
            }

            return $this->success([
                'integration' => $integration,
            ], "Successfully connected to {$platform}");
        } catch (\Exception $e) {
            Log::error("OAuth callback failed for {$platform}: {$e->getMessage()}");
            return $this->serverError("OAuth authentication failed. Please try again.");
        }
    }

    /**
     * Connect a platform (for platforms that don't use OAuth)
     * Used for: WooCommerce (API keys), WordPress (App passwords)
     *
     * @param string $platform
     * @param Request $request
     * @return JsonResponse
     */
    public function connect(string $platform, Request $request): JsonResponse
    {
        try {
            $connector = ConnectorFactory::make($platform);

            // Platform-specific validation
            $credentials = $this->validatePlatformCredentials($platform, $request);

            // Issue #72: Test credentials before saving
            try {
                $testResult = $connector->testCredentials($credentials);
                if (!$testResult['valid']) {
                    return $this->error(
                        'Invalid credentials. ' . ($testResult['message'] ?? 'Unable to connect to platform.'),
                        400,
                        ['credentials' => $testResult['errors'] ?? []],
                        'INVALID_CREDENTIALS'
                    );
                }
            } catch (\Exception $testException) {
                Log::warning("Credential validation failed for {$platform}: {$testException->getMessage()}");
                return $this->error(
                    "Unable to verify credentials. Please check your credentials and try again. Error: {$testException->getMessage()}",
                    400,
                    null,
                    'CREDENTIAL_VALIDATION_FAILED'
                );
            }

            // Credentials are valid, proceed with connection
            $integration = $connector->connect('', array_merge($credentials, [
                'org_id' => $request->user()->org_id ?? $request->input('org_id'),
                'created_by' => $request->user()->user_id ?? null,
            ]));

            return $this->success([
                'integration' => $integration,
                'credentials_verified' => true,
            ], "Successfully connected to {$platform}. Credentials verified.");
        } catch (\Exception $e) {
            Log::error("Failed to connect {$platform}: {$e->getMessage()}");
            return $this->serverError("Failed to connect to {$platform}. Please try again.");
        }
    }

    /**
     * Disconnect a platform integration
     *
     * @param string $integrationId
     * @param Request $request
     * @return JsonResponse
     */
    public function disconnect(string $integrationId, Request $request): JsonResponse
    {
        try {
            $integration = Integration::where('integration_id', $integrationId)
                ->where('org_id', $request->user()->org_id)
                ->firstOrFail();

            $connector = ConnectorFactory::make($integration->platform);
            $connector->disconnect($integration);

            return response()->json([
                'success' => true,
                'message' => "Successfully disconnected from {$integration->platform}",
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to disconnect integration {$integrationId}: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all connected platforms for the organization
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getConnectedPlatforms(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id ?? $request->input('org_id');

            $integrations = Integration::where('org_id', $orgId)
                ->where('is_active', true)
                ->get()
                ->map(function ($integration) {
                    return [
                        'integration_id' => $integration->integration_id,
                        'platform' => $integration->platform,
                        'external_account_id' => $integration->external_account_id,
                        'external_account_name' => $integration->external_account_name,
                        'is_active' => $integration->is_active,
                        'token_expires_at' => $integration->token_expires_at,
                        'last_sync_at' => $integration->last_sync_at,
                        'settings' => $integration->settings,
                        'created_at' => $integration->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'integrations' => $integrations,
                'total' => $integrations->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get connected platforms: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get integration details
     *
     * @param string $integrationId
     * @param Request $request
     * @return JsonResponse
     */
    public function getIntegration(string $integrationId, Request $request): JsonResponse
    {
        try {
            $integration = Integration::where('integration_id', $integrationId)
                ->where('org_id', $request->user()->org_id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'integration' => $integration,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Integration not found',
            ], 404);
        }
    }

    /**
     * Refresh access token for an integration
     *
     * @param string $integrationId
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshToken(string $integrationId, Request $request): JsonResponse
    {
        try {
            $integration = Integration::where('integration_id', $integrationId)
                ->where('org_id', $request->user()->org_id)
                ->firstOrFail();

            $connector = ConnectorFactory::make($integration->platform);
            $updatedIntegration = $connector->refreshToken($integration);

            return response()->json([
                'success' => true,
                'integration' => $updatedIntegration,
                'message' => 'Token refreshed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to refresh token for {$integrationId}: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test integration connection
     *
     * @param string $integrationId
     * @param Request $request
     * @return JsonResponse
     */
    public function testConnection(string $integrationId, Request $request): JsonResponse
    {
        try {
            $integration = Integration::where('integration_id', $integrationId)
                ->where('org_id', $request->user()->org_id)
                ->firstOrFail();

            $connector = ConnectorFactory::make($integration->platform);

            // Try to get account metrics to test connection
            $metrics = $connector->getAccountMetrics($integration);

            // Update last_sync_at
            $integration->update(['last_sync_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Connection is working',
                'platform' => $integration->platform,
            ]);
        } catch (\Exception $e) {
            Log::error("Connection test failed for {$integrationId}: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Connection test failed',
            ], 500);
        }
    }

    /**
     * Get available platforms
     *
     * @return JsonResponse
     */
    public function getAvailablePlatforms(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'platforms' => [
                [
                    'id' => 'meta',
                    'name' => 'Meta (Facebook & Instagram)',
                    'supports' => ['posts', 'comments', 'messages', 'ads', 'oauth'],
                ],
                [
                    'id' => 'google',
                    'name' => 'Google (Ads, Analytics, Drive)',
                    'supports' => ['ads', 'analytics', 'storage', 'oauth'],
                ],
                [
                    'id' => 'tiktok',
                    'name' => 'TikTok',
                    'supports' => ['posts', 'comments', 'ads', 'oauth'],
                ],
                [
                    'id' => 'twitter',
                    'name' => 'Twitter/X',
                    'supports' => ['posts', 'messages', 'comments', 'ads', 'oauth'],
                ],
                [
                    'id' => 'linkedin',
                    'name' => 'LinkedIn',
                    'supports' => ['posts', 'messages', 'comments', 'ads', 'oauth'],
                ],
                [
                    'id' => 'snapchat',
                    'name' => 'Snapchat',
                    'supports' => ['ads', 'oauth'],
                ],
                [
                    'id' => 'youtube',
                    'name' => 'YouTube',
                    'supports' => ['posts', 'comments', 'oauth'],
                ],
                [
                    'id' => 'woocommerce',
                    'name' => 'WooCommerce',
                    'supports' => ['products', 'orders', 'api_key'],
                ],
                [
                    'id' => 'wordpress',
                    'name' => 'WordPress',
                    'supports' => ['posts', 'comments', 'api_key'],
                ],
                [
                    'id' => 'whatsapp',
                    'name' => 'WhatsApp Business',
                    'supports' => ['messages', 'api_key'],
                ],
            ],
        ]);
    }

    /**
     * Validate platform-specific credentials
     *
     * @param string $platform
     * @param Request $request
     * @return array
     */
    protected function validatePlatformCredentials(string $platform, Request $request): array
    {
        switch ($platform) {
            case 'woocommerce':
                $request->validate([
                    'store_url' => 'required|url',
                    'consumer_key' => 'required|string',
                    'consumer_secret' => 'required|string',
                ]);
                return [
                    'store_url' => $request->input('store_url'),
                    'consumer_key' => $request->input('consumer_key'),
                    'consumer_secret' => $request->input('consumer_secret'),
                ];

            case 'wordpress':
                $request->validate([
                    'site_url' => 'required|url',
                    'username' => 'required|string',
                    'application_password' => 'required|string',
                ]);
                return [
                    'site_url' => $request->input('site_url'),
                    'username' => $request->input('username'),
                    'application_password' => $request->input('application_password'),
                ];

            case 'whatsapp':
                $request->validate([
                    'phone_number_id' => 'required|string',
                    'access_token' => 'required|string',
                ]);
                return [
                    'phone_number_id' => $request->input('phone_number_id'),
                    'access_token' => $request->input('access_token'),
                    'business_account_id' => $request->input('business_account_id'),
                ];

            default:
                return [];
        }
    }
}
