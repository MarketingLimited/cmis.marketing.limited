# CMIS Ad Platform Integration Architecture - Analysis & Corrections

**Date:** 2025-11-23
**Status:** ✅ Architecture Verified
**Finding:** Previous agent configuration contained incorrect assumptions

---

## 🔍 Investigation Summary

After comprehensive codebase analysis, we've identified the **ACTUAL** platform integration architecture used in CMIS.

---

## ✅ ACTUAL ARCHITECTURE (VERIFIED)

### Primary Integration System: `cmis.integrations`

**Table:** `cmis.integrations`
**Model:** `App\Models\Core\Integration` or `App\Models\Integration\Integration`
**Status:** ✅ **ACTIVELY USED** by all Ad Platform services

#### Evidence

1. **AbstractAdPlatform Base Class** (`app/Services/AdPlatforms/AbstractAdPlatform.php`):
   ```php
   // Line 24
   protected Integration $integration;

   // Line 46
   public function __construct(Integration $integration)
   {
       $this->integration = $integration;
       ...
   }
   ```

2. **GoogleAdsPlatform Service** (`app/Services/AdPlatforms/Google/GoogleAdsPlatform.php`):
   ```php
   // Line 25
   class GoogleAdsPlatform extends AbstractAdPlatform

   // Line 60-64
   public function __construct($integration)
   {
       parent::__construct($integration);
       $this->customerId = str_replace('-', '', $integration->account_id);
   }

   // Line 72 - Uses Integration model
   'Authorization' => 'Bearer ' . $this->integration->access_token,
   ```

3. **MetaAdsPlatform Service** (`app/Services/AdPlatforms/Meta/MetaAdsPlatform.php`):
   ```php
   // Line 18
   class MetaAdsPlatform extends AbstractAdPlatform

   // Line 56
   'Authorization' => 'Bearer ' . $this->integration->access_token,

   // Line 74
   $adAccountId = $this->integration->account_id;
   ```

4. **IntegrationController** (`app/Http/Controllers/IntegrationController.php`):
   ```php
   // Line 100 - Creates Integration records
   $integration = Integration::create([
       'org_id' => $orgId,
       'platform' => $request->input('platform'),
       'account_id' => $request->input('account_id'),
       'access_token' => $request->input('access_token'),
       ...
   ]);
   ```

#### Schema Structure

```sql
Table: cmis.integrations
Primary Key: integration_id (UUID)

Key Columns:
- org_id (UUID, foreign key to cmis.orgs)
- platform (varchar) - 'meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'
- account_id (varchar) - Platform-specific account ID
- access_token (encrypted text) - OAuth access token
- refresh_token (encrypted text) - OAuth refresh token
- token_expires_at (timestamp)
- is_active (boolean)
- last_synced_at (timestamp)
- sync_status (varchar)
```

#### Existing Ad Platform Services

All located in `app/Services/AdPlatforms/`:

- ✅ `Google/GoogleAdsPlatform.php` (2,400+ lines) - **FULLY IMPLEMENTED**
- ✅ `Meta/MetaAdsPlatform.php` (400+ lines) - **FULLY IMPLEMENTED**
- ✅ `TikTok/` directory exists
- ✅ `LinkedIn/` directory exists
- ✅ `Twitter/` directory exists
- ✅ `Snapchat/` directory exists

---

## ❌ INCORRECT ASSUMPTION: `cmis.platform_connections`

### What We Thought

The `platform_connections` table was the "new" architecture for Ad Platform integrations.

### Reality

**Table:** `cmis.platform_connections`
**Model:** `App\Models\Platform\PlatformConnection`
**Status:** ⚠️ **EXISTS BUT NOT USED by Ad Platform services**

#### Evidence

```bash
# Grep for platform_connections usage in AdPlatforms
grep -r "PlatformConnection\|platform_connections" app/Services/AdPlatforms/
# Result: NO MATCHES

# Where IS it used?
grep -r "PlatformConnection" app/**/*.php
# Results:
# - app/Services/CampaignOrchestratorService.php
# - app/Models/Platform/PlatformConnection.php
# - app/Services/Orchestration/CampaignOrchestrationService.php
# - app/Models/Orchestration/OrchestrationPlatform.php
```

#### Purpose

The `platform_connections` table appears to be used for **Campaign Orchestration**, NOT for Ad Platform API integrations.

**Migration Date:** 2025-11-21
**Purpose:** "Phase 18: Platform Integration & API Orchestration"
**Usage:** Campaign orchestration services (separate from Ad Platform connectors)

---

## 🏛️ Architecture Comparison

| Aspect | integrations (ACTUAL) | platform_connections (SEPARATE SYSTEM) |
|--------|----------------------|----------------------------------------|
| **Purpose** | Ad Platform API integrations | Campaign orchestration |
| **Used By** | AbstractAdPlatform, GoogleAdsPlatform, MetaAdsPlatform | CampaignOrchestratorService |
| **Primary Key** | `integration_id` | `connection_id` |
| **Model** | `App\Models\Core\Integration` | `App\Models\Platform\PlatformConnection` |
| **OAuth Tokens** | ✅ Encrypted | ✅ Encrypted |
| **RLS Support** | ✅ Yes | ✅ Yes |
| **Status** | ACTIVE | EXISTS (different purpose) |
| **Rate Limiting** | Manual in AbstractAdPlatform | Built-in tracking via `platform_rate_limits` |
| **API Call Logs** | Manual logging | Built-in via `platform_api_calls` |
| **Webhooks** | Manual implementation | Built-in via `platform_webhooks` |
| **Created** | Original CMIS architecture | 2025-11-21 (Phase 18) |

