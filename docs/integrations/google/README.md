# Google Ads Integration for CMIS

**Last Updated:** 2025-11-23
**Status:** 🚧 Planning Phase - Not Yet Implemented
**Agent:** `cmis-google-ads-specialist`
**Architecture Version:** 3.2 (Platform Connections)

---

## 📋 Overview

This documentation provides comprehensive guidance for implementing Google Ads integration in CMIS using the Google Ads API. The integration enables campaign management, conversion tracking, audience targeting, and performance optimization across Search, Display, Video, Shopping, and Performance Max campaigns.

### Implementation Status

| Component | Status | Notes |
|-----------|--------|-------|
| Database Schema | ✅ Ready | `platform_connections` table exists |
| Models | ❌ Not Implemented | Need to create `GoogleCampaign`, `GoogleAdGroup`, etc. |
| Connector Service | ❌ Not Implemented | Need to create `GoogleConnector` class |
| OAuth Flow | ❌ Not Implemented | Need to implement Google OAuth 2.0 |
| API Integration | ❌ Not Implemented | Need to integrate Google Ads API |
| Webhook Handlers | ❌ Not Implemented | Need to create webhook endpoints |
| Campaign Management | ❌ Not Implemented | Need to create campaign CRUD operations |

---

## 🏗️ Architecture

### Database Structure

CMIS uses the **NEW** `platform_connections` architecture (created 2025-11-21):

```
cmis.platform_connections       → OAuth tokens, account info
cmis.platform_api_calls         → API request logging
cmis.platform_sync_logs         → Sync operation tracking
cmis.platform_webhooks          → Webhook configuration
cmis.platform_entity_mappings   → CMIS ↔ Google ID mapping
cmis.platform_rate_limits       → Rate limit tracking
cmis.unified_metrics            → Campaign performance data
```

**Legacy Support:** The older `cmis.integrations` table still exists for backward compatibility but NEW implementations should use `platform_connections`.

### Key Differences: platform_connections vs integrations

| Feature | platform_connections | integrations (legacy) |
|---------|---------------------|----------------------|
| Primary Key | `connection_id` | `integration_id` |
| Status Tracking | 4 states (active/expired/revoked/error) | Boolean `is_active` |
| Rate Limiting | Built-in via `platform_rate_limits` | Manual implementation |
| API Logging | Automatic via `platform_api_calls` | Manual logging |
| Webhook Support | Built-in via `platform_webhooks` | Manual setup |
| Entity Mapping | Built-in via `platform_entity_mappings` | Manual tracking |
| Encryption | Auto via model casts | Auto via model casts |
| RLS Support | ✅ Yes | ✅ Yes |

---

## 🔐 Authentication & OAuth 2.0

### Google Ads API Requirements

1. **Google Ads Developer Token**
   - Apply at: https://developers.google.com/google-ads/api/docs/get-started/dev-token
   - Levels: Test account → Basic access → Standard access
   - Store in: `GOOGLE_ADS_DEVELOPER_TOKEN` environment variable

2. **OAuth 2.0 Credentials**
   - Create OAuth client in Google Cloud Console
   - Scopes required: `https://www.googleapis.com/auth/adwords`
   - Store in config: `config/services.php`

3. **Manager Account (MCC) Access** (Optional but recommended)
   - For managing multiple client accounts
   - Customer ID format: `XXX-XXX-XXXX`

### Configuration

**`config/services.php`:**
```php
'google_ads' => [
    'client_id' => env('GOOGLE_ADS_CLIENT_ID'),
    'client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),
    'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
    'api_version' => env('GOOGLE_ADS_API_VERSION', 'v17'), // Current: v17 (2025)
    'redirect_uri' => env('APP_URL') . '/oauth/google-ads/callback',
],
```

**`.env.example`:**
```bash
# Google Ads API Configuration
GOOGLE_ADS_CLIENT_ID=your-oauth-client-id
GOOGLE_ADS_CLIENT_SECRET=your-oauth-client-secret
GOOGLE_ADS_DEVELOPER_TOKEN=your-developer-token
GOOGLE_ADS_API_VERSION=v17
```

