# CMIS Platform Integrations

This directory contains OAuth clients and API clients for all supported social media and advertising platforms.

## Architecture

### Directory Structure

```
app/Integrations/
├── Base/
│   ├── OAuth2Client.php      # Abstract OAuth 2.0 client
│   ├── BaseApiClient.php     # Abstract API client with retry/rate limiting
│   └── ApiException.php      # Custom API exception class
├── Meta/
│   ├── MetaOAuthClient.php   # Facebook/Instagram OAuth
│   └── MetaApiClient.php     # Facebook/Instagram API client
├── Google/
│   ├── GoogleOAuthClient.php # Google OAuth
│   └── GoogleApiClient.php   # Google Ads/YouTube API client
├── TikTok/
│   ├── TikTokOAuthClient.php # TikTok OAuth
│   └── TikTokApiClient.php   # TikTok API client
├── LinkedIn/
│   ├── LinkedInOAuthClient.php
│   └── LinkedInApiClient.php
├── Twitter/
│   ├── TwitterOAuthClient.php
│   └── TwitterApiClient.php
└── README.md (this file)
```

## Base Classes

### OAuth2Client

Abstract base class for OAuth 2.0 authentication flows.

**Features:**
- Authorization URL generation
- Token exchange (code → access token)
- Token refresh
- State validation for CSRF protection

**Usage:**

```php
use App\Integrations\Meta\MetaOAuthClient;

$client = new MetaOAuthClient();

// Step 1: Get authorization URL
$authUrl = $client->getAuthorizationUrl($stateToken);

// Step 2: Exchange code for token (in callback)
$tokenData = $client->getAccessToken($authorizationCode);

// Step 3: Refresh token when expired
$newTokenData = $client->refreshToken($refreshToken);
```

### BaseApiClient

Abstract base class for platform API clients.

**Features:**
- HTTP request wrapper with error handling
- Rate limiting support
- Automatic retry with exponential backoff
- Request/response logging
- Bearer token authentication

**Usage:**

```php
use App\Integrations\Meta\MetaApiClient;

$client = new MetaApiClient([
    'access_token' => $integration->credential_data['access_token']
]);

// Make API request
$pageData = $client->getPage($pageId);

// Publish post
$result = $client->publishPost($pageId, [
    'message' => 'Hello from CMIS!',
    'published' => true
]);
```

### ApiException

Custom exception for API errors.

**Features:**
- Response data access
- Error type detection
- Rate limit information
- Retryable error detection

**Usage:**

```php
try {
    $result = $client->publishPost($pageId, $data);
} catch (ApiException $e) {
    if ($e->isRateLimitError()) {
        // Handle rate limit
        $resetTime = $e->getRateLimitReset();
    } elseif ($e->isAuthenticationError()) {
        // Refresh token
    } elseif ($e->isRetryable()) {
        // Retry later
    }
}
```

## Platform-Specific Implementations

### Meta (Facebook & Instagram)

**OAuth Scopes:**
- pages_show_list
- pages_manage_posts
- instagram_content_publish
- instagram_manage_insights

**Special Features:**
- Long-lived token exchange (60 days)
- Token debug API
- Unified API for Facebook and Instagram

**Example:**

```php
// OAuth flow
$oauth = new MetaOAuthClient();
$tokenData = $oauth->getAccessToken($code);
$longLivedToken = $oauth->getLongLivedToken($tokenData['access_token']);

// API usage
$api = new MetaApiClient(['access_token' => $longLivedToken['access_token']]);
$page = $api->getPage($pageId);
$post = $api->publishPost($pageId, ['message' => 'Test']);
$insights = $api->getPostInsights($post['id']);
```

### Google (Ads & YouTube)

**OAuth Scopes:**
- https://www.googleapis.com/auth/adwords
- https://www.googleapis.com/auth/youtube
- https://www.googleapis.com/auth/youtube.upload

**Special Features:**
- Offline access (refresh tokens)
- Forced consent prompt
- Multiple service integration (Ads + YouTube)

**Example:**

```php
// OAuth flow
$oauth = new GoogleOAuthClient();
$authUrl = $oauth->getAuthorizationUrl($state); // Includes access_type=offline
$tokenData = $oauth->getAccessToken($code);

// API usage (requires Google Ads PHP library)
$client = new GoogleApiClient($tokenData);
$campaigns = $client->getCampaigns($customerId);
```

