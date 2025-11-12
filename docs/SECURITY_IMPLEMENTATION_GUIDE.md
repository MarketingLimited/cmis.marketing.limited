# 🔒 CMIS Security Implementation Guide

**الهدف:** دليل عملي لتطبيق نظام الصلاحيات على جميع Routes والـ Controllers

**الحالة الحالية:** نظام الأمان موجود ومكتمل، لكنه **غير مطبق على معظم Routes**

---

## 📋 الخطوات المطلوبة

### ✅ الخطوة 1: تطبيق Middleware على Routes (أولوية قصوى)

#### 🔴 المشكلة:

معظم Routes محمية فقط بـ `auth:sanctum` دون فحص الصلاحيات:

```php
// ❌ الوضع الحالي (غير آمن):
Route::middleware(['auth:sanctum', 'validate.org.access', 'set.db.context'])
    ->prefix('orgs/{org_id}')
    ->group(function () {
        Route::apiResource('campaigns', CampaignController::class);
        // أي مستخدم مسجل يمكنه الوصول!
    });
```

#### ✅ الحل:

إضافة `permission` middleware مع كود الصلاحية المناسب:

```php
// ✅ الوضع المطلوب (آمن):
Route::middleware(['auth:sanctum', 'validate.org.access', 'set.db.context'])
    ->prefix('orgs/{org_id}')
    ->group(function () {

        // Campaigns
        Route::get('campaigns', [CampaignController::class, 'index'])
            ->middleware('permission:cmis.campaigns.view');

        Route::post('campaigns', [CampaignController::class, 'store'])
            ->middleware('permission:cmis.campaigns.create');

        Route::get('campaigns/{campaign_id}', [CampaignController::class, 'show'])
            ->middleware('permission:cmis.campaigns.view');

        Route::put('campaigns/{campaign_id}', [CampaignController::class, 'update'])
            ->middleware('permission:cmis.campaigns.update');

        Route::delete('campaigns/{campaign_id}', [CampaignController::class, 'destroy'])
            ->middleware('permission:cmis.campaigns.delete');
    });
```

---

### 📖 أمثلة عملية لكل Module

#### 1️⃣ Campaigns

```php
Route::prefix('campaigns')->name('campaigns.')->group(function () {
    Route::get('/', [CampaignController::class, 'index'])
        ->middleware('permission:cmis.campaigns.view')
        ->name('index');

    Route::post('/', [CampaignController::class, 'store'])
        ->middleware('permission:cmis.campaigns.create')
        ->name('store');

    Route::get('/{campaign_id}', [CampaignController::class, 'show'])
        ->middleware('permission:cmis.campaigns.view')
        ->name('show');

    Route::put('/{campaign_id}', [CampaignController::class, 'update'])
        ->middleware('permission:cmis.campaigns.update')
        ->name('update');

    Route::delete('/{campaign_id}', [CampaignController::class, 'destroy'])
        ->middleware('permission:cmis.campaigns.delete')
        ->name('destroy');

    // Custom actions
    Route::post('/{campaign_id}/publish', [CampaignController::class, 'publish'])
        ->middleware('permission:cmis.campaigns.publish')
        ->name('publish');
});
```

#### 2️⃣ Users (إدارة المستخدمين)

```php
Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])
        ->middleware('permission:cmis.users.view')
        ->name('index');

    Route::post('/invite', [UserController::class, 'inviteUser'])
        ->middleware('permission:cmis.users.invite')
        ->name('invite');

    Route::get('/{user_id}', [UserController::class, 'show'])
        ->middleware('permission:cmis.users.view')
        ->name('show');

    Route::put('/{user_id}/role', [UserController::class, 'updateRole'])
        ->middleware('permission:cmis.users.manage_roles')
        ->name('updateRole');

    Route::delete('/{user_id}', [UserController::class, 'remove'])
        ->middleware('permission:cmis.users.remove')
        ->name('remove');
});
```

#### 3️⃣ Creative Assets