### OAuth Flow Implementation

**Step 1: Authorization URL**
```php
public function getAuthorizationUrl(string $orgId): string
{
    $state = base64_encode(json_encode([
        'org_id' => $orgId,
        'timestamp' => now()->timestamp,
        'nonce' => Str::random(32),
    ]));

    $params = http_build_query([
        'client_id' => config('services.google_ads.client_id'),
        'redirect_uri' => config('services.google_ads.redirect_uri'),
        'scope' => 'https://www.googleapis.com/auth/adwords',
        'response_type' => 'code',
        'access_type' => 'offline', // Get refresh token
        'prompt' => 'consent', // Force consent to get refresh token
        'state' => $state,
    ]);

    return "https://accounts.google.com/o/oauth2/v2/auth?{$params}";
}
```

**Step 2: Handle Callback**
```php
public function handleCallback(Request $request): PlatformConnection
{
    $code = $request->input('code');
    $state = json_decode(base64_decode($request->input('state')), true);

    // Verify state
    if (!$state || !isset($state['org_id'])) {
        throw new InvalidStateException('Invalid OAuth state');
    }

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

    // Get customer ID (account ID)
    $customerId = $this->getCustomerId($tokens['access_token']);

    // Create platform connection
    return PlatformConnection::create([
        'org_id' => $state['org_id'],
        'platform' => 'google',
        'account_id' => $customerId,
        'account_name' => $this->getAccountName($tokens['access_token'], $customerId),
        'status' => 'active',
        'access_token' => $tokens['access_token'], // Auto-encrypted
        'refresh_token' => $tokens['refresh_token'], // Auto-encrypted
        'token_expires_at' => now()->addSeconds($tokens['expires_in']),
        'scopes' => ['https://www.googleapis.com/auth/adwords'],
        'auto_sync' => true,
        'sync_frequency_minutes' => 15,
    ]);
}
```

**Step 3: Token Refresh**
```php
public function refreshAccessToken(PlatformConnection $connection): bool
{
    $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
        'refresh_token' => $connection->refresh_token,
        'client_id' => config('services.google_ads.client_id'),
        'client_secret' => config('services.google_ads.client_secret'),
        'grant_type' => 'refresh_token',
    ]);

    if ($response->failed()) {
        $connection->update([
            'status' => 'error',
            'last_error_at' => now(),
            'last_error_message' => 'Token refresh failed: ' . $response->body(),
        ]);
        return false;
    }

    $tokens = $response->json();

    $connection->update([
        'access_token' => $tokens['access_token'],
        'token_expires_at' => now()->addSeconds($tokens['expires_in']),
        'status' => 'active',
        'last_error_at' => null,
        'last_error_message' => null,
    ]);

    return true;
}
```

---

## 🎯 Campaign Types

Google Ads supports multiple campaign types, each with unique requirements:

### 1. Search Campaigns

**Use Case:** Text ads on Google Search results
**Required Assets:** Keywords, headlines, descriptions, landing pages
**Bidding:** Manual CPC, Enhanced CPC, Maximize Clicks, Target CPA, Target ROAS

### 2. Display Campaigns

**Use Case:** Visual ads on Google Display Network
**Required Assets:** Images, responsive display ads, audience targeting
**Bidding:** Smart Bidding, Viewable CPM, Cost per engagement

### 3. Video Campaigns (YouTube)

**Use Case:** Video ads on YouTube and Google video partners
**Required Assets:** Video creative, headlines, descriptions, CTA
**Bidding:** CPV (cost-per-view), Target CPM, Maximize Conversions

### 4. Shopping Campaigns

**Use Case:** Product listings on Google Shopping
**Required Assets:** Google Merchant Center feed, product data
**Bidding:** Manual CPC, Enhanced CPC, Maximize Clicks, Target ROAS

### 5. Performance Max Campaigns

**Use Case:** AI-driven campaigns across all Google properties
**Required Assets:** Asset group (headlines, descriptions, images, videos, logos)
**Bidding:** Maximize Conversions, Maximize Conversion Value, Target CPA, Target ROAS

