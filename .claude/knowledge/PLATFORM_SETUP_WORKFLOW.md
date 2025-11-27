# Platform Integration Setup Workflow Guide
**Version:** 1.1
**Last Updated:** 2025-11-27
**Purpose:** Complete step-by-step workflow for integrating advertising platforms in CMIS
**Prerequisites:** Read `.claude/knowledge/MULTI_TENANCY_PATTERNS.md` for RLS understanding

---

## ⚠️ IMPORTANT: Environment Configuration

**Platform OAuth credentials and database access MUST be configured via `.env` and config files.**

### OAuth Configuration

```bash
# Read platform credentials from .env
cat .env | grep -E "_CLIENT_ID|_CLIENT_SECRET|_REDIRECT"

# Example .env for Meta platform
META_CLIENT_ID=your-app-id
META_CLIENT_SECRET=your-app-secret
META_REDIRECT_URI=https://your-domain.com/oauth/meta/callback

# Database credentials (for discovery queries)
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
```

**In Laravel:**
```php
// ✅ CORRECT: Use config()
$clientId = config('services.meta.client_id');

// ❌ WRONG: Hardcoded
$clientId = '123456789';

// ❌ WRONG: env() outside config files
$clientId = env('META_CLIENT_ID');
```

---

## 🎯 OVERVIEW: What You Need to Understand

Before implementing ANY platform integration, you MUST understand:

1. **The Correct Order** - What to do first, second, third...
2. **Database Schema** - Where data is stored and how it's organized
3. **Token Management** - How OAuth tokens are stored and retrieved
4. **Multi-Tenancy** - Each organization has its own platform accounts
5. **RLS Context** - How to set organization context for database operations

---

## 📋 STEP-BY-STEP WORKFLOW (CORRECT ORDER)

### Phase 1: Database Schema Understanding (START HERE)

**Before writing ANY code, understand the database structure:**

```sql
-- 1. Organizations table (main tenant table)
SELECT * FROM cmis.organizations LIMIT 5;

-- Structure:
-- id (UUID) - organization identifier
-- name - organization name
-- slug - URL-friendly identifier
-- created_at, updated_at

-- 2. Social Accounts table (platform connections per org)
SELECT * FROM cmis_social.social_accounts LIMIT 5;

-- Structure:
-- id (UUID)
-- org_id (UUID) - FK to cmis.organizations.id
-- platform (enum) - 'meta', 'instagram', 'twitter', 'linkedin', 'tiktok', 'snapchat'
-- account_name - platform account name/identifier
-- platform_account_id - ID from the platform's API
-- access_token - encrypted OAuth access token
-- refresh_token - encrypted OAuth refresh token (if available)
-- token_expires_at - timestamp when token expires
-- scopes - array of granted OAuth scopes
-- is_active - boolean, whether connection is active
-- platform_metadata - JSONB for platform-specific data
-- created_at, updated_at, deleted_at

-- 3. Verify RLS policies exist
SELECT tablename, policyname, permissive, roles, cmd, qual
FROM pg_policies
WHERE schemaname = 'cmis_social'
  AND tablename = 'social_accounts';
```

**Key Understanding:**
- ✅ Each `organization` can have MULTIPLE platform accounts
- ✅ Tokens are stored ENCRYPTED in `social_accounts` table
- ✅ RLS policies ensure each org only sees its own accounts
- ✅ Platform metadata stored in JSONB for flexibility

---

### Phase 2: OAuth Configuration (Before Connecting)

**Set up OAuth credentials in Laravel config:**

```bash
# 1. Add platform credentials to .env
META_APP_ID=your_meta_app_id
META_APP_SECRET=your_meta_app_secret
META_WEBHOOK_SECRET=your_webhook_secret

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

TIKTOK_APP_ID=your_tiktok_app_id
TIKTOK_APP_SECRET=your_tiktok_app_secret

LINKEDIN_CLIENT_ID=your_linkedin_client_id
LINKEDIN_CLIENT_SECRET=your_linkedin_client_secret

TWITTER_API_KEY=your_twitter_api_key
TWITTER_API_SECRET=your_twitter_api_secret

SNAPCHAT_CLIENT_ID=your_snapchat_client_id
SNAPCHAT_CLIENT_SECRET=your_snapchat_client_secret
```

