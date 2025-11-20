<?php

namespace App\Integrations\Google;

use App\Integrations\Base\OAuth2Client;

/**
 * Google OAuth Client
 *
 * Handles OAuth 2.0 authentication for Google services (Ads, YouTube)
 */
class GoogleOAuthClient extends OAuth2Client
{
    protected string $authorizationUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
    protected string $tokenUrl = 'https://oauth2.googleapis.com/token';

    protected array $scopes = [
        'https://www.googleapis.com/auth/adwords',
        'https://www.googleapis.com/auth/youtube',
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtube.readonly',
        'https://www.googleapis.com/auth/yt-analytics.readonly',
    ];

    public function __construct(?array $config = null)
    {
        parent::__construct($config ?? [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect_uri'),
        ]);
    }

    /**
     * Get authorization URL with additional parameters
     *
     * @param string $state CSRF token
     * @param array $additionalParams Additional parameters
     * @return string Authorization URL
     */
    public function getAuthorizationUrl(string $state, array $additionalParams = []): string
    {
        $additionalParams['access_type'] = 'offline'; // Get refresh token
        $additionalParams['prompt'] = 'consent'; // Force consent screen

        return parent::getAuthorizationUrl($state, $additionalParams);
    }
}
