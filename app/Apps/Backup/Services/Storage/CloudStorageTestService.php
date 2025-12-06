<?php

namespace App\Apps\Backup\Services\Storage;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloud Storage Test Service
 *
 * Tests connectivity to cloud storage providers for backup functionality.
 */
class CloudStorageTestService
{
    /**
     * Test Google Drive connection
     *
     * @param array $credentials OAuth credentials with client_id and client_secret
     * @return array ['connected' => bool, 'message' => string, 'details' => array]
     */
    public function testGoogleDrive(array $credentials): array
    {
        try {
            // Decrypt the client_secret if encrypted
            $clientId = $credentials['client_id'] ?? null;
            $clientSecret = $credentials['client_secret'] ?? null;

            if (empty($clientId) || empty($clientSecret)) {
                return [
                    'connected' => false,
                    'message' => __('backup.storage_credentials_missing'),
                    'details' => [],
                ];
            }

            // Try to decrypt if encrypted
            try {
                $clientSecret = Crypt::decryptString($clientSecret);
            } catch (\Exception $e) {
                // Already decrypted or not encrypted
            }

            // Check for refresh_token which indicates completed OAuth flow
            $refreshToken = $credentials['refresh_token'] ?? null;

            if (empty($refreshToken)) {
                return [
                    'connected' => false,
                    'message' => __('backup.storage_oauth_required'),
                    'details' => [
                        'needs_oauth' => true,
                        'oauth_url' => $this->getGoogleOAuthUrl($clientId),
                    ],
                ];
            }

            // Try to decrypt refresh token
            try {
                $refreshToken = Crypt::decryptString($refreshToken);
            } catch (\Exception $e) {
                // Already decrypted
            }

            // Use refresh token to get access token
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if (!$tokenResponse->successful()) {
                return [
                    'connected' => false,
                    'message' => __('backup.storage_token_refresh_failed'),
                    'details' => [
                        'error' => $tokenResponse->json('error_description') ?? 'Token refresh failed',
                    ],
                ];
            }

            $accessToken = $tokenResponse->json('access_token');

            // Test by getting drive info
            $driveResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/drive/v3/about', [
                    'fields' => 'user,storageQuota',
                ]);

            if ($driveResponse->successful()) {
                $driveInfo = $driveResponse->json();
                return [
                    'connected' => true,
                    'message' => __('backup.storage_test_success'),
                    'details' => [
                        'user' => $driveInfo['user']['displayName'] ?? 'Unknown',
                        'email' => $driveInfo['user']['emailAddress'] ?? '',
                        'storage_used' => $this->formatBytes((int) ($driveInfo['storageQuota']['usage'] ?? 0)),
                        'storage_total' => $this->formatBytes((int) ($driveInfo['storageQuota']['limit'] ?? 0)),
                    ],
                ];
            }