```php
// 2. Configure in config/services.php
return [
    'meta' => [
        'client_id' => env('META_APP_ID'),
        'client_secret' => env('META_APP_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/meta/callback',
        'scopes' => ['ads_management', 'ads_read', 'pages_read_engagement'],
        'api_version' => 'v19.0', // Update from discovery
        'webhook_secret' => env('META_WEBHOOK_SECRET'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/oauth/google/callback',
        'scopes' => ['https://www.googleapis.com/auth/adwords'],
    ],

    // ... other platforms
];
```

**Key Understanding:**
- ✅ Credentials stored in `.env` (never commit!)
- ✅ Redirect URLs must match platform app settings EXACTLY
- ✅ Scopes determine what permissions you get
- ✅ API versions should be discovered from latest docs

---

### Phase 3: OAuth Flow Implementation (Connecting Accounts)

**This is the FIRST code you implement:**

#### Step 3.1: Initiate OAuth (Redirect to Platform)

```php
// routes/api.php
Route::prefix('oauth')->group(function () {
    Route::get('/{platform}/authorize', [OAuthController::class, 'authorize']);
    Route::get('/{platform}/callback', [OAuthController::class, 'callback']);
});

// app/Http/Controllers/OAuthController.php
use App\Http\Controllers\Concerns\ApiResponse;

class OAuthController extends Controller
{
    use ApiResponse;

    public function authorize(Request $request, string $platform)
    {
        // STEP 1: Build authorization URL
        $config = config("services.{$platform}");

        if (!$config) {
            return $this->error("Platform '{$platform}' not configured", 400);
        }

        // Generate secure state token for CSRF protection
        $state = Str::random(40);
        Cache::put("oauth_state:{$state}", [
            'org_id' => auth()->user()->current_org_id,
            'user_id' => auth()->id(),
            'platform' => $platform,
        ], now()->addMinutes(10));

        // Build platform-specific auth URL
        $authUrl = match($platform) {
            'meta' => "https://www.facebook.com/v19.0/dialog/oauth?" . http_build_query([
                'client_id' => $config['client_id'],
                'redirect_uri' => $config['redirect'],
                'state' => $state,
                'scope' => implode(',', $config['scopes']),
            ]),

            'google' => "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
                'client_id' => $config['client_id'],
                'redirect_uri' => $config['redirect'],
                'state' => $state,
                'scope' => implode(' ', $config['scopes']),
                'response_type' => 'code',
                'access_type' => 'offline', // Get refresh token
            ]),

            // ... other platforms

            default => null,
        };

        if (!$authUrl) {
            return $this->error("Platform '{$platform}' not supported", 400);
        }

        return $this->success([
            'auth_url' => $authUrl,
            'state' => $state,
        ], 'Authorization URL generated');
    }
}
```

#### Step 3.2: Handle OAuth Callback (Receive Token)

