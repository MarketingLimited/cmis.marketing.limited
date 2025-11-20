<?php

namespace App\Integrations\Base;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function __construct(?array $config = null)
    {
        if ($config) {
            $this->clientId = $config['client_id'] ?? '';
            $this->clientSecret = $config['client_secret'] ?? '';
            $this->redirectUri = $config['redirect_uri'] ?? '';
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
        $response = Http::asForm()->post($this->tokenUrl, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            Log::error('OAuth token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Failed to obtain access token: ' . $response->body());
        }

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
        $response = Http::asForm()->post($this->tokenUrl, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            Log::error('OAuth token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Failed to refresh access token: ' . $response->body());
        }

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
