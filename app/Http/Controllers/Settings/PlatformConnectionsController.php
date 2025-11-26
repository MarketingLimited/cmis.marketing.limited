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
        $threadsAccounts = $this->getThreadsAccounts($accessToken, $instagramAccounts);
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
            'threadsAccounts' => $threadsAccounts,
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

        // Each org can only have ONE of each asset type
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|string|max:50',
            'instagram_account' => 'nullable|string|max:50',
            'threads_account' => 'nullable|string|max:50',
            'ad_account' => 'nullable|string|max:50',
            'pixel' => 'nullable|string|max:50',
            'catalog' => 'nullable|string|max:50',
            // Manual ID inputs (override selected values)
            'manual_page_id' => 'nullable|string|max:50',
            'manual_instagram_id' => 'nullable|string|max:50',
            'manual_threads_id' => 'nullable|string|max:50',
            'manual_ad_account_id' => 'nullable|string|max:50',
            'manual_pixel_id' => 'nullable|string|max:50',
            'manual_catalog_id' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Build selected assets (single value per type - one account per org)
        $selectedAssets = [
            'page' => $request->filled('manual_page_id')
                ? $request->input('manual_page_id')
                : $request->input('page'),
            'instagram_account' => $request->filled('manual_instagram_id')
                ? $request->input('manual_instagram_id')
                : $request->input('instagram_account'),
            'threads_account' => $request->filled('manual_threads_id')
                ? $request->input('manual_threads_id')
                : $request->input('threads_account'),
            'ad_account' => $request->filled('manual_ad_account_id')
                ? $this->normalizeAdAccountId($request->input('manual_ad_account_id'))
                : $request->input('ad_account'),
            'pixel' => $request->filled('manual_pixel_id')
                ? $request->input('manual_pixel_id')
                : $request->input('pixel'),
            'catalog' => $request->filled('manual_catalog_id')
                ? $request->input('manual_catalog_id')
                : $request->input('catalog'),
        ];

        // Filter out null values
        $selectedAssets = array_filter($selectedAssets, fn($v) => !is_null($v) && $v !== '');

        // Update connection metadata
        $metadata = $connection->account_metadata ?? [];
        $metadata['selected_assets'] = $selectedAssets;
        $metadata['assets_updated_at'] = now()->toIso8601String();

        $connection->update(['account_metadata' => $metadata]);

        $totalSelected = count($selectedAssets);

        if ($request->wantsJson()) {
            return $this->success([
                'connection' => $connection->fresh(),
                'selected_assets' => $selectedAssets,
            ], "Meta assets configured: {$totalSelected} asset type(s) selected");
        }

        // Build detailed message
        $assetTypes = [];
        if (!empty($selectedAssets['page'])) $assetTypes[] = 'Facebook Page';
        if (!empty($selectedAssets['instagram_account'])) $assetTypes[] = 'Instagram';
        if (!empty($selectedAssets['threads_account'])) $assetTypes[] = 'Threads';
        if (!empty($selectedAssets['ad_account'])) $assetTypes[] = 'Ad Account';
        if (!empty($selectedAssets['pixel'])) $assetTypes[] = 'Pixel';
        if (!empty($selectedAssets['catalog'])) $assetTypes[] = 'Catalog';

        $assetList = count($assetTypes) > 0 ? implode(', ', $assetTypes) : 'None';

        return redirect()
            ->route('orgs.settings.platform-connections.index', $org)
            ->with('success', "Meta assets configured: {$assetList}");
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
     */
    public function selectGoogleAssets(Request $request, string $org, string $connectionId)
    {
        return $this->showGenericPlatformAssets($request, $org, $connectionId, 'google');
    }

    /**
     * Store selected Google assets.
     */
    public function storeGoogleAssets(Request $request, string $org, string $connectionId)
    {
        return $this->storeGenericPlatformAssets($request, $org, $connectionId, 'google', [
            'business_profile', 'ad_account'
        ]);
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
        $state = base64_encode(json_encode(['org_id' => $org, 'platform' => 'meta']));

        session(['oauth_state' => $state]);

        // Meta OAuth scopes for ads management and social publishing
        $scopes = [
            'ads_management',
            'ads_read',
            'business_management',
            'pages_read_engagement',
            'pages_show_list',
            'pages_manage_posts',
            'pages_manage_metadata',
            'instagram_basic',
            'instagram_content_publish',
            'instagram_manage_comments',
            'instagram_manage_insights',
            'read_insights',
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
                ->with('error', 'Invalid OAuth state');
        }

        if ($request->has('error')) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', 'Meta authorization was denied: ' . $request->get('error_description', 'Unknown error'));
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
                ->with('error', 'Failed to obtain access token from Meta');
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
                ->with('error', 'Token validation failed: ' . ($tokenInfo['error'] ?? 'Unknown error'));
        }

        // Get ad accounts
        $adAccounts = $this->getMetaAdAccounts($accessToken);
        $activeAdAccounts = array_filter($adAccounts, fn($acc) => $acc['can_create_ads'] ?? false);

        $hasRequiredPermissions = $tokenInfo['has_all_required_permissions'] ?? true;
        $warnings = $tokenInfo['warnings'] ?? [];

        // Create connection
        $connection = PlatformConnection::updateOrCreate(
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

        session()->forget('oauth_state');

        // Build success message
        $successMessage = 'Meta account connected successfully via OAuth. Found ' . count($adAccounts) . ' ad account(s).';
        if (!$hasRequiredPermissions) {
            $missingPerms = implode(', ', $tokenInfo['missing_required_permissions'] ?? []);
            $successMessage .= " Warning: Missing some permissions ({$missingPerms}).";
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
                ->with('error', 'Invalid OAuth state');
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
                ->with('error', 'Failed to obtain access token from YouTube');
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

        $connection = PlatformConnection::updateOrCreate(
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

        session()->forget('oauth_state');

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', 'YouTube account connected successfully');
    }

    /**
     * Initiate LinkedIn OAuth authorization.
     */
    public function authorizeLinkedIn(Request $request, string $org)
    {
        $config = config('social-platforms.linkedin');
        $state = base64_encode(json_encode(['org_id' => $org, 'platform' => 'linkedin']));

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
                ->with('error', 'Invalid OAuth state');
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
                ->with('error', 'Failed to obtain access token from LinkedIn');
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

        $connection = PlatformConnection::updateOrCreate(
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

        session()->forget('oauth_state');

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', 'LinkedIn account connected successfully');
    }

    /**
     * Initiate Twitter/X OAuth authorization.
     */
    public function authorizeTwitter(Request $request, string $org)
    {
        $config = config('social-platforms.twitter');
        $state = base64_encode(json_encode(['org_id' => $org, 'platform' => 'twitter']));

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
                ->with('error', 'Invalid OAuth state');
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
                ->with('error', 'Failed to obtain access token from Twitter');
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

        $connection = PlatformConnection::updateOrCreate(
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

        session()->forget(['oauth_state', 'twitter_code_verifier']);

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', 'X (Twitter) account connected successfully');
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
                ->with('error', 'Invalid OAuth state');
        }

        $config = config('social-platforms.pinterest');

        $response = Http::asForm()->post($config['token_url'], [
            'grant_type' => 'authorization_code',
            'code' => $request->get('code'),
            'redirect_uri' => $config['redirect_uri'],
        ])->withBasicAuth($config['app_id'], $config['app_secret']);

        if (!$response->successful()) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', 'Failed to obtain access token from Pinterest');
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

        $connection = PlatformConnection::updateOrCreate(
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

        session()->forget('oauth_state');

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', 'Pinterest account connected successfully');
    }

    /**
     * Initiate TikTok OAuth authorization.
     */
    public function authorizeTikTok(Request $request, string $org)
    {
        $config = config('social-platforms.tiktok');
        $state = base64_encode(json_encode(['org_id' => $org, 'platform' => 'tiktok']));

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
     * Handle TikTok OAuth callback.
     */
    public function callbackTikTok(Request $request)
    {
        $state = json_decode(base64_decode($request->get('state')), true);
        $orgId = $state['org_id'] ?? null;

        if (!$orgId || $request->get('state') !== session('oauth_state')) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId ?? 'default')
                ->with('error', 'Invalid OAuth state');
        }

        $config = config('social-platforms.tiktok');

        $response = Http::asForm()->post($config['token_url'], [
            'client_key' => $config['client_key'],
            'client_secret' => $config['client_secret'],
            'code' => $request->get('code'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => $config['redirect_uri'],
        ]);

        if (!$response->successful()) {
            return redirect()->route('orgs.settings.platform-connections.index', $orgId)
                ->with('error', 'Failed to obtain access token from TikTok');
        }

        $tokenData = $response->json('data', []);

        // Get user info
        $userResponse = Http::withToken($tokenData['access_token'])
            ->post('https://open.tiktokapis.com/v2/user/info/', [
                'fields' => ['open_id', 'union_id', 'display_name'],
            ]);

        $accountName = 'TikTok Account';
        $accountId = 'tiktok_' . Str::random(10);

        if ($userResponse->successful()) {
            $userData = $userResponse->json('data.user', []);
            $accountName = $userData['display_name'] ?? 'TikTok Account';
            $accountId = $userData['open_id'] ?? $accountId;
        }

        $connection = PlatformConnection::updateOrCreate(
            [
                'org_id' => $orgId,
                'platform' => 'tiktok',
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
                    'open_id' => $tokenData['open_id'] ?? null,
                ],
            ]
        );

        session()->forget(['oauth_state', 'tiktok_csrf_state']);

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', 'TikTok account connected successfully');
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
                ->with('error', 'Invalid OAuth state');
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
                ->with('error', 'Failed to obtain access token from Reddit');
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

        $connection = PlatformConnection::updateOrCreate(
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

        session()->forget('oauth_state');

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', 'Reddit account connected successfully');
    }

    /**
     * Initiate Tumblr OAuth authorization (OAuth 1.0a).
     */
    public function authorizeTumblr(Request $request, string $org)
    {
        // TODO: Implement OAuth 1.0a flow for Tumblr
        // This requires additional OAuth 1.0a library as it uses a different flow than OAuth 2.0
        return redirect()->route('orgs.settings.platform-connections.index', $org)
            ->with('error', 'Tumblr OAuth integration coming soon. OAuth 1.0a requires additional implementation.');
    }

    /**
     * Handle Tumblr OAuth callback.
     */
    public function callbackTumblr(Request $request)
    {
        // TODO: Implement OAuth 1.0a callback for Tumblr
        return redirect()->route('orgs.settings.platform-connections.index', 'default')
            ->with('error', 'Tumblr OAuth callback not yet implemented');
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
                ->with('error', 'Invalid OAuth state');
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
                ->with('error', 'Failed to obtain access token from Google Business Profile');
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

        $connection = PlatformConnection::updateOrCreate(
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

        session()->forget('oauth_state');

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', 'Google Business Profile connected successfully');
    }

    /**
     * Initiate Snapchat OAuth authorization.
     */
    public function authorizeSnapchat(Request $request, string $org)
    {
        $config = config('social-platforms.snapchat');
        $state = base64_encode(json_encode(['org_id' => $org, 'platform' => 'snapchat']));

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
                ->with('error', 'Invalid OAuth state');
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
                ->with('error', 'Failed to obtain access token from Snapchat');
        }

        $tokenData = $response->json();

        $accountName = 'Snapchat Account';
        $accountId = 'snapchat_' . Str::random(10);

        $connection = PlatformConnection::updateOrCreate(
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

        session()->forget('oauth_state');

        return redirect()->route('orgs.settings.platform-connections.index', $orgId)
            ->with('success', 'Snapchat account connected successfully');
    }
}
