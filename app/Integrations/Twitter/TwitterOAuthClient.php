<?php

namespace App\Integrations\Twitter;

use App\Integrations\Base\OAuth2Client;

/**
 * Twitter/X OAuth Client
 *
 * Handles OAuth 2.0 authentication for Twitter API v2
 */
class TwitterOAuthClient extends OAuth2Client
{
    protected string $authorizationUrl = 'https://twitter.com/i/oauth2/authorize';
    protected string $tokenUrl = 'https://api.twitter.com/2/oauth2/token';

    protected array $scopes = [
        'tweet.read',
        'tweet.write',
        'users.read',
        'offline.access',
    ];

    public function __construct(?array $config = null)
    {
        parent::__construct($config ?? [
            'client_id' => config('services.twitter.client_id'),
            'client_secret' => config('services.twitter.client_secret'),
            'redirect_uri' => config('services.twitter.redirect_uri'),
        ]);
    }

    /**
     * Get authorization URL with PKCE
     *
     * @param string $state CSRF token
     * @param array $additionalParams Additional parameters
     * @return string Authorization URL
     */
    public function getAuthorizationUrl(string $state, array $additionalParams = []): string
    {
        // Generate PKCE code verifier and challenge
        $codeVerifier = $this->generateCodeVerifier();
        $codeChallenge = $this->generateCodeChallenge($codeVerifier);

        // Store code verifier in session
        session(['twitter_code_verifier' => $codeVerifier]);

        $additionalParams['code_challenge'] = $codeChallenge;
        $additionalParams['code_challenge_method'] = 'S256';

        return parent::getAuthorizationUrl($state, $additionalParams);
    }

    /**
     * Generate PKCE code verifier
     *
     * @return string Code verifier
     */
    protected function generateCodeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * Generate PKCE code challenge from verifier
     *
     * @param string $verifier Code verifier
     * @return string Code challenge
     */
    protected function generateCodeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }
}
