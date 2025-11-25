<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Platform\PlatformConnection;
use Illuminate\Http\Request;
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
            'tiktok' => $this->testTikTokConnection($connection),
            default => ['success' => false, 'message' => 'Platform not supported for testing'],
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
        $connection->delete();

        if ($request->wantsJson()) {
            return $this->deleted('Platform connection deleted successfully');
        }

        return redirect()
            ->route('orgs.settings.platform-connections.index', $org)
            ->with('success', "{$platformName} connection deleted successfully");
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

        return back()->with('success', 'Found ' . count($adAccounts) . ' ad account(s)');
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
     * Test Google connection (placeholder).
     */
    private function testGoogleConnection(PlatformConnection $connection): array
    {
        // TODO: Implement Google connection test
        return ['success' => false, 'message' => 'Google connection testing not implemented yet'];
    }

    /**
     * Test TikTok connection (placeholder).
     */
    private function testTikTokConnection(PlatformConnection $connection): array
    {
        // TODO: Implement TikTok connection test
        return ['success' => false, 'message' => 'TikTok connection testing not implemented yet'];
    }

    /**
     * Show asset selection page for a Meta connection.
     */
    public function selectMetaAssets(Request $request, string $org, string $connectionId)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'meta')
            ->firstOrFail();

        $accessToken = $connection->access_token;

        // Fetch all available assets
        $pages = $this->getMetaPages($accessToken);
        $instagramAccounts = $this->getMetaInstagramAccounts($accessToken, $pages);
        $adAccounts = $this->getMetaAdAccounts($accessToken);
        $pixels = $this->getMetaPixels($accessToken, $adAccounts);
        $catalogs = $this->getMetaCatalogs($accessToken);

        // Get currently selected assets
        $selectedAssets = $connection->account_metadata['selected_assets'] ?? [];

        return view('settings.platform-connections.meta-assets', [
            'currentOrg' => $org,
            'connection' => $connection,
            'pages' => $pages,
            'instagramAccounts' => $instagramAccounts,
            'adAccounts' => $adAccounts,
            'pixels' => $pixels,
            'catalogs' => $catalogs,
            'selectedAssets' => $selectedAssets,
        ]);
    }

    /**
     * Store selected Meta assets for a connection.
     */
    public function storeMetaAssets(Request $request, string $org, string $connectionId)
    {
        $connection = PlatformConnection::where('connection_id', $connectionId)
            ->where('org_id', $org)
            ->where('platform', 'meta')
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'pages' => 'nullable|array',
            'pages.*' => 'string',
            'instagram_accounts' => 'nullable|array',
            'instagram_accounts.*' => 'string',
            'ad_accounts' => 'nullable|array',
            'ad_accounts.*' => 'string',
            'pixels' => 'nullable|array',
            'pixels.*' => 'string',
            'catalogs' => 'nullable|array',
            'catalogs.*' => 'string',
            // Manual ID inputs
            'manual_page_id' => 'nullable|string|max:50',
            'manual_instagram_id' => 'nullable|string|max:50',
            'manual_ad_account_id' => 'nullable|string|max:50',
            'manual_pixel_id' => 'nullable|string|max:50',
            'manual_catalog_id' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Build selected assets array
        $selectedAssets = [
            'pages' => $request->input('pages', []),
            'instagram_accounts' => $request->input('instagram_accounts', []),
            'ad_accounts' => $request->input('ad_accounts', []),
            'pixels' => $request->input('pixels', []),
            'catalogs' => $request->input('catalogs', []),
        ];

        // Add manual IDs if provided
        if ($request->filled('manual_page_id')) {
            $selectedAssets['pages'][] = $request->input('manual_page_id');
        }
        if ($request->filled('manual_instagram_id')) {
            $selectedAssets['instagram_accounts'][] = $request->input('manual_instagram_id');
        }
        if ($request->filled('manual_ad_account_id')) {
            $manualAdAccount = $request->input('manual_ad_account_id');
            // Ensure it has act_ prefix
            if (!str_starts_with($manualAdAccount, 'act_')) {
                $manualAdAccount = 'act_' . $manualAdAccount;
            }
            $selectedAssets['ad_accounts'][] = $manualAdAccount;
        }
        if ($request->filled('manual_pixel_id')) {
            $selectedAssets['pixels'][] = $request->input('manual_pixel_id');
        }
        if ($request->filled('manual_catalog_id')) {
            $selectedAssets['catalogs'][] = $request->input('manual_catalog_id');
        }

        // Remove duplicates
        foreach ($selectedAssets as $key => $values) {
            $selectedAssets[$key] = array_unique(array_filter($values));
        }

        // Update connection metadata
        $metadata = $connection->account_metadata ?? [];
        $metadata['selected_assets'] = $selectedAssets;
        $metadata['assets_updated_at'] = now()->toIso8601String();

        $connection->update(['account_metadata' => $metadata]);

        $totalSelected = array_sum(array_map('count', $selectedAssets));

        if ($request->wantsJson()) {
            return $this->success([
                'connection' => $connection->fresh(),
                'selected_assets' => $selectedAssets,
            ], "Selected {$totalSelected} asset(s) successfully");
        }

        return redirect()
            ->route('orgs.settings.platform-connections.index', $org)
            ->with('success', "Meta assets configured successfully. {$totalSelected} asset(s) selected.");
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

        try {
            foreach ($adAccounts as $account) {
                $accountId = $account['id'] ?? null;
                if (!$accountId) continue;

                $response = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$accountId}/adspixels", [
                    'access_token' => $accessToken,
                    'fields' => 'id,name,code,creation_time,is_created_by_business,last_fired_time',
                    'limit' => 50,
                ]);

                if ($response->successful()) {
                    foreach ($response->json('data', []) as $pixel) {
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
                                'is_created_by_business' => $pixel['is_created_by_business'] ?? false,
                            ];
                        }
                    }
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
     */
    private function getMetaCatalogs(string $accessToken): array
    {
        try {
            // First try business owned catalogs
            $response = Http::timeout(30)->get('https://graph.facebook.com/v21.0/me/owned_product_catalogs', [
                'access_token' => $accessToken,
                'fields' => 'id,name,product_count,vertical,business',
                'limit' => 50,
            ]);

            $catalogs = [];

            if ($response->successful()) {
                foreach ($response->json('data', []) as $catalog) {
                    $catalogs[] = [
                        'id' => $catalog['id'],
                        'name' => $catalog['name'] ?? 'Unnamed Catalog',
                        'product_count' => $catalog['product_count'] ?? 0,
                        'vertical' => $catalog['vertical'] ?? 'commerce',
                        'business_id' => $catalog['business']['id'] ?? null,
                        'business_name' => $catalog['business']['name'] ?? null,
                    ];
                }
            }

            // Also try client catalogs if business_management scope available
            $clientResponse = Http::timeout(30)->get('https://graph.facebook.com/v21.0/me/client_product_catalogs', [
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
                            'business_id' => null,
                            'business_name' => null,
                            'is_client_catalog' => true,
                        ];
                    }
                }
            }

            return $catalogs;
        } catch (\Exception $e) {
            Log::error('Failed to fetch Meta catalogs', ['error' => $e->getMessage()]);
            return [];
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
}
