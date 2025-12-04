<?php

namespace App\Integrations\TikTok;

use App\Integrations\Base\OAuth2Client;

/**
 * TikTok OAuth Client
 *
 * Handles OAuth 2.0 authentication for TikTok Marketing API
 */
class TikTokOAuthClient extends OAuth2Client
{
    protected string $authorizationUrl = 'https://business-api.tiktok.com/portal/auth';
    protected string $tokenUrl = 'https://business-api.tiktok.com/open_api/v1.3/oauth2/access_token/';

    protected array $scopes = [
        'user.info.basic',
        'user.info.profile',
        'user.info.stats',
        'video.upload',
        'video.publish',
        'video.list',
    ];

    public function __construct(?array $config = null)
    {
        parent::__construct($config ?? [
            'client_id' => config('services.tiktok.client_key'),
            'client_secret' => config('services.tiktok.client_secret'),
            'redirect_uri' => config('services.tiktok.redirect_uri'),
        ]);
    }

    /**
     * Get authorization URL (TikTok uses different parameter names)
     *
     * @param string $state CSRF token
     * @param array $additionalParams Additional parameters
     * @return string Authorization URL
     */
    public function getAuthorizationUrl(string $state, array $additionalParams = []): string
    {
        $params = array_merge([
            'client_key' => $this->clientId,
            'response_type' => 'code',
            'scope' => implode(',', $this->scopes),
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
        ], $additionalParams);

        return $this->authorizationUrl . '?' . http_build_query($params);
    }
}
