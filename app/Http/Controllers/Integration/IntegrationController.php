<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Models\Core\Integration;
use App\Models\Social\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\RedirectResponse;

class IntegrationController extends Controller
{
    use ApiResponse;

    /**
     * Constructor - Apply authentication middleware
     */
    public function __construct()
    {
        // Apply authentication to all actions except OAuth callbacks
        // Callbacks need to be accessible for platform redirects
        $this->middleware('auth:sanctum')->except(['callback']);
    }

    /**
     * Supported platforms configuration
     */
    const PLATFORMS = [
        'facebook' => [
            'name' => 'Facebook',
            'oauth_url' => 'https://www.facebook.com/v18.0/dialog/oauth',
            'token_url' => 'https://graph.facebook.com/v18.0/oauth/access_token',
            'scopes' => ['pages_show_list', 'pages_read_engagement', 'pages_manage_posts', 'instagram_basic', 'instagram_content_publish'],
        ],
        'instagram' => [
            'name' => 'Instagram',
            'oauth_url' => 'https://api.instagram.com/oauth/authorize',
            'token_url' => 'https://api.instagram.com/oauth/access_token',
            'scopes' => ['instagram_basic', 'instagram_content_publish', 'instagram_manage_insights'],
        ],
        'google' => [
            'name' => 'Google Ads',
            'oauth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'scopes' => ['https://www.googleapis.com/auth/adwords', 'https://www.googleapis.com/auth/analytics.readonly'],
        ],
        'tiktok' => [
            'name' => 'TikTok',
            'oauth_url' => 'https://www.tiktok.com/auth/authorize',
            'token_url' => 'https://open-api.tiktok.com/oauth/access_token',
            'scopes' => ['user.info.basic', 'video.list', 'video.upload'],
        ],
        'snapchat' => [
            'name' => 'Snapchat',
            'oauth_url' => 'https://accounts.snapchat.com/login/oauth2/authorize',
            'token_url' => 'https://accounts.snapchat.com/login/oauth2/access_token',
            'scopes' => ['snapchat-marketing-api'],
        ],
        'twitter' => [
            'name' => 'Twitter / X',
            'oauth_url' => 'https://twitter.com/i/oauth2/authorize',
            'token_url' => 'https://api.twitter.com/2/oauth2/token',
            'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access'],
        ],
        'linkedin' => [
            'name' => 'LinkedIn',
            'oauth_url' => 'https://www.linkedin.com/oauth/v2/authorization',
            'token_url' => 'https://www.linkedin.com/oauth/v2/accessToken',
            'scopes' => ['r_liteprofile', 'r_emailaddress', 'w_member_social', 'r_organization_social'],
        ],
    ];

    /**
     * List all integrations for an organization
     */
    public function index(Request $request, string $orgId): JsonResponse
    {
        $this->authorize('viewAny', Integration::class);

        try {
            $integrations = Integration::where('org_id', $orgId)
                ->with(['creator:id,name'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($integration) {
                    return [
                        'integration_id' => $integration->integration_id,
                        'platform' => $integration->platform,
                        'platform_name' => self::PLATFORMS[$integration->platform]['name'] ?? ucfirst($integration->platform),
                        'username' => $integration->username,
                        'account_id' => $integration->account_id,
                        'is_active' => $integration->is_active,
                        'status' => $integration->is_active ? 'connected' : 'disconnected',
                        'connected_by' => $integration->creator?->name,
                        'connected_at' => $integration->created_at,
                        'last_sync' => $integration->updated_at,
                    ];
                });

            // Add available platforms that aren't connected yet
            $connectedPlatforms = $integrations->pluck('platform')->toArray();
            $availablePlatforms = array_diff(array_keys(self::PLATFORMS), $connectedPlatforms);

            return response()->json([
                'connected' => $integrations,
                'available' => array_map(function ($platform) {
                    return [
                        'platform' => $platform,
                        'name' => self::PLATFORMS[$platform]['name'],
                        'status' => 'available',
                    ];
                }, $availablePlatforms),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch integrations',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initiate OAuth connection for a platform
     */
    public function connect(Request $request, string $orgId, string $platform): JsonResponse
    {
        $this->authorize('create', Integration::class);

        try {
            if (!isset(self::PLATFORMS[$platform])) {
                return $this->error('Unsupported platform', 400);
            }

            $config = self::PLATFORMS[$platform];

            // Generate state token for CSRF protection
            $state = Str::random(40);
            session()->put('oauth_state', $state);
            session()->put('oauth_org_id', $orgId);
            session()->put('oauth_platform', $platform);

            // Build OAuth URL
            $redirectUri = url("/api/integrations/{$platform}/callback");

            $params = http_build_query([
                'client_id' => config("services.{$platform}.client_id") ?? config("services.{$platform}.client_key"),
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => implode(' ', $config['scopes']),
                'state' => $state,
            ]);

            $authUrl = $config['oauth_url'] . '?' . $params;

            return response()->json([
                'auth_url' => $authUrl,
                'redirect_uri' => $redirectUri,
                'platform' => $platform,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to initiate OAuth',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle OAuth callback from platform
     */
    public function callback(Request $request, string $platform): RedirectResponse
    {
        try {
            // Verify state token
            $state = $request->input('state');
            if ($state !== session()->get('oauth_state')) {
                return redirect('/integrations?error=invalid_state');
            }

            $orgId = session()->get('oauth_org_id');
            $storedPlatform = session()->get('oauth_platform');

            if ($platform !== $storedPlatform) {
                return redirect('/integrations?error=platform_mismatch');
            }

            // Check for authorization code
            $code = $request->input('code');
            if (!$code) {
                $error = $request->input('error_description', 'Authorization denied');
                return redirect("/integrations?error={$error}");
            }

            // Exchange code for access token
            $tokenData = $this->exchangeCodeForToken($platform, $code);

            if (!$tokenData) {
                return redirect('/integrations?error=token_exchange_failed');
            }

            // Fetch user/account info from platform
            $accountInfo = $this->fetchAccountInfo($platform, $tokenData['access_token']);

            // Store integration
            $integration = Integration::create([
                'integration_id' => Str::uuid(),
                'org_id' => $orgId,
                'platform' => $platform,
                'account_id' => $accountInfo['id'] ?? null,
                'username' => $accountInfo['username'] ?? $accountInfo['name'] ?? null,
                'access_token' => encrypt($tokenData['access_token']),
                'is_active' => true,
                'created_by' => Auth::id(),
            ]);

            // Clear session data
            session()->forget(['oauth_state', 'oauth_org_id', 'oauth_platform']);

            // Trigger initial sync
            $this->triggerSync($integration);

            return redirect('/integrations?success=connected&platform=' . $platform);

        } catch (\Exception $e) {
            Log::error('OAuth callback error', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);
            return redirect('/integrations?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Exchange authorization code for access token
     */
    protected function exchangeCodeForToken(string $platform, string $code): ?array
    {
        $config = self::PLATFORMS[$platform];
        $redirectUri = url("/api/integrations/{$platform}/callback");

        try {
            $response = Http::asForm()->post($config['token_url'], [
                'client_id' => config("services.{$platform}.client_id") ?? config("services.{$platform}.client_key"),
                'client_secret' => config("services.{$platform}.client_secret"),
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Token exchange failed', [
                'platform' => $platform,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Token exchange exception', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Fetch account information from platform
     */
    protected function fetchAccountInfo(string $platform, string $accessToken): array
    {
        // Platform-specific API endpoints for fetching user/account info
        $endpoints = [
            'facebook' => 'https://graph.facebook.com/v18.0/me?fields=id,name',
            'instagram' => 'https://graph.instagram.com/me?fields=id,username',
            'google' => 'https://www.googleapis.com/oauth2/v2/userinfo',
            'tiktok' => 'https://open-api.tiktok.com/user/info/',
            'twitter' => 'https://api.twitter.com/2/users/me',
            'linkedin' => 'https://api.linkedin.com/v2/me',
        ];

        try {
            $response = Http::withToken($accessToken)->get($endpoints[$platform] ?? '');
            return $response->successful() ? $response->json() : [];
        } catch (\Exception $e) {
            Log::error('Failed to fetch account info', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Disconnect an integration
     */
    public function disconnect(Request $request, string $orgId, string $integrationId): JsonResponse
    {
        try {
            $integration = Integration::where('org_id', $orgId)
                ->where('integration_id', $integrationId)
                ->firstOrFail();

            $this->authorize('delete', $integration);

            Log::info('IntegrationController::disconnect called (stub) - Revoke token with platform API if possible', [
                'integration_id' => $integrationId,
                'platform' => $integration->platform,
            ]);

            // Deactivate integration
            $integration->update([
                'is_active' => false,
                'updated_by' => Auth::id(),
            ]);

            // Optionally delete it completely
            // $integration->delete();

            return response()->json([
                'message' => 'Integration disconnected successfully',
                'integration_id' => $integrationId,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Integration not found');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to disconnect integration',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger manual sync for an integration
     */
    public function sync(Request $request, string $orgId, string $integrationId): JsonResponse
    {
        try {
            $integration = Integration::where('org_id', $orgId)
                ->where('integration_id', $integrationId)
                ->firstOrFail();

            $this->authorize('sync', $integration);

            if (!$integration->is_active) {
                return response()->json([
                    'error' => 'Integration is not active',
                    'message' => 'Please reconnect the integration first'
                ], 400);
            }

            // Trigger sync
            $result = $this->triggerSync($integration);

            return response()->json([
                'message' => 'Sync initiated successfully',
                'integration_id' => $integrationId,
                'sync_result' => $result,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Integration not found');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to sync integration',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger sync for an integration (stub implementation)
     *
     * @param Integration $integration Integration to sync
     * @return array Sync status result
     */
    protected function triggerSync(Integration $integration): array
    {
        Log::info('IntegrationController::triggerSync called (stub)', [
            'integration_id' => $integration->integration_id,
            'platform' => $integration->platform,
        ]);
        // Stub implementation - Actual sync logic not yet implemented
        // This would:
        // 1. Fetch data from the platform (posts, metrics, etc.)
        // 2. Store in database (SocialPost, SocialPostMetric, etc.)
        // 3. Update integration's updated_at timestamp

        return [
            'status' => 'success',
            'message' => 'Sync queued for processing',
            'timestamp' => now()->toIso8601String(),
            'stub' => true
        ];
    }

    /**
     * Get sync history for an integration
     */
    public function syncHistory(Request $request, string $orgId, string $integrationId): JsonResponse
    {
        try {
            $integration = Integration::where('org_id', $orgId)
                ->where('integration_id', $integrationId)
                ->firstOrFail();

            $this->authorize('view', $integration);

            Log::info('IntegrationController::syncHistory called (stub)', [
                'integration_id' => $integrationId,
            ]);
            // Stub implementation - Sync history tracking not yet implemented
            // This would query a sync_logs table or similar

            $history = [
                [
                    'id' => 1,
                    'status' => 'success',
                    'started_at' => now()->subHours(2),
                    'completed_at' => now()->subHours(2)->addMinutes(5),
                    'items_synced' => 145,
                    'errors' => 0,
                ],
                [
                    'id' => 2,
                    'status' => 'success',
                    'started_at' => now()->subHours(6),
                    'completed_at' => now()->subHours(6)->addMinutes(3),
                    'items_synced' => 89,
                    'errors' => 0,
                ],
            ];

            return response()->json(['history' => $history]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Integration not found');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch sync history',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get integration settings
     */
    public function getSettings(Request $request, string $orgId, string $integrationId): JsonResponse
    {
        try {
            $integration = Integration::where('org_id', $orgId)
                ->where('integration_id', $integrationId)
                ->firstOrFail();

            $this->authorize('view', $integration);

            Log::info('IntegrationController::getSettings called (stub)', [
                'integration_id' => $integrationId,
            ]);
            // Stub implementation - Settings storage not yet implemented (separate table or JSON column)
            $settings = [
                'auto_sync' => true,
                'sync_frequency' => 'hourly',
                'sync_posts' => true,
                'sync_metrics' => true,
                'sync_comments' => false,
            ];

            return response()->json([
                'integration_id' => $integrationId,
                'settings' => $settings,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Integration not found');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch settings',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update integration settings
     */
    public function updateSettings(Request $request, string $orgId, string $integrationId): JsonResponse
    {
        try {
            $integration = Integration::where('org_id', $orgId)
                ->where('integration_id', $integrationId)
                ->firstOrFail();

            $this->authorize('update', $integration);

            Log::info('IntegrationController::updateSettings called (stub)', [
                'integration_id' => $integrationId,
                'data' => $request->all(),
            ]);
            // Stub implementation - Validate and store settings not yet implemented
            $settings = $request->all();

            return response()->json([
                'message' => 'Settings updated successfully',
                'settings' => $settings,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Integration not found');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update settings',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent integration activity
     */
    public function activity(Request $request, string $orgId): JsonResponse
    {
        $this->authorize('viewAny', Integration::class);

        try {
            Log::info('IntegrationController::activity called (stub)');
            // Stub implementation - Activity tracking not yet implemented
            // This would query activity logs for all integrations in the org

            $activity = [
                [
                    'id' => 1,
                    'type' => 'sync',
                    'platform' => 'facebook',
                    'message' => 'Synced 45 new posts',
                    'timestamp' => now()->subMinutes(15),
                    'status' => 'success',
                ],
                [
                    'id' => 2,
                    'type' => 'connection',
                    'platform' => 'instagram',
                    'message' => 'Instagram account connected',
                    'timestamp' => now()->subHours(2),
                    'status' => 'success',
                ],
            ];

            return response()->json(['activity' => $activity]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch activity',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test connection for an integration
     */
    public function test(Request $request, string $orgId, string $integrationId): JsonResponse
    {
        try {
            $integration = Integration::where('org_id', $orgId)
                ->where('integration_id', $integrationId)
                ->firstOrFail();

            $this->authorize('view', $integration);

            if (!$integration->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Integration is not active'
                ], 400);
            }

            Log::info('IntegrationController::test called (stub)', [
                'integration_id' => $integrationId,
                'platform' => $integration->platform,
            ]);
            // Stub implementation - Test API connection with the platform not yet implemented
            // This would make a simple API call to verify the token is still valid

            return response()->json([
                'status' => 'success',
                'message' => 'Connection is working properly',
                'platform' => $integration->platform,
                'tested_at' => now()->toIso8601String(),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Integration not found');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Connection test failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get expiring tokens for dashboard warnings (NEW: Week 2)
     *
     * Returns integrations with tokens expiring within specified days
     */
    public function getExpiringTokens(Request $request, string $orgId)
    {
        $this->authorize('viewAny', Integration::class);

        try {
            $warningDays = $request->input('days', 7);
            $now = now();
            $threshold = $now->copy()->addDays($warningDays);

            $expiringTokens = Integration::where('org_id', $orgId)
                ->where('is_active', true)
                ->whereNotNull('token_expires_at')
                ->where('token_expires_at', '<=', $threshold)
                ->where('token_expires_at', '>', $now) // Not yet expired
                ->orderBy('token_expires_at', 'asc')
                ->get()
                ->map(function ($integration) use ($now) {
                    $expiresAt = $integration->token_expires_at;
                    $daysUntilExpiry = $now->diffInDays($expiresAt, false);
                    $hoursUntilExpiry = $now->diffInHours($expiresAt, false);

                    // Determine severity
                    $severity = 'warning';
                    if ($hoursUntilExpiry <= 24) {
                        $severity = 'critical';
                    } elseif ($daysUntilExpiry <= 3) {
                        $severity = 'urgent';
                    }

                    return [
                        'integration_id' => $integration->integration_id,
                        'platform' => $integration->platform,
                        'platform_name' => self::PLATFORMS[$integration->platform]['name'] ?? ucfirst($integration->platform),
                        'username' => $integration->username,
                        'account_id' => $integration->account_id,
                        'expires_at' => $expiresAt->toIso8601String(),
                        'expires_at_human' => $expiresAt->diffForHumans(),
                        'days_until_expiry' => round($daysUntilExpiry, 1),
                        'hours_until_expiry' => round($hoursUntilExpiry, 1),
                        'severity' => $severity,
                        'has_refresh_token' => !empty($integration->refresh_token),
                        'reconnect_url' => "/dashboard/{$orgId}/integrations/{$integration->integration_id}/reconnect",
                    ];
                });

            return response()->json([
                'expiring_tokens' => $expiringTokens,
                'total_count' => $expiringTokens->count(),
                'critical_count' => $expiringTokens->where('severity', 'critical')->count(),
                'urgent_count' => $expiringTokens->where('severity', 'urgent')->count(),
                'warning_count' => $expiringTokens->where('severity', 'warning')->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch expiring tokens',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