            return [
                'connected' => false,
                'message' => __('backup.storage_test_failed'),
                'details' => [
                    'error' => $driveResponse->json('error')['message'] ?? 'Failed to access Drive',
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Google Drive test failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'connected' => false,
                'message' => __('backup.storage_test_error'),
                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Test OneDrive/Microsoft Graph connection
     *
     * @param array $credentials OAuth credentials
     * @return array ['connected' => bool, 'message' => string, 'details' => array]
     */
    public function testOneDrive(array $credentials): array
    {
        try {
            $clientId = $credentials['client_id'] ?? null;
            $clientSecret = $credentials['client_secret'] ?? null;

            if (empty($clientId) || empty($clientSecret)) {
                return [
                    'connected' => false,
                    'message' => __('backup.storage_credentials_missing'),
                    'details' => [],
                ];
            }

            // Try to decrypt if encrypted
            try {
                $clientSecret = Crypt::decryptString($clientSecret);
            } catch (\Exception $e) {
                // Already decrypted
            }

            // Check for refresh_token
            $refreshToken = $credentials['refresh_token'] ?? null;

            if (empty($refreshToken)) {
                return [
                    'connected' => false,
                    'message' => __('backup.storage_oauth_required'),
                    'details' => [
                        'needs_oauth' => true,
                        'oauth_url' => $this->getOneDriveOAuthUrl($clientId),
                    ],
                ];
            }

            // Try to decrypt refresh token
            try {
                $refreshToken = Crypt::decryptString($refreshToken);
            } catch (\Exception $e) {
                // Already decrypted
            }

            // Use refresh token to get access token
            $tokenResponse = Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
                'scope' => 'Files.ReadWrite.All offline_access',
            ]);

            if (!$tokenResponse->successful()) {
                return [
                    'connected' => false,
                    'message' => __('backup.storage_token_refresh_failed'),
                    'details' => [
                        'error' => $tokenResponse->json('error_description') ?? 'Token refresh failed',
                    ],
                ];
            }

            $accessToken = $tokenResponse->json('access_token');

            // Test by getting drive info
            $driveResponse = Http::withToken($accessToken)
                ->get('https://graph.microsoft.com/v1.0/me/drive');

            if ($driveResponse->successful()) {
                $driveInfo = $driveResponse->json();
                return [
                    'connected' => true,
                    'message' => __('backup.storage_test_success'),
                    'details' => [
                        'drive_id' => $driveInfo['id'] ?? '',
                        'drive_type' => $driveInfo['driveType'] ?? '',
                        'owner' => $driveInfo['owner']['user']['displayName'] ?? 'Unknown',
                        'storage_used' => $this->formatBytes((int) ($driveInfo['quota']['used'] ?? 0)),
                        'storage_total' => $this->formatBytes((int) ($driveInfo['quota']['total'] ?? 0)),
                    ],
                ];
            }

            return [
                'connected' => false,
                'message' => __('backup.storage_test_failed'),
                'details' => [
                    'error' => $driveResponse->json('error')['message'] ?? 'Failed to access OneDrive',
                ],
            ];

        } catch (\Exception $e) {
            Log::error('OneDrive test failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'connected' => false,
                'message' => __('backup.storage_test_error'),
                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Test Dropbox connection
     *
     * @param array $credentials Access token
     * @return array ['connected' => bool, 'message' => string, 'details' => array]
     */
    public function testDropbox(array $credentials): array
    {
        try {
            $token = $credentials['token'] ?? null;

            if (empty($token)) {
                return [
                    'connected' => false,
                    'message' => __('backup.storage_credentials_missing'),
                    'details' => [],
                ];
            }

            // Try to decrypt if encrypted
            try {
                $token = Crypt::decryptString($token);
            } catch (\Exception $e) {
                // Already decrypted
            }

            // Test by getting account info
            $response = Http::withToken($token)
                ->post('https://api.dropboxapi.com/2/users/get_current_account');

            if ($response->successful()) {
                $accountInfo = $response->json();

                // Get space usage
                $spaceResponse = Http::withToken($token)
                    ->post('https://api.dropboxapi.com/2/users/get_space_usage');

                $spaceUsed = 0;
                $spaceTotal = 0;

                if ($spaceResponse->successful()) {
                    $spaceInfo = $spaceResponse->json();
                    $spaceUsed = $spaceInfo['used'] ?? 0;
                    $spaceTotal = $spaceInfo['allocation']['allocated'] ?? 0;
                }

                return [
                    'connected' => true,
                    'message' => __('backup.storage_test_success'),
                    'details' => [
                        'name' => $accountInfo['name']['display_name'] ?? 'Unknown',
                        'email' => $accountInfo['email'] ?? '',
                        'account_type' => $accountInfo['account_type']['.tag'] ?? 'unknown',
                        'storage_used' => $this->formatBytes((int) $spaceUsed),
                        'storage_total' => $this->formatBytes((int) $spaceTotal),
                    ],
                ];
            }

            // Handle specific error cases
            $error = $response->json('error') ?? [];
            $errorMessage = is_array($error) ? ($error['error_summary'] ?? 'Unknown error') : $error;

            if (str_contains($errorMessage, 'invalid_access_token') || str_contains($errorMessage, 'expired_access_token')) {
                return [
                    'connected' => false,
                    'message' => __('backup.storage_token_expired'),
                    'details' => [
                        'error' => 'Access token is invalid or expired. Please generate a new token.',
                    ],
                ];
            }

            return [
                'connected' => false,
                'message' => __('backup.storage_test_failed'),
                'details' => [
                    'error' => $errorMessage,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Dropbox test failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'connected' => false,
                'message' => __('backup.storage_test_error'),
                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Get Google OAuth URL for initial authorization
     */
    protected function getGoogleOAuthUrl(string $clientId): string
    {
        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => config('services.google.backup_redirect_uri', url('/oauth/google/callback')),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    /**
     * Get OneDrive OAuth URL for initial authorization
     */
    protected function getOneDriveOAuthUrl(string $clientId): string
    {
        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => config('services.microsoft.backup_redirect_uri', url('/oauth/onedrive/callback')),
            'response_type' => 'code',
            'scope' => 'Files.ReadWrite.All offline_access',
        ]);

        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?' . $params;
    }

    /**
     * Format bytes to human readable string
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
