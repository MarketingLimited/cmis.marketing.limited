# خارطة طريق التنفيذ - CMIS Marketing Platform
## خطة شاملة للتحسينات التقنية والوظيفية

> **تاريخ الإنشاء:** 2024-11-13
> **الإصدار:** 1.0
> **الحالة:** قيد التنفيذ

---

## جدول المحتويات

1. [الملخص التنفيذي](#الملخص-التنفيذي)
2. [التقييم الحالي](#التقييم-الحالي)
3. [الأهداف الاستراتيجية](#الأهداف-الاستراتيجية)
4. [المراحل التنفيذية](#المراحل-التنفيذية)
5. [المتطلبات التقنية](#المتطلبات-التقنية)
6. [مؤشرات النجاح](#مؤشرات-النجاح)
7. [المخاطر وخطط التخفيف](#المخاطر-وخطط-التخفيف)

---

## الملخص التنفيذي

### الرؤية
تحويل CMIS إلى منصة تسويق رقمي موحدة تجمع بين:
- **بساطة Buffer** في جدولة المحتوى
- **قوة Hootsuite** في التحليلات (لكن أبسط)
- **سهولة AdEspresso** في إدارة الحملات الإعلانية

### نسبة الإنجاز الحالية
| المجال | الإنجاز | الملاحظات |
|--------|----------|-----------|
| **البنية التحتية الأساسية** | 70% | Models, Migrations, Repositories موجودة |
| **ربط Repositories بالتطبيق** | 25% | 3 من 18 repository فقط مستخدمة |
| **جدولة المحتوى** | 40% | وظائف أساسية موجودة، تحتاج تحسين UX |
| **التحليلات** | 35% | Data موجودة، Visualization ضعيفة |
| **الحملات الإعلانية** | 45% | Meta/Google جاهزة، منصات أخرى ناقصة |
| **التعاون والموافقات** | 20% | Roles موجودة، Workflows ناقصة |

---

## التقييم الحالي

### 1. المشاكل التقنية الحرجة (التقرير الأول والثاني)

#### أ) البنية المعمارية
- ✅ **تم حله (25%)**: دمج 3 Repositories (Analytics, Knowledge, Embedding)
- ❌ **متبقي (75%)**: 15 Repository غير مستخدمة
- ❌ **حرج**: لا توجد Interfaces ولا Bindings في Service Container
- ❌ **حرج**: Services تستدعي `DB::select()` مباشرة بدلاً من Repositories

**مثال المشكلة:**
```php
// الحالي في CampaignService (❌ خطأ)
DB::select('SELECT * FROM cmis.find_related_campaigns(?, ?)', [$id, $limit]);

// المطلوب (✓ صحيح)
$this->campaignRepo->findRelatedCampaigns($id, $limit);
```

#### ب) الملفات المكررة
- ✅ **تم حله**: حذف 3 ملفات مكررة (AdCampaignController, CampaignController, AdCampaignService)
- ❌ **متبقي**: تداخل في Embedding Services (3 ملفات مختلفة لنفس الغرض)

#### ج) FormRequests و Resources
- ❌ **مشكلة**: Controllers تستخدم `Validator::make()` يدوياً
- ❌ **مشكلة**: لا يتم استخدام API Resources للإخراج المنظم

#### د) مشاكل أخرى
- ❌ **خطأ حرج**: مسار publish خاطئ في `CMISEmbeddingServiceProvider`
- ⚠️ **تحذير**: Migrations لا تغطي إلا 4 جداول (Database-first approach غير موثق)

### 2. الفجوات الوظيفية (التقرير الثالث)

#### أ) جدولة المحتوى
- ✅ موجود: وظائف النشر الأساسية
- ❌ مفقود: Queue per-channel (أوقات افتراضية)
- ❌ مفقود: Bulk compose
- ❌ مفقود: "أفضل توقيت" (AI-suggested timing)
- ❌ مفقود: Workflow موافقات

#### ب) التحليلات
- ✅ موجود: Sync للبيانات الأساسية
- ❌ مفقود: Dashboard واضحة ومنظمة
- ❌ مفقود: PDF Reports
- ❌ مفقود: تفسير AI للاتجاهات ("لماذا ارتفع؟")

#### ج) الحملات الإعلانية
- ✅ موجود: Meta و Google Ads
- ⚠️ جزئي: LinkedIn, X, TikTok, Snapchat (موصلات غير مكتملة)
- ❌ مفقود: A/B Testing مبسط
- ❌ مفقود: Audience Templates
- ❌ مفقود: Budget Optimization

#### د) التعاون
- ✅ موجود: Policies أساسية
- ❌ مفقود: Approval workflows
- ❌ مفقود: Team collaboration features
- ❌ مفقود: Unified Inbox

---

## الأهداف الاستراتيجية

### الأهداف التقنية (6 أشهر)
1. **فصل الطبقات الكامل**: 100% من DB operations عبر Repositories
2. **Type Safety**: استخدام Interfaces + Dependency Injection في كل Service
3. **معايير الكود**: FormRequests + Resources في جميع Controllers
4. **الاختبارات**: 70% Test Coverage (Unit + Feature)
5. **التوثيق**: API Documentation كاملة + Architecture Decision Records

### الأهداف الوظيفية (6 أشهر)
1. **Time-to-First-Post < 10 دقائق** (من إنشاء حساب لأول منشور مجدول)
2. **Multi-platform Campaign Launch < 15 دقيقة** (إطلاق حملة على منصتين+)
3. **زيادة معدل النشر +20%** بعد 30 يوم من الاستخدام
4. **توفير 50% من وقت إعداد التقارير** للمسوقين
5. **دعم 6 منصات رئيسية** (Meta, Google, LinkedIn, X, TikTok, Snapchat)

---

## المراحل التنفيذية

> **ملاحظة:** كل Sprint = أسبوعان عمل

---

### المرحلة 1: الأساسيات التقنية (4 أسابيع)

#### Sprint 1.1: البنية المعمارية الصحيحة
**الهدف:** إصلاح Dependency Injection وإنشاء الواجهات

**المهام:**
1. **إنشاء Repository Interfaces** (يومان)
   ```bash
   app/Repositories/Contracts/
   ├── CampaignRepositoryInterface.php
   ├── ContextRepositoryInterface.php
   ├── CreativeRepositoryInterface.php
   ├── PermissionRepositoryInterface.php
   ├── AnalyticsRepositoryInterface.php
   ├── KnowledgeRepositoryInterface.php
   ├── EmbeddingRepositoryInterface.php
   ├── OperationsRepositoryInterface.php
   ├── AuditRepositoryInterface.php
   ├── CacheRepositoryInterface.php
   ├── MarketingRepositoryInterface.php
   ├── SocialMediaRepositoryInterface.php
   ├── NotificationRepositoryInterface.php
   ├── VerificationRepositoryInterface.php
   └── TriggerRepositoryInterface.php
   ```

2. **تحديث AppServiceProvider** (نصف يوم)
   ```php
   // app/Providers/AppServiceProvider.php
   public function register(): void
   {
       // Campaign
       $this->app->bind(
           \App\Repositories\Contracts\CampaignRepositoryInterface::class,
           \App\Repositories\CMIS\CampaignRepository::class
       );

       // Context
       $this->app->bind(
           \App\Repositories\Contracts\ContextRepositoryInterface::class,
           \App\Repositories\CMIS\ContextRepository::class
       );

       // ... كرر لكل Repository
   }
   ```

3. **إصلاح خطأ CMISEmbeddingServiceProvider** (30 دقيقة)
   ```php
   // قبل (❌):
   __DIR__.'/../config/cmis-embeddings.php'

   // بعد (✓):
   base_path('config/cmis-embeddings.php')
   ```

**مخرجات Sprint 1.1:**
- ✅ 15 Interface جديدة
- ✅ Bindings في AppServiceProvider
- ✅ إصلاح مسار publish
- ✅ Unit tests للـBindings

---

#### Sprint 1.2: ربط CampaignService (النموذج الأول)
**الهدف:** تحويل CampaignService لنموذج مثالي يُقاس عليه

**المهام:**
1. **Refactor CampaignService** (يومان)
   ```php
   // قبل
   class CampaignService {
       public function findRelated($id, $limit) {
           return DB::select('SELECT * FROM cmis.find_related_campaigns(?, ?)', [$id, $limit]);
       }
   }

   // بعد
   class CampaignService {
       public function __construct(
           private CampaignRepositoryInterface $campaigns,
           private PermissionRepositoryInterface $permissions
       ) {}

       public function findRelated(string $id, int $limit = 5): Collection {
           return $this->campaigns->findRelatedCampaigns($id, $limit);
       }
   }
   ```

2. **إنشاء FormRequests للـCampaign** (يوم)
   ```bash
   app/Http/Requests/Campaign/
   ├── StoreCampaignRequest.php
   ├── UpdateCampaignRequest.php
   ├── FilterCampaignsRequest.php
   └── BulkOperationRequest.php
   ```

3. **إنشاء Resources للـCampaign** (يوم)
   ```bash
   app/Http/Resources/Campaign/
   ├── CampaignResource.php
   ├── CampaignCollection.php
   ├── CampaignDetailResource.php
   └── CampaignSummaryResource.php
   ```

4. **تحديث CampaignController** (يوم ونصف)
   ```php
   class CampaignController extends Controller
   {
       public function __construct(
           private CampaignService $campaignService
       ) {}

       public function index(FilterCampaignsRequest $request, string $orgId)
       {
           $campaigns = $this->campaignService->getFiltered(
               $orgId,
               $request->validated()
           );

           return CampaignCollection::make($campaigns);
       }
   }
   ```

**مخرجات Sprint 1.2:**
- ✅ CampaignService معدّل بالكامل
- ✅ 4 FormRequests + 4 Resources
- ✅ CampaignController محدّث
- ✅ Feature tests للـAPI endpoints

---

#### Sprint 1.3: دمج 5 Repositories إضافية
**الهدف:** تعميم النمط على خدمات حرجة أخرى

**المهام:**
1. **ContextService + ContextController** (يومان)
   - Refactor لاستخدام `ContextRepository`
   - FormRequests + Resources

2. **CreativeService + CreativeAssetController** (يومان)
   - Refactor لاستخدام `CreativeRepository`
   - FormRequests + Resources

3. **PermissionService** (يوم)
   - Refactor لاستخدام `PermissionRepository`
   - Middleware محدّث

4. **OperationsService** (يوم ونصف)
   - Refactor لاستخدام `OperationsRepository`
   - Console commands محدّثة

5. **AuditService** (يوم ونصف)
   - Refactor لاستخدام `AuditRepository`
   - Admin dashboard محدّث

**مخرجات Sprint 1.3:**
- ✅ 5 Services معدّلة
- ✅ 3 Controllers محدّثة
- ✅ 15+ FormRequests/Resources جديدة
- ✅ Feature tests

---

#### Sprint 1.4: تنظيف Embedding Services
**الهدف:** حل التداخل في خدمات التضمينات

**المهام:**
1. **تحليل الملفات الموجودة** (نصف يوم)
   - `Services/CMIS/GeminiEmbeddingService.php`
   - `Services/EmbeddingService.php`
   - `Services/Gemini/EmbeddingService.php`
   - `Repositories/Knowledge/EmbeddingRepository.php`

2. **إنشاء هيكل موحد** (يوم ونصف)
   ```
   استراتيجية مقترحة:
   - EmbeddingRepository: DB operations only (pgvector)
   - ExternalEmbeddingService: External API calls (Gemini/OpenAI)
   - EmbeddingOrchestrator: ينسق بينهما
   ```

3. **Refactor الكود الحالي** (يوم ونصف)
4. **حذف الملفات المكررة** (نصف يوم)

**مخرجات Sprint 1.4:**
- ✅ بنية واضحة للـEmbeddings
- ✅ حذف 2-3 ملفات مكررة
- ✅ Documentation لاستراتيجية Embeddings

---

### المرحلة 2: جدولة المحتوى (Buffer-Style) (4 أسابيع)

#### Sprint 2.1: Queue per-Channel
**الهدف:** تمكين كل حساب من وضع أوقات نشر افتراضية

**المهام:**
1. **Database Schema** (يوم)
   ```sql
   CREATE TABLE publishing_queues (
       queue_id UUID PRIMARY KEY,
       org_id UUID NOT NULL,
       social_account_id UUID NOT NULL,
       weekdays_enabled BIT(7), -- MTWTFSS
       time_slots JSONB, -- [{time: "09:00", enabled: true}, ...]
       timezone VARCHAR(50),
       is_active BOOLEAN DEFAULT true,
       created_at TIMESTAMP,
       updated_at TIMESTAMP
   );
   ```

2. **PublishingQueue Model + Repository** (يوم)
3. **QueueService** (يوم ونصف)
4. **API Endpoints** (يوم ونصف)
   - `GET /api/orgs/{orgId}/publishing-queues`
   - `POST /api/orgs/{orgId}/publishing-queues`
   - `PUT /api/orgs/{orgId}/publishing-queues/{queueId}`

**مخرجات Sprint 2.1:**
- ✅ Publishing Queues Database
- ✅ CRUD API
- ✅ Tests

---

#### Sprint 2.2: Bulk Compose
**الهدف:** إنشاء عدة منشورات دفعة واحدة

**المهام:**
1. **تحديث SocialPost Schema** (يوم)
   - إضافة `bulk_batch_id` UUID
   - إضافة `platform_customizations` JSONB

2. **BulkComposeService** (يومان)
   ```php
   public function createBulkPosts(
       string $orgId,
       array $baseContent,
       array $platforms,
       array $scheduleTimes
   ): Collection;
   ```

3. **API Endpoint** (يوم ونصف)
   - `POST /api/orgs/{orgId}/social-posts/bulk`

4. **Frontend: Bulk Composer UI** (يومان - إذا كان ضمن النطاق)

**مخرجات Sprint 2.2:**
- ✅ Bulk posting API
- ✅ Platform-specific customizations
- ✅ Tests

---

#### Sprint 2.3: "أفضل توقيت" (AI-Suggested Timing)
**الهدف:** اقتراح أفضل أوقات النشر بناءً على البيانات

**المهام:**
1. **BestTimeAnalyzer Service** (يومان)
   ```php
   public function analyzeBestTimes(
       string $socialAccountId,
       int $lookbackDays = 30
   ): array {
       // 1. جلب أداء المنشورات السابقة
       $metrics = $this->analyticsRepo->getPostPerformance(...);

       // 2. تجميع حسب يوم الأسبوع والوقت
       // 3. حساب Engagement Rate لكل slot
       // 4. ترتيب وإرجاع Top 5 slots
   }
   ```

2. **دمج مع AIAnalyticsRepository** (يوم)
   - استخدام `recommendFocus()` للتوصيات المتقدمة

3. **API Endpoint** (يوم)
   - `GET /api/social-accounts/{accountId}/best-times`

**مخرجات Sprint 2.3:**
- ✅ Best time recommendations
- ✅ Integration with queue settings
- ✅ Tests

---

#### Sprint 2.4: Approval Workflow
**الهدف:** مسار موافقات بسيط (Creator → Reviewer → Publisher)

**المهام:**
1. **Database Schema** (يوم)
   ```sql
   CREATE TABLE post_approvals (
       approval_id UUID PRIMARY KEY,
       post_id UUID NOT NULL,
       requested_by UUID NOT NULL,
       assigned_to UUID,
       status VARCHAR(20), -- pending/approved/rejected
       comments TEXT,
       reviewed_at TIMESTAMP,
       created_at TIMESTAMP
   );
   ```

2. **ApprovalWorkflow Service** (يومان)
3. **Notifications** (يوم ونصف)
   - إشعار للمراجع عند طلب موافقة
   - إشعار للمُنشئ عند الموافقة/الرفض

4. **API Endpoints** (يوم ونصف)
   - `POST /api/posts/{postId}/request-approval`
   - `POST /api/posts/{postId}/approve`
   - `POST /api/posts/{postId}/reject`

**مخرجات Sprint 2.4:**
- ✅ Approval workflow
- ✅ Email/in-app notifications
- ✅ Tests

---

### المرحلة 3: التحليلات الواضحة (4 أسابيع)

#### Sprint 3.1: Dashboard Redesign
**الهدف:** لوحة KPIs واضحة ومنظمة

**المهام:**
1. **تحليل احتياجات المستخدم** (نصف يوم)
   - ما المقاييس الأهم؟
   - ما الفترات الزمنية المطلوبة؟

2. **Backend: DashboardService** (يومان)
   ```php
   public function getAccountDashboard(string $accountId, array $filters): array
   {
       return [
           'followers' => [
               'current' => $current,
               'growth' => $growth,
               'trend' => 'up/down',
           ],
           'engagement' => [...],
           'reach' => [...],
           'top_posts' => [...],
           'best_times' => [...],
       ];
   }
   ```

3. **API Endpoints** (يوم)
   - `GET /api/analytics/accounts/{accountId}/dashboard`
   - `GET /api/analytics/orgs/{orgId}/overview`

4. **Frontend Components** (3 أيام - إذا ضمن النطاق)
   - KPI Cards
   - Charts (Line, Bar, Sparklines)
   - Top Posts Table

**مخرجات Sprint 3.1:**
- ✅ Restructured analytics API
- ✅ Dashboard endpoints
- ✅ Tests

---

#### Sprint 3.2: Content Performance Analysis
**الهدف:** تحليل المحتوى حسب النوع/الوسائط/الهاشتاق

**المهام:**
1. **تحليل نوع المحتوى** (يوم ونصف)
   ```php
   public function analyzeByContentType(
       string $accountId,
       DateRange $range
   ): array {
       // Image vs Video vs Link vs Text
       return [
           'image' => ['count' => X, 'avg_engagement' => Y],
           'video' => [...],
           ...
       ];
   }
   ```

2. **تحليل الهاشتاقات** (يوم ونصف)
3. **تحليل طول المحتوى** (يوم)
4. **API Endpoints** (يوم)

**مخرجات Sprint 3.2:**
- ✅ Content analysis endpoints
- ✅ Hashtag performance tracking
- ✅ Tests

---

#### Sprint 3.3: AI Insights
**الهدف:** تفسيرات تلقائية ("لماذا ارتفع؟" / "ماذا أفعل؟")

**المهام:**
1. **InsightGenerator Service** (يومان)
   ```php
   public function generateInsights(array $metrics): array
   {
       $insights = [];

       // مثال: اكتشاف نمو غير عادي
       if ($metrics['follower_growth'] > $avgGrowth * 1.5) {
           $insights[] = [
               'type' => 'unusual_growth',
               'message' => 'نمو استثنائي في المتابعين (+25%) بسبب منشور فيديو يوم الثلاثاء',
               'action' => 'انشر المزيد من الفيديوهات أيام الثلاثاء',
           ];
       }

       return $insights;
   }
   ```

2. **دمج مع KnowledgeRepository** (يوم)
   - استخدام `autoAnalyzeKnowledge()` للتحليل الدلالي

3. **API Endpoint** (يوم)
   - `GET /api/analytics/accounts/{accountId}/insights`

**مخرجات Sprint 3.3:**
- ✅ AI-generated insights
- ✅ Actionable recommendations
- ✅ Tests

---

#### Sprint 3.4: PDF Reports
**الهدف:** تقارير PDF قابلة للتنزيل

**المهام:**
1. **اختيار Library** (نصف يوم)
   - DomPDF vs Snappy (wkhtmltopdf)

2. **ReportGenerator Service** (يومان)
   ```php
   public function generatePDF(
       string $type, // account/org/campaign
       string $id,
       DateRange $range
   ): string {
       // Generate PDF and return file path
   }
   ```

3. **Template Design** (يوم ونصف)
   - Header with logo
   - KPIs summary
   - Charts
   - Top posts table

4. **API Endpoint** (يوم)
   - `POST /api/reports/generate`

**مخرجات Sprint 3.4:**
- ✅ PDF report generation
- ✅ Beautiful templates
- ✅ Tests

---

### المرحلة 4: الحملات الإعلانية الموحدة (6 أسابيع)

#### Sprint 4.1: Unified Campaign Builder
**الهدف:** واجهة موحدة لإنشاء حملات عبر منصات متعددة

**المهام:**
1. **CampaignOrchestrator Service** (3 أيام)
   ```php
   public function createMultiPlatformCampaign(
       string $orgId,
       array $platforms, // ['meta', 'google']
       array $campaignData
   ): array {
       $results = [];

       foreach ($platforms as $platform) {
           $connector = ConnectorFactory::make($platform);
           $result = $connector->createCampaign($campaignData);
           $results[$platform] = $result;
       }

       return $results;
   }
   ```

2. **تحديث Connectors** (3 أيام)
   - تطبيق `PlatformConnectorInterface` موحدة
   - Normalize inputs/outputs

3. **API Endpoints** (2 أيام)
   - `POST /api/orgs/{orgId}/ad-campaigns`
   - `GET /api/orgs/{orgId}/ad-campaigns`
   - `PUT /api/ad-campaigns/{campaignId}`

**مخرجات Sprint 4.1:**
- ✅ Multi-platform campaign creation
- ✅ Unified connector interface
- ✅ Tests

---

#### Sprint 4.2: Audience Templates
**الهدف:** مكتبة جماهير قابلة لإعادة الاستخدام

**المهام:**
1. **Database Schema** (يوم)
   ```sql
   CREATE TABLE audience_templates (
       template_id UUID PRIMARY KEY,
       org_id UUID NOT NULL,
       name VARCHAR(255),
       description TEXT,
       targeting_criteria JSONB,
       platforms VARCHAR[] DEFAULT '{meta,google}',
       created_at TIMESTAMP
   );
   ```

2. **AudienceTemplate Model + Service** (يومان)
3. **API CRUD** (يوم ونصف)
4. **تطبيق في Campaign Builder** (يوم ونصف)

**مخرجات Sprint 4.2:**
- ✅ Audience template library
- ✅ Reusable across campaigns
- ✅ Tests

---

#### Sprint 4.3: A/B Testing (Simplified)
**الهدف:** اختبار A/B مبسط (2-3 variants max)

**المهام:**
1. **Database Schema** (يوم)
   ```sql
   CREATE TABLE ad_variants (
       variant_id UUID PRIMARY KEY,
       campaign_id UUID NOT NULL,
       variant_type VARCHAR(50), -- creative/copy/audience
       variant_data JSONB,
       budget_allocation DECIMAL(5,2), -- percentage
       is_winner BOOLEAN DEFAULT false,
       created_at TIMESTAMP
   );
   ```

2. **ABTestingService** (2 أيام)
   ```php
   public function createABTest(
       string $campaignId,
       array $variants // max 3
   ): array;

   public function analyzeResults(string $campaignId): array;

   public function declareWinner(string $variantId): void;
   ```

3. **Auto Budget Allocation** (يوم ونصف)
   - بعد 48-72 ساعة، نقل ميزانية للـvariant الأفضل

4. **API Endpoints** (يوم ونصف)

**مخرجات Sprint 4.3:**
- ✅ A/B testing framework
- ✅ Auto-optimization (optional)
- ✅ Tests

---

#### Sprint 4.4: استكمال الموصلات
**الهدف:** إكمال LinkedIn, X, TikTok, Snapchat

**المهام:**
1. **LinkedInAdsConnector** (2 أيام)
2. **XAdsConnector** (2 أيام)
3. **TikTokAdsConnector** (2 أيام)
4. **SnapchatAdsConnector** (2 أيام)
5. **Integration Testing** (2 أيام)

**مخرجات Sprint 4.4:**
- ✅ 4 connectors إضافية
- ✅ دعم 6 منصات رئيسية
- ✅ Integration tests

---

#### Sprint 4.5: UTM Management
**الهدف:** إدارة وتطبيق UTM تلقائياً

**المهام:**
1. **Database Schema** (يوم)
   ```sql
   CREATE TABLE utm_templates (
       template_id UUID PRIMARY KEY,
       org_id UUID NOT NULL,
       name VARCHAR(255),
       utm_source VARCHAR(100),
       utm_medium VARCHAR(100),
       utm_campaign VARCHAR(100),
       utm_term VARCHAR(100),
       utm_content VARCHAR(100),
       created_at TIMESTAMP
   );
   ```

2. **UtmService** (يوم ونصف)
   ```php
   public function applyUTM(string $url, array $params): string;
   ```

3. **Auto-apply في Campaign Builder** (يوم)
4. **API Endpoints** (يوم ونصف)

**مخرجات Sprint 4.5:**
- ✅ UTM template library
- ✅ Auto-application
- ✅ Tests

---

#### Sprint 4.6: Budget Optimization
**الهدف:** اقتراحات ذكية لتوزيع الميزانية

**المهام:**
1. **BudgetOptimizer Service** (2 أيام)
   ```php
   public function suggestReallocation(
       string $campaignId
   ): array {
       // تحليل أداء الـadsets/variants
       // اقتراح نقل X% من أقل أداء لأعلى أداء
   }
   ```

2. **Job: OptimizeBudgetJob** (يوم)
   - يعمل يومياً
   - يرسل توصيات للمستخدم

3. **API Endpoint** (يوم)
   - `GET /api/ad-campaigns/{campaignId}/budget-suggestions`

**مخرجات Sprint 4.6:**
- ✅ Budget optimization suggestions
- ✅ Auto-notifications
- ✅ Tests

---

### المرحلة 5: التعاون والأتمتة (4 أسابيع)

#### Sprint 5.1: Enhanced Roles & Permissions
**الهدف:** أدوار واضحة ومبسطة

**المهام:**
1. **تحديد الأدوار** (نصف يوم)
   - Owner (كامل الصلاحيات)
   - Editor (إنشاء/تعديل/حذف)
   - Reviewer (موافقة فقط)
   - Viewer (قراءة فقط)

2. **تحديث Policies** (يوم ونصف)
3. **UI للإدارة** (2 أيام - إذا ضمن النطاق)

**مخرجات Sprint 5.1:**
- ✅ 4 أدوار واضحة
- ✅ Updated policies
- ✅ Tests

---

#### Sprint 5.2: Unified Inbox (مبسط)
**الهدف:** صندوق واحد للرسائل والتعليقات المهمة

**المهام:**
1. **Database Schema** (يوم)
   ```sql
   CREATE TABLE inbox_items (
       item_id UUID PRIMARY KEY,
       org_id UUID NOT NULL,
       social_account_id UUID NOT NULL,
       item_type VARCHAR(50), -- comment/message/mention
       platform VARCHAR(50),
       external_id VARCHAR(255),
       content TEXT,
       sender_name VARCHAR(255),
       needs_reply BOOLEAN DEFAULT true,
       assigned_to UUID,
       status VARCHAR(20), -- unread/replied/archived
       replied_at TIMESTAMP,
       created_at TIMESTAMP
   );
   ```

2. **InboxService** (2 أيام)
3. **Sync Jobs** (2 أيام)
   - `SyncFacebookCommentsJob`
   - `SyncInstagramDMsJob`
   - etc.

4. **API Endpoints** (يوم ونصف)
   - `GET /api/orgs/{orgId}/inbox`
   - `POST /api/inbox/{itemId}/reply`
   - `PUT /api/inbox/{itemId}/archive`

**مخرجات Sprint 5.2:**
- ✅ Unified inbox
- ✅ Reply functionality
- ✅ Tests

---

#### Sprint 5.3: Autopilot (اختياري)
**الهدف:** خطة نشر أسبوعية مقترحة تلقائياً

**المهام:**
1. **AutopilotEngine Service** (3 أيام)
   ```php
   public function generateWeeklyPlan(string $orgId): array
   {
       // 1. تحليل أداء سابق
       // 2. اقتراح 7 أوقات نشر
       // 3. توليد 3 أفكار محتوى (AI)
       // 4. اقتراح 2 حملات إعلانية

       return [
           'posts' => [...],
           'campaigns' => [...],
           'estimated_reach' => X,
       ];
   }
   ```

2. **دمج مع MarketingRepository** (يوم)
   - `generateCreativeContent()`

3. **API Endpoints** (يوم ونصف)
   - `POST /api/orgs/{orgId}/autopilot/generate-plan`
   - `POST /api/orgs/{orgId}/autopilot/approve-plan`

**مخرجات Sprint 5.3:**
- ✅ AI-generated weekly plans
- ✅ User approval workflow
- ✅ Tests

---

#### Sprint 5.4: Team Collaboration Features
**الهدف:** ملاحظات، مهام، mentions

**المهام:**
1. **Database Schema** (يوم)
   ```sql
   CREATE TABLE team_comments (
       comment_id UUID PRIMARY KEY,
       entity_type VARCHAR(50), -- post/campaign/creative
       entity_id UUID NOT NULL,
       user_id UUID NOT NULL,
       comment TEXT,
       mentions UUID[], -- user IDs mentioned
       created_at TIMESTAMP
   );

   CREATE TABLE team_tasks (
       task_id UUID PRIMARY KEY,
       org_id UUID NOT NULL,
       title VARCHAR(255),
       description TEXT,
       assigned_to UUID,
       due_date TIMESTAMP,
       status VARCHAR(20), -- pending/completed
       created_by UUID,
       created_at TIMESTAMP
   );
   ```

2. **Services** (2 أيام)
3. **Notifications** (يوم ونصف)
4. **API Endpoints** (يوم ونصف)

**مخرجات Sprint 5.4:**
- ✅ Team comments
- ✅ Task management
- ✅ Mentions & notifications
- ✅ Tests

---

### المرحلة 6: التحسينات والتثبيت (4 أسابيع)

#### Sprint 6.1: Performance Optimization
**المهام:**
1. Query optimization (N+1 problems)
2. Caching strategy (Redis)
3. Job queue optimization
4. Database indexing

---

#### Sprint 6.2: Testing & Quality
**المهام:**
1. رفع Test Coverage إلى 70%
2. Integration tests لكل Connector
3. E2E tests للـflows الحرجة
4. Performance tests

---

#### Sprint 6.3: Documentation
**المهام:**
1. API Documentation (OpenAPI/Swagger)
2. Architecture Decision Records (ADRs)
3. Developer guide
4. User guide

---

#### Sprint 6.4: Security Audit
**المهام:**
1. OWASP Top 10 review
2. API rate limiting
3. Secrets management audit
4. Compliance review (GDPR, etc.)

---

## المتطلبات التقنية

### البنية التحتية
```yaml
Laravel: ^10.0
PHP: ^8.2
PostgreSQL: ^15.0 (with pgvector extension)
Redis: ^7.0 (for caching & queues)
Node.js: ^18.0 (for frontend build)
```

### المكتبات الجديدة المطلوبة
```bash
# PDF Generation
composer require barryvdh/laravel-dompdf

# API Documentation
composer require darkaonline/l5-swagger

# Testing
composer require --dev pestphp/pest

# Queue monitoring
composer require spatie/laravel-horizon
```

### الخدمات الخارجية
- **Gemini API** (للـAI Insights)
- **Platform APIs**:
  - Meta Graph API
  - Google Ads API
  - LinkedIn Marketing API
  - X (Twitter) API v2
  - TikTok for Business API
  - Snapchat Marketing API

---

## مؤشرات النجاح

### KPIs التقنية
| المؤشر | الهدف | الحالي | الموعد |
|--------|-------|--------|---------|
| Repository Usage | 100% | 25% | M3 |
| Test Coverage | 70% | ~15% | M6 |
| API Response Time (p95) | < 500ms | ~800ms | M4 |
| Code Duplication | < 5% | ~15% | M3 |
| Security Score | A+ | B | M6 |

### KPIs الوظيفية
| المؤشر | الهدف | الموعد |
|--------|-------|---------|
| Time-to-First-Post | < 10 min | M2 |
| Multi-platform Campaign Launch | < 15 min | M4 |
| Weekly Post Rate Increase | +20% | M3 |
| Report Generation Time Saved | 50% | M3 |
| Platform Coverage | 6+ | M4 |

### User Satisfaction
- **NPS Score**: > 50
- **Feature Adoption Rate**: > 60% خلال 30 يوم
- **Churn Rate**: < 5% شهرياً

---

## المخاطر وخطط التخفيف

### مخاطر تقنية

#### 1. حدود APIs للمنصات
**المخاطرة:** تجاوز Rate Limits يؤدي لتعطل الخدمة

**خطة التخفيف:**
- طبقة Rate Limiting لكل Connector
- Queue system مع exponential backoff
- Fallback mechanisms
- Monitoring & alerts

```php
// مثال
class RateLimitedConnector
{
    protected function callAPI(string $endpoint, array $params)
    {
        if ($this->rateLimiter->tooManyAttempts($endpoint)) {
            $seconds = $this->rateLimiter->availableIn($endpoint);
            throw new RateLimitException("Retry after {$seconds}s");
        }

        $this->rateLimiter->hit($endpoint);
        return $this->client->post($endpoint, $params);
    }
}
```

---

#### 2. تعقيد الهجرة من DB Direct إلى Repositories
**المخاطرة:** Breaking changes في الكود الحالي

**خطة التخفيف:**
- Incremental migration (service by service)
- Comprehensive testing لكل migration
- Feature flags للتبديل بين القديم والجديد
- Rollback plan

---

#### 3. أداء قاعدة البيانات
**المخاطرة:** بطء Queries مع زيادة البيانات

**خطة التخفيف:**
- Database indexing strategy
- Query optimization (EXPLAIN ANALYZE)
- Caching layer (Redis)
- Read replicas للتحليلات

---

### مخاطر وظيفية

#### 1. اختلاف APIs بين المنصات
**المخاطرة:** صعوبة توحيد واجهة الإنشاء

**خطة التخفيف:**
- Adapter pattern لكل منصة
- Normalization layer
- Clear documentation للحدود

---

#### 2. تعقيد UI
**المخاطرة:** واجهة معقدة تُبعد المستخدمين

**خطة التخفيف:**
- User testing في كل Sprint
- Progressive disclosure (إخفاء الميزات المتقدمة افتراضياً)
- Templates & wizards

---

#### 3. موثوقية المزامنة
**المخاطرة:** بيانات غير دقيقة/متأخرة

**خطة التخفيف:**
- Retry logic قوي
- Monitoring & alerting
- Manual sync trigger للمستخدم
- Status indicators واضحة

---

## الملاحق

### أ) Architecture Decision Records (نماذج)

#### ADR-001: Repository Pattern
**القرار:** استخدام Repository Pattern لجميع DB operations

**السياق:**
- كود حالي يخلط Business Logic و Data Access
- صعوبة الاختبار
- تكرار SQL queries

**البدائل:**
1. Active Record (Eloquent فقط)
2. Data Mapper (Repositories)
3. Query Builder مباشر

**القرار:** Data Mapper (Repositories)

**العواقب:**
- ✅ فصل واضح للمسؤوليات
- ✅ سهولة الاختبار
- ✅ إعادة استخدام Queries
- ❌ طبقة إضافية (overhead بسيط)

---

#### ADR-002: Embedding Strategy
**القرار:** فصل External Embedding Service عن DB Vector Ops

**السياق:**
- تداخل 3 ملفات لنفس الغرض
- عدم وضوح مصدر الحقيقة

**البدائل:**
1. خدمة واحدة تفعل كل شيء
2. فصل External vs DB
3. الاعتماد على DB functions فقط

**القرار:** الخيار 2 (فصل External vs DB)

**البنية:**
```
ExternalEmbeddingService (Gemini/OpenAI API)
    ↓
EmbeddingOrchestrator (ينسق)
    ↓
EmbeddingRepository (pgvector operations)
```

---

### ب) قائمة الـEndpoints المطلوبة

```yaml
# Content Scheduling
POST   /api/orgs/{orgId}/social-posts
GET    /api/orgs/{orgId}/social-posts
PUT    /api/social-posts/{postId}
DELETE /api/social-posts/{postId}
POST   /api/social-posts/bulk
GET    /api/orgs/{orgId}/publishing-queues
POST   /api/posts/{postId}/request-approval
POST   /api/posts/{postId}/approve

# Analytics
GET    /api/analytics/accounts/{accountId}/dashboard
GET    /api/analytics/orgs/{orgId}/overview
GET    /api/analytics/content-performance
GET    /api/analytics/accounts/{accountId}/insights
POST   /api/reports/generate

# Ad Campaigns
POST   /api/orgs/{orgId}/ad-campaigns
GET    /api/orgs/{orgId}/ad-campaigns
PUT    /api/ad-campaigns/{campaignId}
POST   /api/ad-campaigns/{campaignId}/ab-test
GET    /api/ad-campaigns/{campaignId}/budget-suggestions
GET    /api/audience-templates
POST   /api/audience-templates

# Collaboration
GET    /api/orgs/{orgId}/inbox
POST   /api/inbox/{itemId}/reply
POST   /api/team/comments
POST   /api/team/tasks
POST   /api/orgs/{orgId}/autopilot/generate-plan
```

---

### ج) Database Migration Plan

**الاستراتيجية:** Database-first مع version control للـSchema

**الخطوات:**
1. **توثيق الـSchema الحالي** (نصف يوم)
   - تصدير `schema.sql` الحالي
   - توثيق جميع الدوال والـTriggers

2. **إنشاء Migration Strategy** (يوم)
   ```bash
   database/
   ├── migrations/        # Laravel migrations (for new tables)
   ├── schema/
   │   ├── functions/     # PostgreSQL functions
   │   ├── triggers/      # Triggers
   │   ├── views/         # Views
   │   └── schema.sql     # Full schema dump (versioned)
   └── seeders/
   ```

3. **Verification Command** (يوم)
   ```bash
   php artisan cmis:verify-schema
   ```
   - يتحقق من وجود جميع الـSchemas
   - يتحقق من وجود جميع الدوال
   - يُصلح تلقائياً (أو يُنبه)

---

### د) Testing Strategy

#### Unit Tests (40% coverage)
- Repositories (mock DB)
- Services (mock Repositories)
- Validators

#### Feature Tests (25% coverage)
- API Endpoints
- Workflows (publish, approve, sync)

#### Integration Tests (5% coverage)
- Platform Connectors (sandbox APIs)
- Jobs (queue processing)

**Tools:**
- Pest PHP (testing framework)
- Mockery (mocking)
- Laravel Dusk (E2E - optional)

---

## الخاتمة

هذه الخطة توفر:
1. ✅ **حل شامل للمشاكل التقنية** (Repositories, DI, clean architecture)
2. ✅ **رؤية واضحة للمنتج** (Buffer + Hootsuite + AdEspresso)
3. ✅ **خارطة طريق قابلة للتنفيذ** (24 أسبوع / 6 أشهر)
4. ✅ **مؤشرات نجاح واضحة** (KPIs تقنية ووظيفية)
5. ✅ **خطط للمخاطر** (التخفيف والاحتواء)

### الخطوات التالية الفورية

**الأسبوع القادم (Sprint 1.1):**
1. إنشاء Repository Interfaces (15 interface)
2. تحديث AppServiceProvider (bindings)
3. إصلاح خطأ CMISEmbeddingServiceProvider
4. كتابة Unit tests للـBindings

**بعد شهر (M1 Milestone):**
- ✅ 8 Repositories محقونة ومستخدمة
- ✅ CampaignService نموذج مثالي
- ✅ 20+ FormRequests/Resources
- ✅ Test Coverage > 30%

**بعد 3 أشهر (M3 Milestone):**
- ✅ جدولة محتوى كاملة الميزات
- ✅ تحليلات واضحة + PDF reports
- ✅ AI insights v1
- ✅ Repository usage 100%

**بعد 6 أشهر (M6 Milestone):**
- ✅ دعم 6 منصات كاملة
- ✅ Unified campaign builder
- ✅ A/B testing
- ✅ Team collaboration
- ✅ Test coverage 70%
- ✅ Production-ready

---

**تاريخ آخر تحديث:** 2024-11-13
**المسؤول:** Development Team
**المراجع التالي:** كل أسبوعين (Sprint Review)