### TikTok

**OAuth Scopes:**
- user.info.basic - Basic profile info (open_id, avatar, display name)
- user.info.profile - Profile links, bio, verification status
- user.info.stats - Follower count, likes count, video count
- video.upload - Upload videos as drafts
- video.publish - Directly publish videos
- video.list - Read user's public videos

**Special Features:**
- Different parameter names (client_key vs client_id)
- Comma-separated scopes
- Business API integration
- Content Posting API v2 with direct post capability

### LinkedIn

**OAuth Scopes:**
- r_liteprofile
- w_member_social
- w_organization_social

**Special Features:**
- Standard OAuth 2.0 flow
- Organization management
- Professional network focus

### Twitter/X

**OAuth Scopes:**
- tweet.read
- tweet.write
- offline.access

**Special Features:**
- PKCE (Proof Key for Code Exchange)
- OAuth 2.0 (newer than old OAuth 1.0a)
- Code verifier/challenge generation

**Example:**

```php
// OAuth with PKCE
$oauth = new TwitterOAuthClient();
$authUrl = $oauth->getAuthorizationUrl($state); // Generates PKCE challenge
// Code verifier stored in session

// In callback
$tokenData = $oauth->getAccessToken($code); // Uses stored code verifier
```

## Integration Workflow

### 1. User Initiates Connection

User clicks "Connect Platform" button in UI.

### 2. OAuth Authorization

```php
// Controller
public function redirect(string $platform)
{
    $state = Str::random(40);
    session(['oauth_state' => $state]);

    $oauthService = new OAuthService();
    $authUrl = $oauthService->getAuthorizationUrl($platform, $state);

    return redirect($authUrl);
}
```

### 3. Platform Callback

```php
// Controller
public function callback(string $platform, Request $request)
{
    // Validate state
    if ($request->get('state') !== session('oauth_state')) {
        throw new Exception('Invalid state');
    }

    // Exchange code for token
    $oauthService = new OAuthService();
    $integration = $oauthService->handleCallback(
        $platform,
        $request->get('code'),
        auth()->user()
    );

    return redirect()->route('integrations.show', $integration);
}
```

### 4. Store Integration

```php
// OAuthService
public function handleCallback(string $platform, string $code, User $user)
{
    $client = $this->getOAuthClient($platform);
    $tokenData = $client->getAccessToken($code);

    $integration = Integration::create([
        'user_id' => $user->user_id,
        'org_id' => $user->current_org_id,
        'provider' => $platform,
        'credential_data' => encrypt($tokenData),
        'is_active' => true,
    ]);

    return $integration;
}
```

### 5. Use API Client

```php
// In Service class
public function publishPost(Integration $integration, array $data)
{
    $credentials = decrypt($integration->credential_data);

    $client = new MetaApiClient([
        'access_token' => $credentials['access_token']
    ]);

    return $client->publishPost($integration->external_id, $data);
}
```

## Error Handling

### Authentication Errors

```php
try {
    $result = $apiClient->publishPost($pageId, $data);
} catch (ApiException $e) {
    if ($e->isAuthenticationError()) {
        // Token expired - refresh it
        $oauthService = new OAuthService();
        $integration = $oauthService->refreshIntegrationToken($integration);

        // Retry request
        $result = $apiClient->setAccessToken($newToken)->publishPost($pageId, $data);
    }
}
```

### Rate Limit Errors

```php
try {
    $result = $apiClient->publishPost($pageId, $data);
} catch (ApiException $e) {
    if ($e->isRateLimitError()) {
        $resetTime = $e->getRateLimitReset();

        // Queue job to retry after reset
        Queue::later($resetTime - time(), new PublishPostJob($data));
    }
}
```

### Network Errors

```php
try {
    // Use retryable request
    $result = $apiClient->retryableRequest('post', "/page/feed", $data);
} catch (ApiException $e) {
    Log::error('API request failed after retries', [
        'error' => $e->getMessage(),
        'platform' => $apiClient->getPlatform()
    ]);
}
```

## Rate Limiting

All API clients respect platform rate limits using Laravel's built-in rate limiting:

```php
// In BaseApiClient
protected function rateLimitedRequest(string $method, string $endpoint, array $params = [])
{
    $key = "api_rate_limit:{$this->platform}:" . md5($endpoint);

    return Cache::lock($key, 5)->block(10, function () use ($method, $endpoint, $params) {
        return $this->request($method, $endpoint, $params);
    });
}
```

## Testing

### Unit Tests

Test individual API client methods:

```php
public function test_meta_client_publishes_post()
{
    Http::fake([
        'graph.facebook.com/*' => Http::response(['id' => '123'], 200)
    ]);

    $client = new MetaApiClient(['access_token' => 'test_token']);
    $result = $client->publishPost('page_id', ['message' => 'Test']);

    $this->assertEquals('123', $result['id']);
}
```

### Integration Tests

Test with real API (requires test credentials):

```php
/** @group integration */
public function test_real_meta_post_publishing()
{
    $client = new MetaApiClient([
        'access_token' => env('TEST_META_TOKEN')
    ]);

    $result = $client->publishPost(env('TEST_PAGE_ID'), [
        'message' => 'Integration test',
        'published' => false // Draft mode
    ]);

    $this->assertTrue(isset($result['id']));
}
```

## Security

### Credential Storage

Always encrypt platform credentials:

```php
// Store
$integration->credential_data = encrypt([
    'access_token' => $token,
    'refresh_token' => $refreshToken,
    'expires_at' => now()->addSeconds($expiresIn)
]);

// Retrieve
$credentials = decrypt($integration->credential_data);
```

### CSRF Protection

Always validate state parameter in OAuth callbacks:

```php
if (!hash_equals(session('oauth_state'), $request->get('state'))) {
    throw new Exception('Invalid state - possible CSRF attack');
}
```

### Token Expiration

Check and refresh tokens before API calls:

```php
$oauthService = new OAuthService();

if ($oauthService->needsTokenRefresh($integration)) {
    $integration = $oauthService->refreshIntegrationToken($integration);
}
```

## Adding New Platform

To add a new platform integration:

1. **Create OAuth Client:**

```php
// app/Integrations/NewPlatform/NewPlatformOAuthClient.php
class NewPlatformOAuthClient extends OAuth2Client
{
    protected string $authorizationUrl = 'https://platform.com/oauth/authorize';
    protected string $tokenUrl = 'https://platform.com/oauth/token';
    protected array $scopes = ['read', 'write'];

    // Implement any platform-specific methods
}
```

2. **Create API Client:**

```php
// app/Integrations/NewPlatform/NewPlatformApiClient.php
class NewPlatformApiClient extends BaseApiClient
{
    protected string $baseUrl = 'https://api.platform.com/v1';
    protected string $platform = 'newplatform';

    public function createPost(array $data): array
    {
        return $this->request('post', '/posts', $data);
    }
}
```

3. **Update OAuthService:**

```php
// app/Services/OAuth/OAuthService.php
protected function getOAuthClient(string $platform): OAuth2Client
{
    return match($platform) {
        'newplatform' => new \App\Integrations\NewPlatform\NewPlatformOAuthClient(),
        // ... existing platforms
    };
}
```

4. **Add Configuration:**

```php
// config/services.php
'newplatform' => [
    'client_id' => env('NEWPLATFORM_CLIENT_ID'),
    'client_secret' => env('NEWPLATFORM_CLIENT_SECRET'),
    'redirect_uri' => env('NEWPLATFORM_REDIRECT_URI'),
],
```

5. **Add Routes:**

```php
// routes/web.php
Route::get('/oauth/newplatform', [OAuthController::class, 'redirect']);
Route::get('/oauth/newplatform/callback', [OAuthController::class, 'callback']);
```

6. **Test Integration:**

```php
public function test_newplatform_oauth_flow()
{
    // Test OAuth
}

public function test_newplatform_api_requests()
{
    // Test API
}
```

## Resources

- [OAuth 2.0 Specification](https://oauth.net/2/)
- [Laravel HTTP Client](https://laravel.com/docs/http-client)
- [Platform API Documentation](../docs/PHASE-5-IMPLEMENTATION-GUIDE.md)

---

**Version:** 1.0
**Last Updated:** 2025-11-20