```php
public function callback(Request $request, string $platform)
{
    // STEP 2: Verify state token (CSRF protection)
    $state = $request->input('state');
    $stateData = Cache::pull("oauth_state:{$state}");

    if (!$stateData) {
        return redirect()->route('dashboard')
            ->with('error', 'Invalid or expired OAuth state');
    }

    // Handle OAuth error
    if ($request->has('error')) {
        Log::error("OAuth error from {$platform}", [
            'error' => $request->input('error'),
            'description' => $request->input('error_description'),
        ]);

        return redirect()->route('dashboard')
            ->with('error', 'OAuth authorization failed');
    }

    // STEP 3: Exchange authorization code for access token
    $code = $request->input('code');
    $config = config("services.{$platform}");

    $tokenResponse = Http::asForm()->post($this->getTokenUrl($platform), [
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => $config['redirect'],
        'code' => $code,
        'grant_type' => 'authorization_code',
    ]);

    if ($tokenResponse->failed()) {
        Log::error("Token exchange failed for {$platform}", [
            'response' => $tokenResponse->json(),
        ]);

        return redirect()->route('dashboard')
            ->with('error', 'Failed to retrieve access token');
    }

    $tokenData = $tokenResponse->json();

    // STEP 4: Set RLS context BEFORE inserting to database
    DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
        $stateData['user_id'],
        $stateData['org_id'],
    ]);

    // STEP 5: Get platform account info
    $accountInfo = $this->getPlatformAccountInfo($platform, $tokenData['access_token']);

    // STEP 6: Store in social_accounts table with ENCRYPTED tokens
    $socialAccount = SocialAccount::create([
        'org_id' => $stateData['org_id'],
        'platform' => $platform,
        'account_name' => $accountInfo['name'] ?? 'Unknown',
        'platform_account_id' => $accountInfo['id'] ?? null,
        'access_token' => encrypt($tokenData['access_token']), // ENCRYPTED!
        'refresh_token' => isset($tokenData['refresh_token'])
            ? encrypt($tokenData['refresh_token'])
            : null,
        'token_expires_at' => isset($tokenData['expires_in'])
            ? now()->addSeconds($tokenData['expires_in'])
            : null,
        'scopes' => $tokenData['scope'] ?? $config['scopes'],
        'is_active' => true,
        'platform_metadata' => $accountInfo['metadata'] ?? [],
    ]);

    // STEP 7: Trigger initial data sync (optional)
    if ($platform !== 'meta' && $platform !== 'google') {
        // For social platforms, sync initial posts
        SyncSocialPostsJob::dispatch($socialAccount)->delay(now()->addSeconds(10));
    }

    Log::info("Platform connected successfully", [
        'platform' => $platform,
        'org_id' => $stateData['org_id'],
        'account_id' => $socialAccount->id,
    ]);

    return redirect()->route('dashboard')
        ->with('success', ucfirst($platform) . ' account connected successfully');
}

protected function getTokenUrl(string $platform): string
{
    return match($platform) {
        'meta' => 'https://graph.facebook.com/v19.0/oauth/access_token',
        'google' => 'https://oauth2.googleapis.com/token',
        'tiktok' => 'https://business-api.tiktok.com/open_api/v1.3/oauth2/access_token/',
        'linkedin' => 'https://www.linkedin.com/oauth/v2/accessToken',
        'twitter' => 'https://api.twitter.com/2/oauth2/token',
        'snapchat' => 'https://accounts.snapchat.com/login/oauth2/access_token',
        default => throw new \Exception("Unknown platform: {$platform}"),
    };
}

protected function getPlatformAccountInfo(string $platform, string $accessToken): array
{
    // Fetch account details from platform API
    $response = match($platform) {
        'meta' => Http::get("https://graph.facebook.com/v19.0/me", [
            'access_token' => $accessToken,
            'fields' => 'id,name,email',
        ]),

        'google' => Http::withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v2/userinfo'),

        // ... other platforms

        default => null,
    };

    if (!$response || $response->failed()) {
        return ['name' => 'Unknown', 'id' => null];
    }

    return $response->json();
}
```

**Key Understanding:**
- ✅ State token prevents CSRF attacks
- ✅ Tokens are ALWAYS encrypted before storage
- ✅ RLS context MUST be set before database insert
- ✅ Each org can connect multiple accounts per platform

---

### Phase 4: Retrieving and Using Tokens (From Database)

**How to get tokens for API calls:**

```php
// app/Services/AdPlatforms/MetaConnector.php

use App\Models\Social\SocialAccount;

class MetaConnector
{
    public function createCampaign(string $orgId, array $campaignData)
    {
        // STEP 1: Set RLS context
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            auth()->id() ?? config('cmis.system_user_id'),
            $orgId,
        ]);

        // STEP 2: Get active Meta account for this org
        $account = SocialAccount::where('org_id', $orgId)
            ->where('platform', 'meta')
            ->where('is_active', true)
            ->first();

        if (!$account) {
            throw new \Exception("No active Meta account found for organization");
        }

        // STEP 3: Decrypt token
        $accessToken = decrypt($account->access_token);

        // STEP 4: Check if token expired
        if ($account->token_expires_at && $account->token_expires_at->isPast()) {
            // Refresh token if we have refresh_token
            if ($account->refresh_token) {
                $this->refreshToken($account);
                $accessToken = decrypt($account->fresh()->access_token);
            } else {
                throw new \Exception("Access token expired and no refresh token available");
            }
        }

        // STEP 5: Use token for API call
        $response = Http::withToken($accessToken)
            ->post("https://graph.facebook.com/v19.0/{$account->platform_account_id}/campaigns", [
                'name' => $campaignData['name'],
                'objective' => $campaignData['objective'],
                'status' => 'PAUSED',
                'special_ad_categories' => [],
            ]);

        if ($response->failed()) {
            throw new \Exception("Failed to create Meta campaign: " . $response->body());
        }

        return $response->json();
    }

    protected function refreshToken(SocialAccount $account): void
    {
        $config = config('services.meta');

        $response = Http::asForm()->post('https://graph.facebook.com/v19.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'fb_exchange_token' => decrypt($account->access_token),
        ]);

        if ($response->failed()) {
            throw new \Exception("Token refresh failed");
        }

        $tokenData = $response->json();

        // Update token in database (encrypted)
        $account->update([
            'access_token' => encrypt($tokenData['access_token']),
            'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
        ]);

        Log::info("Token refreshed for {$account->platform} account", [
            'account_id' => $account->id,
            'org_id' => $account->org_id,
        ]);
    }
}
```