```php
Route::prefix('creative')->name('creative.')->group(function () {
    Route::prefix('assets')->name('assets.')->group(function () {
        Route::get('/', [CreativeAssetController::class, 'index'])
            ->middleware('permission:cmis.creative.view')
            ->name('index');

        Route::post('/', [CreativeAssetController::class, 'store'])
            ->middleware('permission:cmis.creative.create')
            ->name('store');

        Route::get('/{asset_id}', [CreativeAssetController::class, 'show'])
            ->middleware('permission:cmis.creative.view')
            ->name('show');

        Route::put('/{asset_id}', [CreativeAssetController::class, 'update'])
            ->middleware('permission:cmis.creative.update')
            ->name('update');

        Route::delete('/{asset_id}', [CreativeAssetController::class, 'destroy'])
            ->middleware('permission:cmis.creative.delete')
            ->name('destroy');
    });
});
```

#### 4️⃣ AI & Semantic Search

```php
Route::prefix('cmis')->name('cmis.')->group(function () {
    Route::post('/search', [CMISEmbeddingController::class, 'search'])
        ->middleware('permission:cmis.ai.search')
        ->name('search');

    Route::post('/knowledge/{id}/process', [CMISEmbeddingController::class, 'processKnowledge'])
        ->middleware('permission:cmis.ai.process_knowledge')
        ->name('knowledge.process');

    Route::get('/knowledge/{id}/similar', [CMISEmbeddingController::class, 'findSimilar'])
        ->middleware('permission:cmis.ai.search')
        ->name('knowledge.similar');
});

Route::post('/semantic-search', [SemanticSearchController::class, 'search'])
    ->middleware('permission:cmis.ai.semantic_search')
    ->name('semantic.search');
```

#### 5️⃣ Analytics

```php
Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/dashboard', [AnalyticsController::class, 'dashboard'])
        ->middleware('permission:cmis.analytics.view')
        ->name('dashboard');

    Route::get('/campaigns/{campaign_id}', [AnalyticsController::class, 'campaignMetrics'])
        ->middleware('permission:cmis.analytics.view|cmis.campaigns.view_analytics')
        ->name('campaign.metrics');

    Route::post('/export', [AnalyticsController::class, 'export'])
        ->middleware('permission:cmis.analytics.export')
        ->name('export');
});
```

---

### ✅ الخطوة 2: استخدام Policies في Controllers

بالإضافة إلى middleware، يجب استخدام Policies داخل Controllers للتحقق على مستوى الموارد:

#### مثال: CampaignController

```php
<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * Display the specified campaign.
     */
    public function show(string $campaign_id)
    {
        $campaign = Campaign::findOrFail($campaign_id);

        // ✅ استخدام Policy للتحقق من الوصول لهذه الحملة بالذات
        $this->authorize('view', $campaign);

        return response()->json($campaign);
    }

    /**
     * Update the specified campaign.
     */
    public function update(Request $request, string $campaign_id)
    {
        $campaign = Campaign::findOrFail($campaign_id);

        // ✅ التحقق من أن المستخدم يمكنه تعديل هذه الحملة
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|in:draft,active,paused,completed',
            // ...
        ]);

        $campaign->update($validated);

        return response()->json($campaign);
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy(string $campaign_id)
    {
        $campaign = Campaign::findOrFail($campaign_id);

        // ✅ التحقق من أن المستخدم يمكنه حذف هذه الحملة
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return response()->json(['message' => 'Campaign deleted successfully']);
    }

    /**
     * Publish campaign.
     */
    public function publish(string $campaign_id)
    {
        $campaign = Campaign::findOrFail($campaign_id);

        // ✅ استخدام method مخصص في Policy
        $this->authorize('publish', $campaign);

        $campaign->update(['status' => 'active', 'published_at' => now()]);

        return response()->json($campaign);
    }
}
```

---

### 📊 جدول الصلاحيات الموصى به

