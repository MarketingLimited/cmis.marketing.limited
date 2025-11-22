# دليل الوكلاء - Connectors Layer (app/Services/Connectors/)

## 1. Purpose (الغرض)

طبقة Connectors توفر **واجهة موحدة** للتكامل مع منصات خارجية متعددة:
- **14 Platform Connectors**: Meta, Google, TikTok, LinkedIn, Twitter, Snapchat, YouTube, WhatsApp, Google Business, Google Merchant, WooCommerce, WordPress, Clarity
- **Unified Interface**: `ConnectorInterface` مع 20+ standard methods
- **Factory Pattern**: `ConnectorFactory` لإنشاء connectors ديناميكياً
- **Rate Limiting**: حماية من تجاوز حدود API
- **Auto Token Refresh**: تجديد تلقائي للـ access tokens
- **Error Handling**: معالجة موحدة للأخطاء عبر جميع المنصات

## 2. Owned Scope (النطاق المملوك)

### Connector Organization

```
app/Services/Connectors/
├── Contracts/
│   └── ConnectorInterface.php          # Contract (20+ methods)
│
├── AbstractConnector.php                # Base implementation
│   ├── Rate limiting
│   ├── Token refresh logic
│   ├── Error handling
│   ├── API request wrapper
│   └── Logging
│
├── ConnectorFactory.php                 # Factory pattern
│
└── Providers/                           # 14 Platform implementations
    ├── MetaConnector.php               # Facebook/Instagram (22KB)
    ├── GoogleConnector.php             # Google Ads (12KB)
    ├── TikTokConnector.php             # TikTok Marketing (14KB)
    ├── LinkedInConnector.php           # LinkedIn Ads (9KB)
    ├── TwitterConnector.php            # Twitter/X (11KB)
    ├── SnapchatConnector.php           # Snapchat Ads (7KB)
    ├── YouTubeConnector.php            # YouTube API (7KB)
    ├── WhatsAppConnector.php           # WhatsApp Business (4KB)
    ├── GoogleBusinessConnector.php     # Google My Business (3KB)
    ├── GoogleMerchantConnector.php     # Google Merchant Center (3KB)
    ├── WooCommerceConnector.php        # WooCommerce (5KB)
    ├── WordPressConnector.php          # WordPress (5KB)
    └── ClarityConnector.php            # Microsoft Clarity (3KB)
```

## 3. Key Files & Entry Points

### Core Interface
- `Contracts/ConnectorInterface.php`: 20+ methods covering:
  - **Authentication**: `connect()`, `disconnect()`, `refreshToken()`
  - **Sync Operations**: `syncCampaigns()`, `syncPosts()`, `syncComments()`, `syncMessages()`
  - **Publishing**: `publishPost()`, `schedulePost()`
  - **Messaging**: `sendMessage()`, `replyToComment()`, `hideComment()`, `deleteComment()`, `likeComment()`
  - **Ad Management**: `createAdCampaign()`, `updateAdCampaign()`, `getAdCampaignMetrics()`
  - **Metrics**: `getAccountMetrics()`

### Base Implementation
- `AbstractConnector.php`: Provides:
  - `makeRequest()`: Authenticated HTTP requests
  - `checkRateLimit()`: Rate limiting per platform
  - `shouldRefreshToken()`: Auto-detect token expiration
  - `handleApiError()`: Unified error handling
  - `logApiCall()`: Database logging
  - `storeData()`: Upsert helper
  - `logSync()`: Sync operation logging

### Factory
- `ConnectorFactory.php`: Creates connectors dynamically

### Platform Connectors (14 Total)
- **Ad Platforms**: Meta, Google, TikTok, LinkedIn, Twitter, Snapchat
- **Content Platforms**: YouTube, WordPress
- **Messaging**: WhatsApp
- **Business Tools**: Google Business, Google Merchant
- **eCommerce**: WooCommerce
- **Analytics**: Clarity

## 4. Common Tasks

### Using Factory Pattern

```php
use App\Services\Connectors\ConnectorFactory;

$connector = ConnectorFactory::make('meta');
$campaigns = $connector->syncCampaigns($integration);

$connector = ConnectorFactory::make('google');
$posts = $connector->syncPosts($integration, ['days' => 7]);
```

### Sync Data from Platform

```php
$integration = Integration::where('platform', 'meta')->first();
$connector = ConnectorFactory::make('meta');

// Sync campaigns
$campaigns = $connector->syncCampaigns($integration, [
    'date_range' => ['start' => '2025-01-01', 'end' => '2025-01-31'],
]);
```

## 5. Notes

- **14 Platform Connectors** total
- **Unified Interface** simplifies integration
- **Rate Limiting** automatic per platform
- **Token Refresh** automatic before expiration
