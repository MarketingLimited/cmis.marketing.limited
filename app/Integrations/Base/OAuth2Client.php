<?php

namespace App\Integrations\Base;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Base OAuth 2.0 Client
 *
 * Provides reusable OAuth 2.0 authentication flow for platform integrations
 */
abstract class OAuth2Client
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;
    protected array $scopes = [];
    protected string $authorizationUrl;
    protected string $tokenUrl;
    protected ?string $orgId = null;
    protected string $platform = 'unknown';

    public function __construct(?array $config = null)
    {
        if ($config) {
            $this->clientId = $config['client_id'] ?? '';
            $this->clientSecret = $config['client_secret'] ?? '';
            $this->redirectUri = $config['redirect_uri'] ?? '';
        }
    }

    /**
     * Set org ID for API call logging
     *
     * @param string|null $orgId Organization ID
     * @return self
     */
    public function setOrgId(?string $orgId): self
    {
        $this->orgId = $orgId;
        return $this;
    }

    /**
     * Log API call to platform_api_calls for analytics tracking
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param bool $success Whether call was successful
     * @param int|null $httpStatus HTTP status code
     * @param int|null $durationMs Call duration in milliseconds
     * @param string|null $errorMessage Error message if failed
     */
    protected function logApiCall(
        string $endpoint,
        string $method,
        bool $success,
        ?int $httpStatus = null,
        ?int $durationMs = null,
        ?string $errorMessage = null
    ): void {
        try {
            // Get org_id from session or current user if not set
            $orgId = $this->orgId ?? auth()->user()?->current_org_id ?? null;

            if (!$orgId) {
                return; // Skip logging if no org context
            }

            DB::table('cmis.platform_api_calls')->insert([
                'call_id' => Str::uuid()->toString(),
                'org_id' => $orgId,
                'connection_id' => null,
                'platform' => $this->platform,
                'endpoint' => $endpoint,
                'method' => strtoupper($method),
                'action_type' => 'auth',
                'http_status' => $httpStatus,
                'duration_ms' => $durationMs,
                'success' => $success,
                'error_message' => $errorMessage,
                'called_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log OAuth API call', [
                'error' => $e->getMessage(),
                'platform' => $this->platform,
                'endpoint' => $endpoint,
            ]);
        }
    }

    /**
     * Get authorization URL for OAuth flow
     *
     * @param string $state CSRF protection token
     * @param array $additionalParams Additional URL parameters
     * @return string Authorization URL
     */
    public function getAuthorizationUrl(string $state, array $additionalParams = []): string
    {
        $params = array_merge([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes),
            'state' => $state,
        ], $additionalParams);

        return $this->authorizationUrl . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     *
     * @param string $code Authorization code from callback
     * @return array Token response (access_token, refresh_token, expires_in, etc.)
     * @throws \Exception If token exchange fails
     */
    public function getAccessToken(string $code): array
    {
        $startTime = microtime(true);

        $response = Http::asForm()->post($this->tokenUrl, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);
        $httpStatus = $response->status();

        if (!$response->successful()) {
            Log::error('OAuth token exchange failed', [
                'status' => $httpStatus,
                'body' => $response->body(),
            ]);

            // Log the failed API call
            $this->logApiCall(
                $this->tokenUrl,
                'POST',
                false,
                $httpStatus,
                $durationMs,
                'Token exchange failed: ' . $response->body()
            );

            throw new \Exception('Failed to obtain access token: ' . $response->body());
        }

        // Log the successful API call
        $this->logApiCall($this->tokenUrl, 'POST', true, $httpStatus, $durationMs);

        $data = $response->json();

        // Normalize response format
        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_in' => $data['expires_in'] ?? 3600,
            'expires_at' => isset($data['expires_in'])
                ? now()->addSeconds($data['expires_in'])
                : null,
            'token_type' => $data['token_type'] ?? 'Bearer',
            'scope' => $data['scope'] ?? implode(' ', $this->scopes),
        ];
    }

    /**
     * Refresh access token using refresh token
     *
     * @param string $refreshToken Refresh token
     * @return array New token response
     * @throws \Exception If token refresh fails
     */
    public function refreshToken(string $refreshToken): array
    {
        $startTime = microtime(true);

        $response = Http::asForm()->post($this->tokenUrl, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);
        $httpStatus = $response->status();

        if (!$response->successful()) {
            Log::error('OAuth token refresh failed', [
                'status' => $httpStatus,
                'body' => $response->body(),
            ]);

            // Log the failed API call
            $this->logApiCall(
                $this->tokenUrl . ' (refresh)',
                'POST',
                false,
                $httpStatus,
                $durationMs,
                'Token refresh failed: ' . $response->body()
            );

            throw new \Exception('Failed to refresh access token: ' . $response->body());
        }

        // Log the successful API call
        $this->logApiCall($this->tokenUrl . ' (refresh)', 'POST', true, $httpStatus, $durationMs);

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $refreshToken,
            'expires_in' => $data['expires_in'] ?? 3600,
            'expires_at' => isset($data['expires_in'])
                ? now()->addSeconds($data['expires_in'])
                : null,
            'token_type' => $data['token_type'] ?? 'Bearer',
        ];
    }

    /**
     * Revoke access token
     *
     * @param string $token Access token to revoke
     * @return bool True if revoked successfully
     */
    public function revokeToken(string $token): bool
    {
        // Override in platform-specific implementations if supported
        return false;
    }

    /**
     * Validate state parameter for CSRF protection
     *
     * @param string $state State from callback
     * @param string $expectedState State from session
     * @return bool True if state is valid
     */
    public function validateState(string $state, string $expectedState): bool
    {
        return hash_equals($expectedState, $state);
    }

    /**
     * Get required scopes for this platform
     *
     * @return array Scopes
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Set scopes for authentication
     *
     * @param array $scopes Scopes to request
     * @return self
     */
    public function setScopes(array $scopes): self
    {
        $this->scopes = $scopes;
        return $this;
    }
}