**Key Understanding:**
- ✅ ALWAYS set RLS context before queries
- ✅ Filter by org_id AND platform to get account
- ✅ ALWAYS decrypt tokens before use
- ✅ Check token expiration before API calls
- ✅ Implement token refresh mechanism

---

### Phase 5: Multi-Tenancy Verification (Critical!)

**Verify each org only sees its own accounts:**

```sql
-- Test 1: Set context for Org A
SELECT cmis.init_transaction_context('user-id-1', 'org-a-uuid');

-- Should ONLY return Org A's accounts
SELECT id, org_id, platform, account_name
FROM cmis_social.social_accounts;

-- Test 2: Set context for Org B
SELECT cmis.init_transaction_context('user-id-2', 'org-b-uuid');

-- Should ONLY return Org B's accounts (different from Org A)
SELECT id, org_id, platform, account_name
FROM cmis_social.social_accounts;

-- Test 3: Try to access without context (should see nothing or error)
RESET app.current_org_id;
SELECT * FROM cmis_social.social_accounts; -- Should return 0 rows or error
```

**Key Understanding:**
- ✅ RLS prevents cross-org data leaks
- ✅ NEVER manually filter by org_id (RLS does it)
- ✅ ALWAYS test multi-tenancy isolation
- ✅ Each org can have multiple accounts per platform

---

### Phase 6: Complete Integration Workflow Example

**From OAuth to Campaign Creation:**

```php
// 1. USER: Clicks "Connect Meta Account" button in UI
// → Frontend redirects to: GET /oauth/meta/authorize

// 2. BACKEND: Generates auth URL and redirects to Meta
// → User sees Meta's login page and permission screen

// 3. USER: Approves permissions on Meta
// → Meta redirects back to: GET /oauth/meta/callback?code=xyz&state=abc

// 4. BACKEND: Exchanges code for token and stores in database
// → SocialAccount created with encrypted token

// 5. USER: Creates a campaign via UI
// → Frontend calls: POST /api/campaigns with campaign data

// 6. BACKEND: Retrieves token and creates campaign on Meta
class CampaignController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'platform' => 'required|in:meta,google,tiktok,linkedin,twitter,snapchat',
            'objective' => 'required|string',
            'budget' => 'required|numeric|min:1',
        ]);

        // Set RLS context
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            auth()->id(),
            auth()->user()->current_org_id,
        ]);

        // Get platform connector
        $connector = AdPlatformFactory::make($validated['platform']);

        // Create campaign on platform
        $platformCampaign = $connector->createCampaign(
            auth()->user()->current_org_id,
            $validated
        );

        // Store campaign in CMIS database
        $campaign = Campaign::create([
            'org_id' => auth()->user()->current_org_id,
            'name' => $validated['name'],
            'platform' => $validated['platform'],
            'platform_campaign_id' => $platformCampaign['id'],
            'objective' => $validated['objective'],
            'budget' => $validated['budget'],
            'status' => 'active',
        ]);

        return $this->created($campaign, 'Campaign created successfully');
    }
}
```

---

## 🗂️ DATABASE SCHEMA REFERENCE

### Complete Table Structure

