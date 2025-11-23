# Google Ads Documentation Corrections

**Date:** 2025-11-23
**Status:** 🔧 Corrections Required

---

## ⚠️ IMPORTANT: Architecture Corrections

The initial documentation (`README.md`) contained **INCORRECT** references to the `platform_connections` table and `PlatformConnection` model.

**ACTUAL CMIS ARCHITECTURE:**
- ✅ Use `cmis.integrations` table
- ✅ Use `App\Models\Core\Integration` model
- ✅ Follow `AbstractAdPlatform` base class pattern
- ✅ Reference existing `GoogleAdsPlatform` service (already implemented)

---

## 🔧 Key Corrections

### 1. Database Architecture

❌ **INCORRECT (from original README):**
```markdown
CMIS uses the **NEW** `platform_connections` architecture:
- cmis.platform_connections → OAuth tokens, account info
```

✅ **CORRECT:**
```markdown
CMIS Ad Platforms use the `cmis.integrations` table:
- cmis.integrations → OAuth tokens, account info, platform credentials
```

---

### 2. OAuth Flow Implementation

❌ **INCORRECT:**
```php
use App\Models\Platform\PlatformConnection;

public function handleCallback(Request $request): PlatformConnection
{
    // ... code ...
    return PlatformConnection::create([...]);
}
```

✅ **CORRECT:**
```php
use App\Models\Core\Integration;

public function handleCallback(Request $request): Integration
{
    $code = $request->input('code');
    $state = json_decode(base64_decode($request->input('state')), true);

    // Exchange authorization code for tokens
    $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
        'code' => $code,
        'client_id' => config('services.google_ads.client_id'),
        'client_secret' => config('services.google_ads.client_secret'),
        'redirect_uri' => config('services.google_ads.redirect_uri'),
        'grant_type' => 'authorization_code',
    ]);

    if ($response->failed()) {
        throw new OAuthException('Failed to exchange authorization code');
    }

    $tokens = $response->json();
    $customerId = $this->getCustomerId($tokens['access_token']);

    // Create Integration record
    return Integration::create([
        'org_id' => $state['org_id'],
        'platform' => 'google',
        'account_id' => $customerId,
        'access_token' => $tokens['access_token'], // Auto-encrypted via model
        'refresh_token' => $tokens['refresh_token'],
        'token_expires_at' => now()->addSeconds($tokens['expires_in']),
        'is_active' => true,
    ]);
}
```

---

### 3. Google Ads Service Implementation

❌ **INCORRECT:**
```php
class GoogleConnector extends AbstractAdPlatform
{
    protected PlatformConnection $connection;

    public function __construct(PlatformConnection $connection) {
        $this->connection = $connection;
    }
}
```

✅ **CORRECT (ACTUAL IMPLEMENTATION):**
```php
namespace App\Services\AdPlatforms\Google;

use App\Services\AdPlatforms\AbstractAdPlatform;
use App\Models\Core\Integration;

/**
 * ACTUAL FILE: app/Services/AdPlatforms/Google/GoogleAdsPlatform.php
 * This service is FULLY IMPLEMENTED (2,400+ lines)
 */
class GoogleAdsPlatform extends AbstractAdPlatform
{
    protected string $apiVersion = 'v15';
    protected string $customerId;

    public function __construct(Integration $integration)
    {
        parent::__construct($integration);
        $this->customerId = str_replace('-', '', $integration->account_id);
    }

    protected function getDefaultHeaders(): array
    {
        return array_merge(parent::getDefaultHeaders(), [
            'Authorization' => 'Bearer ' . $this->integration->access_token,
            'developer-token' => config('services.google_ads.developer_token'),
            'login-customer-id' => $this->customerId,
        ]);
    }

    // ... 2,400+ lines of implementation
}
```

---

### 4. Token Refresh Implementation

❌ **INCORRECT:**
```php
$this->connection->update([
    'access_token' => $tokens['access_token'],
]);

// Log to platform_api_calls table
DB::table('cmis.platform_api_calls')->insert([...]);
```

✅ **CORRECT:**
```php
public function refreshAccessToken(): bool
{
    if (!$this->integration->refresh_token) {
        return false;
    }

    $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
        'refresh_token' => $this->integration->refresh_token,
        'client_id' => config('services.google_ads.client_id'),
        'client_secret' => config('services.google_ads.client_secret'),
        'grant_type' => 'refresh_token',
    ]);

    if ($response->failed()) {
        return false;
    }

    $tokens = $response->json();

    // Update Integration model
    $this->integration->update([
        'access_token' => $tokens['access_token'], // Auto-encrypted
        'token_expires_at' => now()->addSeconds($tokens['expires_in']),
        'is_active' => true,
    ]);

    return true;
}
```