| Module | Action | Permission Code | ملاحظات |
|--------|--------|-----------------|----------|
| **Campaigns** | View List | `cmis.campaigns.view` | عرض قائمة الحملات |
| | View Details | `cmis.campaigns.view` | عرض تفاصيل حملة |
| | Create | `cmis.campaigns.create` | إنشاء حملة جديدة |
| | Update | `cmis.campaigns.update` | تعديل حملة |
| | Delete | `cmis.campaigns.delete` | حذف حملة |
| | Publish | `cmis.campaigns.publish` | نشر حملة |
| | View Analytics | `cmis.campaigns.view_analytics` | عرض إحصائيات الحملة |
| **Users** | View | `cmis.users.view` | عرض المستخدمين |
| | Invite | `cmis.users.invite` | دعوة مستخدمين جدد |
| | Manage Roles | `cmis.users.manage_roles` | تعديل أدوار المستخدمين |
| | Remove | `cmis.users.remove` | إزالة مستخدم |
| **Creative** | View | `cmis.creative.view` | عرض الأصول الإبداعية |
| | Create | `cmis.creative.create` | إنشاء أصل جديد |
| | Update | `cmis.creative.update` | تعديل أصل |
| | Delete | `cmis.creative.delete` | حذف أصل |
| **AI/Knowledge** | Search | `cmis.ai.search` | استخدام البحث |
| | Semantic Search | `cmis.ai.semantic_search` | البحث الدلالي |
| | Process Knowledge | `cmis.ai.process_knowledge` | معالجة المعرفة |
| **Analytics** | View | `cmis.analytics.view` | عرض التحليلات |
| | Export | `cmis.analytics.export` | تصدير البيانات |
| **Channels** | View | `cmis.channels.view` | عرض القنوات |
| | Create | `cmis.channels.create` | إضافة قناة |
| | Update | `cmis.channels.update` | تعديل قناة |
| | Delete | `cmis.channels.delete` | حذف قناة |

---

### 🔍 التحقق من تطبيق الصلاحيات

#### سكربت تدقيق Routes:

```php
// في routes/api.php
// أضف في نهاية الملف:

if (app()->environment('local')) {
    Route::get('/_debug/routes', function () {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            $middleware = $route->middleware();
            $hasAuth = in_array('auth:sanctum', $middleware);
            $hasPermission = collect($middleware)->contains(fn($m) => str_starts_with($m, 'permission:'));

            return [
                'uri' => $route->uri(),
                'methods' => implode('|', $route->methods()),
                'name' => $route->getName(),
                'auth' => $hasAuth ? '✅' : '❌',
                'permission' => $hasPermission ? '✅' : '⚠️',
                'middleware' => $middleware,
            ];
        });

        return response()->json($routes->values());
    });
}
```

---

### 🎯 قائمة التحقق (Checklist)

#### Routes Security:

- [ ] جميع Routes تحت `/orgs/{org_id}` محمية بـ `auth:sanctum`
- [ ] جميع Routes الحساسة محمية بـ `permission:...`
- [ ] Routes الـ CRUD تستخدم الصلاحيات المناسبة (view/create/update/delete)
- [ ] Actions المخصصة لها صلاحيات خاصة (publish, approve, etc.)

#### Controller Security:

- [ ] جميع Controllers تستخدم `$this->authorize()` للموارد الفردية
- [ ] التحقق من `org_id` في Policies
- [ ] رسائل خطأ واضحة عند رفض الصلاحية

#### Testing:

- [ ] اختبارات للتحقق من رفض الوصول غير المصرح
- [ ] اختبارات للتحقق من السماح للمستخدمين المصرح لهم
- [ ] اختبارات للـ Policy methods

---

### 🚀 الخطوات التالية

1. **مراجعة `routes/api.php` كاملاً**
   - تحديد جميع Routes التي تحتاج صلاحيات
   - إضافة `permission` middleware

2. **تحديث جميع Controllers**
   - إضافة `$this->authorize()` calls
   - التأكد من تطبيق Policies

3. **إنشاء/تحديث الصلاحيات في قاعدة البيانات**
   - التأكد من وجود جميع permission codes في جدول `permissions`

4. **اختبار النظام**
   - محاولة الوصول بدون صلاحيات
   - التحقق من Logs
   - اختبار جميع السيناريوهات

5. **توثيق الصلاحيات**
   - جدول كامل بجميع الصلاحيات
   - تحديد الأدوار (Roles) والصلاحيات المرتبطة بها

---

**ملاحظة مهمة:**

نظام الصلاحيات **موجود ومكتمل**، نحتاج فقط إلى **تطبيقه** على Routes والـ Controllers.
هذا يجب أن يكون **الأولوية القصوى** قبل أي شيء آخر!

---

*تم إعداده بواسطة: Claude Code Assistant*
*التاريخ: 2025-11-12*