```sql
-- 1. Organizations (Main Tenant)
CREATE TABLE cmis.organizations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
);

-- 2. Social Accounts (Platform Connections per Org)
CREATE TABLE cmis_social.social_accounts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.organizations(id) ON DELETE CASCADE,
    platform VARCHAR(50) NOT NULL CHECK (platform IN ('meta', 'instagram', 'twitter', 'linkedin', 'tiktok', 'snapchat')),
    account_name VARCHAR(255),
    platform_account_id VARCHAR(255),
    access_token TEXT NOT NULL, -- ENCRYPTED
    refresh_token TEXT, -- ENCRYPTED (if available)
    token_expires_at TIMESTAMP,
    scopes JSONB,
    is_active BOOLEAN DEFAULT true,
    platform_metadata JSONB DEFAULT '{}',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP,

    UNIQUE(org_id, platform, platform_account_id)
);

-- Enable RLS
ALTER TABLE cmis_social.social_accounts ENABLE ROW LEVEL SECURITY;

-- RLS Policy: Users can only see their org's accounts
CREATE POLICY social_accounts_select_policy ON cmis_social.social_accounts
    FOR SELECT
    USING (org_id = current_setting('app.current_org_id', true)::uuid);

CREATE POLICY social_accounts_insert_policy ON cmis_social.social_accounts
    FOR INSERT
    WITH CHECK (org_id = current_setting('app.current_org_id', true)::uuid);

CREATE POLICY social_accounts_update_policy ON cmis_social.social_accounts
    FOR UPDATE
    USING (org_id = current_setting('app.current_org_id', true)::uuid);

CREATE POLICY social_accounts_delete_policy ON cmis_social.social_accounts
    FOR DELETE
    USING (org_id = current_setting('app.current_org_id', true)::uuid);

-- 3. Campaigns (Ad Campaigns)
CREATE TABLE cmis.campaigns (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.organizations(id),
    name VARCHAR(255) NOT NULL,
    platform VARCHAR(50) NOT NULL,
    platform_campaign_id VARCHAR(255),
    objective VARCHAR(100),
    budget DECIMAL(15, 2),
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
);

-- Enable RLS for campaigns
ALTER TABLE cmis.campaigns ENABLE ROW LEVEL SECURITY;

-- (Similar RLS policies as social_accounts)
```

---

## 🔐 TOKEN SECURITY BEST PRACTICES

### ALWAYS Follow These Rules:

1. **Encrypt Before Storage:**
```php
// ✅ CORRECT
'access_token' => encrypt($token),

// ❌ WRONG
'access_token' => $token, // Plain text!
```

2. **Decrypt Before Use:**
```php
// ✅ CORRECT
$token = decrypt($account->access_token);
Http::withToken($token)->get(...);

// ❌ WRONG
Http::withToken($account->access_token)->get(...); // Encrypted token won't work!
```

3. **Hide Tokens in API Responses:**
```php
// ✅ CORRECT
return $account->makeHidden(['access_token', 'refresh_token']);

// ❌ WRONG
return $account; // Exposes encrypted tokens!
```

4. **Never Log Tokens:**
```php
// ✅ CORRECT
Log::info("Token refreshed", ['account_id' => $account->id]);

// ❌ WRONG
Log::info("Token refreshed", ['token' => $token]); // Security breach!
```

---

## 🔍 Quick Reference

| Integration Phase | Key Action | Must Have |
|-------------------|------------|-----------|
| 1. Schema Discovery | Query `cmis_social.social_accounts` structure | Database access via .env |
| 2. OAuth Setup | Configure in `config/services.php` | Client ID/Secret in .env |
| 3. OAuth Flow | Implement authorize → callback → token storage | Encryption for tokens |
| 4. RLS Context | Call `init_transaction_context(user_id, org_id)` | Before all DB operations |
| 5. Token Management | Implement refresh mechanism | Check `token_expires_at` |
| 6. Platform API | Use service class with authenticated client | Decrypted access token |
| 7. Testing | Verify multi-tenancy isolation | Multiple test orgs |

### Pre-Implementation Checklist

- [ ] Database schema understood (organizations, social_accounts, campaigns)
- [ ] OAuth credentials configured in .env and config/services.php
- [ ] OAuth flow implemented (authorize → callback → token storage)
- [ ] Tokens stored ENCRYPTED in database
- [ ] RLS context set before ALL database operations
- [ ] Token retrieval and decryption working
- [ ] Token refresh mechanism implemented
- [ ] Multi-tenancy tested (each org sees only its accounts)
- [ ] Platform API calls using correct token
- [ ] Error handling for expired/invalid tokens

---

## 📚 Related Knowledge

**Prerequisites:**
- **MULTI_TENANCY_PATTERNS.md** - RLS and organization isolation patterns
- **DISCOVERY_PROTOCOLS.md** - Discovery methodology

**Related Files:**
- **LARAVEL_CONVENTIONS.md** - Configuration best practices (use config(), not env())
- **PATTERN_RECOGNITION.md** - Service layer patterns
- **CMIS_PROJECT_KNOWLEDGE.md** - Core architecture

**See Also:**
- **CLAUDE.md** - Main project guidelines
- `.claude/agents/cmis-platform-integration.md` - Platform integration agent
- `.claude/agents/cmis-compliance-security.md` - Security specialist agent

---

**Last Updated:** 2025-11-27
**Version:** 1.1
**Maintained By:** CMIS AI Agent Development Team

*"Schema first, OAuth second, RLS always. NEVER hardcode credentials."*
