# Phase 5: Platform API Integration Implementation Guide

This guide provides comprehensive instructions for replacing stub implementations with real API integrations for all supported social media and advertising platforms.

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Prerequisites](#prerequisites)
4. [Integration Priority](#integration-priority)
5. [Platform-Specific Guides](#platform-specific-guides)
6. [OAuth Implementation](#oauth-implementation)
7. [API Client Development](#api-client-development)
8. [Testing Strategy](#testing-strategy)
9. [Rate Limiting & Quotas](#rate-limiting--quotas)
10. [Error Handling](#error-handling)
11. [Security Best Practices](#security-best-practices)

## Overview

### Current State

CMIS currently has **stub implementations** for all platform services. These stubs:
- Log API calls with `(stub)` indicator
- Return mock data with `'stub' => true` flag
- Provide production-ready error handling structure
- Follow Laravel service pattern conventions

**Stub Files (78 total):**
- 9 Sync Services (FacebookSyncService, InstagramSyncService, etc.)
- 11 Social Services (FacebookService, InstagramService, etc.)
- 1 Ads Service (MetaAdsService)

### Phase 5 Goals

1. Replace stubs with real API clients
2. Implement OAuth authentication flows
3. Handle webhook processing
4. Manage token refresh logic
5. Implement rate limiting
6. Add comprehensive error handling
7. Write integration tests

## Architecture

### Service Layer Pattern

```
Controller → Service → API Client → Platform API
                ↓
           Repository → Database
```

**Components:**

1. **Service Class** - Business logic (existing)
2. **API Client** - HTTP communication (to implement)
3. **OAuth Handler** - Authentication (to implement)
4. **Webhook Handler** - Event processing (existing)
5. **Token Manager** - Credential management (to implement)

### Directory Structure

```
app/
├── Services/
│   ├── Social/          # Social media services (stub)
│   ├── Sync/            # Platform sync services (stub)
│   └── Ads/             # Advertising services (stub)
├── Http/
│   ├── Controllers/
│   │   └── OAuth/       # OAuth callback controllers
│   └── Middleware/
│       └── RateLimitPlatformApi.php
└── Integrations/        # NEW: API clients
    ├── Meta/
    │   ├── MetaApiClient.php
    │   ├── FacebookGraphApi.php
    │   └── InstagramGraphApi.php
    ├── Google/
    │   ├── GoogleAdsClient.php
    │   └── YouTubeApiClient.php
    ├── TikTok/
    │   └── TikTokApiClient.php
    └── Base/
        ├── BaseApiClient.php
        ├── OAuth2Client.php
        └── ApiException.php
```

## Prerequisites

### Platform Developer Accounts

Create developer accounts for each platform:

| Platform | Developer Portal | Documentation |
|----------|------------------|---------------|
| **Meta** | https://developers.facebook.com | https://developers.facebook.com/docs |
| **Google** | https://console.cloud.google.com | https://developers.google.com/ads |
| **TikTok** | https://developers.tiktok.com | https://developers.tiktok.com/doc |
| **LinkedIn** | https://www.linkedin.com/developers | https://docs.microsoft.com/linkedin |
| **Twitter/X** | https://developer.twitter.com | https://developer.twitter.com/en/docs |
| **Snapchat** | https://business-api.snapchat.com | https://marketingapi.snapchat.com/docs |
| **Pinterest** | https://developers.pinterest.com | https://developers.pinterest.com/docs |
| **YouTube** | https://console.cloud.google.com | https://developers.google.com/youtube |

### API Credentials

For each platform, obtain:

- **Client ID** / App ID
- **Client Secret** / App Secret
- **Redirect URI** (OAuth callback)
- **API Scopes/Permissions**
- **Webhook Secret** (if applicable)

### Required PHP Packages

Install additional dependencies:

```bash
composer require guzzlehttp/guzzle:^7.0
composer require league/oauth2-client:^2.0
composer require symfony/rate-limiter:^6.0
```

## Integration Priority

### Phase 5A: Meta Platform (Weeks 1-4)

**Priority: Critical** - Largest user base

1. **Facebook**
   - OAuth flow
   - Graph API client
   - Post publishing
   - Page management
   - Insights/metrics

2. **Instagram**
   - Business account integration
   - Media publishing (feed, story, reel)
   - Comments management
   - Insights

### Phase 5B: Google Platform (Weeks 5-8)

**Priority: High** - Key advertising platform

1. **Google Ads**
   - OAuth flow
   - Campaign management
   - Ad group operations
   - Performance metrics

2. **YouTube**
   - Video uploads
   - Channel management
   - Video analytics

### Phase 5C: TikTok & LinkedIn (Weeks 9-12)

**Priority: Medium** - Growing platforms

1. **TikTok**
   - OAuth flow
   - Video publishing
   - Analytics
   - Ad campaigns

2. **LinkedIn**
   - OAuth flow
   - Post sharing
   - Company page management
   - Analytics

### Phase 5D: Twitter, Snapchat, Pinterest (Weeks 13-16)

**Priority: Low** - Secondary platforms

1. **Twitter/X**
2. **Snapchat**
3. **Pinterest**

## Platform-Specific Guides

### Meta Platform (Facebook & Instagram)

#### 1. Create Base API Client

```php
<?php

namespace App\Integrations\Meta;

use App\Integrations\Base\BaseApiClient;
use Illuminate\Support\Facades\Http;

class MetaApiClient extends BaseApiClient
{
    protected string $baseUrl = 'https://graph.facebook.com/v18.0';
    protected string $platform = 'meta';

    public function __construct(array $credentials)
    {
        parent::__construct($credentials);
        $this->accessToken = $credentials['access_token'] ?? null;
    }

    /**
     * Make API request to Facebook Graph API
     */
    protected function request(string $method, string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        $response = Http::withToken($this->accessToken)
            ->timeout(30)
            ->$method($url, $params);

        if (!$response->successful()) {
            $this->handleApiError($response);
        }

        return $response->json();
    }

    /**
     * Get Facebook page information
     */
    public function getPage(string $pageId, array $fields = []): array
    {
        $defaultFields = ['id', 'name', 'about', 'category', 'fan_count'];
        $fields = empty($fields) ? $defaultFields : $fields;

        return $this->request('get', "/{$pageId}", [
            'fields' => implode(',', $fields)
        ]);
    }

    /**
     * Publish post to Facebook page
     */
    public function publishPost(string $pageId, array $data): array
    {
        return $this->request('post', "/{$pageId}/feed", [
            'message' => $data['message'] ?? '',
            'link' => $data['link'] ?? null,
            'published' => $data['published'] ?? true,
        ]);
    }

    /**
     * Get post insights/metrics
     */
    public function getPostInsights(string $postId, array $metrics = []): array
    {
        $defaultMetrics = ['post_impressions', 'post_engaged_users', 'post_clicks'];
        $metrics = empty($metrics) ? $defaultMetrics : $metrics;

        return $this->request('get', "/{$postId}/insights", [
            'metric' => implode(',', $metrics)
        ]);
    }

    /**
     * Handle Meta API errors
     */
    protected function handleApiError($response): void
    {
        $error = $response->json();
        $message = $error['error']['message'] ?? 'Unknown Meta API error';
        $code = $error['error']['code'] ?? 0;
        $type = $error['error']['type'] ?? 'OAuthException';

        throw new \App\Integrations\Base\ApiException(
            "Meta API Error ($type): $message",
            $code,
            null,
            $error
        );
    }
}
```

#### 2. Implement OAuth Flow

```php
<?php

namespace App\Services\OAuth;

use App\Models\Core\Integration;
use Illuminate\Support\Facades\Http;

class MetaOAuthService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.meta.client_id');
        $this->clientSecret = config('services.meta.client_secret');
        $this->redirectUri = config('services.meta.redirect_uri');
    }

    /**
     * Get authorization URL
     */
    public function getAuthorizationUrl(string $state): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'scope' => implode(',', [
                'pages_show_list',
                'pages_read_engagement',
                'pages_manage_posts',
                'pages_read_user_content',
                'instagram_basic',
                'instagram_content_publish',
            ]),
            'response_type' => 'code',
        ];

        return 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken(string $code): array
    {
        $response = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to obtain access token from Meta');
        }

        $data = $response->json();

        // Exchange short-lived token for long-lived token
        return $this->getLongLivedToken($data['access_token']);
    }

    /**
     * Exchange short-lived token for long-lived token
     */
    protected function getLongLivedToken(string $shortToken): array
    {
        $response = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'fb_exchange_token' => $shortToken,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to exchange for long-lived token');
        }

        return $response->json();
    }

    /**
     * Refresh access token
     */
    public function refreshToken(Integration $integration): array
    {
        // Meta long-lived tokens are valid for 60 days
        // Need to re-authenticate after expiration
        // Check if token is about to expire and notify user

        $expiresAt = $integration->credential_data['expires_at'] ?? null;
        if ($expiresAt && now()->addDays(7)->isAfter($expiresAt)) {
            // Token expiring soon - notify user to re-authenticate
            throw new \Exception('Meta access token expiring soon - user must re-authenticate');
        }

        // Return existing token
        return $integration->credential_data;
    }
}
```

#### 3. Update FacebookService

Replace stub implementation in `app/Services/Social/FacebookService.php`:

```php
public function publishPost($integration, array $data): array
{
    try {
        // Remove stub log
        // Log::info('FacebookService::publishPost called (stub)', [...]);

        // Create API client
        $client = new \App\Integrations\Meta\MetaApiClient([
            'access_token' => $integration->credential_data['access_token'],
        ]);

        // Publish post
        $result = $client->publishPost($integration->external_id, [
            'message' => $data['message'] ?? '',
            'link' => $data['link'] ?? null,
            'published' => $data['published'] ?? true,
        ]);

        Log::info('Facebook post published successfully', [
            'post_id' => $result['id'],
            'integration_id' => $integration->integration_id,
        ]);

        return [
            'success' => true,
            'post_id' => $result['id'],
            // Remove: 'stub' => true
        ];
    } catch (\Exception $e) {
        Log::error('Facebook post publishing failed', [
            'error' => $e->getMessage(),
            'integration_id' => $integration->integration_id,
        ]);

        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}
```

#### 4. Testing

```php
<?php

namespace Tests\Integration\Platforms;

use Tests\TestCase;
use App\Models\Core\Integration;
use App\Services\Social\FacebookService;

class FacebookIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_publish_post_to_facebook()
    {
        // Create test integration with valid credentials
        $integration = Integration::factory()->create([
            'provider' => 'facebook',
            'credential_data' => [
                'access_token' => env('TEST_FACEBOOK_TOKEN'),
                'expires_at' => now()->addDays(60),
            ],
            'external_id' => env('TEST_FACEBOOK_PAGE_ID'),
        ]);

        $service = new FacebookService();

        $result = $service->publishPost($integration, [
            'message' => 'Test post from CMIS integration tests',
            'published' => false, // Draft mode for testing
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayNotHasKey('stub', $result);
        $this->assertArrayHasKey('post_id', $result);
    }
}
```

### Google Platform (Ads & YouTube)

#### 1. Install Google Ads SDK

```bash
composer require googleads/google-ads-php:^21.0
```

#### 2. Create Google Ads Client

```php
<?php

namespace App\Integrations\Google;

use Google\Ads\GoogleAds\Lib\V14\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V14\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;

class GoogleAdsApiClient
{
    protected GoogleAdsClient $client;

    public function __construct(array $credentials)
    {
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withClientId($credentials['client_id'])
            ->withClientSecret($credentials['client_secret'])
            ->withRefreshToken($credentials['refresh_token'])
            ->build();

        $this->client = (new GoogleAdsClientBuilder())
            ->withOAuth2Credential($oAuth2Credential)
            ->withDeveloperToken($credentials['developer_token'])
            ->withLoginCustomerId($credentials['customer_id'])
            ->build();
    }

    public function getCampaigns(string $customerId): array
    {
        $googleAdsServiceClient = $this->client->getGoogleAdsServiceClient();

        $query = 'SELECT campaign.id, campaign.name, campaign.status FROM campaign';

        $response = $googleAdsServiceClient->search($customerId, $query);

        $campaigns = [];
        foreach ($response->iterateAllElements() as $row) {
            $campaigns[] = [
                'id' => $row->getCampaign()->getId(),
                'name' => $row->getCampaign()->getName(),
                'status' => $row->getCampaign()->getStatus(),
            ];
        }

        return $campaigns;
    }
}
```

### TikTok Platform

#### 1. TikTok API Client

```php
<?php

namespace App\Integrations\TikTok;

use App\Integrations\Base\BaseApiClient;
use Illuminate\Support\Facades\Http;

class TikTokApiClient extends BaseApiClient
{
    protected string $baseUrl = 'https://business-api.tiktok.com/open_api/v1.3';

    public function uploadVideo(string $advertiserId, string $videoPath): array
    {
        $response = Http::withToken($this->accessToken)
            ->attach('video', file_get_contents($videoPath), basename($videoPath))
            ->post("{$this->baseUrl}/file/video/upload/", [
                'advertiser_id' => $advertiserId,
            ]);

        if (!$response->successful()) {
            $this->handleApiError($response);
        }

        return $response->json();
    }
}
```

## OAuth Implementation

### Base OAuth2 Client

```php
<?php

namespace App\Integrations\Base;

abstract class OAuth2Client
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;
    protected array $scopes = [];

    abstract public function getAuthorizationUrl(string $state): string;
    abstract public function getAccessToken(string $code): array;
    abstract public function refreshToken(string $refreshToken): array;

    protected function buildAuthUrl(string $baseUrl, array $params): string
    {
        $defaultParams = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes),
        ];

        return $baseUrl . '?' . http_build_query(array_merge($defaultParams, $params));
    }
}
```

## API Client Development

### Base API Client

```php
<?php

namespace App\Integrations\Base;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class BaseApiClient
{
    protected string $baseUrl;
    protected string $platform;
    protected ?string $accessToken = null;
    protected array $credentials = [];

    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
        $this->accessToken = $credentials['access_token'] ?? null;
    }

    /**
     * Make rate-limited API request
     */
    protected function rateLimitedRequest(string $method, string $endpoint, array $params = []): array
    {
        $key = "api_rate_limit:{$this->platform}:" . md5($endpoint);

        return Cache::lock($key, 5)->block(10, function () use ($method, $endpoint, $params) {
            return $this->request($method, $endpoint, $params);
        });
    }

    /**
     * Retry failed requests with exponential backoff
     */
    protected function retryableRequest(string $method, string $endpoint, array $params = [], int $maxRetries = 3): array
    {
        $attempt = 0;

        retry:
        try {
            return $this->request($method, $endpoint, $params);
        } catch (\Exception $e) {
            $attempt++;

            if ($attempt >= $maxRetries) {
                throw $e;
            }

            // Exponential backoff
            $delay = (2 ** $attempt) * 1000; // milliseconds
            usleep($delay * 1000);

            Log::warning("Retrying API request (attempt $attempt/$maxRetries)", [
                'platform' => $this->platform,
                'endpoint' => $endpoint,
            ]);

            goto retry;
        }
    }

    abstract protected function request(string $method, string $endpoint, array $params = []): array;
    abstract protected function handleApiError($response): void;
}
```

## Testing Strategy

### 1. Unit Tests

Test individual API client methods:

```php
public function test_meta_client_formats_post_data_correctly()
{
    $client = new MetaApiClient(['access_token' => 'test_token']);

    // Mock HTTP facade
    Http::fake([
        '*' => Http::response(['id' => '123'], 200),
    ]);

    $result = $client->publishPost('page_id', [
        'message' => 'Test message',
    ]);

    $this->assertEquals('123', $result['id']);
}
```

### 2. Integration Tests

Test with real API (sandbox environments):

```php
/** @group integration */
public function test_real_facebook_post_publishing()
{
    if (!env('TEST_FACEBOOK_TOKEN')) {
        $this->markTestSkipped('Facebook test credentials not configured');
    }

    // Test with real API
    $service = new FacebookService();
    $result = $service->publishPost($integration, $data);

    $this->assertTrue($result['success']);
}
```

### 3. Mock Testing

Use VCR for recording/replaying HTTP interactions:

```bash
composer require --dev php-vcr/php-vcr
```

## Rate Limiting & Quotas

### Platform Rate Limits

| Platform | Limit | Window | Burst |
|----------|-------|--------|-------|
| Meta | 200 | 1 hour | No |
| Google Ads | 10,000 | 1 day | Yes |
| TikTok | 10 | 1 minute | No |
| LinkedIn | 500 | 1 day | Yes |
| Twitter | 300 | 15 min | No |

### Implementation

```php
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;

class PlatformRateLimiter
{
    public function limit(string $platform, callable $callback)
    {
        $limiter = (new RateLimiterFactory([
            'id' => "api.$platform",
            'policy' => 'sliding_window',
            'limit' => $this->getLimitFor($platform),
            'interval' => '1 hour',
        ], new CacheStorage(Cache::store())))
        ->create();

        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            throw new \Exception("Rate limit exceeded for $platform");
        }

        return $callback();
    }
}
```

## Error Handling

### Error Categories

1. **Authentication Errors** - Token expired, invalid credentials
2. **Rate Limit Errors** - Too many requests
3. **Validation Errors** - Invalid data format
4. **Server Errors** - Platform API down
5. **Network Errors** - Timeout, connection failed

### Error Recovery

```php
try {
    $result = $client->publishPost($pageId, $data);
} catch (AuthenticationException $e) {
    // Try to refresh token
    $this->refreshToken($integration);
    $result = $client->publishPost($pageId, $data);
} catch (RateLimitException $e) {
    // Queue for later
    Queue::later($e->getRetryAfter(), new PublishPostJob($data));
} catch (ValidationException $e) {
    // Log and notify user
    Log::error('Invalid post data', ['error' => $e->getMessage()]);
    throw $e;
}
```

## Security Best Practices

### 1. Credential Storage

```php
// Store credentials encrypted
$integration->credential_data = encrypt([
    'access_token' => $token,
    'refresh_token' => $refreshToken,
    'expires_at' => now()->addDays(60),
]);
$integration->save();

// Retrieve credentials
$credentials = decrypt($integration->credential_data);
```

### 2. Webhook Signature Verification

```php
public function verifyWebhookSignature(Request $request, string $platform): bool
{
    $signature = $request->header('X-Hub-Signature-256');
    $secret = config("webhooks.$platform.secret");

    $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

    return hash_equals($expectedSignature, $signature);
}
```

### 3. CSRF Protection for OAuth

```php
// Generate state parameter
$state = Str::random(40);
session(['oauth_state' => $state]);

// Verify state in callback
if ($request->get('state') !== session('oauth_state')) {
    throw new \Exception('Invalid OAuth state');
}
```

## Implementation Timeline

### Week 1-2: Infrastructure
- [ ] Create base API client classes
- [ ] Implement OAuth framework
- [ ] Set up rate limiting
- [ ] Configure error handling

### Week 3-4: Meta Platform
- [ ] Facebook integration
- [ ] Instagram integration
- [ ] Meta Ads integration
- [ ] Testing

### Week 5-6: Google Platform
- [ ] Google Ads integration
- [ ] YouTube integration
- [ ] Testing

### Week 7-8: TikTok & LinkedIn
- [ ] TikTok integration
- [ ] LinkedIn integration
- [ ] Testing

### Week 9-12: Remaining Platforms
- [ ] Twitter integration
- [ ] Snapchat integration
- [ ] Pinterest integration
- [ ] Comprehensive testing

### Week 13-14: Polish & Optimization
- [ ] Performance optimization
- [ ] Error handling improvements
- [ ] Documentation updates
- [ ] Load testing

### Week 15-16: Deployment
- [ ] Staging deployment
- [ ] Production deployment
- [ ] Monitoring setup
- [ ] User training

## Success Criteria

- [ ] All stub flags removed from responses
- [ ] OAuth flows working for all platforms
- [ ] Webhooks processing correctly
- [ ] Token refresh working automatically
- [ ] Rate limits respected
- [ ] Error handling comprehensive
- [ ] 80%+ test coverage
- [ ] Documentation complete
- [ ] Production deployment successful

## Resources

- [Meta Marketing API](https://developers.facebook.com/docs/marketing-apis)
- [Google Ads API](https://developers.google.com/google-ads/api)
- [TikTok Marketing API](https://ads.tiktok.com/marketing_api/docs)
- [LinkedIn Marketing API](https://docs.microsoft.com/en-us/linkedin/marketing)
- [Twitter API v2](https://developer.twitter.com/en/docs/twitter-api)
- [Laravel HTTP Client](https://laravel.com/docs/http-client)
- [OAuth 2.0 Specification](https://oauth.net/2/)

---

**Version:** 1.0
**Last Updated:** 2025-11-20
**Estimated Effort:** 16-20 weeks
**Team Size:** 2-3 developers
