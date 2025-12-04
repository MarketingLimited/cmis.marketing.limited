<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Jobs\SyncMetaIntegrationRecords;
use App\Models\Integration;
use App\Models\Platform\PlatformConnection;
use App\Models\Social\IntegrationQueueSettings;
use App\Services\Profile\ProfileSoftDeleteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PlatformConnectionsController extends Controller
{
    use ApiResponse;

    /**
     * Required Meta permissions for ads management.
     * Based on Meta Marketing API documentation.
     */
    private const META_REQUIRED_PERMISSIONS = [
        'ads_management',
        'ads_read',
    ];

    /**
     * Recommended Meta permissions for full functionality.
     */
    private const META_RECOMMENDED_PERMISSIONS = [
        'business_management',
        'pages_read_engagement',
        'pages_show_list',
        'read_insights',
    ];

    /**
     * Display platform connections settings page.
     */
    public function index(Request $request, string $org)
    {
        $connections = PlatformConnection::where('org_id', $org)
            ->orderBy('platform')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group connections by platform
        $connectionsByPlatform = $connections->groupBy('platform');

        if ($request->wantsJson()) {
            return $this->success($connections, 'Platform connections retrieved successfully');
        }

        return view('settings.platform-connections.index', [
            'connections' => $connections,
            'connectionsByPlatform' => $connectionsByPlatform,
            'currentOrg' => $org,
            'platforms' => $this->getAvailablePlatforms(),
        ]);
    }

    /**
     * List connected integrations for API access.
     * Used by Historical Content Import Modal to populate platform dropdown.
     *
     * GET /api/list
     */
    public function listIntegrations(Request $request, string $org)
    {
        // Query Integration table - this is where synced social accounts are stored
        // Note: 'platform' is the column name (not 'platform_type')
        // Note: 'active' is the status (not 'connected')
        $integrations = Integration::where('org_id', $org)
            ->whereIn('status', ['active', 'connected']) // Include both possible statuses
            ->whereIn('platform', ['instagram', 'facebook', 'threads', 'twitter', 'linkedin', 'tiktok', 'youtube'])
            ->select('integration_id', 'platform as platform_type', 'account_name', 'account_username as username', 'status', 'token_expires_at as expires_at')
            ->orderBy('platform')
            ->get();

        Log::debug('listIntegrations response', [
            'org_id' => $org,
            'integrations_count' => $integrations->count(),
            'integrations' => $integrations->toArray(),
        ]);

        // Transform to ensure 'connected' status for display
        $transformed = $integrations->map(function ($item) {
            return [
                'integration_id' => $item->integration_id,
                'platform_type' => $item->platform_type,
                'account_name' => $item->account_name ?: 'Unknown',
                'username' => $item->username,
                'status' => 'connected',
                'expires_at' => $item->expires_at,
            ];
        });

        return $this->success($transformed->values(), 'Connected integrations retrieved successfully');
    }

    /**
     * Show form for adding Meta system user token.
     */
    public function createMetaToken(Request $request, string $org)
    {
        return view('settings.platform-connections.meta-token', [
            'currentOrg' => $org,
            'connection' => null,
        ]);
    }

    /**
     * Store Meta system user token.
     */
    public function storeMetaToken(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'access_token' => 'required|string|min:50',
            'account_name' => 'required|string|max:255',
            'ad_account_id' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        $orgId = $org;
        $accessToken = $request->input('access_token');

        // Validate the token with Meta API
        $tokenInfo = $this->validateMetaToken($accessToken);

        if (!$tokenInfo['valid']) {
            $error = 'Invalid access token: ' . ($tokenInfo['error'] ?? 'Token validation failed');
            if ($request->wantsJson()) {
                return $this->error($error, 400);
            }
            return back()->withErrors(['access_token' => $error])->withInput();
        }

        // Check for missing required permissions (warn but don't block)
        $warnings = $tokenInfo['warnings'] ?? [];
        $hasRequiredPermissions = $tokenInfo['has_all_required_permissions'] ?? true;

        // Get ad accounts associated with this token
        $adAccounts = $this->getMetaAdAccounts($accessToken);

        // Count active ad accounts
        $activeAdAccounts = array_filter($adAccounts, fn($acc) => $acc['can_create_ads'] ?? false);

        // Create or update the connection
        $connection = PlatformConnection::updateOrCreate(
            [
                'org_id' => $orgId,
                'platform' => 'meta',
                'account_id' => $tokenInfo['user_id'] ?? $request->input('ad_account_id') ?? 'system_user_' . Str::random(8),
            ],
            [
                'account_name' => $request->input('account_name'),
                'status' => $hasRequiredPermissions ? 'active' : 'warning',
                'access_token' => $accessToken,
                'token_expires_at' => $tokenInfo['expires_at'],
                'scopes' => $tokenInfo['scopes'] ?? [],
                'account_metadata' => [
                    'token_type' => $tokenInfo['token_type'] ?? 'system_user',
                    'is_system_user' => $tokenInfo['is_system_user'] ?? false,
                    'is_never_expires' => $tokenInfo['is_never_expires'] ?? false,
                    'app_id' => $tokenInfo['app_id'] ?? null,
                    'user_id' => $tokenInfo['user_id'] ?? null,
                    'user_name' => $tokenInfo['user_name'] ?? null,
                    'application' => $tokenInfo['application'] ?? null,
                    'data_access_expires_at' => $tokenInfo['data_access_expires_at']?->toIso8601String(),
                    'issued_at' => $tokenInfo['issued_at']?->toIso8601String(),
                    'ad_accounts' => $adAccounts,
                    'ad_accounts_count' => count($adAccounts),
                    'active_ad_accounts_count' => count($activeAdAccounts),
                    'business_info' => $tokenInfo['business_info'] ?? null,
                    'granular_scopes' => $tokenInfo['granular_scopes'] ?? [],
                    'missing_required_permissions' => $tokenInfo['missing_required_permissions'] ?? [],
                    'missing_recommended_permissions' => $tokenInfo['missing_recommended_permissions'] ?? [],
                    'warnings' => $warnings,
                    'is_valid' => true,
                    'validated_at' => now()->toIso8601String(),
                ],
                'auto_sync' => true,
                'sync_frequency_minutes' => 15,
            ]
        );

        // Build success message
        $successMessage = 'Meta system user token saved successfully. Found ' . count($adAccounts) . ' ad account(s)';
        if (count($activeAdAccounts) < count($adAccounts)) {
            $successMessage .= ' (' . count($activeAdAccounts) . ' active)';
        }
        $successMessage .= '.';

        // Add warning about permissions if needed
        if (!$hasRequiredPermissions) {
            $missingPerms = implode(', ', $tokenInfo['missing_required_permissions'] ?? []);
            $successMessage .= " Warning: Missing required permissions ({$missingPerms}). Some features may not work.";
        }

        if ($request->wantsJson()) {
            return $this->created([
                'connection' => $connection,
                'warnings' => $warnings,
                'ad_accounts_count' => count($adAccounts),
                'active_ad_accounts_count' => count($activeAdAccounts),
            ], $successMessage);
        }

        // Redirect to asset selection page after creating connection
        return redirect()
            ->route('orgs.settings.platform-connections.meta.assets', [$org, $connection->connection_id])
            ->with('success', $successMessage . ' Now select which Pages, Instagram accounts, Pixels, and Catalogs to use.');
    }

    /**
     * Edit Meta system user token.
     */
    public function editMetaToken(Request $request, string $org, string $connectionId)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'meta')
            ->firstOrFail();

        return view('settings.platform-connections.meta-token', [
            'currentOrg' => $org,
            'connection' => $connection,
        ]);
    }

    /**
     * Update Meta system user token.
     */
    public function updateMetaToken(Request $request, string $org, string $connectionId)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'meta')
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'access_token' => 'nullable|string|min:50',
            'account_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        $updateData = [
            'account_name' => $request->input('account_name'),
        ];

        $successMessage = 'Meta connection updated successfully';

        // Only validate and update token if a new one is provided
        if ($request->filled('access_token')) {
            $accessToken = $request->input('access_token');
            $tokenInfo = $this->validateMetaToken($accessToken);

            if (!$tokenInfo['valid']) {
                $error = 'Invalid access token: ' . ($tokenInfo['error'] ?? 'Token validation failed');
                if ($request->wantsJson()) {
                    return $this->error($error, 400);
                }
                return back()->withErrors(['access_token' => $error])->withInput();
            }

            $warnings = $tokenInfo['warnings'] ?? [];
            $hasRequiredPermissions = $tokenInfo['has_all_required_permissions'] ?? true;

            $adAccounts = $this->getMetaAdAccounts($accessToken);
            $activeAdAccounts = array_filter($adAccounts, fn($acc) => $acc['can_create_ads'] ?? false);

            $updateData['access_token'] = $accessToken;
            $updateData['token_expires_at'] = $tokenInfo['expires_at'];
            $updateData['scopes'] = $tokenInfo['scopes'] ?? [];
            $updateData['status'] = $hasRequiredPermissions ? 'active' : 'warning';
            $updateData['last_error_at'] = null;
            $updateData['last_error_message'] = null;
            $updateData['account_metadata'] = array_merge($connection->account_metadata ?? [], [
                'token_type' => $tokenInfo['token_type'] ?? 'system_user',
                'is_system_user' => $tokenInfo['is_system_user'] ?? false,
                'is_never_expires' => $tokenInfo['is_never_expires'] ?? false,
                'app_id' => $tokenInfo['app_id'] ?? null,
                'user_id' => $tokenInfo['user_id'] ?? null,
                'user_name' => $tokenInfo['user_name'] ?? null,
                'application' => $tokenInfo['application'] ?? null,
                'data_access_expires_at' => $tokenInfo['data_access_expires_at']?->toIso8601String(),
                'issued_at' => $tokenInfo['issued_at']?->toIso8601String(),
                'ad_accounts' => $adAccounts,
                'ad_accounts_count' => count($adAccounts),
                'active_ad_accounts_count' => count($activeAdAccounts),
                'business_info' => $tokenInfo['business_info'] ?? null,
                'granular_scopes' => $tokenInfo['granular_scopes'] ?? [],
                'missing_required_permissions' => $tokenInfo['missing_required_permissions'] ?? [],
                'missing_recommended_permissions' => $tokenInfo['missing_recommended_permissions'] ?? [],
                'warnings' => $warnings,
                'is_valid' => true,
                'validated_at' => now()->toIso8601String(),
            ]);

            $successMessage = 'Meta connection updated with new token. Found ' . count($adAccounts) . ' ad account(s)';
            if (count($activeAdAccounts) < count($adAccounts)) {
                $successMessage .= ' (' . count($activeAdAccounts) . ' active)';
            }
            $successMessage .= '.';

            if (!$hasRequiredPermissions) {
                $missingPerms = implode(', ', $tokenInfo['missing_required_permissions'] ?? []);
                $successMessage .= " Warning: Missing required permissions ({$missingPerms}).";
            }
        }

        $connection->update($updateData);

        if ($request->wantsJson()) {
            return $this->success($connection->fresh(), $successMessage);
        }

        return redirect()
            ->route('orgs.settings.platform-connections.index', $org)
            ->with('success', $successMessage);
    }

    // ==================== GOOGLE TOKEN MANAGEMENT ====================

    /**
     * Show form to add Google service account credentials.
     */
    public function createGoogleToken(Request $request, string $org)
    {
        return view('settings.platform-connections.google-token', [
            'currentOrg' => $org,
            'connection' => null,
        ]);
    }

    /**
     * Store Google service account or OAuth credentials.
     */
    public function storeGoogleToken(Request $request, string $org)
    {
        $credentialType = $request->input('credential_type', 'service_account');

        $rules = [
            'account_name' => 'required|string|max:255',
            'credential_type' => 'required|in:service_account,oauth',
        ];

        if ($credentialType === 'service_account') {
            $rules['service_account_json'] = 'required|string|min:100';
        } else {
            $rules['client_id'] = 'required|string';
            $rules['client_secret'] = 'required|string';
            $rules['refresh_token'] = 'nullable|string';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $accountMetadata = [
                'credential_type' => $credentialType,
                'validated_at' => now()->toIso8601String(),
            ];

            $accessToken = null;

            if ($credentialType === 'service_account') {
                $serviceAccountJson = $request->input('service_account_json');
                $serviceAccount = json_decode($serviceAccountJson, true);

                if (!$serviceAccount || !isset($serviceAccount['client_email'])) {
                    return back()->withErrors(['service_account_json' => 'Invalid service account JSON format'])->withInput();
                }

                $accountMetadata['service_account_email'] = $serviceAccount['client_email'];
                $accountMetadata['project_id'] = $serviceAccount['project_id'] ?? null;
                $accessToken = $serviceAccountJson; // Store encrypted
            } else {
                $accountMetadata['client_id'] = $request->input('client_id');
                $accessToken = json_encode([
                    'client_id' => $request->input('client_id'),
                    'client_secret' => $request->input('client_secret'),
                    'refresh_token' => $request->input('refresh_token'),
                ]);
            }

            // Create the connection
            $connection = PlatformConnection::create([
                'connection_id' => Str::uuid(),
                'org_id' => $org,
                'platform' => 'google',
                'account_id' => $accountMetadata['service_account_email'] ?? $request->input('client_id') ?? 'google_' . Str::random(8),
                'account_name' => $request->input('account_name'),
                'status' => 'active',
                'access_token' => $accessToken,
                'account_metadata' => $accountMetadata,
                'auto_sync' => true,
                'sync_frequency_minutes' => 60,
            ]);

            if ($request->wantsJson()) {
                return $this->created($connection, 'Google connection created successfully');
            }

            return redirect()
                ->route('orgs.settings.platform-connections.google.assets', [$org, $connection->connection_id])
                ->with('success', __('settings.created_success'));

        } catch (\Exception $e) {
            Log::error('Failed to store Google credentials', ['error' => $e->getMessage()]);
            return back()->withErrors(['general' => 'Failed to save credentials: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Edit Google credentials.
     */
    public function editGoogleToken(Request $request, string $org, string $connectionId)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'google')
            ->firstOrFail();

        return view('settings.platform-connections.google-token', [
            'currentOrg' => $org,
            'connection' => $connection,
        ]);
    }

    /**
     * Update Google credentials.
     */
    public function updateGoogleToken(Request $request, string $org, string $connectionId)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'google')
            ->firstOrFail();

        $credentialType = $request->input('credential_type', $connection->account_metadata['credential_type'] ?? 'service_account');

        $validator = Validator::make($request->all(), [
            'account_name' => 'required|string|max:255',
            'service_account_json' => 'nullable|string|min:100',
            'client_id' => 'nullable|string',
            'client_secret' => 'nullable|string',
            'refresh_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        $updateData = [
            'account_name' => $request->input('account_name'),
        ];

        $accountMetadata = $connection->account_metadata ?? [];
        $accountMetadata['credential_type'] = $credentialType;

        // Update credentials if provided
        if ($credentialType === 'service_account' && $request->filled('service_account_json')) {
            $serviceAccountJson = $request->input('service_account_json');
            $serviceAccount = json_decode($serviceAccountJson, true);

            if (!$serviceAccount || !isset($serviceAccount['client_email'])) {
                return back()->withErrors(['service_account_json' => 'Invalid service account JSON format'])->withInput();
            }

            $accountMetadata['service_account_email'] = $serviceAccount['client_email'];
            $accountMetadata['project_id'] = $serviceAccount['project_id'] ?? null;
            $updateData['access_token'] = $serviceAccountJson;
            $updateData['account_id'] = $serviceAccount['client_email'];
        } elseif ($credentialType === 'oauth' && ($request->filled('client_id') || $request->filled('client_secret'))) {
            $accountMetadata['client_id'] = $request->input('client_id', $accountMetadata['client_id'] ?? null);
            $updateData['access_token'] = json_encode([
                'client_id' => $request->input('client_id'),
                'client_secret' => $request->input('client_secret'),
                'refresh_token' => $request->input('refresh_token'),
            ]);
        }

        $accountMetadata['validated_at'] = now()->toIso8601String();
        $updateData['account_metadata'] = $accountMetadata;

        $connection->update($updateData);

        if ($request->wantsJson()) {
            return $this->success($connection->fresh(), 'Google connection updated successfully');
        }

        return redirect()
            ->route('orgs.settings.platform-connections.index', $org)
            ->with('success', __('settings.updated_success'));
    }

    /**
     * Test a platform connection.
     */
    public function testConnection(Request $request, string $org, string $connectionId)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->firstOrFail();

        $testResult = match ($connection->platform) {
            'meta' => $this->testMetaConnection($connection),
            'google' => $this->testGoogleConnection($connection),
            'tiktok', 'tiktok_ads' => $this->testTikTokConnection($connection),
            'linkedin' => $this->testLinkedInConnection($connection),
            'twitter' => $this->testTwitterConnection($connection),
            'snapchat' => $this->testSnapchatConnection($connection),
            'pinterest' => $this->testPinterestConnection($connection),
            default => ['success' => false, 'message' => __('settings.platform_not_supported_testing')],
        };

        if ($testResult['success']) {
            $connection->markAsActive();
        } else {
            $connection->markAsError($testResult['message']);
        }

        if ($request->wantsJson()) {
            return $testResult['success']
                ? $this->success($testResult, 'Connection test successful')
                : $this->error($testResult['message'], 400);
        }

        return back()->with($testResult['success'] ? 'success' : 'error', $testResult['message']);
    }

    /**
     * Delete a platform connection.
     */
    public function destroy(Request $request, string $org, string $connectionId)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->firstOrFail();

        $platformName = $connection->getPlatformName();

        // Soft delete profiles that belong ONLY to this connection
        // Profiles shared with other connections will be marked inactive instead
        $profileService = app(ProfileSoftDeleteService::class);
        $deletedCount = $profileService->softDeleteProfilesForConnection($org, $connectionId);

        Log::info('Platform connection deleted', [
            'connection_id' => $connectionId,
            'org_id' => $org,
            'platform' => $connection->platform,
            'profiles_soft_deleted' => $deletedCount,
        ]);

        $connection->delete();

        if ($request->wantsJson()) {
            return $this->deleted('Platform connection deleted successfully');
        }

        return redirect()
            ->route('orgs.settings.platform-connections.index', $org)
            ->with('success', __('settings.platform_connection_deleted', ['platform' => $platformName]));
    }

    /**
     * Refresh ad accounts for a Meta connection.
     */
    public function refreshAdAccounts(Request $request, string $org, string $connectionId)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'meta')
            ->firstOrFail();

        $adAccounts = $this->getMetaAdAccounts($connection->access_token);

        $connection->update([
            'account_metadata' => array_merge($connection->account_metadata ?? [], [
                'ad_accounts' => $adAccounts,
                'ad_accounts_refreshed_at' => now()->toIso8601String(),
            ]),
        ]);

        if ($request->wantsJson()) {
            return $this->success([
                'ad_accounts' => $adAccounts,
                'count' => count($adAccounts),
            ], 'Ad accounts refreshed successfully');
        }

        return back()->with('success', __('settings.ad_accounts_found', ['count' => count($adAccounts)]));
    }

    // ===== Private Helper Methods =====

    /**
     * Validate Meta access token.
     * Uses /me endpoint and /me/permissions for System User tokens since debug_token
     * requires app access token for inspection.
     */
    private function validateMetaToken(string $accessToken): array
    {
        try {
            // First, validate token by calling /me endpoint
            $meResponse = Http::timeout(15)->get('https://graph.facebook.com/v21.0/me', [
                'access_token' => $accessToken,
                'fields' => 'id,name',
            ]);

            if (!$meResponse->successful()) {
                $errorData = $meResponse->json('error', []);
                return [
                    'valid' => false,
                    'error' => $this->parseMetaApiError($errorData, 'Invalid access token'),
                ];
            }

            $userData = $meResponse->json();
            $userId = $userData['id'] ?? null;
            $userName = $userData['name'] ?? null;

            // Try to get permissions via /me/permissions
            $scopes = [];
            $permissionsResponse = Http::timeout(15)->get('https://graph.facebook.com/v21.0/me/permissions', [
                'access_token' => $accessToken,
            ]);

            if ($permissionsResponse->successful()) {
                $permissionsData = $permissionsResponse->json('data', []);
                foreach ($permissionsData as $perm) {
                    if (($perm['status'] ?? '') === 'granted') {
                        $scopes[] = $perm['permission'];
                    }
                }
            }

            // Check for required permissions
            $missingRequired = array_diff(self::META_REQUIRED_PERMISSIONS, $scopes);
            $missingRecommended = array_diff(self::META_RECOMMENDED_PERMISSIONS, $scopes);

            // Try debug_token for additional info (may fail for system users, that's ok)
            $tokenType = 'system_user';
            $appId = null;
            $expiresAt = null;
            $isNeverExpires = true; // Assume system user token never expires
            $dataAccessExpiresAt = null;
            $issuedAt = null;
            $application = null;
            $granularScopes = [];

            $debugResponse = Http::timeout(15)->get('https://graph.facebook.com/v21.0/debug_token', [
                'input_token' => $accessToken,
                'access_token' => $accessToken,
            ]);

            if ($debugResponse->successful()) {
                $debugData = $debugResponse->json('data', []);
                if ($debugData['is_valid'] ?? false) {
                    $appId = $debugData['app_id'] ?? null;
                    $tokenType = $this->detectMetaTokenType($debugData);
                    $granularScopes = $debugData['granular_scopes'] ?? [];
                    $application = $debugData['application'] ?? null;

                    // Use scopes from debug_token if available (more accurate)
                    if (!empty($debugData['scopes'])) {
                        $scopes = $debugData['scopes'];
                        $missingRequired = array_diff(self::META_REQUIRED_PERMISSIONS, $scopes);
                        $missingRecommended = array_diff(self::META_RECOMMENDED_PERMISSIONS, $scopes);
                    }

                    if (isset($debugData['expires_at'])) {
                        if ($debugData['expires_at'] === 0) {
                            $isNeverExpires = true;
                        } else {
                            $isNeverExpires = false;
                            $expiresAt = \Carbon\Carbon::createFromTimestamp($debugData['expires_at']);
                        }
                    }

                    if (isset($debugData['data_access_expires_at']) && $debugData['data_access_expires_at'] > 0) {
                        $dataAccessExpiresAt = \Carbon\Carbon::createFromTimestamp($debugData['data_access_expires_at']);
                    }

                    if (isset($debugData['issued_at'])) {
                        $issuedAt = \Carbon\Carbon::createFromTimestamp($debugData['issued_at']);
                    }
                }
            }

            // Get business info if business_management scope available
            $businessInfo = null;
            if (in_array('business_management', $scopes)) {
                $businessInfo = $this->getMetaBusinessInfo($accessToken);
            }

            return [
                'valid' => true,
                'app_id' => $appId,
                'user_id' => $userId,
                'user_name' => $userName,
                'scopes' => $scopes,
                'granular_scopes' => $granularScopes,
                'expires_at' => $expiresAt,
                'is_never_expires' => $isNeverExpires,
                'data_access_expires_at' => $dataAccessExpiresAt,
                'token_type' => $tokenType,
                'is_system_user' => $tokenType === 'system_user' || $isNeverExpires,
                'profile_id' => $userId,
                'application' => $application,
                'issued_at' => $issuedAt,
                'business_info' => $businessInfo,
                'missing_required_permissions' => array_values($missingRequired),
                'missing_recommended_permissions' => array_values($missingRecommended),
                'has_all_required_permissions' => empty($missingRequired),
                'warnings' => $this->generateTokenWarnings($scopes, array_values($missingRequired), array_values($missingRecommended), $expiresAt, $isNeverExpires),
            ];
        } catch (\Exception $e) {
            Log::error('Meta token validation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'valid' => false,
                'error' => 'Token validation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Detect Meta token type from debug_token response.
     */
    private function detectMetaTokenType(array $data): string
    {
        // Check type field if available
        if (isset($data['type'])) {
            return match (strtolower($data['type'])) {
                'user' => 'user',
                'page' => 'page',
                'app' => 'app',
                'system_user', 'systemuser' => 'system_user',
                default => $data['type'],
            };
        }

        // Infer from other fields
        // System users typically have expires_at = 0 (never expires)
        if (isset($data['expires_at']) && $data['expires_at'] === 0) {
            return 'system_user';
        }

        // If has profile_id, likely a user token
        if (isset($data['profile_id'])) {
            return 'user';
        }

        return 'unknown';
    }

    /**
     * Get Meta Business Manager info.
     */
    private function getMetaBusinessInfo(string $accessToken): ?array
    {
        try {
            $response = Http::timeout(15)->get('https://graph.facebook.com/v21.0/me/businesses', [
                'access_token' => $accessToken,
                'fields' => 'id,name,verification_status,created_time',
                'limit' => 10,
            ]);

            if (!$response->successful()) {
                return null;
            }

            return $response->json('data', []);
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Meta business info', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate warnings for token issues.
     */
    private function generateTokenWarnings(
        array $scopes,
        array $missingRequired,
        array $missingRecommended,
        ?\Carbon\Carbon $expiresAt,
        bool $isNeverExpires
    ): array {
        $warnings = [];

        // Missing required permissions
        if (!empty($missingRequired)) {
            $warnings[] = [
                'type' => 'error',
                'code' => 'missing_required_permissions',
                'message' => 'Missing required permissions: ' . implode(', ', $missingRequired),
                'action' => 'Generate a new token with ads_management and ads_read permissions.',
            ];
        }

        // Missing recommended permissions
        if (!empty($missingRecommended) && empty($missingRequired)) {
            $warnings[] = [
                'type' => 'warning',
                'code' => 'missing_recommended_permissions',
                'message' => 'Missing recommended permissions: ' . implode(', ', $missingRecommended),
                'action' => 'For full functionality, consider adding business_management and pages permissions.',
            ];
        }

        // Token expiration warning
        if (!$isNeverExpires && $expiresAt) {
            $daysUntilExpiry = now()->diffInDays($expiresAt, false);

            if ($daysUntilExpiry < 0) {
                $warnings[] = [
                    'type' => 'error',
                    'code' => 'token_expired',
                    'message' => 'This token has expired.',
                    'action' => 'Generate a new System User token from Meta Business Manager.',
                ];
            } elseif ($daysUntilExpiry <= 7) {
                $warnings[] = [
                    'type' => 'warning',
                    'code' => 'token_expiring_soon',
                    'message' => "Token expires in {$daysUntilExpiry} days.",
                    'action' => 'Consider generating a new System User token with longer expiration.',
                ];
            } elseif ($daysUntilExpiry <= 30) {
                $warnings[] = [
                    'type' => 'info',
                    'code' => 'token_expiring',
                    'message' => "Token expires in {$daysUntilExpiry} days.",
                    'action' => 'Plan to renew your token before expiration.',
                ];
            }
        }

        // Non-system-user token warning
        if (!$isNeverExpires && !in_array('system_user', $scopes)) {
            $warnings[] = [
                'type' => 'info',
                'code' => 'not_system_user_token',
                'message' => 'This appears to be a regular user token, not a System User token.',
                'action' => 'System User tokens are recommended as they never expire and are more secure for server applications.',
            ];
        }

        return $warnings;
    }

    /**
     * Parse Meta API error response.
     */
    private function parseMetaApiError(array $errorData, string $defaultMessage): string
    {
        if (empty($errorData)) {
            return $defaultMessage;
        }

        $message = $errorData['message'] ?? $defaultMessage;
        $code = $errorData['code'] ?? null;
        $subcode = $errorData['error_subcode'] ?? null;

        return $this->getMetaErrorExplanation($code, $message, $subcode);
    }

    /**
     * Get human-readable explanation for Meta API error codes.
     */
    private function getMetaErrorExplanation(?int $code, string $message, ?int $subcode = null): string
    {
        // Common Meta API error codes
        $explanations = [
            190 => 'Access token has expired or is invalid. Please generate a new token.',
            200 => 'Permission denied. The token lacks required permissions for this operation.',
            294 => 'Managing ads requires the ads_management permission. Generate a new token with this permission.',
            2500 => 'Application request limit reached. Too many API calls in a short period.',
            10 => 'Application does not have permission for this action.',
            100 => 'Invalid parameter. The token format may be incorrect.',
            102 => 'Session expired. Please generate a new token.',
            104 => 'Access token signature validation failed. Ensure the token is copied correctly.',
            463 => 'Token has expired. Please generate a new token from Meta Business Manager.',
            467 => 'Invalid access token. The token may have been revoked or is malformed.',
        ];

        if ($code && isset($explanations[$code])) {
            return $explanations[$code];
        }

        // Subcode specific messages
        if ($subcode === 458) {
            return 'App not installed. The token may be from a different app.';
        }
        if ($subcode === 459) {
            return 'User checkpointed. The user needs to log in to Facebook.';
        }
        if ($subcode === 460) {
            return 'Password changed. A new token is required.';
        }

        return $message;
    }

    /**
     * Get Meta ad accounts associated with token.
     * Fetches comprehensive account data including spend caps and funding.
     */
    private function getMetaAdAccounts(string $accessToken): array
    {
        try {
            $response = Http::timeout(30)->get('https://graph.facebook.com/v21.0/me/adaccounts', [
                'access_token' => $accessToken,
                'fields' => implode(',', [
                    'id',
                    'name',
                    'account_id',
                    'account_status',
                    'disable_reason',
                    'currency',
                    'timezone_name',
                    'timezone_id',
                    'business_name',
                    'business',
                    'spend_cap',
                    'amount_spent',
                    'balance',
                    'owner',
                    'funding_source',
                    'funding_source_details',
                    'created_time',
                    'capabilities',
                    'is_prepay_account',
                    'min_campaign_group_spend_cap',
                    'min_daily_budget',
                ]),
                'limit' => 100,
            ]);

            if (!$response->successful()) {
                Log::warning('Failed to fetch Meta ad accounts', [
                    'status' => $response->status(),
                    'error' => $response->json('error', []),
                ]);
                return [];
            }

            return array_map(function ($account) {
                $statusCode = $account['account_status'] ?? 0;
                $disableReason = $account['disable_reason'] ?? null;

                return [
                    'id' => $account['id'],
                    'account_id' => $account['account_id'] ?? str_replace('act_', '', $account['id']),
                    'name' => $account['name'] ?? 'Unknown',
                    'business_name' => $account['business_name'] ?? null,
                    'business_id' => $account['business']['id'] ?? null,
                    'currency' => $account['currency'] ?? 'USD',
                    'timezone' => $account['timezone_name'] ?? 'UTC',
                    'timezone_id' => $account['timezone_id'] ?? null,
                    'status' => $this->getMetaAccountStatusLabel($statusCode),
                    'status_code' => $statusCode,
                    'disable_reason' => $disableReason ? $this->getMetaDisableReasonLabel($disableReason) : null,
                    'spend_cap' => $account['spend_cap'] ?? null,
                    'amount_spent' => $account['amount_spent'] ?? '0',
                    'balance' => $account['balance'] ?? '0',
                    'is_prepay' => $account['is_prepay_account'] ?? false,
                    'min_daily_budget' => $account['min_daily_budget'] ?? null,
                    'capabilities' => $account['capabilities'] ?? [],
                    'funding_source' => $account['funding_source_details']['display_string'] ?? null,
                    'created_at' => $account['created_time'] ?? null,
                    'can_create_ads' => $statusCode === 1,
                ];
            }, $response->json('data', []));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Meta ad accounts', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get label for Meta ad account disable reason.
     */
    private function getMetaDisableReasonLabel(int $reason): string
    {
        return match ($reason) {
            0 => 'None',
            1 => 'Ads violate policy',
            2 => 'Suspicious activity',
            3 => 'Payment failed',
            4 => 'Promoting prohibited products',
            5 => 'Breached terms of service',
            6 => 'Administrative action',
            7 => 'Policy risk',
            8 => 'Unused reseller account',
            9 => 'Gray account',
            default => 'Unknown reason (' . $reason . ')',
        };
    }

    /**
     * Get Meta account status label.
     */
    private function getMetaAccountStatusLabel(int $status): string
    {
        return match ($status) {
            1 => 'Active',
            2 => 'Disabled',
            3 => 'Unsettled',
            7 => 'Pending Risk Review',
            8 => 'Pending Settlement',
            9 => 'In Grace Period',
            100 => 'Pending Closure',
            101 => 'Closed',
            201 => 'Any Active',
            202 => 'Any Closed',
            default => 'Unknown',
        };
    }

    /**
     * Test Meta connection.
     */
    private function testMetaConnection(PlatformConnection $connection): array
    {
        $tokenInfo = $this->validateMetaToken($connection->access_token);

        if (!$tokenInfo['valid']) {
            return [
                'success' => false,
                'message' => 'Token validation failed: ' . ($tokenInfo['error'] ?? 'Unknown error'),
            ];
        }

        $adAccounts = $this->getMetaAdAccounts($connection->access_token);

        return [
            'success' => true,
            'message' => 'Connection is valid. Found ' . count($adAccounts) . ' ad account(s).',
            'ad_accounts' => $adAccounts,
            'token_info' => $tokenInfo,
        ];
    }

    /**
     * Test Google connection.
     */
    private function testGoogleConnection(PlatformConnection $connection): array
    {
        try {
            $accessToken = $this->getValidGoogleAccessToken($connection);
            if (!$accessToken) {
                return ['success' => false, 'message' => __('settings.token_expired_or_invalid')];
            }

            // Test by getting user info
            $response = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v2/userinfo');

            if ($response->successful()) {
                $userInfo = $response->json();
                return [
                    'success' => true,
                    'message' => __('settings.connection_valid_for_user', ['user' => $userInfo['email'] ?? $userInfo['name'] ?? 'Google User']),
                ];
            }

            return [
                'success' => false,
                'message' => __('settings.google_api_error', ['error' => $response->json('error.message', 'Unknown error')]),
            ];
        } catch (\Exception $e) {
            Log::error('Google connection test failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => __('settings.connection_test_failed', ['error' => $e->getMessage()])];
        }
    }

    /**
     * Test TikTok connection.
     */
    private function testTikTokConnection(PlatformConnection $connection): array
    {
        try {
            $accessToken = $connection->access_token;
            if (!$accessToken) {
                return ['success' => false, 'message' => __('settings.token_expired_or_invalid')];
            }

            // Test by getting user info
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://open.tiktokapis.com/v2/user/info/', [
                'fields' => 'open_id,union_id,display_name',
            ]);

            if ($response->successful()) {
                $data = $response->json('data.user', []);
                $displayName = $data['display_name'] ?? 'TikTok User';
                return [
                    'success' => true,
                    'message' => __('settings.connection_valid_for_user', ['user' => $displayName]),
                ];
            }

            $errorMsg = $response->json('error.message', $response->json('message', 'Unknown error'));
            return [
                'success' => false,
                'message' => __('settings.tiktok_api_error', ['error' => $errorMsg]),
            ];
        } catch (\Exception $e) {
            Log::error('TikTok connection test failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => __('settings.connection_test_failed', ['error' => $e->getMessage()])];
        }
    }

    /**
     * Test LinkedIn connection.
     */
    private function testLinkedInConnection(PlatformConnection $connection): array
    {
        try {
            $accessToken = $connection->access_token;
            if (!$accessToken) {
                return ['success' => false, 'message' => __('settings.token_expired_or_invalid')];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'X-Restli-Protocol-Version' => '2.0.0',
            ])->get('https://api.linkedin.com/v2/userinfo');

            if ($response->successful()) {
                $data = $response->json();
                $name = $data['name'] ?? $data['email'] ?? 'LinkedIn User';
                return [
                    'success' => true,
                    'message' => __('settings.connection_valid_for_user', ['user' => $name]),
                ];
            }

            return [
                'success' => false,
                'message' => __('settings.linkedin_api_error', ['error' => $response->json('message', 'Unknown error')]),
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn connection test failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => __('settings.connection_test_failed', ['error' => $e->getMessage()])];
        }
    }

    /**
     * Test Twitter connection.
     */
    private function testTwitterConnection(PlatformConnection $connection): array
    {
        try {
            $accessToken = $connection->access_token;
            if (!$accessToken) {
                return ['success' => false, 'message' => __('settings.token_expired_or_invalid')];
            }

            $response = Http::withToken($accessToken)
                ->get('https://api.twitter.com/2/users/me');

            if ($response->successful()) {
                $data = $response->json('data', []);
                $name = $data['name'] ?? $data['username'] ?? 'Twitter User';
                return [
                    'success' => true,
                    'message' => __('settings.connection_valid_for_user', ['user' => $name]),
                ];
            }

            return [
                'success' => false,
                'message' => __('settings.twitter_api_error', ['error' => $response->json('detail', 'Unknown error')]),
            ];
        } catch (\Exception $e) {
            Log::error('Twitter connection test failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => __('settings.connection_test_failed', ['error' => $e->getMessage()])];
        }
    }

    /**
     * Test Snapchat connection.
     */
    private function testSnapchatConnection(PlatformConnection $connection): array
    {
        try {
            $accessToken = $connection->access_token;
            if (!$accessToken) {
                return ['success' => false, 'message' => __('settings.token_expired_or_invalid')];
            }

            $response = Http::withToken($accessToken)
                ->get('https://adsapi.snapchat.com/v1/me');

            if ($response->successful()) {
                $data = $response->json('me', []);
                $name = $data['display_name'] ?? $data['email'] ?? 'Snapchat User';
                return [
                    'success' => true,
                    'message' => __('settings.connection_valid_for_user', ['user' => $name]),
                ];
            }

            return [
                'success' => false,
                'message' => __('settings.snapchat_api_error', ['error' => $response->json('error_message', 'Unknown error')]),
            ];
        } catch (\Exception $e) {
            Log::error('Snapchat connection test failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => __('settings.connection_test_failed', ['error' => $e->getMessage()])];
        }
    }

    /**
     * Test Pinterest connection.
     */
    private function testPinterestConnection(PlatformConnection $connection): array
    {
        try {
            $accessToken = $connection->access_token;
            if (!$accessToken) {
                return ['success' => false, 'message' => __('settings.token_expired_or_invalid')];
            }

            $response = Http::withToken($accessToken)
                ->get('https://api.pinterest.com/v5/user_account');

            if ($response->successful()) {
                $data = $response->json();
                $name = $data['username'] ?? 'Pinterest User';
                return [
                    'success' => true,
                    'message' => __('settings.connection_valid_for_user', ['user' => $name]),
                ];
            }

            return [
                'success' => false,
                'message' => __('settings.pinterest_api_error', ['error' => $response->json('message', 'Unknown error')]),
            ];
        } catch (\Exception $e) {
            Log::error('Pinterest connection test failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => __('settings.connection_test_failed', ['error' => $e->getMessage()])];
        }
    }

    /**
     * Show asset selection page for a Meta connection.
     *
     * OPTIMIZED: Assets are now loaded via AJAX for better performance.
     * - Initial page load: < 2 seconds (skeleton UI)
     * - Assets loaded progressively via /api/orgs/{org}/meta-connections/{connection}/assets/*
     * - Cache TTL: 1 hour
     * - Pagination: Unlimited assets (follows Meta Graph API cursor)
     *
     * @see App\Services\Platform\MetaAssetsService
     * @see App\Http\Controllers\Api\MetaAssetsApiController
     */
    public function selectMetaAssets(Request $request, string $org, string $connectionId)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'meta')
            ->firstOrFail();

        // Get currently selected assets (from database, not API)
        $selectedAssets = $connection->account_metadata['selected_assets'] ?? [];

        // Return view immediately with skeleton UI
        // Assets will be loaded via AJAX for better performance
        return view('settings.platform-connections.meta-assets', [
            'currentOrg' => $org,
            'connection' => $connection,
            'selectedAssets' => $selectedAssets,
            // Flag for AJAX loading - view will show skeletons and load via API
            'loadViaAjax' => true,
            // Empty arrays for backward compatibility with @foreach loops
            'pages' => [],
            'instagramAccounts' => [],
            'threadsAccounts' => [],
            'adAccounts' => [],
            'pixels' => [],
            'catalogs' => [],
            'whatsappAccounts' => [],
        ]);
    }

    /**
     * Store selected Meta assets for a connection (supports multi-select).
     */
    public function storeMetaAssets(Request $request, string $org, string $connectionId)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'meta')
            ->firstOrFail();

        // Multi-select asset types
        $multiAssetTypes = ['page', 'instagram_account', 'threads_account', 'ad_account', 'pixel', 'catalog', 'whatsapp_account', 'custom_conversion', 'offline_event_set'];

        // Build validation rules for multi-select
        $rules = [];
        foreach ($multiAssetTypes as $type) {
            $rules[$type] = 'nullable|array';
            $rules[$type . '.*'] = 'nullable|string|max:50';
            $rules['manual_' . $type . '_ids'] = 'nullable|array';
            $rules['manual_' . $type . '_ids.*'] = 'nullable|string|max:50';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Build selected assets (multiple values per type)
        $selectedAssets = [];
        foreach ($multiAssetTypes as $type) {
            $manualKey = 'manual_' . $type . '_ids';

            // Get selected values from checkboxes
            $selectedValues = $request->input($type, []);

            // Get manually entered values
            $manualValues = $request->input($manualKey, []);

            // Merge and deduplicate
            $allValues = array_merge($selectedValues, $manualValues);

            // Normalize ad account IDs if needed
            if ($type === 'ad_account') {
                $allValues = array_map(fn($id) => $this->normalizeAdAccountId($id), $allValues);
            }

            $allValues = array_filter(array_unique($allValues));

            if (!empty($allValues)) {
                $selectedAssets[$type] = array_values($allValues);
            }
        }

        // Debug logging for selected assets
        Log::info('Meta assets save - selectedAssets built', [
            'org_id' => $org,
            'connection_id' => $connectionId,
            'instagram_account' => $selectedAssets['instagram_account'] ?? [],
            'all_types' => array_keys($selectedAssets),
            'counts' => array_map('count', $selectedAssets),
        ]);

        // Update connection metadata
        $metadata = $connection->account_metadata ?? [];
        $metadata['selected_assets'] = $selectedAssets;
        $metadata['assets_updated_at'] = now()->toIso8601String();

        $connection->update(['account_metadata' => $metadata]);

        // Dispatch background job to sync Integration records (non-blocking)
        SyncMetaIntegrationRecords::dispatch($org, $connection->connection_id, $selectedAssets);

        if ($request->wantsJson()) {
            return $this->success([
                'connection' => $connection->fresh(),
                'selected_assets' => $selectedAssets,
            ], "Meta assets configured successfully");
        }

        // Build detailed message with counts
        $assetLabels = [
            'page' => 'Facebook Page',
            'instagram_account' => 'Instagram Account',
            'threads_account' => 'Threads Account',
            'ad_account' => 'Ad Account',
            'pixel' => 'Pixel',
            'catalog' => 'Catalog',
            'whatsapp_account' => 'WhatsApp Account',
            'custom_conversion' => 'Custom Conversion',
            'offline_event_set' => 'Offline Event Set',
        ];

        $assetList = [];
        foreach ($multiAssetTypes as $type) {
            if (!empty($selectedAssets[$type])) {
                $count = count($selectedAssets[$type]);
                $label = $assetLabels[$type];
                $assetList[] = $count === 1 ? $label : "{$count} {$label}s";
            }
        }

        $message = 'Meta assets configured: ' . (count($assetList) > 0 ? implode(', ', $assetList) : 'None');

        return redirect()
            ->route('orgs.settings.platform-connections.index', $org)
            ->with('success', $message);
    }

    /**
     * Get Facebook Pages associated with token.
     */
    private function getMetaPages(string $accessToken): array
    {
        try {
            $response = Http::timeout(30)->get('https://graph.facebook.com/v21.0/me/accounts', [
                'access_token' => $accessToken,
                'fields' => 'id,name,category,picture{url},access_token,instagram_business_account',
                'limit' => 100,
            ]);

            if (!$response->successful()) {
                Log::warning('Failed to fetch Meta pages', ['error' => $response->json('error', [])]);
                return [];
            }

            return array_map(function ($page) {
                return [
                    'id' => $page['id'],
                    'name' => $page['name'] ?? 'Unknown Page',
                    'category' => $page['category'] ?? null,
                    'picture' => $page['picture']['data']['url'] ?? null,
                    'has_instagram' => isset($page['instagram_business_account']),
                    'instagram_id' => $page['instagram_business_account']['id'] ?? null,
                ];
            }, $response->json('data', []));
        } catch (\Exception $e) {
            Log::error('Failed to fetch Meta pages', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get Instagram Business accounts associated with token.
     */
    private function getMetaInstagramAccounts(string $accessToken, array $pages = []): array
    {
        $instagramAccounts = [];

        try {
            // First, get from pages that have connected Instagram
            foreach ($pages as $page) {
                if (!empty($page['instagram_id'])) {
                    // Fetch Instagram account details
                    $response = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$page['instagram_id']}", [
                        'access_token' => $accessToken,
                        'fields' => 'id,username,name,profile_picture_url,followers_count,media_count',
                    ]);

                    if ($response->successful()) {
                        $igData = $response->json();
                        $instagramAccounts[] = [
                            'id' => $igData['id'],
                            'username' => $igData['username'] ?? null,
                            'name' => $igData['name'] ?? $igData['username'] ?? 'Unknown',
                            'profile_picture' => $igData['profile_picture_url'] ?? null,
                            'followers_count' => $igData['followers_count'] ?? 0,
                            'media_count' => $igData['media_count'] ?? 0,
                            'connected_page_id' => $page['id'],
                            'connected_page_name' => $page['name'],
                        ];
                    }
                }
            }

            // Also try direct Instagram business accounts query
            $directResponse = Http::timeout(30)->get('https://graph.facebook.com/v21.0/me/instagram_accounts', [
                'access_token' => $accessToken,
                'fields' => 'id,username,name,profile_picture_url,followers_count',
                'limit' => 50,
            ]);

            if ($directResponse->successful()) {
                foreach ($directResponse->json('data', []) as $igAccount) {
                    // Avoid duplicates
                    $existingIds = array_column($instagramAccounts, 'id');
                    if (!in_array($igAccount['id'], $existingIds)) {
                        $instagramAccounts[] = [
                            'id' => $igAccount['id'],
                            'username' => $igAccount['username'] ?? null,
                            'name' => $igAccount['name'] ?? $igAccount['username'] ?? 'Unknown',
                            'profile_picture' => $igAccount['profile_picture_url'] ?? null,
                            'followers_count' => $igAccount['followers_count'] ?? 0,
                            'media_count' => 0,
                            'connected_page_id' => null,
                            'connected_page_name' => null,
                        ];
                    }
                }
            }

            return $instagramAccounts;
        } catch (\Exception $e) {
            Log::error('Failed to fetch Instagram accounts', ['error' => $e->getMessage()]);
            return $instagramAccounts;
        }
    }

    /**
     * Get Meta Pixels from ad accounts.
     */
    private function getMetaPixels(string $accessToken, array $adAccounts = []): array
    {
        $pixels = [];

        Log::info('Fetching Meta Pixels', ['ad_accounts_count' => count($adAccounts)]);

        if (empty($adAccounts)) {
            Log::warning('No ad accounts provided for pixel lookup');
            return $pixels;
        }

        try {
            foreach ($adAccounts as $account) {
                $accountId = $account['id'] ?? null;
                if (!$accountId) {
                    Log::warning('Ad account missing ID', ['account' => $account]);
                    continue;
                }

                // Ensure account ID has act_ prefix for ad account endpoints
                $formattedAccountId = str_starts_with($accountId, 'act_') ? $accountId : 'act_' . $accountId;
                Log::info('Fetching pixels for ad account', ['account_id' => $formattedAccountId]);

                // Use only basic fields - some fields may be deprecated or require advanced access
                $response = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$formattedAccountId}/adspixels", [
                    'access_token' => $accessToken,
                    'fields' => 'id,name,creation_time,last_fired_time',
                    'limit' => 50,
                ]);

                Log::info('Pixels API response', [
                    'account_id' => $formattedAccountId,
                    'status' => $response->status(),
                    'successful' => $response->successful(),
                ]);

                if ($response->successful()) {
                    $pixelData = $response->json('data', []);
                    Log::info('Pixels found for ad account', [
                        'account_id' => $formattedAccountId,
                        'pixels_count' => count($pixelData),
                    ]);
                    foreach ($pixelData as $pixel) {
                        // Avoid duplicates
                        $existingIds = array_column($pixels, 'id');
                        if (!in_array($pixel['id'], $existingIds)) {
                            $pixels[] = [
                                'id' => $pixel['id'],
                                'name' => $pixel['name'] ?? 'Unnamed Pixel',
                                'ad_account_id' => $accountId,
                                'ad_account_name' => $account['name'] ?? 'Unknown',
                                'creation_time' => $pixel['creation_time'] ?? null,
                                'last_fired_time' => $pixel['last_fired_time'] ?? null,
                            ];
                        }
                    }
                } else {
                    Log::warning('Failed to fetch pixels for ad account', [
                        'ad_account_id' => $formattedAccountId,
                        'status' => $response->status(),
                        'error' => $response->json('error.message'),
                        'error_code' => $response->json('error.code'),
                        'error_type' => $response->json('error.type'),
                        'error_subcode' => $response->json('error.error_subcode'),
                    ]);
                }
            }

            return $pixels;
        } catch (\Exception $e) {
            Log::error('Failed to fetch Meta pixels', ['error' => $e->getMessage()]);
            return $pixels;
        }
    }

    /**
     * Get Product Catalogs.
     *
     * Note: /me/businesses often returns empty for System Users even when businesses exist.
     * Workaround: Extract business IDs from ad accounts which include the business field.
     *
     * @see https://developers.facebook.com/docs/marketing-api/reference/product-catalog
     */
    private function getMetaCatalogs(string $accessToken): array
    {
        $catalogs = [];
        $businesses = [];

        try {
            // Step 1: Try /me/businesses first
            $businessesResponse = Http::timeout(15)->get('https://graph.facebook.com/v21.0/me/businesses', [
                'access_token' => $accessToken,
                'fields' => 'id,name',
                'limit' => 50,
            ]);

            if ($businessesResponse->successful()) {
                $businesses = $businessesResponse->json('data', []);
                Log::info('Fetched businesses from /me/businesses', ['count' => count($businesses)]);
            }

            // Step 2: If /me/businesses is empty, extract business IDs from ad accounts
            // This is a workaround because /me/businesses often returns empty for System Users
            if (empty($businesses)) {
                Log::info('No businesses from /me/businesses, trying to extract from ad accounts');

                $adAccountsResponse = Http::timeout(15)->get('https://graph.facebook.com/v21.0/me/adaccounts', [
                    'access_token' => $accessToken,
                    'fields' => 'id,name,business{id,name}',
                    'limit' => 50,
                ]);

                if ($adAccountsResponse->successful()) {
                    $seenBusinessIds = [];
                    foreach ($adAccountsResponse->json('data', []) as $adAccount) {
                        $business = $adAccount['business'] ?? null;
                        if ($business && !empty($business['id']) && !in_array($business['id'], $seenBusinessIds)) {
                            $businesses[] = [
                                'id' => $business['id'],
                                'name' => $business['name'] ?? 'Unknown Business',
                            ];
                            $seenBusinessIds[] = $business['id'];
                        }
                    }
                    Log::info('Extracted businesses from ad accounts', ['count' => count($businesses)]);
                }
            }

            if (empty($businesses)) {
                Log::warning('No businesses found for catalog lookup');
                return $catalogs;
            }

            // Step 3: For each business, fetch owned product catalogs
            foreach ($businesses as $business) {
                $businessId = $business['id'] ?? null;
                $businessName = $business['name'] ?? 'Unknown Business';
                if (!$businessId) continue;

                $catalogResponse = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$businessId}/owned_product_catalogs", [
                    'access_token' => $accessToken,
                    'fields' => 'id,name,product_count,vertical',
                    'limit' => 50,
                ]);

                if ($catalogResponse->successful()) {
                    foreach ($catalogResponse->json('data', []) as $catalog) {
                        $existingIds = array_column($catalogs, 'id');
                        if (!in_array($catalog['id'], $existingIds)) {
                            $catalogs[] = [
                                'id' => $catalog['id'],
                                'name' => $catalog['name'] ?? 'Unnamed Catalog',
                                'product_count' => $catalog['product_count'] ?? 0,
                                'vertical' => $catalog['vertical'] ?? 'commerce',
                                'business_id' => $businessId,
                                'business_name' => $businessName,
                            ];
                        }
                    }
                    Log::info('Fetched catalogs for business', [
                        'business_id' => $businessId,
                        'business_name' => $businessName,
                        'catalogs_count' => count($catalogResponse->json('data', [])),
                    ]);
                } else {
                    Log::warning('Failed to fetch catalogs for business', [
                        'business_id' => $businessId,
                        'business_name' => $businessName,
                        'status' => $catalogResponse->status(),
                        'error' => $catalogResponse->json('error.message'),
                        'error_code' => $catalogResponse->json('error.code'),
                    ]);
                }
            }

            // Step 4: Also try client catalogs (catalogs shared with you)
            foreach ($businesses as $business) {
                $businessId = $business['id'] ?? null;
                if (!$businessId) continue;

                $clientResponse = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$businessId}/client_product_catalogs", [
                    'access_token' => $accessToken,
                    'fields' => 'id,name,product_count,vertical',
                    'limit' => 50,
                ]);

                if ($clientResponse->successful()) {
                    foreach ($clientResponse->json('data', []) as $catalog) {
                        $existingIds = array_column($catalogs, 'id');
                        if (!in_array($catalog['id'], $existingIds)) {
                            $catalogs[] = [
                                'id' => $catalog['id'],
                                'name' => $catalog['name'] ?? 'Unnamed Catalog',
                                'product_count' => $catalog['product_count'] ?? 0,
                                'vertical' => $catalog['vertical'] ?? 'commerce',
                                'business_id' => $businessId,
                                'business_name' => $business['name'] ?? 'Unknown',
                                'is_client_catalog' => true,
                            ];
                        }
                    }
                }
            }

            Log::info('Total catalogs fetched', ['count' => count($catalogs)]);
            return $catalogs;
        } catch (\Exception $e) {
            Log::error('Failed to fetch Meta catalogs', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get all WhatsApp Business phone numbers from Meta Business Manager.
     * These are needed for Click-to-WhatsApp (CTWA) advertising campaigns.
     *
     * @see https://developers.facebook.com/docs/whatsapp/business-management-api/phone-numbers
     */
    private function getMetaWhatsappAccounts(string $accessToken): array
    {
        $whatsappAccounts = [];
        $businesses = [];

        try {
            // Step 1: Get businesses (same pattern as catalogs)
            $businessesResponse = Http::timeout(15)->get('https://graph.facebook.com/v21.0/me/businesses', [
                'access_token' => $accessToken,
                'fields' => 'id,name',
                'limit' => 50,
            ]);

            if ($businessesResponse->successful()) {
                $businesses = $businessesResponse->json('data', []);
                Log::info('Fetched businesses for WhatsApp lookup', ['count' => count($businesses)]);
            }

            // Step 2: If /me/businesses is empty, extract from ad accounts
            if (empty($businesses)) {
                Log::info('No businesses from /me/businesses, trying to extract from ad accounts for WhatsApp');

                $adAccountsResponse = Http::timeout(15)->get('https://graph.facebook.com/v21.0/me/adaccounts', [
                    'access_token' => $accessToken,
                    'fields' => 'id,business{id,name}',
                    'limit' => 50,
                ]);

                if ($adAccountsResponse->successful()) {
                    $seenBusinessIds = [];
                    foreach ($adAccountsResponse->json('data', []) as $adAccount) {
                        $business = $adAccount['business'] ?? null;
                        if ($business && !empty($business['id']) && !in_array($business['id'], $seenBusinessIds)) {
                            $businesses[] = [
                                'id' => $business['id'],
                                'name' => $business['name'] ?? 'Unknown Business',
                            ];
                            $seenBusinessIds[] = $business['id'];
                        }
                    }
                    Log::info('Extracted businesses from ad accounts for WhatsApp', ['count' => count($businesses)]);
                }
            }

            if (empty($businesses)) {
                Log::warning('No businesses found for WhatsApp lookup');
                return $whatsappAccounts;
            }

            // Step 3: For each business, fetch owned WhatsApp Business Accounts
            foreach ($businesses as $business) {
                $businessId = $business['id'] ?? null;
                $businessName = $business['name'] ?? 'Unknown Business';
                if (!$businessId) continue;

                $wabaResponse = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$businessId}/owned_whatsapp_business_accounts", [
                    'access_token' => $accessToken,
                    'fields' => 'id,name,phone_numbers{id,display_phone_number,verified_name,quality_rating,code_verification_status}',
                    'limit' => 50,
                ]);

                if ($wabaResponse->successful()) {
                    $wabas = $wabaResponse->json('data', []);
                    Log::info('Fetched WABAs for business', [
                        'business_id' => $businessId,
                        'business_name' => $businessName,
                        'waba_count' => count($wabas),
                    ]);

                    foreach ($wabas as $waba) {
                        $wabaId = $waba['id'] ?? null;
                        $wabaName = $waba['name'] ?? 'Unnamed WABA';
                        $phoneNumbers = $waba['phone_numbers']['data'] ?? [];

                        foreach ($phoneNumbers as $phone) {
                            $existingIds = array_column($whatsappAccounts, 'id');
                            if (!in_array($phone['id'], $existingIds)) {
                                $whatsappAccounts[] = [
                                    'id' => $phone['id'],
                                    'display_phone_number' => $phone['display_phone_number'] ?? '',
                                    'verified_name' => $phone['verified_name'] ?? '',
                                    'quality_rating' => $phone['quality_rating'] ?? null,
                                    'code_verification_status' => $phone['code_verification_status'] ?? null,
                                    'waba_id' => $wabaId,
                                    'waba_name' => $wabaName,
                                    'business_id' => $businessId,
                                    'business_name' => $businessName,
                                ];
                            }
                        }
                    }
                } else {
                    Log::warning('Failed to fetch WhatsApp accounts for business', [
                        'business_id' => $businessId,
                        'business_name' => $businessName,
                        'status' => $wabaResponse->status(),
                        'error' => $wabaResponse->json('error.message'),
                        'error_code' => $wabaResponse->json('error.code'),
                    ]);
                }
            }

            Log::info('Total WhatsApp phone numbers fetched', ['count' => count($whatsappAccounts)]);
            return $whatsappAccounts;
        } catch (\Exception $e) {
            Log::error('Failed to fetch Meta WhatsApp accounts', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Normalize ad account ID to include act_ prefix.
     */
    private function normalizeAdAccountId(?string $id): ?string
    {
        if (!$id) return null;
        return str_starts_with($id, 'act_') ? $id : 'act_' . $id;
    }

    // ===== Generic Platform Assets Methods =====

    /**
     * Show asset selection page for LinkedIn connection.
     */
    public function selectLinkedInAssets(Request $request, string $org, string $connectionId)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'linkedin')
            ->firstOrFail();

        $selectedAssets = $connection->account_metadata['selected_assets'] ?? [];

        // For now, return empty arrays - these will be populated via API calls when implemented
        return view('settings.platform-connections.linkedin-assets', [
            'currentOrg' => $org,
            'connection' => $connection,
            'profiles' => [],
            'pages' => [],
            'adAccounts' => [],
            'insightTags' => [],
            'selectedAssets' => $selectedAssets,
        ]);
    }

    /**
     * Store selected LinkedIn assets.
     */
    public function storeLinkedInAssets(Request $request, string $org, string $connectionId)
    {
        return $this->storeGenericPlatformAssets($request, $org, $connectionId, 'linkedin', [
            'profile', 'page', 'ad_account', 'pixel'
        ]);
    }

    /**
     * Show asset selection page for Twitter/X connection.
     */
    public function selectTwitterAssets(Request $request, string $org, string $connectionId)
    {
        return $this->showGenericPlatformAssets($request, $org, $connectionId, 'twitter');
    }

    /**
     * Store selected Twitter/X assets.
     */
    public function storeTwitterAssets(Request $request, string $org, string $connectionId)
    {
        return $this->storeGenericPlatformAssets($request, $org, $connectionId, 'twitter', [
            'account', 'ad_account', 'pixel', 'catalog'
        ]);
    }

    /**
     * Show asset selection page for TikTok connection.
     */
    public function selectTikTokAssets(Request $request, string $org, string $connectionId)
    {
        return $this->showGenericPlatformAssets($request, $org, $connectionId, 'tiktok');
    }

    /**
     * Store selected TikTok assets.
     */
    public function storeTikTokAssets(Request $request, string $org, string $connectionId)
    {
        return $this->storeGenericPlatformAssets($request, $org, $connectionId, 'tiktok', [
            'account', 'ad_account', 'pixel', 'catalog'
        ]);
    }

    /**
     * Show asset selection page for Snapchat connection.
     */
    public function selectSnapchatAssets(Request $request, string $org, string $connectionId)
    {
        return $this->showGenericPlatformAssets($request, $org, $connectionId, 'snapchat');
    }

    /**
     * Store selected Snapchat assets.
     */
    public function storeSnapchatAssets(Request $request, string $org, string $connectionId)
    {
        return $this->storeGenericPlatformAssets($request, $org, $connectionId, 'snapchat', [
            'account', 'ad_account', 'pixel', 'catalog'
        ]);
    }

    /**
     * Show asset selection page for Pinterest connection.
     */
    public function selectPinterestAssets(Request $request, string $org, string $connectionId)
    {
        return $this->showGenericPlatformAssets($request, $org, $connectionId, 'pinterest');
    }

    /**
     * Store selected Pinterest assets.
     */
    public function storePinterestAssets(Request $request, string $org, string $connectionId)
    {
        return $this->storeGenericPlatformAssets($request, $org, $connectionId, 'pinterest', [
            'account', 'ad_account', 'pixel', 'catalog'
        ]);
    }

    /**
     * Show asset selection page for YouTube connection.
     */
    public function selectYouTubeAssets(Request $request, string $org, string $connectionId)
    {
        return $this->showGenericPlatformAssets($request, $org, $connectionId, 'youtube');
    }

    /**
     * Store selected YouTube assets.
     */
    public function storeYouTubeAssets(Request $request, string $org, string $connectionId)
    {
        return $this->storeGenericPlatformAssets($request, $org, $connectionId, 'youtube', [
            'channel'
        ]);
    }

    /**
     * Show asset selection page for Google connection.
     * Assets are loaded progressively via AJAX for faster page render.
     */
    public function selectGoogleAssets(Request $request, string $org, string $connectionId)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'google')
            ->firstOrFail();

        // Only pass connection info - assets loaded via AJAX
        return view('settings.platform-connections.google-assets', [
            'currentOrg' => $org,
            'connection' => $connection,
            'selectedAssets' => $connection->account_metadata['selected_assets'] ?? [],
        ]);
    }

    /**
     * Store selected Google assets (supports multi-select for all asset types).
     */
    public function storeGoogleAssets(Request $request, string $org, string $connectionId)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'google')
            ->firstOrFail();

        // Multi-select asset types
        $multiAssetTypes = [
            'youtube_channel',
            'google_ads',
            'analytics',
            'business_profile',
            'tag_manager',
            'merchant_center',
            'search_console',
            'calendar',
        ];

        // Build validation rules
        $rules = [
            'include_my_drive' => 'nullable|boolean',
            'shared_drives' => 'nullable|array',
            'shared_drives.*' => 'nullable|string|max:255',
            'manual_drives' => 'nullable|array',
            'manual_drives.*' => 'nullable|string|max:255',
        ];

        // Add validation for multi-select asset types
        foreach ($multiAssetTypes as $type) {
            $rules[$type] = 'nullable|array';
            $rules[$type . '.*'] = 'nullable|string|max:255';
            $rules['manual_' . $type . '_ids'] = 'nullable|array';
            $rules['manual_' . $type . '_ids.*'] = 'nullable|string|max:255';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Build selected assets (multiple values per type)
        $selectedAssets = [];
        foreach ($multiAssetTypes as $type) {
            $manualKey = 'manual_' . $type . '_ids';

            // Get selected values from checkboxes
            $selectedValues = $request->input($type, []);

            // Get manually entered values
            $manualValues = $request->input($manualKey, []);

            // Merge and deduplicate
            $allValues = array_merge($selectedValues, $manualValues);
            $allValues = array_filter(array_unique($allValues));

            if (!empty($allValues)) {
                $selectedAssets[$type] = array_values($allValues);
            }
        }

        // Handle Google Drive multi-select
        $selectedAssets['include_my_drive'] = (bool) $request->input('include_my_drive', false);

        // Get shared drives
        $sharedDrives = $request->input('shared_drives', []);
        $selectedAssets['shared_drives'] = array_values(array_filter(array_unique($sharedDrives)));

        // Add manually entered drives
        $manualDrives = array_filter($request->input('manual_drives', []));
        $selectedAssets['manual_drives'] = array_values(array_unique($manualDrives));

        // Update connection metadata
        $metadata = $connection->account_metadata ?? [];
        $metadata['selected_assets'] = $selectedAssets;
        $metadata['assets_updated_at'] = now()->toIso8601String();

        $connection->update(['account_metadata' => $metadata]);

        // Create/update Integration records for each selected asset
        $this->syncGoogleIntegrationRecords($org, $connection, $selectedAssets);

        // Build success message with counts
        $assetLabels = [
            'youtube_channel' => 'YouTube Channel',
            'google_ads' => 'Google Ads',
            'analytics' => 'Analytics',
            'business_profile' => 'Business Profile',
            'tag_manager' => 'Tag Manager',
            'merchant_center' => 'Merchant Center',
            'search_console' => 'Search Console',
            'calendar' => 'Calendar',
        ];

        $selectedList = [];
        foreach ($multiAssetTypes as $type) {
            if (!empty($selectedAssets[$type])) {
                $count = count($selectedAssets[$type]);
                $label = $assetLabels[$type] ?? ucfirst(str_replace('_', ' ', $type));
                $selectedList[] = $count === 1 ? $label : "{$count} {$label}s";
            }
        }

        // Add Drive to list if selected
        $driveCount = count($selectedAssets['shared_drives'] ?? []) + count($selectedAssets['manual_drives'] ?? []);
        if ($selectedAssets['include_my_drive'] || $driveCount > 0) {
            $driveLabel = 'Drive';
            if ($selectedAssets['include_my_drive']) {
                $driveLabel .= ' (My Drive';
                if ($driveCount > 0) {
                    $driveLabel .= " + {$driveCount} shared";
                }
                $driveLabel .= ')';
            } elseif ($driveCount > 0) {
                $driveLabel .= " ({$driveCount} shared)";
            }
            $selectedList[] = $driveLabel;
        }

        $message = 'Google assets configured: ' . (count($selectedList) > 0 ? implode(', ', $selectedList) : 'None');

        if ($request->wantsJson()) {
            return $this->success([
                'connection' => $connection->fresh(),
                'selected_assets' => $selectedAssets,
            ], $message);
        }

        return redirect()
            ->route('orgs.settings.platform-connections.index', $org)
            ->with('success', $message);
    }

    // ==================== GOOGLE ASSET FETCHING METHODS ====================

    /**
     * Get a valid Google access token, refreshing if necessary.
     */
    private function getValidGoogleAccessToken(PlatformConnection $connection): ?string
    {
        $accessToken = $connection->access_token;

        // Check if token is expired and we have a refresh token
        if ($connection->token_expires_at && $connection->token_expires_at->isPast() && $connection->refresh_token) {
            $config = config('social-platforms.google');

            try {
                $response = Http::asForm()->post($config['token_url'], [
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'refresh_token' => $connection->refresh_token,
                    'grant_type' => 'refresh_token',
                ]);

                if ($response->successful()) {
                    $tokenData = $response->json();
                    $connection->update([
                        'access_token' => $tokenData['access_token'],
                        'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
                    ]);
                    $accessToken = $tokenData['access_token'];
                } else {
                    Log::warning('Failed to refresh Google token', ['response' => $response->json()]);
                }
            } catch (\Exception $e) {
                Log::error('Exception refreshing Google token', ['error' => $e->getMessage()]);
            }
        }

        return $accessToken;
    }

    /**
     * Get YouTube channels associated with Google connection.
     * This fetches both personal channels and brand account channels the user manages.
     */
    private function getGoogleYouTubeChannels(PlatformConnection $connection): array
    {
        try {
            $accessToken = $this->getValidGoogleAccessToken($connection);
            if (!$accessToken) return [];

            $channels = [];
            $channelIds = [];

            // 1. Get the user's own channel (personal)
            $mineResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet,statistics,contentDetails,brandingSettings',
                    'mine' => 'true',
                ]);

            if ($mineResponse->successful()) {
                foreach ($mineResponse->json('items', []) as $channel) {
                    $channelId = $channel['id'];
                    if (!in_array($channelId, $channelIds)) {
                        $channelIds[] = $channelId;
                        $channels[] = $this->formatYouTubeChannel($channel, 'personal');
                    }
                }
            }

            // 2. Try to get channels from YouTube Studio API (brand accounts)
            // This uses an undocumented but widely-used endpoint
            $delegateResponse = Http::withToken($accessToken)
                ->withHeaders(['X-Origin' => 'https://www.youtube.com'])
                ->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet,statistics,contentDetails,brandingSettings',
                    'managedByMe' => 'true',
                ]);

            if ($delegateResponse->successful()) {
                foreach ($delegateResponse->json('items', []) as $channel) {
                    $channelId = $channel['id'];
                    if (!in_array($channelId, $channelIds)) {
                        $channelIds[] = $channelId;
                        $channels[] = $this->formatYouTubeChannel($channel, 'managed');
                    }
                }
            }

            // 3. Try to get channels via the accounts/delegated endpoint
            // Get list of accessible accounts through Google Account
            $accountsResponse = Http::withToken($accessToken)
                ->get('https://youtube.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet,statistics',
                    'forHandle' => '@' . ($connection->account_metadata['email'] ?? ''),
                ]);

            // This likely won't work but worth trying

            // 4. Alternative: Check subscriptions for owned channels (channels user uploaded to)
            $uploadsResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/youtube/v3/search', [
                    'part' => 'snippet',
                    'forMine' => 'true',
                    'type' => 'channel',
                    'maxResults' => 50,
                ]);

            // 5. Get channels from playlists (each channel has default playlists)
            $playlistsResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/youtube/v3/playlists', [
                    'part' => 'snippet',
                    'mine' => 'true',
                    'maxResults' => 50,
                ]);

            if ($playlistsResponse->successful()) {
                $playlistChannelIds = [];
                foreach ($playlistsResponse->json('items', []) as $playlist) {
                    $playlistChannelId = $playlist['snippet']['channelId'] ?? null;
                    if ($playlistChannelId && !in_array($playlistChannelId, $channelIds) && !in_array($playlistChannelId, $playlistChannelIds)) {
                        $playlistChannelIds[] = $playlistChannelId;
                    }
                }

                // Fetch details for these channels
                if (!empty($playlistChannelIds)) {
                    $channelDetailsResponse = Http::withToken($accessToken)
                        ->get('https://www.googleapis.com/youtube/v3/channels', [
                            'part' => 'snippet,statistics,contentDetails,brandingSettings',
                            'id' => implode(',', $playlistChannelIds),
                        ]);

                    if ($channelDetailsResponse->successful()) {
                        foreach ($channelDetailsResponse->json('items', []) as $channel) {
                            $channelId = $channel['id'];
                            if (!in_array($channelId, $channelIds)) {
                                $channelIds[] = $channelId;
                                $channels[] = $this->formatYouTubeChannel($channel, 'brand');
                            }
                        }
                    }
                }
            }

            // 6. Check activities to find channels user has access to
            $activitiesResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/youtube/v3/activities', [
                    'part' => 'snippet',
                    'mine' => 'true',
                    'maxResults' => 50,
                ]);

            if ($activitiesResponse->successful()) {
                $activityChannelIds = [];
                foreach ($activitiesResponse->json('items', []) as $activity) {
                    $activityChannelId = $activity['snippet']['channelId'] ?? null;
                    if ($activityChannelId && !in_array($activityChannelId, $channelIds) && !in_array($activityChannelId, $activityChannelIds)) {
                        $activityChannelIds[] = $activityChannelId;
                    }
                }

                // Fetch details for activity channels
                if (!empty($activityChannelIds)) {
                    $channelDetailsResponse = Http::withToken($accessToken)
                        ->get('https://www.googleapis.com/youtube/v3/channels', [
                            'part' => 'snippet,statistics,contentDetails,brandingSettings',
                            'id' => implode(',', array_slice($activityChannelIds, 0, 50)),
                        ]);

                    if ($channelDetailsResponse->successful()) {
                        foreach ($channelDetailsResponse->json('items', []) as $channel) {
                            $channelId = $channel['id'];
                            if (!in_array($channelId, $channelIds)) {
                                $channelIds[] = $channelId;
                                $channels[] = $this->formatYouTubeChannel($channel, 'brand');
                            }
                        }
                    }
                }
            }

            return $channels;
        } catch (\Exception $e) {
            Log::error('Exception fetching YouTube channels', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Format a YouTube channel response into a standardized array.
     */
    private function formatYouTubeChannel(array $channel, string $type = 'personal'): array
    {
        return [
            'id' => $channel['id'],
            'title' => $channel['snippet']['title'] ?? 'Unknown Channel',
            'description' => Str::limit($channel['snippet']['description'] ?? '', 100),
            'thumbnail' => $channel['snippet']['thumbnails']['default']['url'] ?? null,
            'subscriber_count' => $channel['statistics']['subscriberCount'] ?? 0,
            'video_count' => $channel['statistics']['videoCount'] ?? 0,
            'view_count' => $channel['statistics']['viewCount'] ?? 0,
            'custom_url' => $channel['snippet']['customUrl'] ?? null,
            'type' => $type, // personal, managed, brand
        ];
    }

    /**
     * Get Google Ads accounts.
     *
     * @return array{accounts: array, error: array|null}
     */
    private function getGoogleAdsAccounts(PlatformConnection $connection): array
    {
        try {
            $accessToken = $this->getValidGoogleAccessToken($connection);
            if (!$accessToken) {
                return ['accounts' => [], 'error' => null];
            }

            $developerToken = config('services.google_ads.developer_token');
            if (!$developerToken) {
                return [
                    'accounts' => [],
                    'error' => [
                        'type' => 'missing_developer_token',
                        'message' => 'Google Ads Developer Token not configured',
                    ],
                ];
            }

            // List accessible customers using Google Ads API
            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'developer-token' => $developerToken,
                ])
                ->get('https://googleads.googleapis.com/v18/customers:listAccessibleCustomers');

            if (!$response->successful()) {
                $body = $response->json();
                $error = $body['error'] ?? [];

                Log::warning('Google Ads listAccessibleCustomers API failed', [
                    'status' => $response->status(),
                    'body' => $body,
                ]);

                // Check for developer token issues
                if ($response->status() === 401 || $response->status() === 403) {
                    $reason = $error['details'][0]['errors'][0]['errorCode']['authenticationError'] ?? null;
                    if ($reason === 'DEVELOPER_TOKEN_NOT_APPROVED') {
                        return [
                            'accounts' => [],
                            'error' => [
                                'type' => 'developer_token_not_approved',
                                'message' => 'Developer Token is not approved. Apply for Basic or Standard access.',
                            ],
                        ];
                    }
                    if (str_contains($error['message'] ?? '', 'developer token')) {
                        return [
                            'accounts' => [],
                            'error' => [
                                'type' => 'developer_token_invalid',
                                'message' => $error['message'] ?? 'Developer Token issue',
                            ],
                        ];
                    }
                }

                // Check for 501 UNIMPLEMENTED - API not enabled or token not approved
                if ($response->status() === 501) {
                    return [
                        'accounts' => [],
                        'error' => [
                            'type' => 'api_not_enabled',
                            'message' => $error['message'] ?? 'Google Ads API not enabled or Developer Token not approved',
                        ],
                    ];
                }

                return ['accounts' => [], 'error' => null];
            }

            $resourceNames = $response->json('resourceNames', []);
            if (empty($resourceNames)) {
                return ['accounts' => [], 'error' => null];
            }

            // Fetch details for each customer
            $accounts = [];
            foreach ($resourceNames as $resourceName) {
                // Extract customer ID from resource name (format: customers/1234567890)
                $customerId = str_replace('customers/', '', $resourceName);

                // Get customer details using GAQL query
                $detailResponse = Http::withToken($accessToken)
                    ->withHeaders([
                        'developer-token' => $developerToken,
                    ])
                    ->post("https://googleads.googleapis.com/v18/customers/{$customerId}/googleAds:searchStream", [
                        'query' => "SELECT customer.id, customer.descriptive_name, customer.currency_code, customer.status FROM customer LIMIT 1",
                    ]);

                if ($detailResponse->successful()) {
                    $results = $detailResponse->json();
                    // searchStream returns array of batches
                    $customer = $results[0]['results'][0]['customer'] ?? null;
                    if ($customer) {
                        $accounts[] = [
                            'id' => $customer['id'] ?? $customerId,
                            'name' => $customer['descriptiveName'] ?? "Account {$customerId}",
                            'descriptive_name' => $customer['descriptiveName'] ?? '',
                            'currency' => $customer['currencyCode'] ?? null,
                            'status' => $customer['status'] ?? 'UNKNOWN',
                        ];
                    }
                } else {
                    // If we can't get details, still include the customer ID
                    $accounts[] = [
                        'id' => $customerId,
                        'name' => "Account {$customerId}",
                        'descriptive_name' => '',
                        'currency' => null,
                        'status' => 'UNKNOWN',
                    ];
                }
            }

            return ['accounts' => $accounts, 'error' => null];
        } catch (\Exception $e) {
            Log::error('Exception fetching Google Ads accounts', ['error' => $e->getMessage()]);
            return ['accounts' => [], 'error' => ['type' => 'exception', 'message' => $e->getMessage()]];
        }
    }

    /**
     * Get Google Analytics properties (GA4).
     */
    private function getGoogleAnalyticsProperties(PlatformConnection $connection): array
    {
        try {
            $accessToken = $this->getValidGoogleAccessToken($connection);
            if (!$accessToken) return [];

            // First get account summaries
            $response = Http::withToken($accessToken)
                ->get('https://analyticsadmin.googleapis.com/v1beta/accountSummaries');

            if (!$response->successful()) {
                Log::warning('Analytics accountSummaries API failed', ['status' => $response->status(), 'body' => $response->json()]);
                return [];
            }

            $properties = [];
            foreach ($response->json('accountSummaries', []) as $account) {
                foreach ($account['propertySummaries'] ?? [] as $property) {
                    $properties[] = [
                        'id' => $property['property'] ?? '',
                        'displayName' => $property['displayName'] ?? 'Unknown Property',
                        'accountName' => $account['displayName'] ?? '',
                        'propertyType' => $property['propertyType'] ?? 'PROPERTY_TYPE_ORDINARY',
                    ];
                }
            }

            return $properties;
        } catch (\Exception $e) {
            Log::error('Exception fetching Analytics properties', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get Google Business Profile locations.
     *
     * @return array{profiles: array, error: array|null}
     */
    private function getGoogleBusinessProfiles(PlatformConnection $connection): array
    {
        try {
            $accessToken = $this->getValidGoogleAccessToken($connection);
            if (!$accessToken) {
                return ['profiles' => [], 'error' => null];
            }

            // First get accounts
            $accountsResponse = Http::withToken($accessToken)
                ->get('https://mybusinessaccountmanagement.googleapis.com/v1/accounts');

            if (!$accountsResponse->successful()) {
                $body = $accountsResponse->json();
                $error = $body['error'] ?? [];

                Log::warning('Business Profile accounts API failed', ['status' => $accountsResponse->status(), 'body' => $body]);

                // Check specifically for quota errors (429)
                if ($accountsResponse->status() === 429) {
                    // Extract project number from error details
                    $projectNumber = null;
                    foreach ($error['details'] ?? [] as $detail) {
                        if (($detail['@type'] ?? '') === 'type.googleapis.com/google.rpc.ErrorInfo') {
                            $projectNumber = $detail['metadata']['consumer'] ?? null;
                            if ($projectNumber) {
                                $projectNumber = str_replace('projects/', '', $projectNumber);
                            }
                            break;
                        }
                    }

                    return [
                        'profiles' => [],
                        'error' => [
                            'type' => 'quota_exceeded',
                            'message' => $error['message'] ?? 'Quota exceeded',
                            'project' => $projectNumber,
                        ],
                    ];
                }

                return ['profiles' => [], 'error' => null];
            }

            $profiles = [];
            foreach ($accountsResponse->json('accounts', []) as $account) {
                $accountName = $account['name'] ?? '';

                // Get locations for this account
                // Note: readMask uses valid field names from Location resource
                // 'categories' contains primary and additional categories
                $locationsResponse = Http::withToken($accessToken)
                    ->get("https://mybusinessbusinessinformation.googleapis.com/v1/{$accountName}/locations", [
                        'readMask' => 'name,title,storefrontAddress,categories',
                    ]);

                if ($locationsResponse->successful()) {
                    foreach ($locationsResponse->json('locations', []) as $location) {
                        $address = $location['storefrontAddress'] ?? [];
                        // Primary category is the first in categories.primaryCategory
                        $primaryCategory = $location['categories']['primaryCategory']['displayName'] ?? '';
                        $profiles[] = [
                            'id' => $location['name'] ?? '',
                            'name' => $location['title'] ?? 'Unknown Location',
                            'address' => implode(', ', array_filter([
                                $address['addressLines'][0] ?? '',
                                $address['locality'] ?? '',
                                $address['administrativeArea'] ?? '',
                            ])),
                            'primaryCategory' => $primaryCategory,
                        ];
                    }
                } else {
                    // Log non-successful responses for debugging
                    Log::warning('Business Profile locations API failed for account', [
                        'account' => $accountName,
                        'status' => $locationsResponse->status(),
                        'body' => $locationsResponse->json(),
                    ]);
                }
            }

            return ['profiles' => $profiles, 'error' => null];
        } catch (\Exception $e) {
            Log::error('Exception fetching Business Profiles', ['error' => $e->getMessage()]);
            return ['profiles' => [], 'error' => ['type' => 'exception', 'message' => $e->getMessage()]];
        }
    }

    /**
     * Get Google Tag Manager containers.
     */
    private function getGoogleTagManagerContainers(PlatformConnection $connection): array
    {
        try {
            $accessToken = $this->getValidGoogleAccessToken($connection);
            if (!$accessToken) return [];

            // First get accounts
            $accountsResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/tagmanager/v2/accounts');

            if (!$accountsResponse->successful()) {
                Log::warning('Tag Manager accounts API failed', ['status' => $accountsResponse->status(), 'body' => $accountsResponse->json()]);
                return [];
            }

            $containers = [];
            foreach ($accountsResponse->json('account', []) as $account) {
                $accountPath = $account['path'] ?? '';

                // Get containers for this account
                $containersResponse = Http::withToken($accessToken)
                    ->get("https://www.googleapis.com/tagmanager/v2/{$accountPath}/containers");

                if ($containersResponse->successful()) {
                    foreach ($containersResponse->json('container', []) as $container) {
                        $containers[] = [
                            'containerId' => $container['containerId'] ?? '',
                            'name' => $container['name'] ?? 'Unknown Container',
                            'publicId' => $container['publicId'] ?? '',
                            'domainName' => $container['domainName'] ?? [],
                            'accountId' => $account['accountId'] ?? '',
                        ];
                    }
                }
            }

            return $containers;
        } catch (\Exception $e) {
            Log::error('Exception fetching Tag Manager containers', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get Google Merchant Center accounts.
     *
     * @return array{accounts: array, error: array|null}
     */
    private function getGoogleMerchantCenterAccounts(PlatformConnection $connection): array
    {
        try {
            $accessToken = $this->getValidGoogleAccessToken($connection);
            if (!$accessToken) {
                return ['accounts' => [], 'error' => null];
            }

            $response = Http::withToken($accessToken)
                ->get('https://shoppingcontent.googleapis.com/content/v2.1/accounts/authinfo');

            if (!$response->successful()) {
                $body = $response->json();
                $error = $body['error'] ?? [];

                Log::warning('Merchant Center authinfo API failed', ['status' => $response->status(), 'body' => $body]);

                // Check for scope insufficient errors (403)
                if ($response->status() === 403) {
                    $reason = $error['details'][0]['reason'] ?? '';
                    if ($reason === 'ACCESS_TOKEN_SCOPE_INSUFFICIENT') {
                        return [
                            'accounts' => [],
                            'error' => [
                                'type' => 'scope_insufficient',
                                'message' => $error['message'] ?? 'Missing OAuth scope for Merchant Center',
                            ],
                        ];
                    }
                }

                return ['accounts' => [], 'error' => null];
            }

            $accounts = [];
            foreach ($response->json('accountIdentifiers', []) as $identifier) {
                $merchantId = $identifier['merchantId'] ?? $identifier['aggregatorId'] ?? null;
                if ($merchantId) {
                    // Get account details
                    $accountResponse = Http::withToken($accessToken)
                        ->get("https://shoppingcontent.googleapis.com/content/v2.1/{$merchantId}/accounts/{$merchantId}");

                    $accountData = $accountResponse->successful() ? $accountResponse->json() : [];

                    $accounts[] = [
                        'id' => $merchantId,
                        'name' => $accountData['name'] ?? "Merchant {$merchantId}",
                        'websiteUrl' => $accountData['websiteUrl'] ?? '',
                    ];
                }
            }

            return ['accounts' => $accounts, 'error' => null];
        } catch (\Exception $e) {
            Log::error('Exception fetching Merchant Center accounts', ['error' => $e->getMessage()]);
            return ['accounts' => [], 'error' => ['type' => 'exception', 'message' => $e->getMessage()]];
        }
    }

    /**
     * Get Google Search Console sites.
     */
    private function getGoogleSearchConsoleSites(PlatformConnection $connection): array
    {
        try {
            $accessToken = $this->getValidGoogleAccessToken($connection);
            if (!$accessToken) return [];

            $response = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/webmasters/v3/sites');

            if (!$response->successful()) {
                Log::warning('Search Console sites API failed', ['status' => $response->status(), 'body' => $response->json()]);
                return [];
            }

            $sites = [];
            foreach ($response->json('siteEntry', []) as $site) {
                $sites[] = [
                    'siteUrl' => $site['siteUrl'] ?? '',
                    'permissionLevel' => $site['permissionLevel'] ?? 'siteUnverifiedUser',
                ];
            }

            return $sites;
        } catch (\Exception $e) {
            Log::error('Exception fetching Search Console sites', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get Google Calendars.
     */
    private function getGoogleCalendars(PlatformConnection $connection): array
    {
        try {
            $accessToken = $this->getValidGoogleAccessToken($connection);
            if (!$accessToken) return [];

            $response = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/calendar/v3/users/me/calendarList', [
                    'minAccessRole' => 'writer',
                ]);

            if (!$response->successful()) {
                Log::warning('Calendar list API failed', ['status' => $response->status(), 'body' => $response->json()]);
                return [];
            }

            $calendars = [];
            foreach ($response->json('items', []) as $calendar) {
                $calendars[] = [
                    'id' => $calendar['id'] ?? '',
                    'summary' => $calendar['summary'] ?? 'Unknown Calendar',
                    'description' => $calendar['description'] ?? '',
                    'backgroundColor' => $calendar['backgroundColor'] ?? '#4285f4',
                    'primary' => $calendar['primary'] ?? false,
                    'accessRole' => $calendar['accessRole'] ?? 'reader',
                ];
            }

            return $calendars;
        } catch (\Exception $e) {
            Log::error('Exception fetching Google Calendars', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get Google Drive shared drives/folders.
     */
    private function getGoogleDriveFolders(PlatformConnection $connection): array
    {
        try {
            $accessToken = $this->getValidGoogleAccessToken($connection);
            if (!$accessToken) return [];

            $drives = [];

            // Get shared drives
            $drivesResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/drive/v3/drives', [
                    'pageSize' => 100,
                ]);

            if ($drivesResponse->successful()) {
                foreach ($drivesResponse->json('drives', []) as $drive) {
                    $drives[] = [
                        'id' => $drive['id'] ?? '',
                        'name' => $drive['name'] ?? 'Unknown Drive',
                        'kind' => 'drive#drive',
                    ];
                }
            }

            // Also get root folders from My Drive if no shared drives
            if (empty($drives)) {
                $foldersResponse = Http::withToken($accessToken)
                    ->get('https://www.googleapis.com/drive/v3/files', [
                        'q' => "mimeType='application/vnd.google-apps.folder' and 'root' in parents",
                        'pageSize' => 20,
                        'fields' => 'files(id,name,mimeType)',
                    ]);

                if ($foldersResponse->successful()) {
                    foreach ($foldersResponse->json('files', []) as $folder) {
                        $drives[] = [
                            'id' => $folder['id'] ?? '',
                            'name' => $folder['name'] ?? 'Unknown Folder',
                            'kind' => 'drive#folder',
                        ];
                    }
                }
            }

            return $drives;
        } catch (\Exception $e) {
            Log::error('Exception fetching Google Drive folders', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Show asset selection page for Reddit connection.
     */
    public function selectRedditAssets(Request $request, string $org, string $connectionId)
    {
        return $this->showGenericPlatformAssets($request, $org, $connectionId, 'reddit');
    }

    /**
     * Store selected Reddit assets.
     */
    public function storeRedditAssets(Request $request, string $org, string $connectionId)
    {
        return $this->storeGenericPlatformAssets($request, $org, $connectionId, 'reddit', [
            'account'
        ]);
    }

    /**
     * Generic method to show platform assets page.
     */
    private function showGenericPlatformAssets(Request $request, string $org, string $connectionId, string $platform)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', $platform)
            ->firstOrFail();

        $selectedAssets = $connection->account_metadata['selected_assets'] ?? [];

        $platformNames = [
            'twitter' => 'X (Twitter)',
            'tiktok' => 'TikTok',
            'snapchat' => 'Snapchat',
            'pinterest' => 'Pinterest',
            'youtube' => 'YouTube',
            'google' => 'Google',
            'reddit' => 'Reddit',
        ];

        return view('settings.platform-connections.platform-assets', [
            'currentOrg' => $org,
            'connection' => $connection,
            'platform' => $platform,
            'platformName' => $platformNames[$platform] ?? ucfirst($platform),
            'accounts' => [],
            'channels' => [],
            'businessProfiles' => [],
            'adAccounts' => [],
            'pixels' => [],
            'catalogs' => [],
            'selectedAssets' => $selectedAssets,
        ]);
    }

    /**
     * Generic method to store platform assets.
     */
    private function storeGenericPlatformAssets(Request $request, string $org, string $connectionId, string $platform, array $assetTypes)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', $platform)
            ->firstOrFail();

        // Build validation rules based on asset types
        $rules = [];
        foreach ($assetTypes as $type) {
            $rules[$type] = 'nullable|string|max:100';
            $rules['manual_' . $type . '_id'] = 'nullable|string|max:100';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Build selected assets (single value per type)
        $selectedAssets = [];
        foreach ($assetTypes as $type) {
            $manualKey = 'manual_' . $type . '_id';
            $value = $request->filled($manualKey)
                ? $request->input($manualKey)
                : $request->input($type);

            if ($value) {
                $selectedAssets[$type] = $value;
            }
        }

        // Update connection metadata
        $metadata = $connection->account_metadata ?? [];
        $metadata['selected_assets'] = $selectedAssets;
        $metadata['assets_updated_at'] = now()->toIso8601String();

        $connection->update(['account_metadata' => $metadata]);

        // Build success message
        $assetLabels = [
            'account' => 'Account',
            'profile' => 'Profile',
            'page' => 'Page',
            'channel' => 'Channel',
            'business_profile' => 'Business Profile',
            'ad_account' => 'Ad Account',
            'pixel' => 'Pixel',
            'catalog' => 'Catalog',
        ];

        $selectedList = [];
        foreach ($selectedAssets as $type => $value) {
            $selectedList[] = $assetLabels[$type] ?? ucfirst($type);
        }

        $message = ucfirst($platform) . ' assets configured: ' . (count($selectedList) > 0 ? implode(', ', $selectedList) : 'None');

        if ($request->wantsJson()) {
            return $this->success([
                'connection' => $connection->fresh(),
                'selected_assets' => $selectedAssets,
            ], $message);
        }

        return redirect()
            ->route('orgs.settings.platform-connections.index', $org)
            ->with('success', $message);
    }

    /**
     * Get Threads accounts associated with Instagram Business accounts.
     *
     * IMPORTANT: Threads API (graph.threads.net) requires SEPARATE OAuth authentication!
     * - Threads uses a different API domain: https://graph.threads.net
     * - Requires separate OAuth flow with Threads-specific scopes (threads_basic, threads_content_publish)
     * - Facebook/Meta access tokens CANNOT be used with Threads API
     *
     * This method attempts to fetch Threads data but will likely fail unless:
     * 1. A separate Threads OAuth flow has been implemented
     * 2. The access token includes Threads scopes
     *
     * For now, users should use "Add manually" to enter their Threads account ID.
     *
     * @see https://developers.facebook.com/docs/threads/overview
     * @see https://developers.facebook.com/docs/threads/get-started/get-access-tokens-and-permissions
     */
    private function getThreadsAccounts(string $accessToken, array $instagramAccounts = []): array
    {
        $threadsAccounts = [];

        // Log explanation for why Threads likely won't work
        Log::info('Attempting to fetch Threads accounts', [
            'note' => 'Threads API requires separate OAuth with threads_* scopes. Meta tokens typically do not include these scopes.',
            'instagram_accounts_count' => count($instagramAccounts),
        ]);

        try {
            foreach ($instagramAccounts as $ig) {
                $igId = $ig['id'] ?? null;
                if (!$igId) continue;

                // Try to get Threads profile using the Instagram account ID
                // NOTE: This will likely fail because Threads API requires separate OAuth
                $response = Http::timeout(15)->get("https://graph.threads.net/v1.0/{$igId}", [
                    'access_token' => $accessToken,
                    'fields' => 'id,username,name,threads_profile_picture_url,threads_biography',
                ]);

                if ($response->successful()) {
                    $threadsData = $response->json();
                    if (!empty($threadsData['id'])) {
                        $threadsAccounts[] = [
                            'id' => $threadsData['id'],
                            'username' => $threadsData['username'] ?? $ig['username'] ?? null,
                            'name' => $threadsData['name'] ?? $ig['name'] ?? 'Threads Account',
                            'profile_picture' => $threadsData['threads_profile_picture_url'] ?? $ig['profile_picture'] ?? null,
                            'biography' => $threadsData['threads_biography'] ?? null,
                            'connected_instagram' => $ig['username'] ?? null,
                            'instagram_id' => $igId,
                        ];
                    }
                } else {
                    // Expected: Threads API requires separate OAuth with threads_* scopes
                    // The Meta/Facebook access token will not work with graph.threads.net
                    Log::info('Threads API requires separate OAuth authentication', [
                        'instagram_id' => $igId,
                        'instagram_username' => $ig['username'] ?? null,
                        'status' => $response->status(),
                        'error' => $response->json('error.message'),
                        'error_code' => $response->json('error.code'),
                        'hint' => 'Use "Add manually" button to enter Threads account ID, or implement separate Threads OAuth flow',
                    ]);
                }
            }

            return $threadsAccounts;
        } catch (\Exception $e) {
            Log::error('Failed to fetch Threads accounts', ['error' => $e->getMessage()]);
            return $threadsAccounts;
        }
    }

    /**
     * Get available platforms.
     */
    private function getAvailablePlatforms(): array
    {
        return [
            'meta' => [
                'name' => 'Meta (Facebook/Instagram)',
                'icon' => 'fab fa-facebook',
                'color' => 'blue',
                'supports_system_user' => true,
                'supports_oauth' => true,
            ],
            'google' => [
                'name' => 'Google Ads',
                'icon' => 'fab fa-google',
                'color' => 'red',
                'supports_system_user' => false,
                'supports_oauth' => true,
            ],
            'tiktok' => [
                'name' => 'TikTok Ads',
                'icon' => 'fab fa-tiktok',
                'color' => 'gray',
                'supports_system_user' => false,
                'supports_oauth' => true,
            ],
            'linkedin' => [
                'name' => 'LinkedIn Ads',
                'icon' => 'fab fa-linkedin',
                'color' => 'blue',
                'supports_system_user' => false,
                'supports_oauth' => true,
            ],
            'twitter' => [
                'name' => 'Twitter (X) Ads',
                'icon' => 'fab fa-twitter',
                'color' => 'sky',
                'supports_system_user' => false,
                'supports_oauth' => true,
            ],
            'snapchat' => [
                'name' => 'Snapchat Ads',
                'icon' => 'fab fa-snapchat',
                'color' => 'yellow',
                'supports_system_user' => false,
                'supports_oauth' => true,
            ],
        ];
    }

    // ===== OAuth 2.0 Methods for Social Media Platforms =====

    /**
     * Initiate Meta (Facebook) OAuth authorization.
     */
    public function authorizeMeta(Request $request, string $org)
    {
        $config = config('social-platforms.meta');
        $stateData = ['org_id' => $org, 'platform' => 'meta'];

        // Include wizard mode in state if present
        if ($request->has('wizard_mode')) {
            $stateData['wizard_mode'] = true;
        }

        $state = base64_encode(json_encode($stateData));
        session(['oauth_state' => $state]);

        // Meta OAuth scopes for ads management and social publishing
        $scopes = [
            // Core advertising permissions
            'ads_management',
            'ads_read',
            'business_management',

            // Facebook Pages permissions
            'pages_read_engagement',
            'pages_show_list',
            'pages_manage_posts',
            'pages_manage_metadata',

            // Instagram permissions
            'instagram_basic',
            'instagram_content_publish',
            'instagram_manage_comments',
            'instagram_manage_insights',

            // Analytics
            'read_insights',

            // WhatsApp Business permissions (for Click-to-WhatsApp campaigns)
            'whatsapp_business_management',
            'whatsapp_business_messaging',

            // Product catalog permissions (for shopping/collection ads)
            'catalog_management',

            // Lead generation permissions
            'leads_retrieval',
        ];

        $params = http_build_query([
            'client_id' => $config['app_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(',', $scopes),
            'state' => $state,
        ]);

        return redirect('https://www.facebook.com/v21.0/dialog/oauth?' . $params);
    }

    /**
     * Handle Meta (Facebook) OAuth callback.
     */
    public function callbackMeta(Request $request)
    {
        $state = json_decode(base64_decode($request->get('state')), true);
        $orgId = $state['org_id'] ?? null;

        if (!$orgId || $request->get('state') !== session('oauth_state')) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId ?? 'default')
                ->with('error', __('settings.invalid'));
        }

        if ($request->has('error')) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.meta_auth_denied', ['error' => $request->get('error_description', 'Unknown error')]));
        }

        $config = config('social-platforms.meta');

        // Exchange code for access token
        $response = Http::get('https://graph.facebook.com/v21.0/oauth/access_token', [
            'client_id' => $config['app_id'],
            'client_secret' => $config['app_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'code' => $request->get('code'),
        ]);

        if (!$response->successful()) {
            Log::error('Meta OAuth token exchange failed', [
                'error' => $response->json(),
            ]);
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed'));
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'];

        // Exchange for long-lived token
        $longLivedResponse = Http::get('https://graph.facebook.com/v21.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $config['app_id'],
            'client_secret' => $config['app_secret'],
            'fb_exchange_token' => $accessToken,
        ]);

        if ($longLivedResponse->successful()) {
            $longLivedData = $longLivedResponse->json();
            $accessToken = $longLivedData['access_token'];
            $expiresIn = $longLivedData['expires_in'] ?? 5184000; // Default 60 days
        } else {
            $expiresIn = $tokenData['expires_in'] ?? 3600;
        }

        // Validate the token and get user info
        $tokenInfo = $this->validateMetaToken($accessToken);

        if (!$tokenInfo['valid']) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.token_validation_failed', ['error' => ($tokenInfo['error'] ?? 'Unknown error')]));
        }

        // Get ad accounts
        $adAccounts = $this->getMetaAdAccounts($accessToken);
        $activeAdAccounts = array_filter($adAccounts, fn($acc) => $acc['can_create_ads'] ?? false);

        $hasRequiredPermissions = $tokenInfo['has_all_required_permissions'] ?? true;
        $warnings = $tokenInfo['warnings'] ?? [];

        // Create connection within a transaction with RLS context
        $connection = DB::transaction(function () use ($orgId, $tokenInfo, $hasRequiredPermissions, $accessToken, $expiresIn, $adAccounts, $activeAdAccounts, $warnings) {
            // Set RLS context for the organization (LOCAL = true, applies to this transaction)
            DB::statement("SELECT set_config('app.current_org_id', ?, true)", [$orgId]);

            return PlatformConnection::updateOrCreate(
                [
                    'org_id' => $orgId,
                    'platform' => 'meta',
                    'account_id' => $tokenInfo['user_id'] ?? 'oauth_user_' . Str::random(8),
                ],
                [
                    'account_name' => $tokenInfo['user_name'] ?? 'Meta Account',
                    'status' => $hasRequiredPermissions ? 'active' : 'warning',
                    'access_token' => $accessToken,
                    'token_expires_at' => now()->addSeconds($expiresIn),
                    'scopes' => $tokenInfo['scopes'] ?? [],
                    'account_metadata' => [
                        'token_type' => 'oauth_user',
                        'is_system_user' => false,
                        'is_never_expires' => false,
                        'app_id' => $tokenInfo['app_id'] ?? null,
                        'user_id' => $tokenInfo['user_id'] ?? null,
                        'user_name' => $tokenInfo['user_name'] ?? null,
                        'ad_accounts' => $adAccounts,
                        'ad_accounts_count' => count($adAccounts),
                        'active_ad_accounts_count' => count($activeAdAccounts),
                        'missing_required_permissions' => $tokenInfo['missing_required_permissions'] ?? [],
                        'missing_recommended_permissions' => $tokenInfo['missing_recommended_permissions'] ?? [],
                        'warnings' => $warnings,
                        'is_valid' => true,
                        'validated_at' => now()->toIso8601String(),
                        'connected_via' => 'oauth',
                    ],
                    'auto_sync' => true,
                    'sync_frequency_minutes' => 15,
                ]
            );
        });

        session()->forget('oauth_state');

        // Build success message
        $successMessage = 'Meta account connected successfully via OAuth. Found ' . count($adAccounts) . ' ad account(s).';
        if (!$hasRequiredPermissions) {
            $missingPerms = implode(', ', $tokenInfo['missing_required_permissions'] ?? []);
            $successMessage .= " Warning: Missing some permissions ({$missingPerms}).";
        }

        // Check for wizard mode and redirect accordingly
        if (!empty($state['wizard_mode'])) {
            return redirect()
                ->route('orgs.settings.platform-connections.wizard.assets', [$orgId, 'meta', $connection->connection_id])
                ->with('success', __('wizard.mode.direct.connecting'));
        }

        // Redirect to asset selection
        return redirect()
            ->route('orgs.settings.platform-connections.meta.assets', [$orgId, $connection->connection_id])
            ->with('success', $successMessage . ' Now select which Pages, Instagram accounts, and other assets to use.');
    }

    /**
     * Initiate YouTube OAuth authorization.
     */
    public function authorizeYouTube(Request $request, string $org)
    {
        $config = config('social-platforms.youtube');
        $state = base64_encode(json_encode(['org_id' => $org, 'platform' => 'youtube']));

        session(['oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(' ', $config['scopes']),
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return redirect($config['authorize_url'] . '?' . $params);
    }

    /**
     * Handle YouTube OAuth callback.
     */
    public function callbackYouTube(Request $request)
    {
        $state = json_decode(base64_decode($request->get('state')), true);
        $orgId = $state['org_id'] ?? null;

        if (!$orgId || $request->get('state') !== session('oauth_state')) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId ?? 'default')
                ->with('error', __('settings.invalid'));
        }

        $config = config('social-platforms.youtube');

        $response = Http::asForm()->post($config['token_url'], [
            'code' => $request->get('code'),
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed'));
        }

        $tokenData = $response->json();

        // Get user info
        $userResponse = Http::withToken($tokenData['access_token'])
            ->get('https://www.googleapis.com/youtube/v3/channels', [
                'part' => 'snippet',
                'mine' => 'true',
            ]);

        $accountName = 'YouTube Channel';
        $accountId = 'youtube_' . Str::random(10);

        if ($userResponse->successful()) {
            $channelData = $userResponse->json('items.0', []);
            $accountName = $channelData['snippet']['title'] ?? 'YouTube Channel';
            $accountId = $channelData['id'] ?? $accountId;
        }

        // Create or update the connection within a transaction with RLS context
        $connection = DB::transaction(function () use ($orgId, $accountId, $accountName, $tokenData) {
            // Set RLS context for the organization (LOCAL = true, applies to this transaction)
            DB::statement("SELECT set_config('app.current_org_id', ?, true)", [$orgId]);

            return PlatformConnection::updateOrCreate(
                [
                    'org_id' => $orgId,
                    'platform' => 'youtube',
                    'account_id' => $accountId,
                ],
                [
                    'account_name' => $accountName,
                    'status' => 'active',
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_expires_at' => isset($tokenData['expires_in'])
                        ? now()->addSeconds($tokenData['expires_in'])
                        : null,
                    'scopes' => explode(' ', $tokenData['scope'] ?? ''),
                    'account_metadata' => [
                        'token_type' => $tokenData['token_type'] ?? 'Bearer',
                        'connected_at' => now()->toIso8601String(),
                    ],
                ]
            );
        });

        session()->forget('oauth_state');

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', __('settings.youtube_account_connected_successfully'));
    }

    /**
     * Initiate LinkedIn OAuth authorization.
     */
    public function authorizeLinkedIn(Request $request, string $org)
    {
        $config = config('social-platforms.linkedin');
        $stateData = ['org_id' => $org, 'platform' => 'linkedin'];

        // Include wizard mode in state if present
        if ($request->has('wizard_mode')) {
            $stateData['wizard_mode'] = true;
        }

        $state = base64_encode(json_encode($stateData));
        session(['oauth_state' => $state]);

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'state' => $state,
            'scope' => implode(' ', $config['scopes']),
        ]);

        return redirect($config['authorize_url'] . '?' . $params);
    }

    /**
     * Handle LinkedIn OAuth callback.
     */
    public function callbackLinkedIn(Request $request)
    {
        $state = json_decode(base64_decode($request->get('state')), true);
        $orgId = $state['org_id'] ?? null;

        if (!$orgId || $request->get('state') !== session('oauth_state')) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId ?? 'default')
                ->with('error', __('settings.invalid'));
        }

        $config = config('social-platforms.linkedin');

        $response = Http::asForm()->post($config['token_url'], [
            'grant_type' => 'authorization_code',
            'code' => $request->get('code'),
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
        ]);

        if (!$response->successful()) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed'));
        }

        $tokenData = $response->json();

        // Get user info
        $userResponse = Http::withToken($tokenData['access_token'])
            ->withHeaders(['LinkedIn-Version' => '202401'])
            ->get('https://api.linkedin.com/v2/userinfo');

        $accountName = 'LinkedIn Profile';
        $accountId = 'li_' . Str::random(10);

        if ($userResponse->successful()) {
            $userData = $userResponse->json();
            $accountName = $userData['name'] ?? $userData['email'] ?? 'LinkedIn Profile';
            $accountId = $userData['sub'] ?? $accountId;
        }

        // Create or update the connection within a transaction with RLS context
        $connection = DB::transaction(function () use ($orgId, $accountId, $accountName, $tokenData) {
            // Set RLS context for the organization (LOCAL = true, applies to this transaction)
            DB::statement("SELECT set_config('app.current_org_id', ?, true)", [$orgId]);

            return PlatformConnection::updateOrCreate(
                [
                    'org_id' => $orgId,
                    'platform' => 'linkedin',
                    'account_id' => $accountId,
                ],
                [
                    'account_name' => $accountName,
                    'status' => 'active',
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_expires_at' => isset($tokenData['expires_in'])
                        ? now()->addSeconds($tokenData['expires_in'])
                        : null,
                    'scopes' => explode(' ', $tokenData['scope'] ?? ''),
                    'account_metadata' => [
                        'token_type' => $tokenData['token_type'] ?? 'Bearer',
                        'connected_at' => now()->toIso8601String(),
                    ],
                ]
            );
        });

        session()->forget('oauth_state');

        // Check for wizard mode and redirect accordingly
        if (!empty($state['wizard_mode'])) {
            return redirect()
                ->route('orgs.settings.platform-connections.wizard.assets', [$orgId, 'linkedin', $connection->connection_id])
                ->with('success', __('wizard.mode.direct.connecting'));
        }

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', __('settings.linkedin_account_connected_successfully'));
    }

    /**
     * Initiate Twitter/X OAuth authorization.
     */
    public function authorizeTwitter(Request $request, string $org)
    {
        $config = config('social-platforms.twitter');
        $stateData = ['org_id' => $org, 'platform' => 'twitter'];

        // Include wizard mode in state if present
        if ($request->has('wizard_mode')) {
            $stateData['wizard_mode'] = true;
        }

        $state = base64_encode(json_encode($stateData));
        session(['oauth_state' => $state]);

        $codeVerifier = Str::random(128);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        session(['twitter_code_verifier' => $codeVerifier]);

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'scope' => implode(' ', $config['scopes']),
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return redirect($config['authorize_url'] . '?' . $params);
    }

    /**
     * Handle Twitter/X OAuth callback.
     */
    public function callbackTwitter(Request $request)
    {
        $state = json_decode(base64_decode($request->get('state')), true);
        $orgId = $state['org_id'] ?? null;

        if (!$orgId || $request->get('state') !== session('oauth_state')) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId ?? 'default')
                ->with('error', __('settings.invalid'));
        }

        $config = config('social-platforms.twitter');

        $response = Http::asForm()
            ->withBasicAuth($config['client_id'], $config['client_secret'])
            ->post($config['token_url'], [
                'code' => $request->get('code'),
                'grant_type' => 'authorization_code',
                'client_id' => $config['client_id'],
                'redirect_uri' => $config['redirect_uri'],
                'code_verifier' => session('twitter_code_verifier'),
            ]);

        if (!$response->successful()) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed'));
        }

        $tokenData = $response->json();

        // Get user info
        $userResponse = Http::withToken($tokenData['access_token'])
            ->get('https://api.twitter.com/2/users/me');

        $accountName = 'X (Twitter) Account';
        $accountId = 'twitter_' . Str::random(10);

        if ($userResponse->successful()) {
            $userData = $userResponse->json('data', []);
            $accountName = '@' . ($userData['username'] ?? 'twitter');
            $accountId = $userData['id'] ?? $accountId;
        }

        // Create or update the connection within a transaction with RLS context
        $connection = DB::transaction(function () use ($orgId, $accountId, $accountName, $tokenData) {
            // Set RLS context for the organization (LOCAL = true, applies to this transaction)
            DB::statement("SELECT set_config('app.current_org_id', ?, true)", [$orgId]);

            return PlatformConnection::updateOrCreate(
                [
                    'org_id' => $orgId,
                    'platform' => 'twitter',
                    'account_id' => $accountId,
                ],
                [
                    'account_name' => $accountName,
                    'status' => 'active',
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_expires_at' => isset($tokenData['expires_in'])
                        ? now()->addSeconds($tokenData['expires_in'])
                        : null,
                    'scopes' => explode(' ', $tokenData['scope'] ?? ''),
                    'account_metadata' => [
                        'token_type' => $tokenData['token_type'] ?? 'Bearer',
                        'connected_at' => now()->toIso8601String(),
                    ],
                ]
            );
        });

        session()->forget(['oauth_state', 'twitter_code_verifier']);

        // Check for wizard mode and redirect accordingly
        if (!empty($state['wizard_mode'])) {
            return redirect()
                ->route('orgs.settings.platform-connections.wizard.assets', [$orgId, 'twitter', $connection->connection_id])
                ->with('success', __('wizard.mode.direct.connecting'));
        }

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', __('settings.x_twitter_account_connected_successfully'));
    }

    /**
     * Initiate Pinterest OAuth authorization.
     */
    public function authorizePinterest(Request $request, string $org)
    {
        $config = config('social-platforms.pinterest');
        $state = base64_encode(json_encode(['org_id' => $org, 'platform' => 'pinterest']));

        session(['oauth_state' => $state]);

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $config['app_id'],
            'redirect_uri' => $config['redirect_uri'],
            'state' => $state,
            'scope' => implode(',', $config['scopes']),
        ]);

        return redirect($config['authorize_url'] . '?' . $params);
    }

    /**
     * Handle Pinterest OAuth callback.
     */
    public function callbackPinterest(Request $request)
    {
        $state = json_decode(base64_decode($request->get('state')), true);
        $orgId = $state['org_id'] ?? null;

        if (!$orgId || $request->get('state') !== session('oauth_state')) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId ?? 'default')
                ->with('error', __('settings.invalid'));
        }

        $config = config('social-platforms.pinterest');

        $response = Http::asForm()->post($config['token_url'], [
            'grant_type' => 'authorization_code',
            'code' => $request->get('code'),
            'redirect_uri' => $config['redirect_uri'],
        ])->withBasicAuth($config['app_id'], $config['app_secret']);

        if (!$response->successful()) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed'));
        }

        $tokenData = $response->json();

        // Get user info
        $userResponse = Http::withToken($tokenData['access_token'])
            ->get('https://api.pinterest.com/v5/user_account');

        $accountName = 'Pinterest Account';
        $accountId = 'pinterest_' . Str::random(10);

        if ($userResponse->successful()) {
            $userData = $userResponse->json();
            $accountName = $userData['username'] ?? 'Pinterest Account';
            $accountId = $userData['id'] ?? $accountId;
        }

        // Create or update the connection within a transaction with RLS context
        $connection = DB::transaction(function () use ($orgId, $accountId, $accountName, $tokenData) {
            // Set RLS context for the organization (LOCAL = true, applies to this transaction)
            DB::statement("SELECT set_config('app.current_org_id', ?, true)", [$orgId]);

            return PlatformConnection::updateOrCreate(
                [
                    'org_id' => $orgId,
                    'platform' => 'pinterest',
                    'account_id' => $accountId,
                ],
                [
                    'account_name' => $accountName,
                    'status' => 'active',
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_expires_at' => isset($tokenData['expires_in'])
                        ? now()->addSeconds($tokenData['expires_in'])
                        : null,
                    'scopes' => $tokenData['scope'] ? explode(',', $tokenData['scope']) : [],
                    'account_metadata' => [
                        'token_type' => $tokenData['token_type'] ?? 'Bearer',
                        'connected_at' => now()->toIso8601String(),
                    ],
                ]
            );
        });

        session()->forget('oauth_state');

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', __('settings.pinterest_account_connected_successfully'));
    }

    /**
     * Initiate TikTok OAuth authorization.
     */
    public function authorizeTikTok(Request $request, string $org)
    {
        $config = config('social-platforms.tiktok');
        $stateData = ['org_id' => $org, 'platform' => 'tiktok'];

        // Include wizard mode in state if present
        if ($request->has('wizard_mode')) {
            $stateData['wizard_mode'] = true;
        }

        $state = base64_encode(json_encode($stateData));
        session(['oauth_state' => $state]);

        $csrfState = Str::random(32);
        session(['tiktok_csrf_state' => $csrfState]);

        $params = http_build_query([
            'client_key' => $config['client_key'],
            'scope' => implode(',', $config['scopes']),
            'response_type' => 'code',
            'redirect_uri' => $config['redirect_uri'],
            'state' => $state,
        ]);

        return redirect($config['authorize_url'] . '?' . $params);
    }

    /**
     * Handle TikTok Account OAuth callback (Login Kit v2).
     *
     * TikTok Login Kit is for user account access (video publishing).
     * For ads management, use TikTok Ads (Business API) instead.
     */
    public function callbackTikTok(Request $request)
    {
        $state = json_decode(base64_decode($request->get('state')), true);
        $orgId = $state['org_id'] ?? null;

        if (!$orgId || $request->get('state') !== session('oauth_state')) {
            Log::warning('TikTok Account OAuth state mismatch', [
                'expected' => session('oauth_state'),
                'received' => $request->get('state'),
            ]);
            return redirect()->route('orgs.settings.platform-connections.index', $orgId ?? 'default')
                ->with('error', __('settings.invalid'));
        }

        // Check for error from TikTok
        if ($request->has('error')) {
            Log::error('TikTok Account OAuth error', [
                'error' => $request->get('error'),
                'error_description' => $request->get('error_description'),
            ]);
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed') . ': ' . $request->get('error_description', $request->get('error')));
        }

        $config = config('social-platforms.tiktok');
        $code = $request->get('code');

        if (!$code) {
            Log::error('TikTok Account OAuth: No code received');
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed'));
        }

        // Login Kit v2 uses application/x-www-form-urlencoded
        $response = Http::asForm()->post($config['token_url'], [
            'client_key' => $config['client_key'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $config['redirect_uri'],
        ]);

        if (!$response->successful()) {
            Log::error('TikTok Account token exchange failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed'));
        }

        // TikTok Login Kit v2 response structure
        $responseData = $response->json();
        $tokenData = $responseData['data'] ?? $responseData;

        // Check for API error
        if (isset($responseData['error']) || empty($tokenData['access_token'])) {
            Log::error('TikTok Account API error', [
                'error' => $responseData['error'] ?? 'No access_token',
                'error_description' => $responseData['error_description'] ?? null,
            ]);
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed'));
        }

        // Get user info using the access token
        $userResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenData['access_token'],
        ])->get('https://open.tiktokapis.com/v2/user/info/', [
            'fields' => 'open_id,union_id,display_name,avatar_url',
        ]);

        $accountName = __('settings.tiktok_account');
        $accountId = 'tiktok_' . Str::random(10);
        $userData = [];

        if ($userResponse->successful()) {
            $userResponseData = $userResponse->json();
            $userData = $userResponseData['data']['user'] ?? [];
            $accountName = $userData['display_name'] ?? $accountName;
            $accountId = $tokenData['open_id'] ?? $userData['open_id'] ?? $accountId;
        }

        // Create or update the connection within a transaction with RLS context
        $result = DB::transaction(function () use ($orgId, $accountId, $accountName, $tokenData, $userData) {
            // Set RLS context for the organization (LOCAL = true, applies to this transaction)
            DB::statement("SELECT set_config('app.current_org_id', ?, true)", [$orgId]);

            // Parse scopes safely
            $scopes = [];
            if (!empty($tokenData['scope'])) {
                $scopes = is_array($tokenData['scope']) ? $tokenData['scope'] : explode(',', $tokenData['scope']);
            }

            // Use withTrashed() to find soft-deleted records and restore them
            // This prevents unique constraint violations when reconnecting
            $connection = PlatformConnection::withTrashed()
                ->where('org_id', $orgId)
                ->where('platform', 'tiktok')
                ->where('account_id', $accountId)
                ->first();

            $connectionData = [
                'account_name' => $accountName,
                'status' => 'active',
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => isset($tokenData['expires_in'])
                    ? now()->addSeconds($tokenData['expires_in'])
                    : now()->addHours(24), // Login Kit tokens expire in 24 hours
                'scopes' => $scopes,
                'account_metadata' => [
                    'token_type' => $tokenData['token_type'] ?? 'Bearer',
                    'connected_at' => now()->toIso8601String(),
                    'open_id' => $tokenData['open_id'] ?? null,
                    'union_id' => $userData['union_id'] ?? null,
                    'avatar_url' => $userData['avatar_url'] ?? null,
                    'refresh_expires_in' => $tokenData['refresh_expires_in'] ?? null,
                ],
            ];

            if ($connection) {
                // Restore if soft-deleted, then update
                if ($connection->trashed()) {
                    $connection->restore();
                }
                $connection->update($connectionData);
            } else {
                $connection = PlatformConnection::create(array_merge([
                    'org_id' => $orgId,
                    'platform' => 'tiktok',
                    'account_id' => $accountId,
                ], $connectionData));
            }

            // Also create an Integration record so the profile appears on the Profiles page
            // This allows users to use TikTok for social publishing
            // Use withTrashed() to handle soft-deleted records
            $integration = Integration::withTrashed()
                ->where('org_id', $orgId)
                ->where('platform', 'tiktok')
                ->where('account_id', $accountId)
                ->first();

            $integrationData = [
                'account_name' => $accountName,
                'username' => $userData['display_name'] ?? $accountName,
                'avatar_url' => $userData['avatar_url'] ?? null,
                'status' => 'active',
                'is_active' => true,
                'is_enabled' => true,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => isset($tokenData['expires_in'])
                    ? now()->addSeconds($tokenData['expires_in'])
                    : now()->addHours(24),
                'scopes' => $scopes,
                'platform_connection_id' => $connection->connection_id,
                'metadata' => [
                    'connection_id' => $connection->connection_id,
                    'open_id' => $tokenData['open_id'] ?? null,
                    'union_id' => $userData['union_id'] ?? null,
                    'synced_at' => now()->toIso8601String(),
                ],
            ];

            if ($integration) {
                // Restore if soft-deleted, then update
                if ($integration->trashed()) {
                    $integration->restore();
                }
                $integration->update($integrationData);
            } else {
                $integration = Integration::create(array_merge([
                    'org_id' => $orgId,
                    'platform' => 'tiktok',
                    'account_id' => $accountId,
                ], $integrationData));
            }

            return ['connection' => $connection, 'integration' => $integration];
        });

        $connection = $result['connection'];

        session()->forget(['oauth_state', 'tiktok_csrf_state']);

        // Check for wizard mode and redirect accordingly
        if (!empty($state['wizard_mode'])) {
            return redirect()
                ->route('orgs.settings.platform-connections.wizard.assets', [$orgId, 'tiktok', $connection->connection_id])
                ->with('success', __('wizard.mode.direct.connecting'));
        }

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', __('settings.tiktok_account_connected_successfully'));
    }

    /**
     * Refresh TikTok Account token using the refresh token.
     *
     * TikTok Login Kit v2 tokens expire in 24 hours but refresh tokens last 365 days.
     * This method refreshes the access token before it expires.
     */
    public function refreshTikTokToken(Request $request, string $org, string $connection)
    {
        $platformConnection = PlatformConnection::where('connection_id', $connection)
            ->where('org_id', $org)
            ->where('platform', 'tiktok')
            ->firstOrFail();

        if (!$platformConnection->refresh_token) {
            return redirect()->route('orgs.settings.platform-connections.index', $org)
                ->with('error', __('settings.no_refresh_token'));
        }

        $config = config('social-platforms.tiktok');

        // TikTok Login Kit v2 token refresh
        $response = Http::asForm()->post($config['token_url'], [
            'client_key' => $config['client_key'],
            'client_secret' => $config['client_secret'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $platformConnection->refresh_token,
        ]);

        if (!$response->successful()) {
            Log::error('TikTok token refresh failed', [
                'status' => $response->status(),
                'response' => $response->json(),
                'connection_id' => $connection,
            ]);

            // If refresh fails, mark connection as expired
            $platformConnection->markAsExpired();

            return redirect()->route('orgs.settings.platform-connections.index', $org)
                ->with('error', __('settings.token_refresh_failed'));
        }

        $responseData = $response->json();
        $tokenData = $responseData['data'] ?? $responseData;

        if (empty($tokenData['access_token'])) {
            Log::error('TikTok token refresh: No access_token in response', [
                'response' => $responseData,
                'connection_id' => $connection,
            ]);

            $platformConnection->markAsExpired();

            return redirect()->route('orgs.settings.platform-connections.index', $org)
                ->with('error', __('settings.token_refresh_failed'));
        }

        // Update the connection with new tokens
        $platformConnection->update([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? $platformConnection->refresh_token,
            'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 86400),
            'status' => 'active',
            'last_error_at' => null,
            'last_error_message' => null,
        ]);

        Log::info('TikTok token refreshed successfully', [
            'connection_id' => $connection,
            'expires_at' => $platformConnection->token_expires_at,
        ]);

        return redirect()->route('orgs.settings.platform-connections.index', $org)
            ->with('success', __('settings.token_refreshed_successfully'));
    }

    /**
     * Static method to refresh TikTok token programmatically.
     * Can be called from scheduled commands or middleware.
     *
     * @param PlatformConnection $connection
     * @return bool
     */
    public static function refreshTikTokTokenSilently(PlatformConnection $connection): bool
    {
        if ($connection->platform !== 'tiktok' || !$connection->refresh_token) {
            return false;
        }

        $config = config('social-platforms.tiktok');

        try {
            $response = Http::asForm()->post($config['token_url'], [
                'client_key' => $config['client_key'],
                'client_secret' => $config['client_secret'],
                'grant_type' => 'refresh_token',
                'refresh_token' => $connection->refresh_token,
            ]);

            if (!$response->successful()) {
                Log::error('TikTok silent token refresh failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'connection_id' => $connection->connection_id,
                ]);
                $connection->markAsExpired();
                return false;
            }

            $responseData = $response->json();
            $tokenData = $responseData['data'] ?? $responseData;

            if (empty($tokenData['access_token'])) {
                $connection->markAsExpired();
                return false;
            }

            $connection->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? $connection->refresh_token,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 86400),
                'status' => 'active',
                'last_error_at' => null,
                'last_error_message' => null,
            ]);

            Log::info('TikTok token refreshed silently', [
                'connection_id' => $connection->connection_id,
                'expires_at' => $connection->fresh()->token_expires_at,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('TikTok silent token refresh exception', [
                'connection_id' => $connection->connection_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Initiate TikTok Ads OAuth authorization (Business API).
     *
     * TikTok Business API is separate from Login Kit and is used for:
     * - Campaign management
     * - Ad group management
     * - Reporting and analytics
     * - Audience management
     */
    public function authorizeTikTokAds(Request $request, string $org)
    {
        $config = config('social-platforms.tiktok_ads');

        if (!$config || empty($config['app_id'])) {
            return redirect()->route('orgs.settings.platform-connections.index', $org)
                ->with('error', __('settings.tiktok_ads_not_configured'));
        }

        $stateData = ['org_id' => $org, 'platform' => 'tiktok_ads'];

        // Include wizard mode in state if present
        if ($request->has('wizard_mode')) {
            $stateData['wizard_mode'] = true;
        }

        $state = base64_encode(json_encode($stateData));
        session(['oauth_state' => $state]);

        // TikTok Business API authorization URL
        // Uses app_id instead of client_key (unlike Login Kit)
        $params = http_build_query([
            'app_id' => $config['app_id'],
            'redirect_uri' => $config['redirect_uri'],
            'state' => $state,
        ]);

        return redirect($config['authorize_url'] . '?' . $params);
    }

    /**
     * Handle TikTok Ads OAuth callback (Business API).
     *
     * Returns:
     * - access_token (never expires unless revoked)
     * - advertiser_ids (list of ad accounts the user has access to)
     */
    public function callbackTikTokAds(Request $request)
    {
        $state = json_decode(base64_decode($request->get('state')), true);
        $orgId = $state['org_id'] ?? null;

        if (!$orgId || $request->get('state') !== session('oauth_state')) {
            Log::warning('TikTok Ads OAuth state mismatch', [
                'expected' => session('oauth_state'),
                'received' => $request->get('state'),
            ]);
            return redirect()->route('orgs.settings.platform-connections.index', $orgId ?? 'default')
                ->with('error', __('settings.invalid'));
        }

        // Check for error from TikTok
        if ($request->has('error')) {
            Log::error('TikTok Ads OAuth error', [
                'error' => $request->get('error'),
                'error_description' => $request->get('error_description'),
            ]);
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed') . ': ' . $request->get('error_description', $request->get('error')));
        }

        $config = config('social-platforms.tiktok_ads');
        $authCode = $request->get('auth_code');

        if (!$authCode) {
            Log::error('TikTok Ads OAuth: No auth_code received');
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed'));
        }

        // Exchange auth code for access token
        // TikTok Business API uses JSON body, not form-encoded
        $response = Http::post($config['token_url'], [
            'app_id' => $config['app_id'],
            'secret' => $config['app_secret'],
            'auth_code' => $authCode,
        ]);

        if (!$response->successful()) {
            Log::error('TikTok Ads token exchange failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed'));
        }

        $responseData = $response->json();

        // TikTok Business API wraps data in 'data' key
        if (isset($responseData['code']) && $responseData['code'] !== 0) {
            Log::error('TikTok Ads API error', [
                'code' => $responseData['code'],
                'message' => $responseData['message'] ?? 'Unknown error',
            ]);
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed') . ': ' . ($responseData['message'] ?? 'Unknown error'));
        }

        $data = $responseData['data'] ?? $responseData;
        $accessToken = $data['access_token'] ?? null;
        $advertiserIds = $data['advertiser_ids'] ?? [];

        if (!$accessToken) {
            Log::error('TikTok Ads: No access_token in response', ['data' => $data]);
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed'));
        }

        // Create or update the connection within a transaction with RLS context
        $connection = DB::transaction(function () use ($orgId, $accessToken, $advertiserIds, $data) {
            // Set RLS context for the organization
            DB::statement("SELECT set_config('app.current_org_id', ?, true)", [$orgId]);

            $accountId = 'tiktok_ads_' . ($data['advertiser_ids'][0] ?? Str::random(10));

            return PlatformConnection::updateOrCreate(
                [
                    'org_id' => $orgId,
                    'platform' => 'tiktok_ads',
                ],
                [
                    'account_name' => __('settings.tiktok_ads_manager'),
                    'account_id' => $accountId,
                    'status' => 'active',
                    'access_token' => $accessToken,
                    'refresh_token' => null, // TikTok Business tokens don't use refresh tokens
                    'token_expires_at' => null, // TikTok Business tokens don't expire
                    'scopes' => [], // Scopes are managed in TikTok Developer Portal
                    'account_metadata' => [
                        'advertiser_ids' => $advertiserIds,
                        'advertiser_count' => count($advertiserIds),
                        'connected_at' => now()->toIso8601String(),
                        'api_version' => config('social-platforms.tiktok_ads.api_version', 'v1.3'),
                    ],
                ]
            );
        });

        session()->forget('oauth_state');

        // Check for wizard mode and redirect accordingly
        if (!empty($state['wizard_mode'])) {
            return redirect()
                ->route('orgs.settings.platform-connections.wizard.assets', [$orgId, 'tiktok_ads', $connection->connection_id])
                ->with('success', __('wizard.mode.direct.connecting'));
        }

        // Redirect to asset selection to pick specific advertiser accounts
        if (count($advertiserIds) > 0) {
            return redirect()
                ->route('orgs.settings.platform-connections.tiktok-ads.assets', [$orgId, $connection->connection_id])
                ->with('success', __('settings.tiktok_ads_connected_successfully'));
        }

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', __('settings.tiktok_ads_connected_successfully'));
    }

    /**
     * Display TikTok Ads asset selection page.
     * Allows user to select which advertiser accounts to manage.
     */
    public function selectTikTokAdsAssets(Request $request, string $org, string $connection)
    {
        $platformConnection = PlatformConnection::where('connection_id', $connection)
            ->where('org_id', $org)
            ->where('platform', 'tiktok_ads')
            ->firstOrFail();

        $metadata = $platformConnection->account_metadata ?? [];
        $advertiserIds = $metadata['advertiser_ids'] ?? [];
        $selectedAssets = $metadata['selected_assets'] ?? [];

        // If we have advertiser IDs, fetch their details from TikTok API
        $advertisers = [];
        if (!empty($advertiserIds) && $platformConnection->access_token) {
            $config = config('social-platforms.tiktok_ads');
            try {
                $response = Http::withHeaders([
                    'Access-Token' => $platformConnection->access_token,
                ])->get($config['advertiser_url'], [
                    'app_id' => $config['app_id'],
                    'secret' => $config['app_secret'],
                ]);

                if ($response->successful()) {
                    $responseData = $response->json();
                    if (isset($responseData['data']['list'])) {
                        $advertisers = $responseData['data']['list'];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch TikTok advertiser details', [
                    'error' => $e->getMessage(),
                ]);
                // Fall back to just showing IDs
                foreach ($advertiserIds as $id) {
                    $advertisers[] = [
                        'advertiser_id' => $id,
                        'advertiser_name' => __('settings.advertiser_account') . ' ' . $id,
                    ];
                }
            }
        }

        return view('settings.platform-connections.tiktok-ads-assets', [
            'connection' => $platformConnection,
            'advertisers' => $advertisers,
            'advertiserIds' => $advertiserIds,
            'selectedAssets' => $selectedAssets,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Store TikTok Ads asset selection.
     */
    public function storeTikTokAdsAssets(Request $request, string $org, string $connection)
    {
        $platformConnection = PlatformConnection::where('connection_id', $connection)
            ->where('org_id', $org)
            ->where('platform', 'tiktok_ads')
            ->firstOrFail();

        $validated = $request->validate([
            'advertiser_ids' => 'nullable|array',
            'advertiser_ids.*' => 'string',
        ]);

        $metadata = $platformConnection->account_metadata ?? [];
        $metadata['selected_assets'] = [
            'advertiser_ids' => $validated['advertiser_ids'] ?? [],
        ];

        // Update the default advertiser if one is selected
        if (!empty($validated['advertiser_ids'])) {
            $metadata['default_advertiser_id'] = $validated['advertiser_ids'][0];
        }

        $platformConnection->account_metadata = $metadata;
        $platformConnection->save();

        return redirect()->route('orgs.settings.platform-connections.index', $org)
            ->with('success', __('settings.assets_saved_successfully'));
    }

    /**
     * Initiate Reddit OAuth authorization.
     */
    public function authorizeReddit(Request $request, string $org)
    {
        $config = config('social-platforms.reddit');
        $state = base64_encode(json_encode(['org_id' => $org, 'platform' => 'reddit']));

        session(['oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => $config['client_id'],
            'response_type' => 'code',
            'state' => $state,
            'redirect_uri' => $config['redirect_uri'],
            'duration' => 'permanent',
            'scope' => implode(' ', $config['scopes']),
        ]);

        return redirect($config['authorize_url'] . '?' . $params);
    }

    /**
     * Handle Reddit OAuth callback.
     */
    public function callbackReddit(Request $request)
    {
        $state = json_decode(base64_decode($request->get('state')), true);
        $orgId = $state['org_id'] ?? null;

        if (!$orgId || $request->get('state') !== session('oauth_state')) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId ?? 'default')
                ->with('error', __('settings.invalid'));
        }

        $config = config('social-platforms.reddit');

        $response = Http::asForm()
            ->withBasicAuth($config['client_id'], $config['client_secret'])
            ->post($config['token_url'], [
                'grant_type' => 'authorization_code',
                'code' => $request->get('code'),
                'redirect_uri' => $config['redirect_uri'],
            ]);

        if (!$response->successful()) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed'));
        }

        $tokenData = $response->json();

        // Get user info
        $userResponse = Http::withToken($tokenData['access_token'])
            ->withHeaders(['User-Agent' => $config['user_agent']])
            ->get('https://oauth.reddit.com/api/v1/me');

        $accountName = 'Reddit Account';
        $accountId = 'reddit_' . Str::random(10);

        if ($userResponse->successful()) {
            $userData = $userResponse->json();
            $accountName = 'u/' . ($userData['name'] ?? 'redditor');
            $accountId = $userData['id'] ?? $accountId;
        }

        // Create or update the connection within a transaction with RLS context
        $connection = DB::transaction(function () use ($orgId, $accountId, $accountName, $tokenData) {
            // Set RLS context for the organization (LOCAL = true, applies to this transaction)
            DB::statement("SELECT set_config('app.current_org_id', ?, true)", [$orgId]);

            return PlatformConnection::updateOrCreate(
                [
                    'org_id' => $orgId,
                    'platform' => 'reddit',
                    'account_id' => $accountId,
                ],
                [
                    'account_name' => $accountName,
                    'status' => 'active',
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_expires_at' => isset($tokenData['expires_in'])
                        ? now()->addSeconds($tokenData['expires_in'])
                        : null,
                    'scopes' => explode(' ', $tokenData['scope'] ?? ''),
                    'account_metadata' => [
                        'token_type' => $tokenData['token_type'] ?? 'Bearer',
                        'connected_at' => now()->toIso8601String(),
                    ],
                ]
            );
        });

        session()->forget('oauth_state');

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', __('settings.reddit_account_connected_successfully'));
    }

    /**
     * Initiate Tumblr OAuth authorization (OAuth 1.0a).
     */
    public function authorizeTumblr(Request $request, string $org)
    {
        // TODO: Implement OAuth 1.0a flow for Tumblr
        // This requires additional OAuth 1.0a library as it uses a different flow than OAuth 2.0
        return redirect()->route('orgs.settings.platform-connections.index', $org)
            ->with('error', __('settings.tumblr_oauth_integration_coming_soon_oauth_10a_req'));
    }

    /**
     * Handle Tumblr OAuth callback.
     */
    public function callbackTumblr(Request $request)
    {
        // TODO: Implement OAuth 1.0a callback for Tumblr
        return redirect()->route('orgs.settings.platform-connections.index', 'default')
            ->with('error', __('settings.tumblr_oauth_callback_not_yet_implemented'));
    }

    /**
     * Initiate Google Business Profile OAuth authorization.
     */
    public function authorizeGoogleBusiness(Request $request, string $org)
    {
        $config = config('social-platforms.google_business');
        $state = base64_encode(json_encode(['org_id' => $org, 'platform' => 'google_business']));

        session(['oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(' ', $config['scopes']),
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return redirect($config['authorize_url'] . '?' . $params);
    }

    /**
     * Handle Google Business Profile OAuth callback.
     */
    public function callbackGoogleBusiness(Request $request)
    {
        $state = json_decode(base64_decode($request->get('state')), true);
        $orgId = $state['org_id'] ?? null;

        if (!$orgId || $request->get('state') !== session('oauth_state')) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId ?? 'default')
                ->with('error', __('settings.invalid'));
        }

        $config = config('social-platforms.google_business');

        $response = Http::asForm()->post($config['token_url'], [
            'code' => $request->get('code'),
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed'));
        }

        $tokenData = $response->json();

        // Get locations
        $locationsResponse = Http::withToken($tokenData['access_token'])
            ->get('https://mybusiness.googleapis.com/v4/accounts');

        $accountName = 'Google Business Profile';
        $accountId = 'gbp_' . Str::random(10);

        if ($locationsResponse->successful()) {
            $accounts = $locationsResponse->json('accounts', []);
            if (!empty($accounts)) {
                $accountName = $accounts[0]['accountName'] ?? 'Google Business Profile';
                $accountId = $accounts[0]['name'] ?? $accountId;
            }
        }

        // Create or update the connection within a transaction with RLS context
        $connection = DB::transaction(function () use ($orgId, $accountId, $accountName, $tokenData) {
            // Set RLS context for the organization (LOCAL = true, applies to this transaction)
            DB::statement("SELECT set_config('app.current_org_id', ?, true)", [$orgId]);

            return PlatformConnection::updateOrCreate(
                [
                    'org_id' => $orgId,
                    'platform' => 'google_business',
                    'account_id' => $accountId,
                ],
                [
                    'account_name' => $accountName,
                    'status' => 'active',
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_expires_at' => isset($tokenData['expires_in'])
                        ? now()->addSeconds($tokenData['expires_in'])
                        : null,
                    'scopes' => explode(' ', $tokenData['scope'] ?? ''),
                    'account_metadata' => [
                        'token_type' => $tokenData['token_type'] ?? 'Bearer',
                        'connected_at' => now()->toIso8601String(),
                    ],
                ]
            );
        });

        session()->forget('oauth_state');

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', __('settings.google_business_profile_connected_successfully'));
    }

    /**
     * Initiate Google OAuth authorization (unified for all Google services).
     */
    public function authorizeGoogle(Request $request, string $org)
    {
        $config = config('social-platforms.google');

        if (!$config['client_id'] || !$config['client_secret']) {
            return redirect()->route('orgs.settings.platform-connections.index', $org)
                ->with('error', __('settings.not_configured'));
        }

        $stateData = ['org_id' => $org, 'platform' => 'google'];

        // Include wizard mode in state if present
        if ($request->has('wizard_mode')) {
            $stateData['wizard_mode'] = true;
        }

        $state = base64_encode(json_encode($stateData));
        session(['oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(' ', $config['scopes']),
            'state' => $state,
            'access_type' => 'offline',
            // Force account selector AND consent screen
            // This allows users to select Brand Accounts and ensures we get a refresh token
            'prompt' => 'select_account consent',
            'include_granted_scopes' => 'true',
        ]);

        return redirect($config['authorize_url'] . '?' . $params);
    }

    /**
     * Handle Google OAuth callback (unified for all Google services).
     */
    public function callbackGoogle(Request $request)
    {
        $state = json_decode(base64_decode($request->get('state')), true);
        $orgId = $state['org_id'] ?? null;
        $platform = $state['platform'] ?? 'google';

        // Check if this is YouTube incremental auth callback
        if ($platform === 'google_youtube') {
            return $this->handleGoogleYouTubeCallback($request, $state);
        }

        if (!$orgId || $request->get('state') !== session('oauth_state')) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId ?? 'default')
                ->with('error', __('settings.invalid'));
        }

        if ($request->has('error')) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.google_auth_denied', ['error' => $request->get('error_description', $request->get('error'))]));
        }

        $config = config('social-platforms.google');

        // Exchange code for tokens
        $response = Http::asForm()->post($config['token_url'], [
            'code' => $request->get('code'),
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            Log::error('Google OAuth token exchange failed', [
                'error' => $response->json(),
                'status' => $response->status(),
            ]);
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed'));
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'];
        $refreshToken = $tokenData['refresh_token'] ?? null;
        $expiresIn = $tokenData['expires_in'] ?? 3600;
        $grantedScopes = explode(' ', $tokenData['scope'] ?? '');

        // Get user info
        $userInfo = $this->getGoogleUserInfo($accessToken);

        $accountName = $userInfo['name'] ?? $userInfo['email'] ?? 'Google Account';
        $accountId = $userInfo['id'] ?? 'google_' . Str::random(10);
        $accountEmail = $userInfo['email'] ?? null;

        // Prepare the data for upsert
        $updateData = [
            'account_name' => $accountName,
            'status' => 'active',
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_expires_at' => now()->addSeconds($expiresIn),
            'scopes' => $grantedScopes,
            'account_metadata' => [
                'credential_type' => 'oauth',
                'token_type' => $tokenData['token_type'] ?? 'Bearer',
                'user_id' => $accountId,
                'email' => $accountEmail,
                'name' => $userInfo['name'] ?? null,
                'picture' => $userInfo['picture'] ?? null,
                'granted_scopes' => $grantedScopes,
                'connected_at' => now()->toIso8601String(),
                'connected_via' => 'oauth_direct',
            ],
            'auto_sync' => true,
            'sync_frequency_minutes' => 60,
        ];

        // Find existing connection (including soft-deleted) and update/restore it
        $connection = DB::transaction(function () use ($orgId, $accountId, $updateData) {
            // Set RLS context for the organization
            DB::statement("SELECT set_config('app.current_org_id', ?, true)", [$orgId]);

            // Find existing connection INCLUDING soft-deleted ones (to avoid unique constraint violation)
            $existing = PlatformConnection::withTrashed()
                ->where('org_id', $orgId)
                ->where('platform', 'google')
                ->where('account_id', $accountId)
                ->first();

            if ($existing) {
                // Restore if soft-deleted
                if ($existing->trashed()) {
                    $existing->restore();
                }

                // Merge new metadata with existing metadata to preserve YouTube data and user selections
                $existingMetadata = $existing->account_metadata ?? [];
                $newMetadata = $updateData['account_metadata'];

                // Preserve important fields that should not be overwritten during reconnection
                $preserveFields = [
                    'youtube_channels',           // Stored YouTube channels
                    'youtube_brand_account',      // Brand Account token data
                    'youtube_authorized',         // YouTube authorization flag
                    'youtube_authorized_at',      // YouTube authorization timestamp
                    'youtube_channels_updated_at', // Channels last updated
                    'selected_assets',            // User's selected assets
                    'assets_updated_at',          // Assets selection timestamp
                ];

                foreach ($preserveFields as $field) {
                    if (isset($existingMetadata[$field])) {
                        $newMetadata[$field] = $existingMetadata[$field];
                    }
                }

                // Update the connection with merged metadata
                $existing->update([
                    'account_name' => $updateData['account_name'],
                    'status' => $updateData['status'],
                    'access_token' => $updateData['access_token'],
                    'refresh_token' => $updateData['refresh_token'],
                    'token_expires_at' => $updateData['token_expires_at'],
                    'scopes' => $updateData['scopes'],
                    'account_metadata' => $newMetadata,
                    'auto_sync' => $updateData['auto_sync'],
                    'sync_frequency_minutes' => $updateData['sync_frequency_minutes'],
                ]);

                return $existing->fresh();
            } else {
                // Create new connection
                return PlatformConnection::create([
                    'org_id' => $orgId,
                    'platform' => 'google',
                    'account_id' => $accountId,
                    'account_name' => $updateData['account_name'],
                    'status' => $updateData['status'],
                    'access_token' => $updateData['access_token'],
                    'refresh_token' => $updateData['refresh_token'],
                    'token_expires_at' => $updateData['token_expires_at'],
                    'scopes' => $updateData['scopes'],
                    'account_metadata' => $updateData['account_metadata'],
                    'auto_sync' => $updateData['auto_sync'],
                    'sync_frequency_minutes' => $updateData['sync_frequency_minutes'],
                ]);
            }
        });

        session()->forget('oauth_state');

        // Check for wizard mode and redirect accordingly
        if (!empty($state['wizard_mode'])) {
            return redirect()
                ->route('orgs.settings.platform-connections.wizard.assets', [$orgId, 'google', $connection->connection_id])
                ->with('success', __('wizard.mode.direct.connecting'));
        }

        // Redirect to asset selection page
        return redirect()
            ->route('orgs.settings.platform-connections.google.assets', [$orgId, $connection->connection_id])
            ->with('success', __('settings.google_account_connected_successfully_now_select_w'));
    }

    /**
     * Get Google user info from access token.
     */
    private function getGoogleUserInfo(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v2/userinfo');

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Failed to get Google user info', ['response' => $response->json()]);
            return [];
        } catch (\Exception $e) {
            Log::error('Exception getting Google user info', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Initiate Google OAuth for YouTube scopes (incremental authorization).
     * This allows adding YouTube permissions to an existing Google connection.
     */
    public function authorizeGoogleYouTube(Request $request, string $org, string $connection)
    {
        // Verify connection exists and belongs to this org
        $platformConnection = PlatformConnection::where('connection_id', $connection)
            ->where('org_id', $org)
            ->where('platform', 'google')
            ->first();

        if (!$platformConnection) {
            return redirect()->route('orgs.settings.platform-connections.index', $org)
                ->with('error', __('settings.connection_not_found'));
        }

        $config = config('social-platforms.google');

        if (!$config['client_id'] || !$config['client_secret']) {
            return redirect()->route('orgs.settings.platform-connections.google.assets', [$org, $connection])
                ->with('error', __('settings.google_not_configured'));
        }

        // Get the email and user_id from the existing connection for login_hint and validation
        $loginHint = $platformConnection->account_metadata['email'] ?? null;
        $storedSub = $platformConnection->account_metadata['user_id'] ?? null;

        $stateData = [
            'org_id' => $org,
            'platform' => 'google_youtube',
            'connection_id' => $connection,
            'expected_sub' => $storedSub,
        ];

        $state = base64_encode(json_encode($stateData));
        session(['oauth_state_youtube' => $state]);

        // Build OAuth URL with YouTube scopes only + core identity scopes
        $youtubeScopes = $config['youtube_scopes'] ?? [
            'https://www.googleapis.com/auth/youtube.readonly',
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube',
        ];

        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],  // Use same callback as main Google OAuth
            'response_type' => 'code',
            'scope' => implode(' ', array_merge(['openid', 'email'], $youtubeScopes)),
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
        ];

        // Add login_hint if we have the user's email (guides user to correct account)
        if ($loginHint) {
            $params['login_hint'] = $loginHint;
        }

        return redirect($config['authorize_url'] . '?' . http_build_query($params));
    }

    /**
     * Handle Google OAuth callback for YouTube incremental authorization.
     * This is called from callbackGoogle when the state indicates YouTube auth.
     */
    private function handleGoogleYouTubeCallback(Request $request, array $state)
    {
        $stateParam = $request->get('state');
        $orgId = $state['org_id'] ?? null;
        $connectionId = $state['connection_id'] ?? null;
        $expectedSub = $state['expected_sub'] ?? null;

        // Validate state
        if (!$orgId || !$connectionId || $stateParam !== session('oauth_state_youtube')) {
            Log::warning('YouTube OAuth state validation failed', [
                'has_org' => (bool) $orgId,
                'has_connection' => (bool) $connectionId,
                'state_match' => $stateParam === session('oauth_state_youtube'),
            ]);
            return redirect()->route('orgs.settings.platform-connections.index', $orgId ?? 'default')
                ->with('error', __('settings.invalid_oauth_state'));
        }

        if ($request->has('error')) {
            Log::warning('YouTube OAuth was denied', ['error' => $request->get('error')]);
            return redirect()->route('orgs.settings.platform-connections.google.assets', [$orgId, $connectionId])
                ->with('error', __('settings.google_auth_denied', ['error' => $request->get('error_description', $request->get('error'))]));
        }

        // Get existing connection
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $orgId)
            ->where('platform', 'google')
            ->first();

        if (!$connection) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.connection_not_found'));
        }

        $config = config('social-platforms.google');

        // Exchange code for tokens
        $response = Http::asForm()->post($config['token_url'], [
            'code' => $request->get('code'),
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],  // Use same callback as main Google OAuth
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            Log::error('Google YouTube OAuth token exchange failed', [
                'error' => $response->json(),
                'status' => $response->status(),
            ]);
            return redirect()->route('orgs.settings.platform-connections.google.assets', [$orgId, $connectionId])
                ->with('error', __('settings.youtube_oauth_failed'));
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'];
        $refreshToken = $tokenData['refresh_token'] ?? null;
        $expiresIn = $tokenData['expires_in'] ?? 3600;
        $grantedScopes = explode(' ', $tokenData['scope'] ?? '');

        // DEBUG: Log token data (without sensitive values)
        Log::info('YouTube OAuth token data received', [
            'connection_id' => $connectionId,
            'has_access_token' => !empty($accessToken),
            'has_refresh_token' => !empty($refreshToken),
            'expires_in' => $expiresIn,
            'granted_scopes' => $grantedScopes,
            'token_type' => $tokenData['token_type'] ?? 'unknown',
        ]);

        // Validate account consistency - get user info and compare sub
        $userInfo = $this->getGoogleUserInfo($accessToken);
        $newSub = $userInfo['id'] ?? null;

        // DEBUG: Log user info
        Log::info('YouTube OAuth user info', [
            'connection_id' => $connectionId,
            'user_id' => $newSub,
            'user_email' => $userInfo['email'] ?? 'not provided',
            'user_name' => $userInfo['name'] ?? 'not provided',
        ]);

        // Verify the same account was used (allow Brand Accounts for YouTube)
        $userEmail = $userInfo['email'] ?? '';
        $isBrandAccount = str_ends_with($userEmail, '@pages.plusgoogle.com');

        if ($expectedSub && $newSub && $expectedSub !== $newSub) {
            if ($isBrandAccount) {
                // Allow Brand Account connections for YouTube
                Log::info('YouTube OAuth - Brand Account detected, allowing connection', [
                    'connection_id' => $connectionId,
                    'original_account_sub' => $expectedSub,
                    'brand_account_sub' => $newSub,
                    'brand_account_email' => $userEmail,
                ]);
            } else {
                // Block non-Brand Account mismatches
                Log::warning('YouTube OAuth account mismatch', [
                    'connection_id' => $connectionId,
                    'expected_sub' => $expectedSub,
                    'actual_sub' => $newSub,
                ]);
                return redirect()->route('orgs.settings.platform-connections.google.assets', [$orgId, $connectionId])
                    ->with('error', __('settings.youtube_account_mismatch'));
            }
        }

        // Merge new scopes with existing scopes
        $existingScopes = $connection->scopes ?? [];
        $mergedScopes = array_values(array_unique(array_merge($existingScopes, $grantedScopes)));

        // Get existing stored YouTube channels BEFORE modifying metadata
        $existingYoutubeChannels = $connection->account_metadata['youtube_channels'] ?? [];

        // Update account metadata (preserve existing youtube_channels)
        $accountMetadata = $connection->account_metadata ?? [];
        $accountMetadata['youtube_authorized'] = true;
        $accountMetadata['youtube_authorized_at'] = now()->toIso8601String();
        $accountMetadata['granted_scopes'] = $mergedScopes;
        $accountMetadata['youtube_channels'] = $existingYoutubeChannels; // Preserve existing channels

        // Store Brand Account info if applicable
        // Brand Account tokens are stored separately to preserve the main account's token
        // which has permissions for Analytics, Tag Manager, Ads, etc.
        if ($isBrandAccount) {
            $accountMetadata['youtube_brand_account'] = [
                'sub' => $newSub,
                'email' => $userEmail,
                'connected_at' => now()->toIso8601String(),
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_expires_at' => now()->addSeconds($expiresIn)->toIso8601String(),
            ];
        }

        // Update connection - only update main tokens if NOT a Brand Account
        // Brand Account tokens are stored separately in metadata to avoid
        // replacing the main token which has permissions for other Google services
        $updateData = [
            'scopes' => $mergedScopes,
            'account_metadata' => $accountMetadata,
        ];

        if (!$isBrandAccount) {
            // Same account - safe to update main tokens
            $updateData['access_token'] = $accessToken;
            $updateData['refresh_token'] = $refreshToken ?? $connection->refresh_token;
            $updateData['token_expires_at'] = now()->addSeconds($expiresIn);
        }

        $connection->update($updateData);

        session()->forget('oauth_state_youtube');

        // Fetch YouTube channels accessible with this token and STORE them in metadata
        $newChannelName = null;
        try {
            $youtubeResponse = Http::withToken($accessToken)
                ->timeout(30)
                ->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet,statistics,brandingSettings',
                    'mine' => 'true',
                    'maxResults' => 50,
                ]);

            $youtubeData = $youtubeResponse->json();
            $discoveredChannels = [];

            foreach ($youtubeData['items'] ?? [] as $ch) {
                $channelData = [
                    'id' => $ch['id'] ?? null,
                    'title' => $ch['snippet']['title'] ?? 'Unknown Channel',
                    'description' => \Illuminate\Support\Str::limit($ch['snippet']['description'] ?? '', 100),
                    'thumbnail' => $ch['snippet']['thumbnails']['default']['url'] ?? null,
                    'custom_url' => $ch['snippet']['customUrl'] ?? null,
                    'subscriber_count' => $ch['statistics']['subscriberCount'] ?? 0,
                    'video_count' => $ch['statistics']['videoCount'] ?? 0,
                    'view_count' => $ch['statistics']['viewCount'] ?? 0,
                    'type' => $isBrandAccount ? 'brand' : 'personal',
                    'added_at' => now()->toIso8601String(),
                    'brand_account_sub' => $isBrandAccount ? $newSub : null,
                    'brand_account_email' => $isBrandAccount ? $userEmail : null,
                ];
                if ($channelData['id']) {
                    $discoveredChannels[] = $channelData;
                    $newChannelName = $channelData['title'];
                }
            }

            Log::info('YouTube OAuth - Channels discovered', [
                'connection_id' => $connectionId,
                'status_code' => $youtubeResponse->status(),
                'discovered_count' => count($discoveredChannels),
                'channels' => collect($discoveredChannels)->map(fn($c) => ['id' => $c['id'], 'title' => $c['title']])->toArray(),
            ]);

            // Get existing stored channels from metadata
            $existingChannels = $accountMetadata['youtube_channels'] ?? [];
            $existingChannelIds = array_column($existingChannels, 'id');

            // Add newly discovered channels (avoid duplicates)
            $addedCount = 0;
            foreach ($discoveredChannels as $channel) {
                if (!in_array($channel['id'], $existingChannelIds)) {
                    $existingChannels[] = $channel;
                    $existingChannelIds[] = $channel['id'];
                    $addedCount++;
                }
            }

            // Update metadata with merged channels
            $accountMetadata['youtube_channels'] = $existingChannels;
            $accountMetadata['youtube_channels_updated_at'] = now()->toIso8601String();

            // Update the connection with the stored channels
            $connection->update([
                'account_metadata' => $accountMetadata,
            ]);

            Log::info('YouTube OAuth - Channels stored in metadata', [
                'connection_id' => $connectionId,
                'total_stored' => count($existingChannels),
                'newly_added' => $addedCount,
            ]);

        } catch (\Exception $e) {
            Log::warning('YouTube OAuth - Failed to fetch/store channels', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
        }

        // Clear the YouTube channels cache so fresh data is fetched
        Cache::forget("google_assets:{$connectionId}:youtube_channels");
        Log::info('YouTube OAuth - Cleared YouTube channels cache', [
            'connection_id' => $connectionId,
        ]);

        Log::info('YouTube OAuth completed successfully', [
            'connection_id' => $connectionId,
            'scopes_count' => count($mergedScopes),
        ]);

        // Build success message
        $successMessage = $newChannelName
            ? __('settings.youtube_channel_added', ['channel' => $newChannelName])
            : __('settings.youtube_connected_successfully');

        return redirect()->route('orgs.settings.platform-connections.google.assets', [$orgId, $connectionId])
            ->with('success', $successMessage);
    }

    /**
     * Initiate Snapchat OAuth authorization.
     */
    public function authorizeSnapchat(Request $request, string $org)
    {
        $config = config('social-platforms.snapchat');
        $stateData = ['org_id' => $org, 'platform' => 'snapchat'];

        // Include wizard mode in state if present
        if ($request->has('wizard_mode')) {
            $stateData['wizard_mode'] = true;
        }

        $state = base64_encode(json_encode($stateData));
        session(['oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(',', $config['scopes']),
            'state' => $state,
        ]);

        return redirect($config['authorize_url'] . '?' . $params);
    }

    /**
     * Handle Snapchat OAuth callback.
     */
    public function callbackSnapchat(Request $request)
    {
        $state = json_decode(base64_decode($request->get('state')), true);
        $orgId = $state['org_id'] ?? null;

        if (!$orgId || $request->get('state') !== session('oauth_state')) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId ?? 'default')
                ->with('error', __('settings.invalid'));
        }

        $config = config('social-platforms.snapchat');

        $response = Http::asForm()->post($config['token_url'], [
            'code' => $request->get('code'),
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', __('settings.operation_failed'));
        }

        $tokenData = $response->json();

        $accountName = 'Snapchat Account';
        $accountId = 'snapchat_' . Str::random(10);

        // Create or update the connection within a transaction with RLS context
        $connection = DB::transaction(function () use ($orgId, $accountId, $accountName, $tokenData) {
            // Set RLS context for the organization (LOCAL = true, applies to this transaction)
            DB::statement("SELECT set_config('app.current_org_id', ?, true)", [$orgId]);

            return PlatformConnection::updateOrCreate(
                [
                    'org_id' => $orgId,
                    'platform' => 'snapchat',
                    'account_id' => $accountId,
                ],
                [
                    'account_name' => $accountName,
                    'status' => 'active',
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_expires_at' => isset($tokenData['expires_in'])
                        ? now()->addSeconds($tokenData['expires_in'])
                        : null,
                    'account_metadata' => [
                        'token_type' => $tokenData['token_type'] ?? 'Bearer',
                        'connected_at' => now()->toIso8601String(),
                    ],
                ]
            );
        });

        session()->forget('oauth_state');

        // Check for wizard mode and redirect accordingly
        if (!empty($state['wizard_mode'])) {
            return redirect()
                ->route('orgs.settings.platform-connections.wizard.assets', [$orgId, 'snapchat', $connection->connection_id])
                ->with('success', __('wizard.mode.direct.connecting'));
        }

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', __('settings.snapchat_account_connected_successfully'));
    }

    /**
     * Sync Integration records for selected Meta assets.
     * Creates individual Integration records for each selected asset (Page, Instagram, Threads, etc.)
     */
    private function syncIntegrationRecords(string $orgId, PlatformConnection $connection, array $selectedAssets): void
    {
        $accessToken = $connection->access_token;
        $metadata = $connection->account_metadata ?? [];

        // Mapping of asset types to platform names and methods to fetch details
        $assetTypeMapping = [
            'page' => ['platform' => 'facebook', 'method' => 'getMetaPages'],
            'instagram_account' => ['platform' => 'instagram', 'method' => 'getMetaInstagramAccounts'],
            'threads_account' => ['platform' => 'threads', 'method' => 'getThreadsAccounts'],
        ];

        // Track all integration IDs that should exist after sync
        $expectedIntegrationIds = [];

        foreach ($assetTypeMapping as $assetType => $config) {
            if (empty($selectedAssets[$assetType])) {
                continue;
            }

            $platform = $config['platform'];
            $method = $config['method'];

            // Fetch asset details from Meta API
            $assets = [];
            try {
                if ($method === 'getMetaPages') {
                    $assets = $this->getMetaPages($accessToken);
                } elseif ($method === 'getMetaInstagramAccounts') {
                    $pages = $this->getMetaPages($accessToken);
                    $assets = $this->getMetaInstagramAccounts($accessToken, $pages);
                } elseif ($method === 'getThreadsAccounts') {
                    $pages = $this->getMetaPages($accessToken);
                    $igAccounts = $this->getMetaInstagramAccounts($accessToken, $pages);
                    $assets = $this->getThreadsAccounts($accessToken, $igAccounts);
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch {$platform} assets", ['error' => $e->getMessage()]);
                continue;
            }

            // Create array keyed by asset ID for easy lookup
            $assetsById = collect($assets)->keyBy('id')->toArray();

            foreach ($selectedAssets[$assetType] as $assetId) {
                $assetData = $assetsById[$assetId] ?? null;

                // Determine account name
                $accountName = 'Unknown';
                $accountUsername = null;
                $avatarUrl = null;

                if ($assetData) {
                    if ($platform === 'facebook') {
                        $accountName = $assetData['name'] ?? 'Unknown Page';
                        $avatarUrl = $assetData['picture'] ?? null;
                    } elseif ($platform === 'instagram') {
                        $accountName = $assetData['name'] ?? $assetData['username'] ?? 'Unknown Account';
                        $accountUsername = $assetData['username'] ?? null;
                        $avatarUrl = $assetData['profile_picture'] ?? null;
                    } elseif ($platform === 'threads') {
                        $accountName = $assetData['name'] ?? $assetData['username'] ?? 'Unknown Account';
                        $accountUsername = $assetData['username'] ?? null;
                        $avatarUrl = $assetData['profile_picture'] ?? null;
                    }
                }

                // Create or update Integration record
                $integration = Integration::updateOrCreate(
                    [
                        'org_id' => $orgId,
                        'platform' => $platform,
                        'account_id' => $assetId,
                    ],
                    [
                        'account_name' => $accountName,
                        'username' => $accountUsername,
                        'avatar_url' => $avatarUrl,
                        'status' => 'active',
                        'is_active' => true,
                        'access_token' => $accessToken, // Share the same access token from platform connection
                        'metadata' => array_merge($assetData ?? [], [
                            'connection_id' => $connection->connection_id,
                            'synced_at' => now()->toIso8601String(),
                        ]),
                    ]
                );

                $expectedIntegrationIds[] = $integration->integration_id;
            }
        }

        // Deactivate Integration records that are no longer selected
        // Only deactivate integrations that were created from this connection
        Integration::where('org_id', $orgId)
            ->whereIn('platform', ['facebook', 'instagram', 'threads'])
            ->where('metadata->connection_id', $connection->connection_id)
            ->whereNotIn('integration_id', $expectedIntegrationIds)
            ->update([
                'is_active' => false,
                'status' => 'inactive',
            ]);
    }

    /**
     * Sync Integration records for selected Google assets.
     * Creates individual Integration records for each selected asset (YouTube, Business Profile, etc.)
     */
    private function syncGoogleIntegrationRecords(string $orgId, PlatformConnection $connection, array $selectedAssets): void
    {
        $accessToken = $connection->access_token;

        // Mapping of asset types to platform names and methods to fetch details
        $assetTypeMapping = [
            'youtube_channel' => ['platform' => 'youtube', 'method' => 'getGoogleYouTubeChannels'],
            'business_profile' => ['platform' => 'google_business', 'method' => 'getGoogleBusinessProfiles'],
        ];

        // Track all integration IDs that should exist after sync
        $expectedIntegrationIds = [];

        foreach ($assetTypeMapping as $assetType => $config) {
            if (empty($selectedAssets[$assetType])) {
                continue;
            }

            $platform = $config['platform'];
            $method = $config['method'];

            // Get asset details - prefer stored metadata (what user saw when selecting)
            $assets = [];
            try {
                if ($method === 'getGoogleYouTubeChannels') {
                    // First try stored metadata from when user viewed the assets page
                    $storedChannels = $connection->account_metadata['youtube_channels'] ?? [];

                    if (!empty($storedChannels)) {
                        $assets = $storedChannels;
                        Log::info('Using stored YouTube channels for profile sync', [
                            'connection_id' => $connection->connection_id,
                            'channel_count' => count($assets),
                        ]);
                    } else {
                        // Fall back to API fetch if no stored data
                        $assets = $this->getGoogleYouTubeChannels($connection);
                        Log::info('Fetched YouTube channels from API for profile sync', [
                            'connection_id' => $connection->connection_id,
                            'channel_count' => count($assets),
                        ]);
                    }
                } elseif ($method === 'getGoogleBusinessProfiles') {
                    // First try stored metadata from when user viewed the assets page
                    $storedProfiles = $connection->account_metadata['business_profiles'] ?? [];

                    if (!empty($storedProfiles)) {
                        $assets = $storedProfiles;
                        Log::info('Using stored Google Business Profiles for profile sync', [
                            'connection_id' => $connection->connection_id,
                            'profile_count' => count($assets),
                        ]);
                    } else {
                        // Fall back to API fetch if no stored data
                        $assets = $this->getGoogleBusinessProfiles($connection);
                        Log::info('Fetched Google Business Profiles from API for profile sync', [
                            'connection_id' => $connection->connection_id,
                            'profile_count' => count($assets),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch {$platform} assets", ['error' => $e->getMessage()]);
                continue;
            }

            // Create array keyed by asset ID for easy lookup
            $assetsById = collect($assets)->keyBy('id')->toArray();

            foreach ($selectedAssets[$assetType] as $assetId) {
                $assetData = $assetsById[$assetId] ?? null;

                // Determine account name
                $accountName = 'Unknown';
                $accountUsername = null;
                $avatarUrl = null;

                if ($assetData) {
                    if ($platform === 'youtube') {
                        $accountName = $assetData['title'] ?? $assetData['name'] ?? 'Unknown Channel';
                        $accountUsername = $assetData['custom_url'] ?? $assetData['customUrl'] ?? $assetData['handle'] ?? null;
                        $avatarUrl = $assetData['thumbnail'] ?? $assetData['thumbnails']['default']['url'] ?? null;
                    } elseif ($platform === 'google_business') {
                        $accountName = $assetData['title'] ?? $assetData['name'] ?? 'Unknown Business';
                        $avatarUrl = $assetData['profile_photo_url'] ?? null;
                    }
                }

                // Check if soft-deleted integration exists and restore it
                $existingIntegration = Integration::withTrashed()
                    ->where('org_id', $orgId)
                    ->where('platform', $platform)
                    ->where('account_id', $assetId)
                    ->first();

                if ($existingIntegration && $existingIntegration->trashed()) {
                    // Restore the profile and its queue settings
                    $existingIntegration->restore();

                    // Restore queue settings if they exist
                    IntegrationQueueSettings::withTrashed()
                        ->where('integration_id', $existingIntegration->integration_id)
                        ->restore();

                    Log::info('Restored soft-deleted Google profile and related data', [
                        'integration_id' => $existingIntegration->integration_id,
                        'platform' => $platform,
                        'account_id' => $assetId,
                    ]);
                }

                // Create or update Integration record
                $integration = Integration::updateOrCreate(
                    [
                        'org_id' => $orgId,
                        'platform' => $platform,
                        'account_id' => $assetId,
                    ],
                    [
                        'account_name' => $accountName,
                        'username' => $accountUsername,
                        'avatar_url' => $avatarUrl,
                        'status' => 'active',
                        'is_active' => true,
                        'access_token' => $accessToken, // Share the same access token from platform connection
                        'metadata' => array_merge($assetData ?? [], [
                            'connection_id' => $connection->connection_id,
                            'synced_at' => now()->toIso8601String(),
                        ]),
                    ]
                );

                $expectedIntegrationIds[] = $integration->integration_id;
            }
        }

        // Process deselected integrations - soft delete or mark inactive
        $profileService = app(ProfileSoftDeleteService::class);
        $deselectedProfiles = Integration::where('org_id', $orgId)
            ->whereIn('platform', ['youtube', 'google_business'])
            ->where('metadata->connection_id', $connection->connection_id)
            ->whereNotIn('integration_id', $expectedIntegrationIds)
            ->get();

        foreach ($deselectedProfiles as $profile) {
            // Check if this asset is used in another connection
            if (!$profileService->isAssetUsedInOtherConnections(
                $orgId,
                $profile->platform,
                $profile->account_id,
                $connection->connection_id
            )) {
                // Soft delete if not used elsewhere (observer handles cascade)
                $profile->delete();

                Log::info('Google profile soft deleted (asset deselected)', [
                    'integration_id' => $profile->integration_id,
                    'platform' => $profile->platform,
                    'account_id' => $profile->account_id,
                ]);
            } else {
                // Just mark inactive if used in another connection
                $profile->update([
                    'is_active' => false,
                    'status' => 'inactive',
                ]);

                Log::info('Google profile marked inactive (asset in other connection)', [
                    'integration_id' => $profile->integration_id,
                    'platform' => $profile->platform,
                ]);
            }
        }
    }

    // ==================================================================================
    // CONNECTION WIZARD METHODS
    // ==================================================================================

    /**
     * Display simplified wizard dashboard with platform grid.
     */
    public function wizardDashboard(Request $request, string $org)
    {
        $connections = PlatformConnection::where('org_id', $org)->get();
        $integrations = Integration::where('org_id', $org)->get();

        $platformStats = $this->buildWizardPlatformStats($connections, $integrations);
        $summary = $this->buildWizardSummary($platformStats, $integrations);

        if ($request->wantsJson()) {
            return $this->success([
                'platforms' => $platformStats,
                'summary' => $summary,
            ], 'Dashboard data retrieved successfully');
        }

        return view('settings.platform-connections.dashboard', [
            'currentOrg' => $org,
            'platforms' => $platformStats,
            'summary' => $summary,
        ]);
    }

    /**
     * Build platform statistics for wizard dashboard.
     */
    private function buildWizardPlatformStats($connections, $integrations): array
    {
        $platforms = config('platform-wizard.platforms', []);
        $stats = [];

        foreach ($platforms as $key => $config) {
            $platformConnections = $connections->where('platform', $key);
            $platformIntegrations = $integrations->whereIn('platform', $this->getPlatformIntegrationTypes($key));

            // Determine status
            $status = 'disconnected';
            if ($platformConnections->count() > 0) {
                $hasError = $platformConnections->contains('status', 'error');
                $hasWarning = $platformConnections->where('status', 'warning')->count() > 0
                    || $platformConnections->filter(function ($c) {
                        return $c->isTokenExpiringSoon();
                    })->count() > 0;
                $status = $hasError ? 'error' : ($hasWarning ? 'warning' : 'active');
            }

            $stats[$key] = [
                'key' => $key,
                'name' => $config['name'] ?? $key,
                'display_name' => __($config['display_name'] ?? $key),
                'description' => __($config['description'] ?? ''),
                'icon' => $config['icon'] ?? 'fas fa-plug',
                'color' => $config['color'] ?? '#6B7280',
                'text_color' => $config['text_color'] ?? '#FFFFFF',
                'connected' => $platformConnections->count() > 0,
                'connections_count' => $platformConnections->count(),
                'assets_count' => $platformIntegrations->count(),
                'status' => $status,
                'supports_oauth' => $config['supports_oauth'] ?? false,
                'supports_manual' => $config['supports_manual'] ?? false,
            ];
        }

        return $stats;
    }

    /**
     * Get integration types for a platform.
     */
    private function getPlatformIntegrationTypes(string $platform): array
    {
        return match ($platform) {
            'meta' => ['facebook', 'instagram', 'threads'],
            'google' => ['youtube', 'google_ads', 'google_analytics', 'google_business'],
            'linkedin' => ['linkedin'],
            'tiktok' => ['tiktok'],
            'twitter' => ['twitter'],
            'snapchat' => ['snapchat'],
            default => [$platform],
        };
    }

    /**
     * Build summary statistics for wizard dashboard.
     */
    private function buildWizardSummary(array $platformStats, $integrations): array
    {
        $connectedPlatforms = collect($platformStats)->filter(fn($p) => $p['connected'])->count();
        $totalAssets = $integrations->count();

        // Calculate health status
        $hasError = collect($platformStats)->contains(fn($p) => $p['status'] === 'error');
        $hasWarning = collect($platformStats)->contains(fn($p) => $p['status'] === 'warning');
        $healthStatus = $hasError ? 'error' : ($hasWarning ? 'warning' : 'healthy');

        return [
            'platforms_connected' => $connectedPlatforms,
            'total_platforms' => count($platformStats),
            'total_assets' => $totalAssets,
            'health_status' => $healthStatus,
        ];
    }

    /**
     * Start wizard flow for a specific platform.
     */
    public function startWizard(Request $request, string $org, string $platform)
    {
        $platformConfig = config("platform-wizard.platforms.{$platform}");

        if (!$platformConfig) {
            if ($request->wantsJson()) {
                return $this->error(__('wizard.errors.platform_not_found'), 404);
            }
            return redirect()
                ->route('orgs.settings.platform-connections.wizard.dashboard', $org)
                ->with('error', __('wizard.errors.platform_not_found'));
        }

        // Store wizard state in session
        $wizardState = [
            'org_id' => $org,
            'platform' => $platform,
            'step' => 1,
            'started_at' => now()->toIso8601String(),
        ];
        session()->put(config('platform-wizard.wizard.session_key', 'platform_wizard_state'), $wizardState);

        // Build OAuth URL if supported
        $oauthUrl = null;
        if ($platformConfig['supports_oauth'] && isset($platformConfig['oauth_route'])) {
            try {
                $oauthUrl = route($platformConfig['oauth_route'], ['org' => $org]);
            } catch (\Exception $e) {
                Log::warning("Wizard: Could not generate OAuth URL for {$platform}", ['error' => $e->getMessage()]);
            }
        }

        // Build manual URL if supported
        $manualUrl = null;
        if ($platformConfig['supports_manual'] && isset($platformConfig['manual_route'])) {
            try {
                $manualUrl = route($platformConfig['manual_route'], ['org' => $org]);
            } catch (\Exception $e) {
                Log::warning("Wizard: Could not generate manual URL for {$platform}", ['error' => $e->getMessage()]);
            }
        }

        if ($request->wantsJson()) {
            return $this->success([
                'platform' => $platform,
                'config' => $platformConfig,
                'oauth_url' => $oauthUrl,
                'manual_url' => $manualUrl,
            ], 'Wizard initialized');
        }

        return view('settings.platform-connections.wizard.wizard', [
            'currentOrg' => $org,
            'platform' => $platform,
            'platformConfig' => $platformConfig,
            'oauthUrl' => $oauthUrl,
            'manualUrl' => $manualUrl,
            'step' => 1,
        ]);
    }

    /**
     * Handle wizard return after OAuth callback.
     */
    public function wizardOAuthReturn(Request $request, string $org, string $platform, string $connection)
    {
        $platformConfig = config("platform-wizard.platforms.{$platform}");

        if (!$platformConfig) {
            return redirect()
                ->route('orgs.settings.platform-connections.wizard.dashboard', $org)
                ->with('error', __('wizard.errors.platform_not_found'));
        }

        $connectionModel = PlatformConnection::where('connection_id', $connection)
            ->where('org_id', $org)
            ->first();

        if (!$connectionModel) {
            return redirect()
                ->route('orgs.settings.platform-connections.wizard.start', [$org, $platform])
                ->with('error', __('wizard.errors.connection_failed'));
        }

        // Update wizard state
        $wizardState = session()->get(config('platform-wizard.wizard.session_key', 'platform_wizard_state'), []);
        $wizardState['step'] = 2;
        $wizardState['connection_id'] = $connection;
        session()->put(config('platform-wizard.wizard.session_key', 'platform_wizard_state'), $wizardState);

        // Redirect to asset selection
        return redirect()->route('orgs.settings.platform-connections.wizard.assets', [$org, $platform, $connection]);
    }

    /**
     * Display wizard asset selection page.
     */
    public function wizardAssets(Request $request, string $org, string $platform, string $connection)
    {
        $platformConfig = config("platform-wizard.platforms.{$platform}");

        if (!$platformConfig) {
            return redirect()
                ->route('orgs.settings.platform-connections.wizard.dashboard', $org)
                ->with('error', __('wizard.errors.platform_not_found'));
        }

        $connectionModel = PlatformConnection::where('connection_id', $connection)
            ->where('org_id', $org)
            ->first();

        if (!$connectionModel) {
            return redirect()
                ->route('orgs.settings.platform-connections.wizard.start', [$org, $platform])
                ->with('error', __('wizard.errors.connection_failed'));
        }

        // Fetch available assets
        $assets = $this->fetchWizardPlatformAssets($connectionModel, $platform);
        $smartDefaults = $this->calculateWizardSmartDefaults($platform, $assets);

        // Get previously selected assets if any
        $previouslySelected = $connectionModel->account_metadata['selected_assets'] ?? [];

        if ($request->wantsJson()) {
            return $this->success([
                'connection_id' => $connection,
                'assets' => $assets,
                'smart_defaults' => $smartDefaults,
                'previously_selected' => $previouslySelected,
            ], 'Assets loaded');
        }

        return view('settings.platform-connections.wizard.wizard', [
            'currentOrg' => $org,
            'platform' => $platform,
            'platformConfig' => $platformConfig,
            'connection' => $connectionModel,
            'assets' => $assets,
            'smartDefaults' => $smartDefaults,
            'previouslySelected' => $previouslySelected,
            'step' => 2,
        ]);
    }

    /**
     * Fetch available assets for a platform connection.
     */
    private function fetchWizardPlatformAssets(PlatformConnection $connection, string $platform): array
    {
        $accessToken = $connection->access_token;

        try {
            return match ($platform) {
                'meta' => [
                    'page' => $this->getMetaPages($accessToken),
                    'instagram_account' => $this->getMetaInstagramAccounts($accessToken, $this->getMetaPages($accessToken)),
                    'threads_account' => $this->getThreadsAccounts($accessToken, []),
                    'ad_account' => $this->getMetaAdAccounts($accessToken),
                    'pixel' => $this->getMetaPixels($accessToken, $this->getMetaAdAccounts($accessToken)),
                    'catalog' => $this->getMetaCatalogs($accessToken),
                ],
                'google' => $this->getGoogleAssets($connection),
                'linkedin' => $this->getLinkedInAssets($connection),
                'tiktok' => $this->getTikTokAssets($connection),
                'twitter' => $this->getTwitterAssets($connection),
                'snapchat' => $this->getSnapchatAssets($connection),
                default => [],
            };
        } catch (\Exception $e) {
            Log::error("Wizard: Failed to fetch assets for {$platform}", [
                'error' => $e->getMessage(),
                'connection_id' => $connection->connection_id,
            ]);
            return [];
        }
    }

    /**
     * Get Google assets for wizard.
     */
    private function getGoogleAssets(PlatformConnection $connection): array
    {
        // Reuse existing methods or return empty if not implemented
        return [
            'youtube_channel' => [],
            'ads_account' => [],
            'analytics_property' => [],
        ];
    }

    /**
     * Get LinkedIn assets for wizard.
     */
    private function getLinkedInAssets(PlatformConnection $connection): array
    {
        return [
            'profile' => [],
            'page' => [],
            'ad_account' => [],
        ];
    }

    /**
     * Get TikTok assets for wizard.
     */
    private function getTikTokAssets(PlatformConnection $connection): array
    {
        return [
            'account' => [],
            'ad_account' => [],
        ];
    }

    /**
     * Get Twitter assets for wizard.
     */
    private function getTwitterAssets(PlatformConnection $connection): array
    {
        return [
            'account' => [],
            'ad_account' => [],
        ];
    }

    /**
     * Get Snapchat assets for wizard.
     */
    private function getSnapchatAssets(PlatformConnection $connection): array
    {
        return [
            'account' => [],
            'ad_account' => [],
        ];
    }

    /**
     * Calculate smart defaults for asset selection.
     */
    private function calculateWizardSmartDefaults(string $platform, array $assets): array
    {
        $platformConfig = config("platform-wizard.platforms.{$platform}", []);
        $defaults = [];

        foreach ($platformConfig['asset_types'] ?? [] as $assetType => $typeConfig) {
            $strategy = $typeConfig['smart_default'] ?? 'none';
            $availableAssets = $assets[$assetType] ?? [];

            if (empty($availableAssets)) {
                continue;
            }

            $selectedIds = match ($strategy) {
                'most_followers' => $this->selectWizardMostFollowers($availableAssets),
                'first' => [$availableAssets[0]['id'] ?? null],
                'all' => array_filter(array_column($availableAssets, 'id')),
                'active_only' => $this->selectWizardActiveOnly($availableAssets),
                'linked_to_instagram' => [], // Requires context of selected Instagram accounts
                'linked_to_ad_accounts' => [], // Requires context of selected ad accounts
                default => [],
            };

            $selectedIds = array_filter($selectedIds);
            if (!empty($selectedIds)) {
                $defaults[$assetType] = array_values($selectedIds);
            }
        }

        return $defaults;
    }

    /**
     * Select asset with most followers.
     */
    private function selectWizardMostFollowers(array $assets): array
    {
        if (empty($assets)) {
            return [];
        }

        $sorted = collect($assets)->sortByDesc(function ($asset) {
            return $asset['followers_count']
                ?? $asset['subscriber_count']
                ?? $asset['fan_count']
                ?? 0;
        });

        $first = $sorted->first();
        return $first ? [$first['id'] ?? null] : [];
    }

    /**
     * Select only active assets.
     */
    private function selectWizardActiveOnly(array $assets): array
    {
        return collect($assets)
            ->filter(function ($asset) {
                $status = strtolower($asset['status'] ?? '');
                $canCreateAds = $asset['can_create_ads'] ?? $asset['is_active'] ?? true;

                return $canCreateAds
                    && !in_array($status, ['disabled', 'inactive', 'suspended', 'closed']);
            })
            ->pluck('id')
            ->filter()
            ->toArray();
    }

    /**
     * Save wizard asset selections.
     */
    public function saveWizardAssets(Request $request, string $org, string $platform, string $connection)
    {
        $platformConfig = config("platform-wizard.platforms.{$platform}");

        if (!$platformConfig) {
            return $request->wantsJson()
                ? $this->error(__('wizard.errors.platform_not_found'), 404)
                : redirect()->route('orgs.settings.platform-connections.wizard.dashboard', $org)
                    ->with('error', __('wizard.errors.platform_not_found'));
        }

        $connectionModel = PlatformConnection::where('connection_id', $connection)
            ->where('org_id', $org)
            ->first();

        if (!$connectionModel) {
            return $request->wantsJson()
                ? $this->error(__('wizard.errors.connection_failed'), 404)
                : redirect()->route('orgs.settings.platform-connections.wizard.start', [$org, $platform])
                    ->with('error', __('wizard.errors.connection_failed'));
        }

        $selectedAssets = $request->input('selected_assets', []);

        // Save selected assets to connection metadata
        $metadata = $connectionModel->account_metadata ?? [];
        $metadata['selected_assets'] = $selectedAssets;
        $metadata['assets_updated_at'] = now()->toIso8601String();
        $metadata['wizard_completed'] = true;

        $connectionModel->update(['account_metadata' => $metadata]);

        // Sync integration records based on platform
        try {
            $this->syncWizardIntegrationRecords($org, $connectionModel, $platform, $selectedAssets);
        } catch (\Exception $e) {
            Log::error("Wizard: Failed to sync integrations for {$platform}", [
                'error' => $e->getMessage(),
                'connection_id' => $connection,
            ]);
        }

        // Update wizard state
        $wizardState = session()->get(config('platform-wizard.wizard.session_key', 'platform_wizard_state'), []);
        $wizardState['step'] = 3;
        session()->put(config('platform-wizard.wizard.session_key', 'platform_wizard_state'), $wizardState);

        if ($request->wantsJson()) {
            return $this->success([
                'connection_id' => $connection,
                'selected_assets' => $selectedAssets,
            ], 'Assets saved successfully');
        }

        return redirect()->route('orgs.settings.platform-connections.wizard.success', [$org, $platform, $connection]);
    }

    /**
     * Sync integration records for wizard.
     */
    private function syncWizardIntegrationRecords(string $orgId, PlatformConnection $connection, string $platform, array $selectedAssets): void
    {
        // Delegate to existing sync methods based on platform
        switch ($platform) {
            case 'meta':
                $this->syncMetaIntegrationRecords($orgId, $connection, $selectedAssets);
                break;
            case 'google':
                $this->syncGoogleIntegrationRecords($orgId, $connection, $selectedAssets);
                break;
            // Other platforms can be added here
            default:
                Log::info("Wizard: No sync handler for platform {$platform}");
        }
    }

    /**
     * Display wizard success screen.
     */
    public function wizardSuccess(Request $request, string $org, string $platform, string $connection)
    {
        $platformConfig = config("platform-wizard.platforms.{$platform}");

        if (!$platformConfig) {
            return redirect()
                ->route('orgs.settings.platform-connections.wizard.dashboard', $org)
                ->with('error', __('wizard.errors.platform_not_found'));
        }

        $connectionModel = PlatformConnection::where('connection_id', $connection)
            ->where('org_id', $org)
            ->first();

        if (!$connectionModel) {
            return redirect()
                ->route('orgs.settings.platform-connections.wizard.dashboard', $org)
                ->with('error', __('wizard.errors.connection_failed'));
        }

        // Get synced integrations count
        $syncedCount = Integration::where('org_id', $org)
            ->whereIn('platform', $this->getPlatformIntegrationTypes($platform))
            ->where('metadata->connection_id', $connection)
            ->where('is_active', true)
            ->count();

        // Clear wizard state
        session()->forget(config('platform-wizard.wizard.session_key', 'platform_wizard_state'));

        if ($request->wantsJson()) {
            return $this->success([
                'connection_id' => $connection,
                'synced_count' => $syncedCount,
            ], 'Connection completed successfully');
        }

        return view('settings.platform-connections.wizard.wizard', [
            'currentOrg' => $org,
            'platform' => $platform,
            'platformConfig' => $platformConfig,
            'connection' => $connectionModel,
            'syncedCount' => $syncedCount,
            'step' => 3,
        ]);
    }

    /**
     * Get wizard platform configuration (API).
     */
    public function getWizardPlatformConfig(Request $request, string $org, string $platform)
    {
        $platformConfig = config("platform-wizard.platforms.{$platform}");

        if (!$platformConfig) {
            return $this->error(__('wizard.errors.platform_not_found'), 404);
        }

        return $this->success([
            'platform' => $platform,
            'config' => $platformConfig,
        ], 'Platform config retrieved');
    }

    /**
     * Get wizard connection assets (API).
     */
    public function getWizardConnectionAssets(Request $request, string $org, string $platform, string $connection)
    {
        $connectionModel = PlatformConnection::where('connection_id', $connection)
            ->where('org_id', $org)
            ->first();

        if (!$connectionModel) {
            return $this->error(__('wizard.errors.connection_failed'), 404);
        }

        $assets = $this->fetchWizardPlatformAssets($connectionModel, $platform);
        $smartDefaults = $this->calculateWizardSmartDefaults($platform, $assets);

        return $this->success([
            'assets' => $assets,
            'smart_defaults' => $smartDefaults,
        ], 'Assets retrieved');
    }

    /**
     * Get wizard dashboard stats (API).
     */
    public function getWizardStats(Request $request, string $org)
    {
        $connections = PlatformConnection::where('org_id', $org)->get();
        $integrations = Integration::where('org_id', $org)->get();

        $platformStats = $this->buildWizardPlatformStats($connections, $integrations);
        $summary = $this->buildWizardSummary($platformStats, $integrations);

        return $this->success([
            'platforms' => $platformStats,
            'summary' => $summary,
        ], 'Stats retrieved');
    }
}