### 6. Discovery Campaigns

**Use Case:** Visually rich ads on Discover, YouTube Home, Gmail
**Required Assets:** Images, headlines, descriptions, logos, audience targeting
**Bidding:** Smart Bidding, Target CPA, Maximize Conversions

---

## 📊 Data Storage: Unified Metrics

CMIS stores Google Ads campaign data in the **unified_metrics** table (Phase 1 implementation):

```sql
-- Example: Storing Google Ads campaign metrics
INSERT INTO cmis.unified_metrics (
    org_id,
    platform,
    entity_id,
    entity_type,
    metric_date,
    period_type,
    metric_data
) VALUES (
    'org-uuid',
    'google',
    'campaign-123456789',
    'campaign',
    '2025-11-23',
    'daily',
    jsonb_build_object(
        'campaign_id', '123456789',
        'campaign_name', 'Q1 Product Launch',
        'campaign_type', 'SEARCH',
        'status', 'ENABLED',
        'impressions', 15420,
        'clicks', 892,
        'conversions', 45,
        'cost', 2847.50,
        'ctr', 5.78,
        'cpc', 3.19,
        'conversion_rate', 5.04,
        'quality_score_avg', 7.2
    )
);
```

### Monthly Partitioning

Unified metrics uses monthly partitioning for performance:
```sql
-- Partition naming: unified_metrics_YYYYMM
unified_metrics_202511  -- November 2025
unified_metrics_202512  -- December 2025
```

---

## 🔄 Sync Architecture

### Sync Flow

1. **Schedule:** Laravel Scheduler triggers sync jobs every 15 minutes
2. **Token Check:** Verify token validity, refresh if needed
3. **API Call:** Fetch campaign data from Google Ads API
4. **Entity Mapping:** Map Google IDs to CMIS IDs via `platform_entity_mappings`
5. **Data Transform:** Convert Google format to CMIS format
6. **Store Metrics:** Insert into `unified_metrics` table
7. **Log Sync:** Record sync status in `platform_sync_logs`

### Implementation Example

