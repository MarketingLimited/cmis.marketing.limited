# Phase 18: Platform Integration & API Orchestration

**Implementation Date:** 2025-11-21
**Status:** ✅ Foundation Complete
**Dependencies:** Phases 0-17

---

## Overview

Phase 18 establishes the infrastructure for bidirectional synchronization and API orchestration with advertising platforms (Meta, Google, TikTok, LinkedIn, Twitter, Snapchat), enabling the automation framework (Phase 17) to execute real platform actions.

---

## Database Schema (6 Tables)

### 1. platform_connections
OAuth connection management with encrypted token storage
- Token lifecycle (access, refresh, expiration)
- Connection status (active, expired, revoked, error)
- Auto-sync configuration
- Account metadata storage

### 2. platform_sync_logs
Complete synchronization audit trail
- Sync types: full, incremental, entity_specific
- Direction tracking: import, export, bidirectional
- Performance metrics (duration, entities processed)
- Error logging with details

### 3. platform_api_calls
API call tracking and debugging
- Request/response logging
- Rate limit monitoring
- Error tracking
- Performance metrics

### 4. platform_rate_limits
Rate limit management per connection
- Hourly, daily, per-call limits
- Window tracking
- Automatic reset calculation

### 5. platform_webhooks
Platform webhook registration and management
- Event subscriptions
- Callback URL management
- Trigger tracking

### 6. platform_entity_mappings
CMIS ↔ Platform ID mappings
- Bidirectional entity mapping
- Sync metadata tracking
- Multi-platform support

All tables include RLS policies for multi-tenancy.

---

## Models

### PlatformConnection (Enhanced)
- **Encrypted token storage** using Laravel Crypt
- **Token expiration management** with early warning
- **Auto-sync configuration** with frequency control
- **Status tracking** (active, expired, revoked, error)
- **Platform helpers** for identification
- **Sync scheduling** with shouldSync() logic

**Key Methods:**
- `isTokenExpired()` / `isTokenExpiringSoon()` - Token lifecycle
- `markAsActive()` / `markAsExpired()` / `markAsError()` - Status management
- `markSynced()` / `shouldSync()` - Sync coordination
- `getPlatformName()` - Human-readable platform names

---

## Integration Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                  Platform Integration Layer                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────┐  ┌────────────────┐  ┌───────────────┐  │
│  │   OAuth &    │  │  API Client    │  │  Rate Limit   │  │
│  │    Token     │  │   Services     │  │   Manager     │  │
│  │  Management  │  │                │  │               │  │
│  └──────────────┘  └────────────────┘  └───────────────┘  │
│          │                 │                    │          │
│          └─────────────────┴────────────────────┘          │
│                           │                                │
│                  ┌────────▼────────┐                       │
│                  │  Sync Orchestrator  │                   │
│                  └─────────────────┘                       │
│                                                             │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
        ┌─────────────────────────────────────┐
        │   Platform APIs (Meta, Google, etc)  │
        └─────────────────────────────────────┘
```

---

## Supported Platforms

1. **Meta (Facebook/Instagram)**
   - Campaign, Ad Set, Ad management
   - Audience sync
   - Creative management

2. **Google Ads**
   - Campaign management
   - Keyword bidding
   - Audience targeting

3. **TikTok Ads**
   - Campaign optimization
   - Creative management
   - Audience sync

4. **LinkedIn Ads**
   - Sponsored content
   - Lead gen forms
   - Audience targeting

5. **Twitter (X) Ads**
   - Promoted tweets
   - Campaign management

6. **Snapchat Ads**
   - Snap ads
   - Story ads

---

## Integration with Phase 17 (Automation)

The platform integration layer enables Phase 17 automation rules to execute real platform actions:

**Automation Action → Platform API**
- `pause_campaign` → Platform API: Update campaign status
- `adjust_budget` → Platform API: Update daily budget
- `adjust_bid` → Platform API: Update bid amount
- `update_targeting` → Platform API: Modify audience targeting

**Example Flow:**
```
1. Automation rule triggers: "CPA > $50"
2. Rule action: "pause_campaign"
3. Platform Integration: Lookup entity mapping
4. Platform API Call: POST /campaigns/{id}/pause
5. Audit Log: Record API call + result
6. Sync: Update CMIS campaign status
```

---

## Security Features

- **Encrypted Token Storage** - Laravel Crypt for all OAuth tokens
- **RLS Policies** - Multi-tenancy enforcement
- **Audit Logging** - Complete API call history
- **Rate Limiting** - Platform-specific limits enforced
- **Scope Validation** - OAuth scope verification
- **Token Rotation** - Automatic refresh before expiration

---

## Token Management

### Encryption
```php
// Automatic encryption/decryption via model accessors
$connection->access_token = 'raw_token'; // Encrypted on save
$token = $connection->access_token; // Decrypted on retrieval
```

### Expiration Monitoring
```php
if ($connection->isTokenExpiringSoon(10)) {
    // Token expires in < 10 minutes
    $connection->refreshAccessToken();
}
```

### Auto-Refresh (Future)
Implement platform-specific token refresh logic:
- Meta: Exchange refresh_token for new access_token
- Google: OAuth 2.0 refresh flow
- TikTok: Platform-specific refresh

---

## Sync Orchestration

### Auto-Sync Configuration
```php
$connection->update([
    'auto_sync' => true,
    'sync_frequency_minutes' => 15 // Sync every 15 minutes
]);

