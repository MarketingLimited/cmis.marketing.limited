<?php

namespace App\Services\OAuth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tumblr OAuth 1.0a Service
 *
 * Handles the OAuth 1.0a authentication flow for Tumblr API.
 */
class TumblrOAuthService
{
    protected string $consumerKey;
    protected string $consumerSecret;
    protected string $callbackUrl;
    protected string $requestTokenUrl;
    protected string $authorizeUrl;
    protected string $accessTokenUrl;

    public function __construct()
    {
        $config = config('social-platforms.tumblr');

        $this->consumerKey = $config['consumer_key'] ?? '';
        $this->consumerSecret = $config['consumer_secret'] ?? '';
        $this->callbackUrl = $config['redirect_uri'] ?? url('/integrations/tumblr/callback');
        $this->requestTokenUrl = $config['request_token_url'] ?? 'https://www.tumblr.com/oauth/request_token';
        $this->authorizeUrl = $config['authorize_url'] ?? 'https://www.tumblr.com/oauth/authorize';
        $this->accessTokenUrl = $config['access_token_url'] ?? 'https://www.tumblr.com/oauth/access_token';
    }

    /**
     * Get the request token and return the authorization URL
     *
     * @param string $orgId Organization ID for state tracking
     * @return array ['url' => string, 'oauth_token' => string, 'oauth_token_secret' => string]
     * @throws \Exception
     */
    public function getAuthorizationUrl(string $orgId): array
    {
        $oauthParams = [
            'oauth_callback' => $this->callbackUrl,
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => $this->generateNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0',
        ];

        // Generate signature
        $oauthParams['oauth_signature'] = $this->generateSignature(
            'POST',
            $this->requestTokenUrl,
            $oauthParams,
            $this->consumerSecret,
            ''
        );

        // Make request for request token
        $response = Http::withHeaders([
            'Authorization' => $this->buildAuthorizationHeader($oauthParams),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post($this->requestTokenUrl);

        if (!$response->successful()) {
            Log::error('Tumblr request token failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception(__('settings.tumblr_oauth_request_token_failed'));
        }

        // Parse response
        parse_str($response->body(), $result);

        if (empty($result['oauth_token']) || empty($result['oauth_token_secret'])) {
            throw new \Exception(__('settings.tumblr_oauth_invalid_response'));
        }

        // Store the request token secret in cache for later use
        $cacheKey = "tumblr_oauth_{$result['oauth_token']}";
        Cache::put($cacheKey, [
            'oauth_token_secret' => $result['oauth_token_secret'],
            'org_id' => $orgId,
        ], now()->addMinutes(15));

        // Build authorization URL
        $authUrl = $this->authorizeUrl . '?' . http_build_query([
            'oauth_token' => $result['oauth_token'],
        ]);

        return [
            'url' => $authUrl,
            'oauth_token' => $result['oauth_token'],
            'oauth_token_secret' => $result['oauth_token_secret'],
        ];
    }

    /**
     * Exchange request token for access token
     *
     * @param string $oauthToken The oauth_token from callback
     * @param string $oauthVerifier The oauth_verifier from callback
     * @return array ['access_token' => string, 'access_token_secret' => string, 'org_id' => string]
     * @throws \Exception
     */
    public function getAccessToken(string $oauthToken, string $oauthVerifier): array
    {
        // Retrieve stored request token secret
        $cacheKey = "tumblr_oauth_{$oauthToken}";
        $cached = Cache::get($cacheKey);

        if (!$cached) {
            throw new \Exception(__('settings.tumblr_oauth_session_expired'));
        }

        $oauthTokenSecret = $cached['oauth_token_secret'];
        $orgId = $cached['org_id'];

        $oauthParams = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => $this->generateNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $oauthToken,
            'oauth_verifier' => $oauthVerifier,
            'oauth_version' => '1.0',
        ];

        // Generate signature
        $oauthParams['oauth_signature'] = $this->generateSignature(
            'POST',
            $this->accessTokenUrl,
            $oauthParams,
            $this->consumerSecret,
            $oauthTokenSecret
        );

        // Make request for access token
        $response = Http::withHeaders([
            'Authorization' => $this->buildAuthorizationHeader($oauthParams),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post($this->accessTokenUrl);

        if (!$response->successful()) {
            Log::error('Tumblr access token failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception(__('settings.tumblr_oauth_access_token_failed'));
        }

        // Parse response
        parse_str($response->body(), $result);

        if (empty($result['oauth_token']) || empty($result['oauth_token_secret'])) {
            throw new \Exception(__('settings.tumblr_oauth_invalid_access_response'));
        }

        // Clear the cache
        Cache::forget($cacheKey);

        return [
            'access_token' => $result['oauth_token'],
            'access_token_secret' => $result['oauth_token_secret'],
            'org_id' => $orgId,
        ];
    }

    /**
     * Get user info from Tumblr API
     *
     * @param string $accessToken
     * @param string $accessTokenSecret
     * @return array
     */
    public function getUserInfo(string $accessToken, string $accessTokenSecret): array
    {
        $url = 'https://api.tumblr.com/v2/user/info';

        $oauthParams = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => $this->generateNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $accessToken,
            'oauth_version' => '1.0',
        ];

        // Generate signature
        $oauthParams['oauth_signature'] = $this->generateSignature(
            'GET',
            $url,
            $oauthParams,
            $this->consumerSecret,
            $accessTokenSecret
        );

        $response = Http::withHeaders([
            'Authorization' => $this->buildAuthorizationHeader($oauthParams),
        ])->get($url);

        if (!$response->successful()) {
            Log::error('Tumblr user info failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception(__('settings.tumblr_failed_to_get_user_info'));
        }

        $data = $response->json();

        return $data['response']['user'] ?? [];
    }

    /**
     * Generate OAuth signature
     */
    protected function generateSignature(
        string $method,
        string $url,
        array $params,
        string $consumerSecret,
        string $tokenSecret
    ): string {
        // Sort parameters
        ksort($params);

        // Create parameter string
        $paramString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        // Create signature base string
        $signatureBase = strtoupper($method) . '&' .
            rawurlencode($url) . '&' .
            rawurlencode($paramString);

        // Create signing key
        $signingKey = rawurlencode($consumerSecret) . '&' . rawurlencode($tokenSecret);

        // Generate signature
        return base64_encode(hash_hmac('sha1', $signatureBase, $signingKey, true));
    }

    /**
     * Build OAuth Authorization header
     */
    protected function buildAuthorizationHeader(array $params): string
    {
        $header = 'OAuth ';
        $parts = [];

        foreach ($params as $key => $value) {
            if (strpos($key, 'oauth_') === 0) {
                $parts[] = rawurlencode($key) . '="' . rawurlencode($value) . '"';
            }
        }

        return $header . implode(', ', $parts);
    }

    /**
     * Generate a unique nonce
     */
    protected function generateNonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Make an authenticated API request
     *
     * @param string $method HTTP method
     * @param string $url API URL
     * @param string $accessToken OAuth access token
     * @param string $accessTokenSecret OAuth access token secret
     * @param array $data Request data
     * @return array
     */
    public function makeRequest(
        string $method,
        string $url,
        string $accessToken,
        string $accessTokenSecret,
        array $data = []
    ): array {
        $oauthParams = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => $this->generateNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $accessToken,
            'oauth_version' => '1.0',
        ];

        // For POST requests, include body params in signature
        $signatureParams = $oauthParams;
        if (strtoupper($method) === 'POST' && !empty($data)) {
            $signatureParams = array_merge($signatureParams, $data);
        }

        // Generate signature
        $oauthParams['oauth_signature'] = $this->generateSignature(
            $method,
            $url,
            $signatureParams,
            $this->consumerSecret,
            $accessTokenSecret
        );

        $request = Http::withHeaders([
            'Authorization' => $this->buildAuthorizationHeader($oauthParams),
        ]);

        if (strtoupper($method) === 'POST') {
            $response = $request->asForm()->post($url, $data);
        } elseif (strtoupper($method) === 'GET') {
            $response = $request->get($url, $data);
        } else {
            $response = $request->send($method, $url, ['form_params' => $data]);
        }

        if (!$response->successful()) {
            Log::error('Tumblr API request failed', [
                'method' => $method,
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Tumblr API request failed: ' . $response->body());
        }

        return $response->json();
    }
}