---

### 5. Discovery SQL Queries

❌ **INCORRECT:**
```sql
-- Discover Google Ads integrations (NEW architecture)
SELECT * FROM cmis.platform_connections WHERE platform = 'google';
```

✅ **CORRECT:**
```sql
-- Discover Google Ads integrations
SELECT
    integration_id,
    org_id,
    platform,
    account_id,
    is_active,
    token_expires_at,
    last_synced_at,
    sync_status,
    created_at
FROM cmis.integrations
WHERE platform = 'google'
ORDER BY created_at DESC;
```

---

## 📋 What to Reference Instead

### Actual Implementation Files

1. **AbstractAdPlatform Base Class**
   - File: `app/Services/AdPlatforms/AbstractAdPlatform.php`
   - Uses: `Integration` model
   - Features: HTTP retry logic, rate limiting, error handling

2. **GoogleAdsPlatform Service** (✅ FULLY IMPLEMENTED)
   - File: `app/Services/AdPlatforms/Google/GoogleAdsPlatform.php`
   - Size: 2,400+ lines
   - Features: All campaign types, ad groups, keywords, ads, extensions

3. **Integration Model**
   - File: `app/Models/Core/Integration.php`
   - Table: `cmis.integrations`
   - Features: Encrypted tokens, RLS support, multi-tenancy

4. **IntegrationController**
   - File: `app/Http/Controllers/IntegrationController.php`
   - Creates/manages Integration records

---

## 🎯 Implementation Guidance

### For New Google Ads Features

**Follow this pattern:**

1. **Extend the existing GoogleAdsPlatform service**
   ```php
   // File: app/Services/AdPlatforms/Google/GoogleAdsPlatform.php
   public function createPerformanceMaxCampaign(array $data): array
   {
       // Use $this->integration for OAuth tokens
       // Use $this->makeRequest() for API calls
       // Extends AbstractAdPlatform functionality
   }
   ```

2. **Use Integration model for OAuth**
   ```php
   $integration = Integration::where('platform', 'google')
       ->where('org_id', $orgId)
       ->where('is_active', true)
       ->first();

   $googleAds = new GoogleAdsPlatform($integration);
   ```

3. **Store campaign data in unified_metrics**
   ```php
   DB::table('cmis.unified_metrics')->insert([
       'org_id' => $integration->org_id,
       'platform' => 'google',
       'entity_id' => $campaignId,
       'entity_type' => 'campaign',
       'metric_data' => $campaignData,
   ]);
   ```

---

## 🚫 What NOT to Use

❌ **Do NOT use:**
- `PlatformConnection` model (different purpose - campaign orchestration)
- `cmis.platform_connections` table (not used by AdPlatforms services)
- `cmis.platform_api_calls` table (separate system)
- `cmis.platform_webhooks` table (separate system)

These tables exist for **Campaign Orchestration** (not Ad Platform integrations).

---

## ✅ Verification Commands

Run these to verify the correct architecture:

```bash
# Confirm AbstractAdPlatform uses Integration
grep -A 5 "protected Integration" app/Services/AdPlatforms/AbstractAdPlatform.php

# Confirm GoogleAdsPlatform exists
wc -l app/Services/AdPlatforms/Google/GoogleAdsPlatform.php
# Expected: 2400+ lines

# Confirm NO usage of PlatformConnection in AdPlatforms
grep -r "PlatformConnection" app/Services/AdPlatforms/
# Expected: NO MATCHES

# See actual Integration usage
grep -A 10 "class GoogleAdsPlatform" app/Services/AdPlatforms/Google/GoogleAdsPlatform.php
```

---

## 📚 Correct Reference Files

**For Google Ads implementation, consult these files:**

1. `.claude/agents/cmis-google-ads-specialist.md` (corrected)
2. `app/Services/AdPlatforms/AbstractAdPlatform.php`
3. `app/Services/AdPlatforms/Google/GoogleAdsPlatform.php`
4. `app/Models/Core/Integration.php`
5. `docs/integrations/google/ARCHITECTURE_ANALYSIS.md`

**Do NOT reference:**
- Initial `README.md` (contains incorrect platform_connections references)

---

## 🎓 Key Takeaway

**The original README.md promoted an incorrect architecture.**

**Actual CMIS Ad Platform Architecture:**
- Integration Model + AbstractAdPlatform Base Class
- GoogleAdsPlatform service (already fully implemented)
- cmis.integrations table (active and in use)

Refer to the **ARCHITECTURE_ANALYSIS.md** document for the complete verified architecture.

---

**Corrections Applied:** 2025-11-23
**Status:** ✅ Agent corrected, README marked for revision
**Next Action:** Use corrected agent and architecture analysis for future work