```php
class SyncGoogleAdsCampaignsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public PlatformConnection $connection
    ) {}

    public function handle(): void
    {
        $syncLog = DB::table('cmis.platform_sync_logs')->insertGetId([
            'sync_id' => DB::raw('gen_random_uuid()'),
            'org_id' => $this->connection->org_id,
            'connection_id' => $this->connection->connection_id,
            'sync_type' => 'incremental',
            'entity_type' => 'campaigns',
            'direction' => 'import',
            'status' => 'running',
            'started_at' => now(),
        ], 'sync_id');

        try {
            // Initialize Google Ads client
            $connector = new GoogleConnector($this->connection);

            // Fetch campaigns
            $campaigns = $connector->getCampaigns();

            $processed = 0;
            $created = 0;
            $updated = 0;

            foreach ($campaigns as $campaign) {
                // Store in unified_metrics
                $result = $this->storeCampaignMetrics($campaign);

                if ($result['created']) {
                    $created++;
                } else {
                    $updated++;
                }
                $processed++;
            }

            // Update sync log
            DB::table('cmis.platform_sync_logs')
                ->where('sync_id', $syncLog)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'duration_ms' => now()->diffInMilliseconds($this->connection->last_sync_at),
                    'entities_processed' => $processed,
                    'entities_created' => $created,
                    'entities_updated' => $updated,
                ]);

            $this->connection->update(['last_sync_at' => now()]);

        } catch (\Exception $e) {
            DB::table('cmis.platform_sync_logs')
                ->where('sync_id', $syncLog)
                ->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);

            $this->connection->update([
                'last_error_at' => now(),
                'last_error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

---

## 🎨 Smart Bidding Strategies

### Available Strategies

| Strategy | Goal | Best For |
|----------|------|----------|
| **Maximize Clicks** | Get most clicks within budget | Traffic generation |
| **Maximize Conversions** | Get most conversions | Lead generation |
| **Maximize Conversion Value** | Get most conversion value | E-commerce revenue |
| **Target CPA** | Achieve target cost per acquisition | Predictable CPA |
| **Target ROAS** | Achieve target return on ad spend | E-commerce profitability |
| **Enhanced CPC** | Manual bidding with automated adjustments | Manual control + automation |
| **Manual CPC** | Full manual control | Testing, experimentation |

### Implementation

```php
class SmartBiddingService
{
    public function setBiddingStrategy(
        string $campaignId,
        string $strategy,
        array $options = []
    ): void {
        $config = match($strategy) {
            'MAXIMIZE_CLICKS' => [
                'type' => 'MAXIMIZE_CLICKS',
                'max_cpc_bid_limit' => $options['max_cpc'] ?? null,
            ],
            'MAXIMIZE_CONVERSIONS' => [
                'type' => 'MAXIMIZE_CONVERSIONS',
                'target_cpa' => $options['target_cpa'] ?? null,
            ],
            'TARGET_CPA' => [
                'type' => 'TARGET_CPA',
                'target_cpa' => $options['target_cpa'],
            ],
            'TARGET_ROAS' => [
                'type' => 'TARGET_ROAS',
                'target_roas' => $options['target_roas'],
            ],
            default => throw new InvalidBiddingStrategyException($strategy),
        };

        // Apply via Google Ads API
        $this->connector->updateCampaignBidding($campaignId, $config);
    }
}
```

---

## 📈 Quality Score Optimization

### Quality Score Components

1. **Expected CTR (40%)** - Historical click-through rate
2. **Ad Relevance (40%)** - How well ad matches keyword
3. **Landing Page Experience (20%)** - Page relevance and UX

### Optimization Service

```php
class QualityScoreOptimizer
{
    public function analyzeKeyword(string $keywordId): array
    {
        $keyword = $this->connector->getKeyword($keywordId);

        $score = $keyword['quality_score'];
        $issues = [];
        $recommendations = [];

        // Analyze expected CTR
        if ($keyword['expected_ctr'] === 'BELOW_AVERAGE') {
            $issues[] = 'Low expected CTR';
            $recommendations[] = [
                'action' => 'IMPROVE_AD_COPY',
                'description' => 'Add keyword to ad headline for better relevance',
                'priority' => 'HIGH',
            ];
        }

        // Analyze ad relevance
        if ($keyword['ad_relevance'] === 'BELOW_AVERAGE') {
            $issues[] = 'Low ad relevance';
            $recommendations[] = [
                'action' => 'TIGHTEN_KEYWORD_MATCH',
                'description' => 'Use exact or phrase match instead of broad match',
                'priority' => 'HIGH',
            ];
        }

        // Analyze landing page
        if ($keyword['landing_page_experience'] === 'BELOW_AVERAGE') {
            $issues[] = 'Poor landing page experience';
            $recommendations[] = [
                'action' => 'OPTIMIZE_LANDING_PAGE',
                'description' => 'Include keyword in page title and improve load speed',
                'priority' => 'MEDIUM',
            ];
        }

        return [
            'keyword' => $keyword['text'],
            'quality_score' => $score,
            'issues' => $issues,
            'recommendations' => $recommendations,
        ];
    }
}
```

---

## 🛒 Google Shopping Integration

### Prerequisites

1. **Google Merchant Center Account**
2. **Product Feed** (XML or CSV)
3. **Verified and claimed website**
4. **Shipping and tax settings configured**

### Feed Management

```php
class GoogleShoppingFeedManager
{
    public function syncProductFeed(string $orgId): void
    {
        // Get products from CMIS
        $products = Product::where('org_id', $orgId)
            ->where('is_active', true)
            ->get();

        // Transform to Google format
        $feedItems = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->name,
                'description' => $product->description,
                'link' => $product->url,
                'image_link' => $product->image_url,
                'availability' => $product->in_stock ? 'in_stock' : 'out_of_stock',
                'price' => number_format($product->price, 2) . ' USD',
                'brand' => $product->brand,
                'gtin' => $product->gtin,
                'condition' => 'new',
            ];
        });

        // Upload to Merchant Center via Content API
        $this->uploadToMerchantCenter($orgId, $feedItems);
    }
}
```

---

## 🎯 Conversion Tracking

### Google Tag Manager (GTM) Integration

**Setup Steps:**
1. Create GTM container
2. Add Google Ads conversion tracking tag
3. Set up conversion actions in Google Ads
4. Configure triggers for conversion events
5. Test with Google Tag Assistant

### Conversion Upload via API

```php
public function trackConversion(array $conversionData): void
{
    $this->connector->uploadConversion([
        'gclid' => $conversionData['gclid'], // Google Click ID
        'conversion_action' => 'customers/123456789/conversionActions/987654321',
        'conversion_date_time' => now()->toDateTimeString(),
        'conversion_value' => $conversionData['value'],
        'currency_code' => 'USD',
    ]);
}
```

---

## 🚨 Error Handling

### Common API Errors

| Error Code | Meaning | Solution |
|------------|---------|----------|
| 401 | Authentication failed | Refresh access token |
| 403 | Permission denied | Check OAuth scopes |
| 400 | Bad request | Validate request parameters |
| 404 | Resource not found | Verify customer ID and resource ID |
| 429 | Rate limit exceeded | Implement exponential backoff |
| 500 | Server error | Retry with backoff |

### Error Handler Implementation

```php
class GoogleAdsErrorHandler
{
    protected array $retryableErrors = [400, 429, 500, 503];