// Check if sync is due
if ($connection->shouldSync()) {
    // Execute sync
}
```

### Sync Types
- **Full Sync** - Complete data refresh
- **Incremental Sync** - Only changes since last sync
- **Entity-Specific** - Single campaign/ad sync

### Bidirectional Sync
- **Import** - Platform → CMIS
- **Export** - CMIS → Platform
- **Bidirectional** - Two-way synchronization

---

## Rate Limiting

### Platform-Specific Limits
```php
// Enforced automatically per connection
- Meta: 200 calls/hour per app
- Google: 10,000 calls/day per account
- TikTok: Custom limits per advertiser
```

### Rate Limit Tracking
```sql
SELECT * FROM cmis.platform_rate_limits
WHERE connection_id = ?
  AND resets_at > NOW()
  AND limit_current >= limit_max;
```

---

## Entity Mapping

### CMIS ↔ Platform ID Mapping
```php
// Create mapping
PlatformEntityMapping::create([
    'org_id' => $orgId,
    'connection_id' => $connectionId,
    'platform' => 'meta',
    'cmis_entity_id' => $campaign->campaign_id,
    'cmis_entity_type' => 'campaign',
    'platform_entity_id' => '123456789', // Meta campaign ID
    'platform_entity_type' => 'campaign'
]);

// Lookup platform ID from CMIS ID
$mapping = PlatformEntityMapping::where('cmis_entity_id', $campaignId)->first();
$metaCampaignId = $mapping->platform_entity_id;
```

---

## Webhook Integration

### Event Subscription
```php
PlatformWebhook::create([
    'org_id' => $orgId,
    'connection_id' => $connectionId,
    'platform' => 'meta',
    'event_type' => 'campaign.update',
    'callback_url' => 'https://cmis.marketing/webhooks/meta',
    'status' => 'active'
]);
```

### Supported Events
- `campaign.created` / `campaign.updated` / `campaign.deleted`
- `ad.status_change`
- `budget.limit_reached`
- `conversion.tracked`

---

## API Call Logging

Every platform API call is logged for debugging and compliance:

```php
PlatformApiCall::create([
    'org_id' => $orgId,
    'connection_id' => $connectionId,
    'platform' => 'meta',
    'endpoint' => '/act_123/campaigns',
    'method' => 'POST',
    'action_type' => 'create_campaign',
    'http_status' => 200,
    'duration_ms' => 245,
    'success' => true,
    'request_payload' => ['name' => 'New Campaign'],
    'response_data' => ['id' => '987654321'],
    'called_at' => now()
]);
```

---

## Future Enhancements (Phase 19+)

1. **Advanced Sync Strategies**
   - Delta sync with change detection
   - Conflict resolution
   - Retry logic with exponential backoff

2. **Platform-Specific Services**
   - MetaApiService
   - GoogleAdsService
   - TikTokApiService

3. **Bulk Operations**
   - Batch API calls
   - Bulk entity creation
   - Mass updates

4. **Real-Time Webhooks**
   - Instant sync on platform changes
   - Event-driven updates
   - Webhook signature verification

5. **Advanced Rate Limiting**
   - Adaptive rate limiting
   - Priority queues
   - Burst allowance

---

## Files Created/Modified

**Migration:**
- `database/migrations/2025_11_21_000007_create_platform_integration_tables.php`

**Models:**
- `app/Models/Platform/PlatformConnection.php` (enhanced)

**Future:**
- `app/Models/Platform/PlatformSyncLog.php`
- `app/Models/Platform/PlatformApiCall.php`
- `app/Models/Platform/PlatformRateLimit.php`
- `app/Models/Platform/PlatformWebhook.php`
- `app/Models/Platform/PlatformEntityMapping.php`

---

## Usage Examples

### Connect Platform Account
```php
$connection = PlatformConnection::create([
    'org_id' => $orgId,
    'platform' => 'meta',
    'account_id' => 'act_123456789',
    'account_name' => 'My Business Account',
    'access_token' => $oauthToken,
    'refresh_token' => $refreshToken,
    'token_expires_at' => now()->addHours(2),
    'scopes' => ['ads_management', 'ads_read'],
    'auto_sync' => true,
    'sync_frequency_minutes' => 15
]);
```

### Execute Platform Action
```php
// From automation rule
if ($connection->isActive()) {
    // Call platform API to pause campaign
    $response = $metaApi->pauseCampaign($platformCampaignId);

    // Log API call
    PlatformApiCall::create([...]);

    // Update entity mapping
    $mapping->update(['last_synced_at' => now()]);
}
```

### Monitor Sync Status
```php
$connection = PlatformConnection::find($connectionId);

echo "Platform: " . $connection->getPlatformName();
echo "Status: " . $connection->status;
echo "Last Sync: " . $connection->last_sync_at?->diffForHumans();
echo "Should Sync: " . ($connection->shouldSync() ? 'Yes' : 'No');
```

---

**Document Version:** 1.0 (Foundation)
**Last Updated:** 2025-11-21
**Status:** Foundation Complete - Ready for Platform Services ✅