---

## 🔧 What Needs to Be Fixed

### 1. **Google Ads Specialist Agent** (`.claude/agents/cmis-google-ads-specialist.md`)

#### Issues
- ❌ References `platform_connections` as preferred table (WRONG)
- ❌ Suggests migration from `integrations` to `platform_connections` (WRONG)
- ❌ Shows code examples using `PlatformConnection` model (WRONG)
- ❌ Added confusing "dual-table architecture" section (MISLEADING)

#### Corrections Needed
- ✅ Use ONLY `cmis.integrations` table
- ✅ Use ONLY `Integration` model (`App\Models\Core\Integration`)
- ✅ Follow existing `AbstractAdPlatform` pattern
- ✅ Match `GoogleAdsPlatform` existing implementation
- ✅ Remove all references to `platform_connections`

### 2. **Documentation** (`docs/integrations/google/README.md`)

#### Issues
- ❌ Promotes `platform_connections` as primary architecture
- ❌ Shows OAuth flow creating `PlatformConnection` records
- ❌ Code examples use wrong model

#### Corrections Needed
- ✅ Update all code examples to use `Integration` model
- ✅ OAuth flow creates `Integration` records
- ✅ Remove platform_connections references

---

## ✅ CORRECT Implementation Pattern

### OAuth Flow (CORRECTED)

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

    $tokens = $response->json();
    $customerId = $this->getCustomerId($tokens['access_token']);

    // Create Integration record (NOT PlatformConnection)
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

### Google Ads Connector (CORRECTED)

```php
namespace App\Services\AdPlatforms\Google;

use App\Services\AdPlatforms\AbstractAdPlatform;
use App\Models\Core\Integration;

class GoogleAdsConnector extends AbstractAdPlatform
{
    // Extends AbstractAdPlatform which expects Integration

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
        ]);
    }

    public function refreshAccessToken(): bool
    {
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
            'access_token' => $tokens['access_token'],
            'token_expires_at' => now()->addSeconds($tokens['expires_in']),
        ]);

        return true;
    }
}
```

---

## 📚 Reference Files

### Verified Implementation Files
1. `app/Services/AdPlatforms/AbstractAdPlatform.php` - Base class for all platforms
2. `app/Services/AdPlatforms/Google/GoogleAdsPlatform.php` - Google Ads implementation (2,400+ lines)
3. `app/Services/AdPlatforms/Meta/MetaAdsPlatform.php` - Meta Ads implementation
4. `app/Models/Core/Integration.php` - Integration model
5. `app/Http/Controllers/IntegrationController.php` - Integration management
6. `database/migrations/*_create_integrations_table.php` - Integration schema

### Files Using platform_connections (DIFFERENT PURPOSE)
1. `app/Models/Platform/PlatformConnection.php` - Model (not used by AdPlatforms)
2. `app/Services/CampaignOrchestratorService.php` - Campaign orchestration
3. `database/migrations/2025_11_21_000007_create_platform_integration_tables.php` - Schema

---

## 🎯 Action Items

### Immediate
1. ✅ Revert Google Ads specialist agent to use `integrations` table only
2. ✅ Update documentation to remove `platform_connections` references
3. ✅ Correct all code examples to use `Integration` model
4. ✅ Remove misleading "dual-table architecture" sections

### Documentation Updates
1. ✅ Clearly state: "CMIS Ad Platforms use `cmis.integrations` table"
2. ✅ Reference existing `GoogleAdsPlatform` implementation as the pattern
3. ✅ Show OAuth flow creating `Integration` records
4. ✅ Update discovery protocols to search `integrations` table only

---

## 🧪 Verification

Run these commands to verify the architecture:

```bash
# Confirm AbstractAdPlatform uses Integration
grep -n "Integration" app/Services/AdPlatforms/AbstractAdPlatform.php

# Confirm GoogleAdsPlatform exists and extends AbstractAdPlatform
head -100 app/Services/AdPlatforms/Google/GoogleAdsPlatform.php

# Confirm NO usage of PlatformConnection in AdPlatforms
grep -r "PlatformConnection" app/Services/AdPlatforms/
# Expected: NO MATCHES

# Find all Integration usage
grep -r "Integration::create" app/Http/Controllers/
```

---

## 📝 Lessons Learned

1. **Always verify existing implementation** before documenting "new" architecture
2. **Check actual service code**, not just migrations
3. **Migration date ≠ current usage** - a table can exist but not be used
4. **Two tables with similar purposes** may serve different systems
5. **Discovery protocols must be followed** - grep the codebase first!

---

**Analysis Completed:** 2025-11-23
**Verified By:** Codebase grep, file reads, service inspection
**Next Step:** Apply corrections to agent and documentation
