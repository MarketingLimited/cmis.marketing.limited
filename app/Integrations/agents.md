# دليل الوكلاء - Integrations Layer (app/Integrations/)

## 1. Purpose (الغرض)

طبقة Integrations توفر **low-level OAuth و API clients** للتكامل مع المنصات الخارجية:
- **OAuth 2.0 Authentication**: تدفق OAuth موحد عبر جميع المنصات
- **Platform API Clients**: واجهات مباشرة لـ platform APIs
- **Base Classes**: `OAuth2Client` و `BaseApiClient` للكود المشترك
- **5 Platform Integrations**: Meta, Google, TikTok, LinkedIn, Twitter

## 2. Owned Scope

```
app/Integrations/
├── Base/
│   ├── OAuth2Client.php            # Base OAuth 2.0 client
│   ├── BaseApiClient.php           # Base API client
│   └── ApiException.php            # Custom exception
│
├── Meta/
│   ├── MetaOAuthClient.php         # Facebook/Instagram OAuth
│   └── MetaApiClient.php           # Graph API client
│
├── Google/
│   └── GoogleOAuthClient.php       # Google Ads OAuth
│
├── TikTok/
│   └── TikTokOAuthClient.php       # TikTok Marketing OAuth
│
├── LinkedIn/
│   └── LinkedInOAuthClient.php     # LinkedIn Ads OAuth
│
└── Twitter/
    └── TwitterOAuthClient.php      # Twitter/X OAuth
```

## 3. Key Files

- **`Base/OAuth2Client.php`**: Abstract OAuth 2.0 client providing:
  - `getAuthorizationUrl(string $state)`: Get OAuth authorization URL
  - `getAccessToken(string $code)`: Exchange code for token
  - `refreshToken(string $refreshToken)`: Refresh expired token
  - `revokeToken(string $token)`: Revoke access
  - `validateState(string $state, string $expected)`: CSRF protection

## 4. Standard OAuth Flow

```php
// 1. Redirect user to authorization URL
$oauthClient = new MetaOAuthClient();
$state = Str::random(40);
session(['oauth_state' => $state]);

$authUrl = $oauthClient->getAuthorizationUrl($state);
return redirect($authUrl);

// 2. Handle callback
public function handleCallback(Request $request)
{
    $oauthClient = new MetaOAuthClient();

    // Validate state (CSRF protection)
    if (!$oauthClient->validateState($request->state, session('oauth_state'))) {
        abort(403, 'Invalid state parameter');
    }

    // Exchange code for token
    $tokens = $oauthClient->getAccessToken($request->code);

    // Store in Integration model
    Integration::create([
        'org_id' => auth()->user()->org_id,
        'platform' => 'meta',
        'access_token' => $tokens['access_token'],
        'refresh_token' => $tokens['refresh_token'],
        'token_expires_at' => $tokens['expires_at'],
    ]);
}
```

## 5. Notes

- **OAuth 2.0** standard authorization code flow
- **CSRF Protection** via state parameter
- **Token Refresh** logic included
- **5 Platform Clients** (Meta, Google, TikTok, LinkedIn, Twitter)
