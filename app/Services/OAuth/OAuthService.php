<?php

namespace App\Services\OAuth;

use App\Models\Core\Integration;
use App\Models\Core\User;
use App\Integrations\Base\OAuth2Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OAuth Service
 *
 * Orchestrates OAuth authentication flows and manages integrations
 */
class OAuthService
{
    /**
     * Get OAuth client for platform
     *
     * @param string $platform Platform name
     * @return OAuth2Client OAuth client instance
     * @throws \Exception If platform not supported
     */
    protected function getOAuthClient(string $platform): OAuth2Client
    {
        return match($platform) {
            'meta', 'facebook', 'instagram' => new \App\Integrations\Meta\MetaOAuthClient(),
            'google', 'google-ads', 'youtube' => new \App\Integrations\Google\GoogleOAuthClient(),
            'tiktok' => new \App\Integrations\TikTok\TikTokOAuthClient(),
            'linkedin' => new \App\Integrations\LinkedIn\LinkedInOAuthClient(),
            'twitter', 'x' => new \App\Integrations\Twitter\TwitterOAuthClient(),
            default => throw new \Exception("Unsupported platform: $platform"),
        };
    }

    /**
     * Get authorization URL for platform
     *
     * @param string $platform Platform name
     * @param string $state CSRF state token
     * @return string Authorization URL
     */
    public function getAuthorizationUrl(string $platform, string $state): string
    {
        $client = $this->getOAuthClient($platform);
        return $client->getAuthorizationUrl($state);
    }

    /**
     * Handle OAuth callback and create integration
     *
     * @param string $platform Platform name
     * @param string $code Authorization code
     * @param User $user Authenticated user
     * @return Integration Created integration
     */
    public function handleCallback(string $platform, string $code, User $user): Integration
    {
        DB::beginTransaction();

        try {
            // Get OAuth client
            $client = $this->getOAuthClient($platform);

            // Exchange code for access token
            $tokenData = $client->getAccessToken($code);

            Log::info('OAuth token obtained', [
                'platform' => $platform,
                'user_id' => $user->user_id,
                'expires_at' => $tokenData['expires_at'] ?? null,
            ]);

            // Create or update integration
            $integration = $this->createOrUpdateIntegration(
                $platform,
                $user,
                $tokenData
            );

            DB::commit();

            return $integration;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('OAuth callback handling failed', [
                'platform' => $platform,
                'user_id' => $user->user_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create or update integration record
     *
     * @param string $platform Platform name
     * @param User $user User
     * @param array $tokenData Token data
     * @return Integration Integration record
     */
    protected function createOrUpdateIntegration(
        string $platform,
        User $user,
        array $tokenData
    ): Integration {
        // Find existing integration or create new
        $integration = Integration::where('user_id', $user->user_id)
            ->where('provider', $platform)
            ->first();

        if (!$integration) {
            $integration = new Integration();
            $integration->user_id = $user->user_id;
            $integration->org_id = $user->current_org_id;
            $integration->provider = $platform;
            $integration->status = 'active';
        }

        // Encrypt and store credentials
        $integration->credential_data = encrypt([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'expires_at' => $tokenData['expires_at'] ?? null,
            'token_type' => $tokenData['token_type'] ?? 'Bearer',
            'scope' => $tokenData['scope'] ?? null,
        ]);

        $integration->is_active = true;
        $integration->last_sync_at = null; // Reset sync status
        $integration->save();

        Log::info('Integration created/updated', [
            'integration_id' => $integration->integration_id,
            'platform' => $platform,
            'user_id' => $user->user_id,
        ]);

        return $integration;
    }

    /**
     * Refresh access token for integration
     *
     * @param Integration $integration Integration to refresh
     * @return Integration Updated integration
     * @throws \Exception If refresh fails
     */
    public function refreshIntegrationToken(Integration $integration): Integration
    {
        try {
            $credentials = decrypt($integration->credential_data);
            $refreshToken = $credentials['refresh_token'] ?? null;

            if (!$refreshToken) {
                throw new \Exception('No refresh token available');
            }

            // Get OAuth client and refresh token
            $client = $this->getOAuthClient($integration->provider);
            $newTokenData = $client->refreshToken($refreshToken);

            // Update credentials
            $integration->credential_data = encrypt([
                'access_token' => $newTokenData['access_token'],
                'refresh_token' => $newTokenData['refresh_token'] ?? $refreshToken,
                'expires_at' => $newTokenData['expires_at'] ?? null,
                'token_type' => $newTokenData['token_type'] ?? 'Bearer',
            ]);

            $integration->save();

            Log::info('Integration token refreshed', [
                'integration_id' => $integration->integration_id,
                'platform' => $integration->provider,
            ]);

            return $integration;
        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'integration_id' => $integration->integration_id,
                'platform' => $integration->provider,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Revoke integration and delete credentials
     *
     * @param string $integrationId Integration ID
     * @param User $user User
     * @return bool True if revoked
     */
    public function revokeIntegration(string $integrationId, User $user): bool
    {
        $integration = Integration::where('integration_id', $integrationId)
            ->where('user_id', $user->user_id)
            ->firstOrFail();

        try {
            // Try to revoke token with platform (if supported)
            $credentials = decrypt($integration->credential_data);
            $client = $this->getOAuthClient($integration->provider);

            if ($token = $credentials['access_token'] ?? null) {
                $client->revokeToken($token);
            }
        } catch (\Exception $e) {
            Log::warning('Token revocation failed (continuing with deletion)', [
                'integration_id' => $integrationId,
                'error' => $e->getMessage(),
            ]);
        }

        // Deactivate integration (soft delete)
        $integration->is_active = false;
        $integration->status = 'revoked';
        $integration->save();

        Log::info('Integration revoked', [
            'integration_id' => $integrationId,
            'platform' => $integration->provider,
        ]);

        return true;
    }

    /**
     * Check if token needs refresh
     *
     * @param Integration $integration Integration
     * @return bool True if token needs refresh
     */
    public function needsTokenRefresh(Integration $integration): bool
    {
        try {
            $credentials = decrypt($integration->credential_data);
            $expiresAt = $credentials['expires_at'] ?? null;

            if (!$expiresAt) {
                return false; // No expiration info
            }

            // Refresh if expiring within 5 minutes
            return now()->addMinutes(5)->isAfter($expiresAt);
        } catch (\Exception $e) {
            return false;
        }
    }
}
