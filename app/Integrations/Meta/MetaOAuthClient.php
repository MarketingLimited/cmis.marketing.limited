<?php

namespace App\Integrations\Meta;

use App\Integrations\Base\OAuth2Client;
use Illuminate\Support\Facades\Http;

/**
 * Meta (Facebook & Instagram) OAuth Client
 *
 * Handles OAuth 2.0 authentication for Meta platforms
 */
class MetaOAuthClient extends OAuth2Client
{
    protected string $authorizationUrl = 'https://www.facebook.com/v19.0/dialog/oauth';
    protected string $tokenUrl = 'https://graph.facebook.com/v19.0/oauth/access_token';

    protected array $scopes = [
        'pages_show_list',
        'pages_read_engagement',
        'pages_manage_posts',
        'pages_read_user_content',
        'instagram_basic',
        'instagram_content_publish',
        'instagram_manage_comments',
        'instagram_manage_insights',
        'read_insights',
    ];

    public function __construct(?array $config = null)
    {
        parent::__construct($config ?? [
            'client_id' => config('services.meta.client_id'),
            'client_secret' => config('services.meta.client_secret'),
            'redirect_uri' => config('services.meta.redirect_uri'),
        ]);
    }

    /**
     * Exchange short-lived token for long-lived token
     *
     * @param string $shortLivedToken Short-lived access token
     * @return array Long-lived token data
     */
    public function getLongLivedToken(string $shortLivedToken): array
    {
        $response = Http::get('https://graph.facebook.com/v19.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to exchange for long-lived token');
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'token_type' => $data['token_type'] ?? 'Bearer',
            'expires_in' => $data['expires_in'] ?? 5184000, // ~60 days
            'expires_at' => now()->addSeconds($data['expires_in'] ?? 5184000),
        ];
    }

    /**
     * Get access token (overridden to get long-lived token)
     *
     * @param string $code Authorization code
     * @return array Token data
     */
    public function getAccessToken(string $code): array
    {
        // First get short-lived token
        $tokenData = parent::getAccessToken($code);

        // Exchange for long-lived token
        return $this->getLongLivedToken($tokenData['access_token']);
    }

    /**
     * Debug token to check validity
     *
     * @param string $token Access token
     * @return array Token debug info
     */
    public function debugToken(string $token): array
    {
        $response = Http::get('https://graph.facebook.com/v19.0/debug_token', [
            'input_token' => $token,
            'access_token' => $this->clientId . '|' . $this->clientSecret,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to debug token');
        }

        return $response->json()['data'] ?? [];
    }
}