    public function handleApiError(\Exception $e, PlatformConnection $connection): void
    {
        $code = $this->extractErrorCode($e);

        match($code) {
            401 => $this->handleAuthError($connection),
            429 => $this->handleRateLimit($connection),
            default => $this->logError($e, $connection),
        };
    }

    protected function handleAuthError(PlatformConnection $connection): void
    {
        // Attempt token refresh
        if ($this->refreshAccessToken($connection)) {
            // Retry the operation
            return;
        }

        // Mark connection as expired
        $connection->update([
            'status' => 'expired',
            'last_error_at' => now(),
            'last_error_message' => 'Authentication failed - token refresh unsuccessful',
        ]);
    }
}
```

---

## 📚 Resources

### Official Documentation
- [Google Ads API](https://developers.google.com/google-ads/api/docs/start)
- [Google Ads PHP Client Library](https://github.com/googleads/google-ads-php)
- [Google Merchant Center API](https://developers.google.com/shopping-content/guides/quickstart)
- [Google Tag Manager](https://developers.google.com/tag-manager)

### CMIS Documentation
- [Platform Setup Workflow](../../../.claude/knowledge/PLATFORM_SETUP_WORKFLOW.md)
- [Meta Cognitive Framework](../../../.claude/knowledge/META_COGNITIVE_FRAMEWORK.md)
- [Discovery Protocols](../../../.claude/knowledge/DISCOVERY_PROTOCOLS.md)
- [CMIS Project Knowledge](../../../.claude/knowledge/CMIS_PROJECT_KNOWLEDGE.md)

---

## 🎯 Next Steps

### Implementation Roadmap

**Phase 1: Foundation (Week 1-2)**
- [ ] Create `PlatformConnection` model
- [ ] Implement OAuth 2.0 flow
- [ ] Set up Google Ads API client
- [ ] Create connector service
- [ ] Add token refresh logic

**Phase 2: Campaign Management (Week 3-4)**
- [ ] Implement Search campaigns
- [ ] Implement Display campaigns
- [ ] Implement Video campaigns
- [ ] Create unified metrics storage
- [ ] Build sync jobs

**Phase 3: Advanced Features (Week 5-6)**
- [ ] Implement Shopping campaigns
- [ ] Implement Performance Max campaigns
- [ ] Add Smart Bidding support
- [ ] Create Quality Score optimizer
- [ ] Build conversion tracking

**Phase 4: Testing & Optimization (Week 7-8)**
- [ ] Write unit tests
- [ ] Write integration tests
- [ ] Performance optimization
- [ ] Documentation completion
- [ ] Production deployment

---

**Last Updated:** 2025-11-23
**Maintained By:** CMIS Development Team
**Agent:** `cmis-google-ads-specialist`
