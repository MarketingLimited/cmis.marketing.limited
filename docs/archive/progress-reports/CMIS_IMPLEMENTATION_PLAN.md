# خطة التنفيذ الكاملة لنظام CMIS
## Complete Implementation Plan - CMIS Marketing Intelligence System

**التاريخ:** 2025-11-16
**الإصدار:** v1.0
**المدة المقدرة:** 8-10 أسابيع
**الفريق المطلوب:** 3-4 مطورين

---

## جدول المحتويات

1. [نظرة عامة](#نظرة-عامة)
2. [المرحلة 1: إصلاحات أمنية حرجة](#المرحلة-1-إصلاحات-أمنية-حرجة)
3. [المرحلة 2: البنية التحتية الأساسية](#المرحلة-2-البنية-التحتية-الأساسية)
4. [المرحلة 3: Content Plans & Calendar](#المرحلة-3-content-plans--calendar)
5. [المرحلة 4: GPT Interface - Foundation](#المرحلة-4-gpt-interface---foundation)
6. [المرحلة 5: GPT Interface - Completion](#المرحلة-5-gpt-interface---completion)
7. [المرحلة 6: Testing & Documentation](#المرحلة-6-testing--documentation)
8. [الملحق: Checklists التفصيلية](#الملحق-checklists-التفصيلية)

---

## نظرة عامة

### الهدف
إكمال نظام CMIS ليكون نظام تشغيل متكامل لوكالات التسويق مع أربع واجهات عمل (Web / API / CLI / GPT).

### الحالة الحالية
- **التقييم:** B+ (82/100)
- **ما يعمل:** Database, Models, API, Web UI, CLI (80-95%)
- **ما يحتاج إكمال:** GPT Interface, Content Plans, Security Fixes

### الأولويات
1. 🔴 **Critical:** Security Fixes (Week 1)
2. 🔴 **Critical:** Reference Data API (Week 1)
3. 🟡 **High:** Content Plans Module (Week 2-3)
4. 🟡 **High:** GPT Interface (Week 4-7)
5. 🟢 **Medium:** Enhancements (Week 8+)

---

## المرحلة 1: إصلاحات أمنية حرجة
**المدة:** أسبوع واحد (Week 1)
**الأولوية:** 🔴 CRITICAL
**المسؤول:** Senior Developer

### 1.1 إصلاح Login Security Bug

**المشكلة:**
```php
// app/Http/Controllers/Auth/AuthController.php
// Line 127-133
$user = User::where('email', $request->email)->first();
// ❌ لا يتحقق من password!
```

**الحل:**
```php
// في method login()
$user = User::where('email', $request->email)->first();

if (!$user || !Hash::check($request->password, $user->password)) {
    throw ValidationException::withMessages([
        'email' => ['بيانات الاعتماد غير صحيحة.'],
    ]);
}

// التحقق من حالة المستخدم
if ($user->status !== 'active') {
    throw ValidationException::withMessages([
        'email' => ['الحساب غير نشط.'],
    ]);
}

// إنشاء token
$token = $user->createToken('api-token')->plainTextToken;
```

**Checklist:**
- [ ] إضافة password check في AuthController::login()
- [ ] إضافة test للتحقق من الثغرة
- [ ] مراجعة جميع auth endpoints
- [ ] Update documentation

**الوقت المقدر:** 2 ساعات

---

### 1.2 تفعيل Token Expiration

**المشكلة:**
```php
// config/sanctum.php
'expiration' => null,  // ❌ لا انتهاء صلاحية
```

**الحل:**
```php
// في config/sanctum.php
'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 60 * 24 * 7), // 7 أيام

// إضافة middleware للتحقق من expiration
// app/Http/Middleware/CheckTokenExpiration.php
```

**خطوات التنفيذ:**
1. تحديث config/sanctum.php
2. إضافة SANCTUM_TOKEN_EXPIRATION إلى .env
3. إنشاء Middleware للتحقق
4. إضافة endpoint لـ refresh token
5. Testing

**Checklist:**
- [ ] تحديث config/sanctum.php
- [ ] إضافة env variable
- [ ] إنشاء CheckTokenExpiration middleware
- [ ] إضافة POST /api/auth/refresh-token endpoint
- [ ] كتابة tests
- [ ] Update API documentation

**الوقت المقدر:** 4 ساعات

---

### 1.3 تفعيل Row-Level Security (RLS)

**المشكلة:**
```sql
-- Policies موجودة لكن RLS غير مُفعّل على الجداول
```

**الحل:**
```sql
-- database/migrations/2025_01_enable_rls.php
ALTER TABLE cmis.campaigns ENABLE ROW LEVEL SECURITY;
ALTER TABLE cmis.creative_assets ENABLE ROW LEVEL SECURITY;
ALTER TABLE cmis.content_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE cmis.ad_accounts ENABLE ROW LEVEL SECURITY;
ALTER TABLE cmis.ad_campaigns ENABLE ROW LEVEL SECURITY;
ALTER TABLE cmis.integrations ENABLE ROW LEVEL SECURITY;
ALTER TABLE cmis.user_permissions ENABLE ROW LEVEL SECURITY;

-- التحقق من تفعيل Policies
SELECT tablename, policyname, permissive, roles, cmd, qual
FROM pg_policies
WHERE schemaname = 'cmis';
```

**Checklist:**
- [ ] إنشاء migration لتفعيل RLS
- [ ] تفعيل على جميع الجداول الحساسة
- [ ] التحقق من عمل Policies
- [ ] Testing للـ org isolation
- [ ] Documentation

**الوقت المقدر:** 3 ساعات

---

### 1.4 إضافة Rate Limiting على AI Routes

**المشكلة:**
```php
// routes/api.php - AI routes بدون throttle
Route::prefix('ai')->group(function () {
    // ❌ لا يوجد ->middleware('throttle:ai')
});
```

**الحل:**
```php
// في routes/api.php
Route::prefix('ai')
    ->middleware(['auth:sanctum', 'throttle:ai'])
    ->group(function () {
        Route::post('/generate', [...]);
        Route::post('/captions', [...]);
        Route::post('/hashtags', [...]);
        // ... إلخ
    });

// في app/Providers/RouteServiceProvider.php
// التأكد من تكوين rate limiter للـ ai
```

**Checklist:**
- [ ] إضافة throttle:ai على جميع AI routes
- [ ] التحقق من تكوين RateLimiter
- [ ] Testing rate limiting
- [ ] إضافة logging للـ throttled requests

**الوقت المقدر:** 2 ساعات

---

### 1.5 إضافة Reference Data API Endpoints

**المشكلة:**
```
❌ لا توجد endpoints للـ Reference Data
❌ GET /api/reference/markets غير موجود
```

**الحل:**
```php
// app/Http/Controllers/ReferenceDataController.php
class ReferenceDataController extends Controller
{
    public function markets() {
        return response()->json(
            Market::select('market_id', 'market_name', 'language_code', 'currency_code')
                ->orderBy('market_name')
                ->get()
        );
    }

    public function channels() {
        return response()->json(
            Channel::select('channel_id', 'code', 'name', 'constraints')
                ->with('formats')
                ->get()
        );
    }

    // ... باقي methods
}

// في routes/api.php
Route::prefix('reference')->group(function () {
    Route::get('/markets', [ReferenceDataController::class, 'markets']);
    Route::get('/channels', [ReferenceDataController::class, 'channels']);
    Route::get('/channel-formats', [ReferenceDataController::class, 'channelFormats']);
    Route::get('/industries', [ReferenceDataController::class, 'industries']);
    Route::get('/languages', [ReferenceDataController::class, 'languages']);
    Route::get('/currencies', [ReferenceDataController::class, 'currencies']);
    Route::get('/timezones', [ReferenceDataController::class, 'timezones']);
});
```

**Checklist:**
- [ ] إنشاء ReferenceDataController
- [ ] إضافة methods لكل reference type
- [ ] إضافة routes في api.php
- [ ] إضافة caching (15 دقيقة)
- [ ] كتابة tests
- [ ] Update API docs

**الوقت المقدر:** 4 ساعات

---

### ملخص المرحلة 1

| المهمة | الأولوية | الوقت | الحالة |
|-------|----------|-------|--------|
| Login Security Fix | 🔴 | 2h | [ ] |
| Token Expiration | 🔴 | 4h | [ ] |
| Enable RLS | 🔴 | 3h | [ ] |
| AI Rate Limiting | 🔴 | 2h | [ ] |
| Reference Data API | 🔴 | 4h | [ ] |
| **المجموع** | | **15h** | |

---

## المرحلة 2: البنية التحتية الأساسية
**المدة:** أسبوع واحد (Week 2)
**الأولوية:** 🟡 HIGH
**المسؤول:** Backend Developer

### 2.1 حل تضارب UUID vs BigInt

**المشكلة:**
```sql
-- cmis.users.id = BigInt
-- معظم الجداول الأخرى = UUID
```

**الحل المقترح:**

#### الخيار 1: تحويل users.id إلى UUID (مُفضّل)
```sql
-- Migration: 2025_02_convert_users_to_uuid.php

-- 1. إضافة عمود UUID مؤقت
ALTER TABLE cmis.users ADD COLUMN user_uuid UUID DEFAULT uuid_generate_v4();

-- 2. نسخ البيانات (إنشاء mapping)
CREATE TABLE temp_user_id_mapping AS
SELECT id as old_id, user_uuid as new_uuid FROM cmis.users;

-- 3. تحديث جميع Foreign Keys
UPDATE cmis.user_orgs SET user_id = (
    SELECT new_uuid FROM temp_user_id_mapping
    WHERE old_id = user_orgs.user_id
);

-- 4. تغيير نوع العمود
ALTER TABLE cmis.users DROP COLUMN id;
ALTER TABLE cmis.users RENAME COLUMN user_uuid TO id;
ALTER TABLE cmis.users ADD PRIMARY KEY (id);

-- 5. إعادة بناء Foreign Keys
ALTER TABLE cmis.user_orgs ADD CONSTRAINT fk_user_orgs_user
    FOREIGN KEY (user_id) REFERENCES cmis.users(id) ON DELETE CASCADE;

-- 6. حذف الجدول المؤقت
DROP TABLE temp_user_id_mapping;
```

**Checklist:**
- [ ] Backup قاعدة البيانات
- [ ] إنشاء migration للتحويل
- [ ] Testing على development environment
- [ ] تحديث Models (User, UserOrg, Sessions)
- [ ] تحديث Seeds
- [ ] Testing شامل
- [ ] Rollback plan جاهز

**الوقت المقدر:** 8 ساعات

---

### 2.2 توحيد UUID Generation في Models

**المشكلة:**
```php
// معظم Models لا تحتوي على UUID generation
```

**الحل:**
```php
// app/Models/BaseModel.php
abstract class BaseModel extends Model
{
    use HasFactory, SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-generate UUID if not set
            if ($model->incrementing === false
                && $model->getKeyType() === 'string'
                && empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
```

**Checklist:**
- [ ] تحديث BaseModel
- [ ] التأكد من جميع Models ترث من BaseModel
- [ ] حذف UUID generation من Models الفردية
- [ ] Testing للتحقق من التوليد التلقائي

**الوقت المقدر:** 3 ساعات

---

### 2.3 حذف Models المكررة

**المشكلة:**
```
❌ Campaign.php و Core/Campaign.php
❌ Permission.php و Security/Permission.php
```

**الحل:**
1. تحديد الـ Model الصحيح لكل نوع
2. نقل أي code فريد إلى الـ Model الأساسي
3. تحديث imports في جميع Controllers
4. حذف الملفات المكررة

**Checklist:**
- [ ] تحليل الفروقات بين Models المكررة
- [ ] دمج الـ code في Model واحد
- [ ] تحديث imports (Find & Replace)
- [ ] حذف Models الزائدة
- [ ] Testing شامل

**الوقت المقدر:** 4 ساعات

---

### 2.4 إضافة علاقات ناقصة في Models

**المشكلة:**
```php
// CreativeAsset.php - مفقود علاقات
```

**الحل:**
```php
// في CreativeAsset model
public function channel(): BelongsTo
{
    return $this->belongsTo(Channel::class, 'channel_id');
}

public function format(): BelongsTo
{
    return $this->belongsTo(ChannelFormat::class, 'format_id');
}

public function createdBy(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}

// في Campaign model
public function contents(): HasMany
{
    return $this->hasMany(ContentItem::class, 'campaign_id');
}

// ... إلخ
```

**Checklist:**
- [ ] مراجعة جميع Models للعلاقات الناقصة
- [ ] إضافة relations methods
- [ ] تحديث eager loading في Controllers
- [ ] Testing

**الوقت المقدر:** 3 ساعات

---

### 2.5 إنشاء CLI Commands المفقودة

**المطلوب:**
```bash
php artisan cmis:seed:reference-data
php artisan cmis:knowledge:import {source}
php artisan cmis:jobs:retry-failed
```

**الحل:**
```php
// app/Console/Commands/SeedReferenceDataCommand.php
class SeedReferenceDataCommand extends Command
{
    protected $signature = 'cmis:seed:reference-data
                            {--force : Force reseed}
                            {--type= : Specific type}';

    protected $description = 'Seed reference data (markets, channels, etc.)';

    public function handle()
    {
        // Call appropriate seeders
        $this->call('db:seed', ['--class' => 'ReferenceDataSeeder']);
        // ...
    }
}

// app/Console/Commands/ImportKnowledgeCommand.php
class ImportKnowledgeCommand extends Command
{
    protected $signature = 'cmis:knowledge:import
                            {source : file or url}
                            {--format=json}
                            {--category=}
                            {--batch=100}
                            {--dry-run}';

    public function handle()
    {
        // Parse source file
        // Validate format
        // Import to cmis_knowledge
        // Generate embeddings
    }
}
```

**Checklist:**
- [ ] إنشاء SeedReferenceDataCommand
- [ ] إنشاء ImportKnowledgeCommand
- [ ] إنشاء RetryFailedJobsCommand (wrapper)
- [ ] Testing
- [ ] Documentation

**الوقت المقدر:** 6 ساعات

---

### ملخص المرحلة 2

| المهمة | الأولوية | الوقت | الحالة |
|-------|----------|-------|--------|
| UUID vs BigInt Fix | 🟡 | 8h | [ ] |
| UUID Generation | 🟡 | 3h | [ ] |
| حذف Models المكررة | 🟡 | 4h | [ ] |
| إضافة Relations | 🟡 | 3h | [ ] |
| CLI Commands | 🟡 | 6h | [ ] |
| **المجموع** | | **24h** | |

---

## المرحلة 3: Content Plans & Calendar
**المدة:** أسبوعان (Week 3-4)
**الأولوية:** 🟡 HIGH
**المسؤول:** Full-Stack Developer

### 3.1 Backend - ContentPlan System

#### 3.1.1 إكمال ContentPlanController

**المشكلة:**
```php
// app/Http/Controllers/ContentPlanController.php - فارغ
```

**الحل:**
```php
class ContentPlanController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->user()->current_org_id;

        $plans = ContentPlan::where('org_id', $orgId)
            ->with(['campaign', 'items', 'creator'])
            ->when($request->campaign_id, fn($q, $id) => $q->where('campaign_id', $id))
            ->latest()
            ->paginate(20);

        return response()->json($plans);
    }

    public function store(StoreContentPlanRequest $request)
    {
        $plan = ContentPlan::create([
            'org_id' => $request->user()->current_org_id,
            'campaign_id' => $request->campaign_id,
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'strategy' => $request->strategy,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($plan, 201);
    }

    public function show(ContentPlan $plan)
    {
        $this->authorize('view', $plan);
        return response()->json($plan->load(['items', 'campaign']));
    }

    public function update(UpdateContentPlanRequest $request, ContentPlan $plan)
    {
        $this->authorize('update', $plan);
        $plan->update($request->validated());
        return response()->json($plan);
    }

    public function destroy(ContentPlan $plan)
    {
        $this->authorize('delete', $plan);
        $plan->delete();
        return response()->json(null, 204);
    }
}
```

**Checklist:**
- [ ] إكمال ContentPlanController
- [ ] إنشاء StoreContentPlanRequest
- [ ] إنشاء UpdateContentPlanRequest
- [ ] إنشاء ContentPlanPolicy
- [ ] إضافة routes في api.php و web.php
- [ ] Testing

**الوقت المقدر:** 6 ساعات

---

#### 3.1.2 ContentItem CRUD

```php
// app/Http/Controllers/ContentItemController.php
class ContentItemController extends Controller
{
    public function store(StoreContentItemRequest $request)
    {
        $item = ContentItem::create([
            'plan_id' => $request->plan_id,
            'channel_id' => $request->channel_id,
            'format_id' => $request->format_id,
            'title' => $request->title,
            'description' => $request->description,
            'scheduled_at' => $request->scheduled_at,
            'status' => 'draft',
            'brief' => $request->brief,
        ]);

        return response()->json($item, 201);
    }

    public function calendar(Request $request)
    {
        $orgId = $request->user()->current_org_id;

        $items = ContentItem::whereHas('plan', function ($q) use ($orgId) {
                $q->where('org_id', $orgId);
            })
            ->whereBetween('scheduled_at', [
                $request->start_date,
                $request->end_date
            ])
            ->with(['plan', 'channel', 'format'])
            ->orderBy('scheduled_at')
            ->get();

        return response()->json($items);
    }
}
```

**Checklist:**
- [ ] CRUD operations للـ ContentItem
- [ ] Calendar endpoint
- [ ] Bulk operations (create multiple items)
- [ ] Reschedule functionality
- [ ] Testing

**الوقت المقدر:** 5 ساعات

---

### 3.2 Frontend - Content Calendar UI

#### 3.2.1 Content Plans Index View

```blade
<!-- resources/views/content-plans/index.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2>خطط المحتوى</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="mb-6">
                <select wire:model="campaignId">
                    <option value="">جميع الحملات</option>
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Plans List -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($plans as $plan)
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3>{{ $plan->title }}</h3>
                        <p class="text-gray-600">{{ $plan->description }}</p>
                        <div class="mt-4">
                            <span>{{ $plan->items_count }} منشور</span>
                            <span>{{ $plan->start_date }} - {{ $plan->end_date }}</span>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <a href="{{ route('content-plans.show', $plan) }}">عرض</a>
                            <a href="{{ route('content-plans.calendar', $plan) }}">التقويم</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
```

**Checklist:**
- [ ] إنشاء index view
- [ ] إنشاء create form
- [ ] إنشاء show view
- [ ] Filters و Pagination
- [ ] Testing

**الوقت المقدر:** 6 ساعات

---

#### 3.2.2 Calendar View

```blade
<!-- resources/views/content-plans/calendar.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2>تقويم المحتوى</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto">
            <!-- Calendar Component -->
            <div x-data="calendar()" x-init="init()">
                <!-- Month Navigation -->
                <div class="flex justify-between mb-4">
                    <button @click="previousMonth()">السابق</button>
                    <h3 x-text="currentMonthYear"></h3>
                    <button @click="nextMonth()">التالي</button>
                </div>

                <!-- Calendar Grid -->
                <div class="grid grid-cols-7 gap-2">
                    <!-- Days of Week -->
                    <template x-for="day in daysOfWeek">
                        <div class="text-center font-bold" x-text="day"></div>
                    </template>

                    <!-- Calendar Cells -->
                    <template x-for="day in calendarDays">
                        <div class="border rounded p-2 min-h-[100px]"
                             :class="{ 'bg-gray-100': !day.isCurrentMonth }">
                            <div class="text-sm" x-text="day.date"></div>

                            <!-- Content Items for this day -->
                            <template x-for="item in day.items">
                                <div class="text-xs bg-blue-100 rounded p-1 mt-1"
                                     @click="openItem(item)">
                                    <span x-text="item.title"></span>
                                </div>
                            </template>

                            <!-- Add Item Button -->
                            <button class="text-xs text-blue-500 mt-1"
                                    @click="addItem(day.date)">
                                + إضافة منشور
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function calendar() {
        return {
            currentDate: new Date(),
            items: [],

            init() {
                this.loadItems();
            },

            async loadItems() {
                const start = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1);
                const end = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 0);

                const response = await fetch(`/api/content-items/calendar?start_date=${start.toISOString()}&end_date=${end.toISOString()}`);
                this.items = await response.json();
            },

            // ... باقي methods
        }
    }
    </script>
    @endpush
</x-app-layout>
```

**Checklist:**
- [ ] إنشاء calendar view
- [ ] Alpine.js calendar component
- [ ] Drag & drop لإعادة الجدولة
- [ ] Add/Edit item modal
- [ ] Filters (channel, status)
- [ ] Export calendar (PDF, Excel)
- [ ] Testing

**الوقت المقدر:** 10 ساعات

---

### 3.3 Features الإضافية

#### 3.3.1 Auto-Generate Content Plan

```php
// app/Services/ContentPlanService.php
class ContentPlanService
{
    public function autoGenerate(Campaign $campaign, array $params)
    {
        $plan = ContentPlan::create([
            'campaign_id' => $campaign->id,
            'org_id' => $campaign->org_id,
            'title' => "خطة محتوى - {$campaign->name}",
            'start_date' => $params['start_date'],
            'end_date' => $params['end_date'],
        ]);

        // حساب عدد الأيام
        $days = Carbon::parse($params['start_date'])
            ->diffInDays($params['end_date']);

        // توزيع المنشورات
        $postsPerDay = $params['posts_per_day'] ?? 1;
        $channels = $params['channels'] ?? ['instagram'];

        foreach (range(0, $days) as $day) {
            $date = Carbon::parse($params['start_date'])->addDays($day);

            foreach (range(1, $postsPerDay) as $post) {
                ContentItem::create([
                    'plan_id' => $plan->id,
                    'channel_id' => Channel::where('code', $channels[0])->first()->id,
                    'title' => "منشور {$day + 1}-{$post}",
                    'scheduled_at' => $date->setHour(10 + ($post * 3)),
                    'status' => 'draft',
                ]);
            }
        }

        return $plan->load('items');
    }
}
```

**Checklist:**
- [ ] إنشاء ContentPlanService
- [ ] Auto-generate method
- [ ] AI-powered content suggestions
- [ ] Template library
- [ ] Testing

**الوقت المقدر:** 6 ساعات

---

### ملخص المرحلة 3

| المهمة | الأولوية | الوقت | الحالة |
|-------|----------|-------|--------|
| ContentPlan Backend | 🟡 | 6h | [ ] |
| ContentItem CRUD | 🟡 | 5h | [ ] |
| Plans Index View | 🟡 | 6h | [ ] |
| Calendar View | 🟡 | 10h | [ ] |
| Auto-Generate | 🟡 | 6h | [ ] |
| **المجموع** | | **33h** | |

---

## المرحلة 4: GPT Interface - Foundation
**المدة:** أسبوعان (Week 5-6)
**الأولوية:** 🟡 HIGH
**المسؤول:** Senior Backend Developer

### 4.1 GPT Action Schema (OpenAPI)

```yaml
# public/gpt-action-schema.yaml
openapi: 3.1.0
info:
  title: CMIS GPT Actions API
  description: API للتكامل مع ChatGPT لإدارة حملات التسويق
  version: 1.0.0

servers:
  - url: https://your-domain.com/api/gpt/v1
    description: Production API

security:
  - GptToken: []

paths:
  /knowledge/search:
    post:
      operationId: searchKnowledge
      summary: البحث في قاعدة المعرفة
      security:
        - GptToken: [gpt:read:knowledge]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [query]
              properties:
                query:
                  type: string
                  description: استعلام البحث
                limit:
                  type: integer
                  default: 10
                  minimum: 1
                  maximum: 50
      responses:
        '200':
          description: نتائج البحث
          content:
            application/json:
              schema:
                type: object
                properties:
                  results:
                    type: array
                    items:
                      $ref: '#/components/schemas/KnowledgeItem'

  /campaigns/drafts:
    post:
      operationId: createCampaignDraft
      summary: إنشاء مسودة حملة
      security:
        - GptToken: [gpt:write:drafts]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/CampaignDraft'
      responses:
        '201':
          description: تم إنشاء المسودة
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Draft'

  /ai/generate:
    post:
      operationId: generateContent
      summary: توليد محتوى بالذكاء الاصطناعي
      security:
        - GptToken: [gpt:generate:content]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [type, prompt]
              properties:
                type:
                  type: string
                  enum: [caption, hashtags, description]
                prompt:
                  type: string
                context:
                  type: object
      responses:
        '200':
          description: المحتوى المولّد

components:
  securitySchemes:
    GptToken:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    KnowledgeItem:
      type: object
      properties:
        id:
          type: string
          format: uuid
        title:
          type: string
        content:
          type: string
        relevance:
          type: number
          format: float

    CampaignDraft:
      type: object
      required: [name, objective, channel]
      properties:
        name:
          type: string
        objective:
          type: string
        channel:
          type: string
        budget:
          type: number
        start_date:
          type: string
          format: date

    Draft:
      type: object
      properties:
        draft_id:
          type: string
          format: uuid
        status:
          type: string
          enum: [pending, approved, rejected]
        approval_url:
          type: string
          format: uri
```

**Checklist:**
- [ ] إنشاء OpenAPI schema كامل
- [ ] تعريف جميع Operations (10-15 tool)
- [ ] تعريف Security schemes
- [ ] تعريف Request/Response schemas
- [ ] Validation مع examples
- [ ] Publish على /gpt-action-schema.yaml

**الوقت المقدر:** 8 ساعات

---

### 4.2 Token Abilities System

```php
// app/Providers/AuthServiceProvider.php
public function boot()
{
    // Define GPT Abilities
    $abilities = [
        'gpt:read:knowledge',
        'gpt:search:semantic',
        'gpt:read:campaigns',
        'gpt:read:content',
        'gpt:write:drafts',
        'gpt:generate:content',
        'gpt:read:analytics',
    ];

    foreach ($abilities as $ability) {
        Gate::define($ability, function ($user) use ($ability) {
            return $user->tokenCan($ability);
        });
    }

    // Super Admin bypass
    Gate::before(function ($user, $ability) {
        if ($user->hasRole('super_admin')) {
            return true;
        }
    });
}
```

```php
// app/Http/Controllers/GPT/GPTTokenController.php
class GPTTokenController extends Controller
{
    public function createToken(Request $request)
    {
        $this->authorize('create-gpt-token', $request->user());

        $abilities = [
            'gpt:read:knowledge',
            'gpt:search:semantic',
            'gpt:read:campaigns',
            'gpt:write:drafts',
            'gpt:generate:content',
        ];

        $token = $request->user()->createToken(
            'gpt-interface',
            $abilities,
            now()->addDays(90)
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => now()->addDays(90),
            'abilities' => $abilities,
        ]);
    }
}
```

**Checklist:**
- [ ] تعريف GPT Abilities
- [ ] تطبيق Gates
- [ ] إنشاء GPTTokenController
- [ ] Middleware للتحقق من Abilities
- [ ] Testing
- [ ] Documentation

**الوقت المقدر:** 5 ساعات

---

### 4.3 Draft System

```sql
-- database/migrations/2025_03_create_gpt_drafts_table.php
CREATE TABLE cmis.gpt_drafts (
    draft_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES cmis.users(id) ON DELETE CASCADE,
    entity_type TEXT NOT NULL, -- 'campaign', 'content', 'brief'
    draft_data JSONB NOT NULL,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
    created_by TEXT DEFAULT 'GPT',
    reviewed_by UUID REFERENCES cmis.users(id),
    reviewed_at TIMESTAMPTZ,
    notes TEXT,
    created_at TIMESTAMPTZ DEFAULT now(),
    updated_at TIMESTAMPTZ DEFAULT now()
);

CREATE INDEX idx_gpt_drafts_org ON cmis.gpt_drafts(org_id);
CREATE INDEX idx_gpt_drafts_status ON cmis.gpt_drafts(status);
CREATE INDEX idx_gpt_drafts_created_at ON cmis.gpt_drafts(created_at);
```

```php
// app/Models/GPT/Draft.php
class Draft extends Model
{
    protected $table = 'cmis.gpt_drafts';
    protected $primaryKey = 'draft_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id', 'user_id', 'entity_type', 'draft_data',
        'status', 'created_by', 'notes',
    ];

    protected $casts = [
        'draft_data' => 'array',
        'created_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function org() {
        return $this->belongsTo(Org::class, 'org_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approve(User $user) {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        // إنشاء الـ entity الفعلي
        return $this->createEntity();
    }

    protected function createEntity() {
        switch ($this->entity_type) {
            case 'campaign':
                return Campaign::create($this->draft_data);
            case 'content':
                return ContentItem::create($this->draft_data);
            // ...
        }
    }
}
```

```php
// app/Http/Controllers/GPT/GPTDraftController.php
class GPTDraftController extends Controller
{
    public function store(StoreDraftRequest $request)
    {
        $draft = Draft::create([
            'org_id' => $request->user()->current_org_id,
            'user_id' => $request->user()->id,
            'entity_type' => $request->entity_type,
            'draft_data' => $request->draft_data,
            'created_by' => 'GPT',
        ]);

        // إرسال notification للمراجعة
        $this->notifyReviewers($draft);

        return response()->json([
            'draft_id' => $draft->draft_id,
            'status' => 'pending',
            'approval_url' => route('drafts.review', $draft),
        ], 201);
    }

    public function approve(Draft $draft)
    {
        $this->authorize('approve', $draft);

        $entity = $draft->approve(auth()->user());

        return response()->json([
            'status' => 'approved',
            'entity' => $entity,
        ]);
    }
}
```

**Checklist:**
- [ ] إنشاء migration للـ gpt_drafts
- [ ] إنشاء Draft model
- [ ] إنشاء GPTDraftController
- [ ] Approval workflow
- [ ] Notifications للمراجعين
- [ ] Testing
- [ ] UI للمراجعة (Web)

**الوقت المقدر:** 10 ساعات

---

### 4.4 GPT-Specific Endpoints

```php
// routes/api.php
Route::prefix('gpt/v1')
    ->middleware(['auth:sanctum', 'throttle:gpt'])
    ->group(function () {

        // Knowledge
        Route::post('/knowledge/search', [GPTKnowledgeController::class, 'search'])
            ->middleware('can:gpt:search:semantic');

        // Campaigns
        Route::get('/campaigns', [GPTCampaignController::class, 'index'])
            ->middleware('can:gpt:read:campaigns');
        Route::get('/campaigns/{id}', [GPTCampaignController::class, 'show'])
            ->middleware('can:gpt:read:campaigns');

        // Drafts
        Route::post('/drafts', [GPTDraftController::class, 'store'])
            ->middleware('can:gpt:write:drafts');
        Route::get('/drafts', [GPTDraftController::class, 'index'])
            ->middleware('can:gpt:write:drafts');

        // Content Generation
        Route::post('/ai/generate', [GPTAIController::class, 'generate'])
            ->middleware('can:gpt:generate:content');

        // Analytics (Read-only)
        Route::get('/analytics/overview', [GPTAnalyticsController::class, 'overview'])
            ->middleware('can:gpt:read:analytics');
    });

// Rate Limiter
RateLimiter::for('gpt', function (Request $request) {
    return [
        Limit::perMinute(30)->by($request->header('X-GPT-Session-ID')),
        Limit::perHour(500)->by($request->header('X-GPT-Session-ID')),
    ];
});
```

**Checklist:**
- [ ] إنشاء GPT routes group
- [ ] إنشاء GPT Controllers (5-7 controllers)
- [ ] تطبيق Ability checks
- [ ] Rate limiting خاص بالـ GPT
- [ ] Response formatting للـ GPT
- [ ] Error handling
- [ ] Testing
- [ ] API Documentation

**الوقت المقدر:** 12 ساعات

---

### ملخص المرحلة 4

| المهمة | الأولوية | الوقت | الحالة |
|-------|----------|-------|--------|
| OpenAPI Schema | 🟡 | 8h | [ ] |
| Token Abilities | 🟡 | 5h | [ ] |
| Draft System | 🟡 | 10h | [ ] |
| GPT Endpoints | 🟡 | 12h | [ ] |
| **المجموع** | | **35h** | |

---

## المرحلة 5: GPT Interface - Completion
**المدة:** أسبوع واحد (Week 7)
**الأولوية:** 🟡 HIGH
**المسؤول:** Senior Backend Developer

### 5.1 Advanced GPT Features

#### 5.1.1 Context Loader للـ GPT

```php
// app/Services/GPT/ContextLoaderService.php
class ContextLoaderService
{
    public function loadContext(User $user, string $contextType, array $params = [])
    {
        switch ($contextType) {
            case 'campaign':
                return $this->loadCampaignContext($params['campaign_id']);
            case 'organization':
                return $this->loadOrgContext($user->current_org_id);
            case 'market':
                return $this->loadMarketContext($params['market_code']);
            // ...
        }
    }

    protected function loadCampaignContext($campaignId)
    {
        $campaign = Campaign::with(['org', 'offerings', 'metrics'])
            ->findOrFail($campaignId);

        return [
            'campaign' => [
                'name' => $campaign->name,
                'objective' => $campaign->objective,
                'budget' => $campaign->budget,
                'start_date' => $campaign->start_date,
                'end_date' => $campaign->end_date,
            ],
            'organization' => [
                'name' => $campaign->org->name,
                'industry' => $campaign->org->industry,
            ],
            'performance' => $campaign->metrics->toArray(),
        ];
    }
}
```

**Checklist:**
- [ ] إنشاء ContextLoaderService
- [ ] Load methods لأنواع مختلفة من Context
- [ ] Caching للـ Context
- [ ] Endpoint: POST /gpt/v1/context/load
- [ ] Testing

**الوقت المقدر:** 6 ساعات

---

#### 5.1.2 GPT Audit Logger

```php
// app/Http/Middleware/GPTAuditLogger.php
class GPTAuditLogger
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        $response = $next($request);

        $executionTime = (microtime(true) - $startTime) * 1000;

        DB::table('cmis_audit.gpt_actions')->insert([
            'action_id' => Str::uuid(),
            'org_id' => $request->user()->current_org_id,
            'user_id' => $request->user()->id,
            'tool_name' => $request->route()->getName(),
            'request_payload' => json_encode($request->except(['password', 'token'])),
            'response_status' => $response->status(),
            'execution_time_ms' => $executionTime,
            'session_id' => $request->header('X-GPT-Session-ID'),
            'created_at' => now(),
        ]);

        return $response;
    }
}
```

**Migration:**
```sql
CREATE TABLE cmis_audit.gpt_actions (
    action_id UUID PRIMARY KEY,
    org_id UUID NOT NULL,
    user_id UUID NOT NULL,
    tool_name TEXT NOT NULL,
    request_payload JSONB,
    response_status INTEGER,
    execution_time_ms NUMERIC,
    session_id TEXT,
    created_at TIMESTAMPTZ DEFAULT now()
);

CREATE INDEX idx_gpt_actions_org ON cmis_audit.gpt_actions(org_id);
CREATE INDEX idx_gpt_actions_session ON cmis_audit.gpt_actions(session_id);
CREATE INDEX idx_gpt_actions_created_at ON cmis_audit.gpt_actions(created_at);
```

**Checklist:**
- [ ] إنشاء migration للـ gpt_actions
- [ ] إنشاء GPTAuditLogger middleware
- [ ] تطبيق على GPT routes
- [ ] Dashboard للـ GPT usage
- [ ] Alerts للـ suspicious activity
- [ ] Testing

**الوقت المقدر:** 5 ساعات

---

#### 5.1.3 GPT Validation Layer

```php
// app/Http/Middleware/ValidateGPTRequest.php
class ValidateGPTRequest
{
    protected $schemas = [];

    public function handle(Request $request, Closure $next)
    {
        $operationId = $request->route()->getName();
        $schema = $this->loadSchema($operationId);

        if (!$schema) {
            return $next($request);
        }

        $validator = new Opis\JsonSchema\Validator();
        $result = $validator->validate($request->all(), $schema);

        if (!$result->isValid()) {
            return response()->json([
                'error' => 'Invalid request schema',
                'violations' => $result->error()->format(),
            ], 422);
        }

        return $next($request);
    }

    protected function loadSchema(string $operationId)
    {
        // Load من OpenAPI schema
        if (!isset($this->schemas[$operationId])) {
            $openapi = Yaml::parseFile(public_path('gpt-action-schema.yaml'));
            // Extract schema for this operation
        }

        return $this->schemas[$operationId] ?? null;
    }
}
```

**Checklist:**
- [ ] إنشاء ValidateGPTRequest middleware
- [ ] تحميل schemas من OpenAPI
- [ ] JSON Schema validation
- [ ] Error messages واضحة
- [ ] Testing

**الوقت المقدر:** 4 ساعات

---

### 5.2 Testing & Integration

#### 5.2.1 Unit Tests للـ GPT

```php
// tests/Feature/GPT/GPTKnowledgeTest.php
class GPTKnowledgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_search_knowledge_with_gpt_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('gpt', ['gpt:search:semantic']);

        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/gpt/v1/knowledge/search', [
                'query' => 'Instagram best practices',
                'limit' => 5,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'results' => [
                    '*' => ['id', 'title', 'content', 'relevance']
                ]
            ]);
    }

    public function test_cannot_search_without_ability()
    {
        $user = User::factory()->create();
        $token = $user->createToken('regular', []);

        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/gpt/v1/knowledge/search', [
                'query' => 'Test',
            ]);

        $response->assertForbidden();
    }

    public function test_rate_limiting_applies()
    {
        $user = User::factory()->create();
        $token = $user->createToken('gpt', ['gpt:search:semantic']);

        // إرسال 31 request (أكثر من الحد)
        for ($i = 0; $i < 31; $i++) {
            $response = $this->withToken($token->plainTextToken)
                ->postJson('/api/gpt/v1/knowledge/search', [
                    'query' => "Query {$i}",
                ]);
        }

        $response->assertStatus(429); // Too Many Requests
    }
}
```

**Checklist:**
- [ ] كتابة tests للـ Knowledge endpoints
- [ ] كتابة tests للـ Campaigns endpoints
- [ ] كتابة tests للـ Drafts system
- [ ] كتابة tests للـ Token Abilities
- [ ] كتابة tests للـ Rate Limiting
- [ ] Integration tests
- [ ] Coverage > 80%

**الوقت المقدر:** 8 ساعات

---

#### 5.2.2 GPT Testing Playground

```php
// routes/web.php (for development only)
Route::prefix('gpt/playground')
    ->middleware(['auth', 'env:local'])
    ->group(function () {
        Route::get('/', [GPTPlaygroundController::class, 'index']);
        Route::post('/test', [GPTPlaygroundController::class, 'test']);
    });

// resources/views/gpt/playground.blade.php
<x-app-layout>
    <div class="container mx-auto p-6">
        <h1>GPT Testing Playground</h1>

        <div class="grid grid-cols-2 gap-6">
            <!-- Request Panel -->
            <div>
                <h2>Request</h2>
                <select id="operation">
                    <option value="searchKnowledge">Search Knowledge</option>
                    <option value="createCampaignDraft">Create Campaign Draft</option>
                    <option value="generateContent">Generate Content</option>
                </select>

                <textarea id="request-body" rows="15"></textarea>

                <button onclick="sendRequest()">Send Request</button>
            </div>

            <!-- Response Panel -->
            <div>
                <h2>Response</h2>
                <pre id="response"></pre>
            </div>
        </div>
    </div>

    <script>
    async function sendRequest() {
        const operation = document.getElementById('operation').value;
        const body = JSON.parse(document.getElementById('request-body').value);

        const response = await fetch(`/api/gpt/v1/${operation}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getGPTToken()}`,
            },
            body: JSON.stringify(body),
        });

        const data = await response.json();
        document.getElementById('response').textContent = JSON.stringify(data, null, 2);
    }
    </script>
</x-app-layout>
```

**Checklist:**
- [ ] إنشاء GPT Playground view
- [ ] Select operations
- [ ] Request editor
- [ ] Response viewer
- [ ] Token management
- [ ] Request history

**الوقت المقدر:** 4 ساعات

---

### ملخص المرحلة 5

| المهمة | الأولوية | الوقت | الحالة |
|-------|----------|-------|--------|
| Context Loader | 🟡 | 6h | [ ] |
| GPT Audit Logger | 🟡 | 5h | [ ] |
| Validation Layer | 🟡 | 4h | [ ] |
| Unit Tests | 🟡 | 8h | [ ] |
| Testing Playground | 🟢 | 4h | [ ] |
| **المجموع** | | **27h** | |

---

## المرحلة 6: Testing & Documentation
**المدة:** أسبوع واحد (Week 8)
**الأولوية:** 🟢 MEDIUM
**المسؤول:** Full Team

### 6.1 Comprehensive Testing

#### 6.1.1 Integration Tests
```bash
# Testing checklist
- [ ] Auth flow (login, register, token refresh)
- [ ] Multi-tenancy isolation
- [ ] RLS policies enforcement
- [ ] Campaigns CRUD
- [ ] Content Plans & Calendar
- [ ] Knowledge search
- [ ] AI generation
- [ ] Compliance checks
- [ ] GPT endpoints
- [ ] Draft system
- [ ] Rate limiting
```

#### 6.1.2 Security Testing
```bash
# Security checklist
- [ ] SQL Injection attempts
- [ ] XSS vulnerabilities
- [ ] CSRF protection
- [ ] Authentication bypass attempts
- [ ] Authorization bypass attempts
- [ ] Token expiration
- [ ] Rate limiting bypass attempts
- [ ] RLS bypass attempts
```

#### 6.1.3 Performance Testing
```bash
# Load testing
- [ ] 100 concurrent users
- [ ] 1000 requests/minute
- [ ] Database query optimization
- [ ] API response times < 200ms
- [ ] Vector search performance
```

**الوقت المقدر:** 16 ساعات

---

### 6.2 Documentation

#### 6.2.1 API Documentation
```bash
# Generate API docs
php artisan scribe:generate

# أو استخدام Swagger
php artisan l5-swagger:generate
```

**المطلوب:**
- [ ] API Reference كامل
- [ ] Authentication guide
- [ ] Examples لكل endpoint
- [ ] Error codes و responses
- [ ] Rate limiting documentation
- [ ] Webhooks documentation

#### 6.2.2 GPT Integration Guide
```markdown
# GPT Integration Guide

## Overview
كيفية دمج CMIS مع ChatGPT عبر GPT Actions.

## Setup
1. الحصول على GPT Token
2. تكوين GPT Action في ChatGPT
3. Testing الاتصال

## Available Tools
- searchKnowledge
- createCampaignDraft
- generateContent
- getCampaignInsights

## Examples
...
```

#### 6.2.3 Developer Documentation
```markdown
# Developer Guide

## Architecture
- Database Schema
- Models Structure
- Service Layer
- Multi-tenancy
- RLS Implementation

## Getting Started
- Setup environment
- Database migrations
- Seeding data
- Running tests

## Contributing
- Code style
- Testing requirements
- Pull request process
```

**الوقت المقدر:** 8 ساعات

---

### 6.3 Deployment Preparation

```bash
# Deployment checklist
- [ ] Environment configuration
- [ ] Database backup plan
- [ ] Migration scripts
- [ ] Seeding scripts
- [ ] SSL certificates
- [ ] Domain configuration
- [ ] CDN setup
- [ ] Monitoring setup
- [ ] Error tracking (Sentry)
- [ ] Performance monitoring (New Relic)
```

**الوقت المقدر:** 8 ساعات

---

### ملخص المرحلة 6

| المهمة | الأولوية | الوقت | الحالة |
|-------|----------|-------|--------|
| Integration Tests | 🟢 | 16h | [ ] |
| API Documentation | 🟢 | 8h | [ ] |
| Deployment Prep | 🟢 | 8h | [ ] |
| **المجموع** | | **32h** | |

---

## الملخص النهائي

### إجمالي الوقت المقدر

| المرحلة | المدة | الساعات | الحالة |
|---------|------|---------|--------|
| المرحلة 1: Security Fixes | Week 1 | 15h | [ ] |
| المرحلة 2: Infrastructure | Week 2 | 24h | [ ] |
| المرحلة 3: Content Plans | Week 3-4 | 33h | [ ] |
| المرحلة 4: GPT Foundation | Week 5-6 | 35h | [ ] |
| المرحلة 5: GPT Completion | Week 7 | 27h | [ ] |
| المرحلة 6: Testing & Docs | Week 8 | 32h | [ ] |
| **المجموع** | **8 أسابيع** | **166h** | |

### الفريق المطلوب
- **1 Senior Backend Developer:** GPT Interface & Security
- **1 Backend Developer:** Infrastructure & APIs
- **1 Full-Stack Developer:** Content Plans & UI
- **1 QA Engineer:** Testing & Documentation

### التكلفة التقديرية
```
Senior Developer: 166h × $75/h = $12,450
Backend Developer: 90h × $60/h = $5,400
Full-Stack Developer: 90h × $60/h = $5,400
QA Engineer: 80h × $50/h = $4,000
────────────────────────────────────
Total: $27,250
```

---

## الملحق: Checklists التفصيلية

### A. Security Checklist
```
[ ] Password hashing في Login
[ ] Token expiration enabled
[ ] RLS enabled على جميع الجداول
[ ] Rate limiting على AI routes
[ ] GPT Token Abilities
[ ] Input validation شامل
[ ] SQL Injection protection
[ ] XSS protection
[ ] CSRF protection
[ ] Audit logging شامل
```

### B. Features Checklist
```
[ ] Reference Data API
[ ] Content Plans Module
[ ] Content Calendar
[ ] GPT Interface
[ ] Draft System
[ ] Token Abilities
[ ] Context Loader
[ ] Auto-generate Content Plan
```

### C. Testing Checklist
```
[ ] Unit Tests > 80% coverage
[ ] Integration Tests
[ ] Security Tests
[ ] Performance Tests
[ ] Load Tests
[ ] GPT Tests
```

### D. Documentation Checklist
```
[ ] API Documentation
[ ] GPT Integration Guide
[ ] Developer Guide
[ ] Deployment Guide
[ ] User Manual
[ ] Admin Manual
```

---

**نهاية الخطة**

**التاريخ:** 2025-11-16
**الإصدار:** v1.0
**المُعد:** Claude Code Assistant
**الحالة:** جاهز للتنفيذ ✅
