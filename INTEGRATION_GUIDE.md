# دليل التكامل: Connectors vs AdPlatforms

## نظرة عامة

يحتوي النظام على طبقتين للتعامل مع المنصات الخارجية:

1. **Connectors** - للاتصال والمزامنة العامة
2. **AdPlatforms** - لإدارة الحملات الإعلانية المفصّلة

كلاهما يعمل معاً بشكل متكامل ومتوافق.

---

## 1. Connectors (الموجود - يستمر العمل)

### الموقع
```
app/Services/Connectors/
├── Contracts/ConnectorInterface.php
├── AbstractConnector.php
├── ConnectorFactory.php
└── Providers/
    ├── MetaConnector.php
    ├── GoogleConnector.php
    ├── TikTokConnector.php
    └── ... إلخ
```

### الاستخدامات
- ✅ OAuth Authentication & Token Management
- ✅ Social Media Posts (Facebook, Instagram, Twitter, etc.)
- ✅ Comments & Engagement
- ✅ Direct Messages (DMs)
- ✅ General Platform Sync
- ✅ Content Publishing & Scheduling

### مثال على الاستخدام
```php
use App\Services\Connectors\ConnectorFactory;

// إنشاء connector
$connector = ConnectorFactory::make('meta');

// الاتصال بالمنصة
$integration = $connector->connect($authCode);

// مزامنة المنشورات
$posts = $connector->syncPosts($integration, [
    'since' => '2025-01-01',
    'limit' => 100
]);

// نشر منشور
$postId = $connector->publishPost($integration, $contentItem);

// الرد على تعليق
$connector->replyToComment($integration, $commentId, 'شكراً لك!');

// مزامنة الحملات (عامة)
$campaigns = $connector->syncCampaigns($integration);
```

---

## 2. AdPlatforms (الجديد - مخصص للإعلانات)

### الموقع
```
app/Services/AdPlatforms/
├── Contracts/AdPlatformInterface.php
├── AbstractAdPlatform.php
├── AdPlatformFactory.php
└── Meta/
    └── MetaAdsPlatform.php
└── Google/
    └── GoogleAdsPlatform.php
```

### الاستخدامات
- ✅ Ad Campaign Management (إنشاء، تعديل، حذف)
- ✅ Ad Sets / Ad Groups
- ✅ Ad Creatives
- ✅ Detailed Performance Metrics
- ✅ Budget & Bidding Management
- ✅ Targeting & Audiences
- ✅ A/B Testing

### مثال على الاستخدام
```php
use App\Services\AdPlatforms\AdPlatformFactory;

// إنشاء ad platform service
$adPlatform = AdPlatformFactory::make($integration);

// إنشاء حملة إعلانية
$result = $adPlatform->createCampaign([
    'name' => 'حملة الربيع 2025',
    'objective' => 'OUTCOME_SALES',
    'status' => 'ACTIVE',
    'daily_budget' => 500, // ريال
    'start_date' => '2025-03-01',
    'end_date' => '2025-03-31',
]);

// إنشاء ad set
$adSet = $adPlatform->createAdSet($result['external_id'], [
    'name' => 'Ad Set 1',
    'daily_budget' => 200,
    'optimization_goal' => 'CONVERSIONS',
    'targeting' => [
        'geo_locations' => ['countries' => ['SA', 'AE']],
        'age_min' => 25,
        'age_max' => 45,
    ],
]);

// الحصول على metrics مفصّلة
$metrics = $adPlatform->getCampaignMetrics(
    $campaignExternalId,
    '2025-03-01',
    '2025-03-15'
);

// تحديث حالة الحملة
$adPlatform->updateCampaignStatus($campaignExternalId, 'PAUSED');
```

---

## 3. متى تستخدم أيهما؟

### استخدم Connectors عندما:
- ✅ تحتاج OAuth authentication
- ✅ تريد نشر/جدولة منشورات اجتماعية
- ✅ تحتاج إدارة التعليقات والرسائل
- ✅ تريد مزامنة بيانات عامة
- ✅ تعمل مع المحتوى الاجتماعي (posts, stories, etc.)

### استخدم AdPlatforms عندما:
- ✅ تحتاج إنشاء حملات إعلانية مدفوعة
- ✅ تريد إدارة ميزانيات وعروض الأسعار
- ✅ تحتاج targeting مُفصّل
- ✅ تريد metrics وإحصائيات تفصيلية
- ✅ تعمل مع Ad Sets, Ad Groups, Creatives

---

## 4. التكامل بينهما

### Workflow مثالي:

```php
// الخطوة 1: استخدم Connector للاتصال
$connector = ConnectorFactory::make('meta');
$integration = $connector->connect($authCode, [
    'account_id' => 'act_123456789',
]);

// الخطوة 2: استخدم AdPlatform لإنشاء الحملة
$adPlatform = AdPlatformFactory::make($integration);
$campaign = $adPlatform->createCampaign([...]);

// الخطوة 3: احفظ في قاعدة البيانات
AdCampaign::create([
    'org_id' => auth()->user()->org_id,
    'integration_id' => $integration->integration_id,
    'campaign_external_id' => $campaign['external_id'],
    'name' => $data['name'],
    'status' => 'active',
    // ... إلخ
]);

// الخطوة 4: استخدم Connector للمزامنة الدورية
$connector->syncCampaigns($integration); // مزامنة عامة

// الخطوة 5: استخدم AdPlatform للحصول على Metrics مفصّلة
$metrics = $adPlatform->getCampaignMetrics(...);
```

---

## 5. الميزات الجديدة في AdPlatforms

### 1. Rate Limiting تلقائي
```php
// يتم تطبيقه تلقائياً - 200 request/minute
$adPlatform->createCampaign([...]); // ✅ محمي
```

### 2. Retry Logic مع Exponential Backoff
```php
// يعيد المحاولة تلقائياً عند الفشل
// Retry: 1s, 2s, 4s
```

### 3. Request Caching
```php
// يتم caching النتائج تلقائياً
$campaign = $adPlatform->getCampaign($id); // Cache 5 min
```

### 4. Validation Layer
```php
$result = $adPlatform->validateCampaignData($data);
if (!$result['valid']) {
    // ['errors' => [...]]
}
```

### 5. Platform-Specific Mapping
```php
// تحويل تلقائي للقيم
'sales' → 'OUTCOME_SALES' (Meta)
'sales' → 'MAXIMIZE_CONVERSIONS' (Google)
```

---

## 6. المنصات المدعومة

### Connectors
- ✅ Meta (Facebook & Instagram)
- ✅ Google (Analytics, Ads, Business)
- ✅ TikTok
- ✅ Twitter/X
- ✅ LinkedIn
- ✅ Snapchat
- ✅ YouTube
- ✅ WhatsApp
- ✅ WooCommerce
- ✅ WordPress
- ✅ Microsoft Clarity
- ✅ Google Merchant Center

### AdPlatforms (جديد)
- ✅ Meta Ads (كامل التطبيق)
- 🔜 Google Ads (قريباً)
- 🔜 TikTok Ads (قريباً)
- 🔜 LinkedIn Ads (قريباً)
- 🔜 Twitter Ads (قريباً)
- 🔜 Snapchat Ads (قريباً)

---

## 7. Best Practices

### ✅ استخدم Connectors للـ:
```php
// Authentication
$connector->connect($authCode);
$connector->refreshToken($integration);

// Social Content
$connector->publishPost($integration, $content);
$connector->syncPosts($integration);

// Engagement
$connector->replyToComment($integration, $commentId, $text);
```

### ✅ استخدم AdPlatforms للـ:
```php
// Ad Campaigns
$adPlatform->createCampaign($data);
$adPlatform->updateCampaign($id, $updates);

// Performance
$adPlatform->getCampaignMetrics($id, $start, $end);

// Targeting
$adPlatform->createAdSet($campaignId, $targeting);
```

### ⚠️ لا تخلط بينهما:
```php
// ❌ خطأ
$connector->createAdCampaign(...);  // استخدم AdPlatform بدلاً

// ❌ خطأ
$adPlatform->publishPost(...);  // استخدم Connector بدلاً
```

---

## 8. الترقية من Connectors إلى AdPlatforms

إذا كان لديك كود قديم يستخدم Connectors للإعلانات:

### قبل:
```php
$connector = ConnectorFactory::make('meta');
$result = $connector->createAdCampaign($integration, $data);
```

### بعد:
```php
$adPlatform = AdPlatformFactory::make($integration);
$result = $adPlatform->createCampaign($data);
```

**ملاحظة:** Connectors تستمر في العمل للاستخدامات الأخرى!

---

## 9. الدعم والمساعدة

### للأسئلة حول Connectors:
- ملف: `app/Services/Connectors/`
- Interface: `ConnectorInterface.php`

### للأسئلة حول AdPlatforms:
- ملف: `app/Services/AdPlatforms/`
- Interface: `AdPlatformInterface.php`
- تقرير الفحص: `ad-campaign-audit-report.md`

---

## 10. ملخص سريع

| الميزة | Connectors | AdPlatforms |
|-------|-----------|-------------|
| OAuth | ✅ | ❌ (يستخدم Integration) |
| Social Posts | ✅ | ❌ |
| Comments/DMs | ✅ | ❌ |
| Ad Campaigns | Basic | ✅ Detailed |
| Ad Sets/Groups | ❌ | ✅ |
| Targeting | ❌ | ✅ |
| Metrics | Basic | ✅ Detailed |
| Budget Mgmt | ❌ | ✅ |
| Rate Limiting | ❌ | ✅ Auto |
| Retry Logic | ❌ | ✅ Auto |
| Caching | ❌ | ✅ Auto |

---

**الخلاصة:** كلا النظامين يكملان بعضهما ويعملان معاً بشكل مثالي! 🎉
