# خطة عمل شاملة لبناء نظام الاختبارات

## النظرة العامة

**الهدف:** رفع نسبة تغطية الاختبارات من 1.4% إلى 70%+ في 6 أشهر

**الحالة الحالية:**
- 770+ ملف بدون اختبارات (Models, Controllers, Services, Jobs, Repositories, Views)
- فقط 35 ملف اختبار موجود
- نسبة تغطية: ~1.4% فقط

**المراحل:**

---

## المرحلة 1: الأساسيات (الأسبوع 1-2)

### 1.1 إعداد بيئة الاختبار
- [ ] مراجعة وتحديث `phpunit.xml`
- [ ] إعداد قاعدة بيانات اختبار منفصلة
- [ ] تكوين environment متغيرات الاختبار
- [ ] تثبيت أي مكتبات اختبار مفقودة
- [ ] إعداد test database seeding

### 1.2 بناء Base Test Classes
- [ ] تحسين `tests/TestCase.php`
- [ ] إنشاء `AuthenticatedTestCase.php` لـ authenticated requests
- [ ] إنشاء `APITestCase.php` لـ API tests
- [ ] إنشاء `FeatureTestCase.php` لـ feature tests

### 1.3 تحسين Test Traits
- [ ] تحسين `CreatesTestData.php` - إضافة factories لجميع Models الرئيسية
- [ ] تحسين `InteractsWithRLS.php` - دعم كامل لـ Row Level Security
- [ ] تحسين `MocksExternalAPIs.php` - mock لجميع integrations الخارجية

---

## المرحلة 2: اختبارات Models (الأسبوع 2-4)

### 2.1 اختبارات Models الحرجة (Priority High)
```
الهدف: اختبار 50 model أساسي
المدة: 2-3 أسابيع
```

**Priority 1: Core Models (الأسبوع الأول)**
- [ ] /tests/Unit/Models/Campaign/CampaignTest.php
- [ ] /tests/Unit/Models/User/UserTest.php ✅ (exists - improve)
- [ ] /tests/Unit/Models/Offering/OfferingTest.php
- [ ] /tests/Unit/Models/Channel/ChannelTest.php
- [ ] /tests/Unit/Models/CreativeAsset/CreativeAssetTest.php
- [ ] /tests/Unit/Models/SocialPost/SocialPostTest.php
- [ ] /tests/Unit/Models/SocialAccount/SocialAccountTest.php
- [ ] /tests/Unit/Models/Notification/NotificationTest.php
- [ ] /tests/Unit/Models/Core/OrgTest.php
- [ ] /tests/Unit/Models/Core/UserOrgTest.php
- [ ] /tests/Unit/Models/Core/IntegrationTest.php
- [ ] /tests/Unit/Models/Core/RoleTest.php

**Priority 2: Ad Platform Models (الأسبوع الثاني)**
- [ ] /tests/Unit/Models/AdPlatform/AdAccountTest.php
- [ ] /tests/Unit/Models/AdPlatform/AdCampaignTest.php
- [ ] /tests/Unit/Models/AdPlatform/AdSetTest.php
- [ ] /tests/Unit/Models/AdPlatform/AdAudienceTest.php
- [ ] /tests/Unit/Models/AdPlatform/AdMetricTest.php

**Priority 3: Creative Models (الأسبوع الثاني)**
- [ ] /tests/Unit/Models/Creative/CreativeBriefTest.php
- [ ] /tests/Unit/Models/Creative/ContentItemTest.php
- [ ] /tests/Unit/Models/Creative/ContentPlanTest.php
- [ ] /tests/Unit/Models/Creative/VideoTemplateTest.php

**Priority 4: Analytics Models (الأسبوع الثالث)**
- [ ] /tests/Unit/Models/Analytics/CampaignAnalyticsTest.php
- [ ] /tests/Unit/Models/Analytics/KpiTargetTest.php
- [ ] /tests/Unit/Models/Analytics/AnalyticsIntegrationTest.php

**Priority 5: Knowledge Models (الأسبوع الثالث)**
- [ ] /tests/Unit/Models/Knowledge/KnowledgeIndexTest.php
- [ ] /tests/Unit/Models/Knowledge/EmbeddingsCacheTest.php
- [ ] /tests/Unit/Models/Knowledge/SemanticSearchLogTest.php

### 2.2 اختبارات Relationships و Scopes
- [ ] لكل model اختبر:
  - Has many relationships
  - Belongs to relationships
  - Many to many relationships
  - Scopes (if any)
  - Casts
  - Accessors/Mutators

### 2.3 اختبارات Validation
- [ ] اختبر validation rules لكل model
- [ ] اختبر custom validation

---

## المرحلة 3: اختبارات Services (الأسبوع 4-6)

### 3.1 Core Services (Priority High)
```
الهدف: اختبار 30 service أساسي
المدة: 2-3 أسابيع
```

**Priority 1: Campaign Services**
- [ ] /tests/Unit/Services/CampaignService/CampaignServiceTest.php ✅ (improve)
- [ ] /tests/Unit/Services/CampaignOrchestratorServiceTest.php
- [ ] /tests/Unit/Services/CampaignAnalyticsServiceTest.php

**Priority 2: AI Services**
- [ ] /tests/Unit/Services/AIServiceTest.php
- [ ] /tests/Unit/Services/AIInsightsServiceTest.php
- [ ] /tests/Unit/Services/AIAutomationServiceTest.php
- [ ] /tests/Unit/Services/CMIS/GeminiEmbeddingServiceTest.php
- [ ] /tests/Unit/Services/CMIS/KnowledgeEmbeddingProcessorTest.php
- [ ] /tests/Unit/Services/CMIS/SemanticSearchServiceTest.php

**Priority 3: Publishing Services**
- [ ] /tests/Unit/Services/PublishingServiceTest.php
- [ ] /tests/Unit/Services/PublishingQueueServiceTest.php
- [ ] /tests/Unit/Services/BulkPostServiceTest.php

**Priority 4: Social Services**
- [ ] /tests/Unit/Services/Social/FacebookSyncServiceTest.php ✅ (improve)
- [ ] /tests/Unit/Services/Social/InstagramSyncServiceTest.php
- [ ] /tests/Unit/Services/Social/AbstractSocialServiceTest.php

**Priority 5: Ad Services**
- [ ] /tests/Unit/Services/Ads/MetaAdsServiceTest.php
- [ ] /tests/Unit/Services/AdCampaignServiceTest.php
- [ ] /tests/Unit/Services/AdCreativeServiceTest.php

**Priority 6: Connector Services**
- [ ] /tests/Unit/Services/Connectors/ConnectorFactoryTest.php
- [ ] /tests/Unit/Services/Connectors/Providers/MetaConnectorTest.php
- [ ] /tests/Unit/Services/Connectors/Providers/GoogleConnectorTest.php

### 3.2 Test Coverage لـ Services
- [ ] تجميع Public Methods
- [ ] اختبر Success Cases
- [ ] اختبر Exception Cases
- [ ] اختبر External API Calls (mocked)
- [ ] اختبر Database Transactions

---

## المرحلة 4: اختبارات Controllers (الأسبوع 6-10)

### 4.1 API Controllers (Priority High)
```
الهدف: اختبار 40 controller
المدة: 3-4 أسابيع
```

**Priority 1: Core API Controllers**
- [ ] /tests/Feature/API/Core/OrgControllerTest.php
- [ ] /tests/Feature/API/Core/UserControllerTest.php
- [ ] /tests/Feature/API/Auth/AuthControllerTest.php
- [ ] /tests/Feature/API/Auth/LoginControllerTest.php

**Priority 2: Campaign Controllers**
- [ ] /tests/Feature/API/CampaignAPIControllerTest.php ✅ (improve)
- [ ] /tests/Feature/API/Campaigns/CampaignControllerTest.php
- [ ] /tests/Feature/API/AdCampaignControllerTest.php

**Priority 3: Analytics Controllers**
- [ ] /tests/Feature/API/AnalyticsControllerTest.php
- [ ] /tests/Feature/API/Analytics/KpiControllerTest.php
- [ ] /tests/Feature/API/CampaignAnalyticsControllerTest.php

**Priority 4: Creative Controllers**
- [ ] /tests/Feature/API/Creative/CreativeAssetControllerTest.php
- [ ] /tests/Feature/API/CreativeBriefControllerTest.php
- [ ] /tests/Feature/API/AdCreativeControllerTest.php

**Priority 5: Social Controllers**
- [ ] /tests/Feature/API/Social/SocialSchedulerControllerTest.php
- [ ] /tests/Feature/API/UnifiedInboxControllerTest.php
- [ ] /tests/Feature/API/UnifiedCommentsControllerTest.php

**Priority 6: AI Controllers**
- [ ] /tests/Feature/API/AI/AIGenerationControllerTest.php
- [ ] /tests/Feature/API/AI/AIInsightsControllerTest.php

**Priority 7: Webhook Controllers**
- [ ] /tests/Feature/API/WebhookControllerTest.php

### 4.2 Web Controllers
- [ ] /tests/Feature/Web/DashboardControllerTest.php
- [ ] /tests/Feature/Web/Campaigns/CampaignControllerTest.php
- [ ] /tests/Feature/Web/Orgs/OrgControllerTest.php

### 4.3 HTTP Test Guidelines
- [ ] اختبر GET requests
- [ ] اختبر POST/PUT requests
- [ ] اختبر DELETE requests
- [ ] اختبر validation errors
- [ ] اختبر authentication/authorization
- [ ] اختبر response structure
- [ ] اختبر status codes

---

## المرحلة 5: اختبارات Integration (الأسبوع 10-14)

### 5.1 Complete Workflows
```
الهدف: اختبار سيناريوهات نهاية-إلى-نهاية
المدة: 4 أسابيع
```

**Priority 1: Campaign Lifecycle**
- [ ] /tests/Integration/Campaign/CompleteCampaignLifecycleTest.php ✅ (improve)
- [ ] /tests/Integration/Campaign/CampaignPublishingWorkflowTest.php
- [ ] /tests/Integration/Campaign/CampaignAnalyticsWorkflowTest.php

**Priority 2: Ad Platform Workflows**
- [ ] /tests/Integration/AdPlatform/MetaAdsWorkflowTest.php ✅ (improve)
- [ ] /tests/Integration/AdPlatform/GoogleAdsWorkflowTest.php
- [ ] /tests/Integration/AdPlatform/TikTokAdsWorkflowTest.php

**Priority 3: Social Media Workflows**
- [ ] /tests/Integration/Social/SocialMediaPublishingTest.php ✅ (improve)
- [ ] /tests/Integration/Social/FacebookSyncIntegrationTest.php ✅ (improve)
- [ ] /tests/Integration/Social/InstagramSyncWorkflowTest.php ✅ (improve)
- [ ] /tests/Integration/Social/SocialMediaCommentsTest.php ✅ (improve)
- [ ] /tests/Integration/Social/SocialMediaMessagingTest.php ✅ (improve)
- [ ] /tests/Integration/Social/TikTokSyncWorkflowTest.php
- [ ] /tests/Integration/Social/LinkedInSyncWorkflowTest.php

**Priority 4: AI & Knowledge Workflows**
- [ ] /tests/Integration/Knowledge/EmbeddingWorkflowTest.php ✅ (improve)
- [ ] /tests/Integration/Knowledge/SemanticSearchWorkflowTest.php
- [ ] /tests/Integration/Knowledge/KnowledgeProcessingWorkflowTest.php

**Priority 5: Publishing Workflows**
- [ ] /tests/Integration/Publishing/PublishingWorkflowTest.php ✅ (improve)
- [ ] /tests/Integration/Publishing/ApprovalWorkflowTest.php
- [ ] /tests/Integration/Publishing/BulkPublishingWorkflowTest.php

**Priority 6: Team & Onboarding**
- [ ] /tests/Integration/Team/UserOnboardingWorkflowTest.php ✅ (improve)
- [ ] /tests/Integration/Team/TeamCollaborationWorkflowTest.php
- [ ] /tests/Integration/Team/PermissionsWorkflowTest.php

### 5.2 Error & Recovery Scenarios
- [ ] /tests/Integration/ErrorHandling/ErrorRecoveryWorkflowTest.php ✅ (improve)
- [ ] /tests/Integration/ErrorHandling/TransactionRollbackTest.php
- [ ] /tests/Integration/ErrorHandling/ExternalAPISyncFailureTest.php

### 5.3 Complete Flow Test
- [ ] /tests/Integration/CompleteFlow/CompleteMarketingWorkflowTest.php ✅ (improve)
- [ ] /tests/Integration/CompleteFlow/MultiChannelCampaignTest.php
- [ ] /tests/Integration/CompleteFlow/DataIntegrityTest.php

---

## المرحلة 6: اختبارات E2E (Browser Tests) (الأسبوع 14-16)

### 6.1 Setup Playwright
- [ ] تكوين Playwright config
- [ ] إعداد test database للـ E2E
- [ ] تثبيت/تشغيل Playwright

### 6.2 Critical User Journeys
```
الهدف: اختبار أهم سيناريوهات المستخدم
المدة: 2-3 أسابيع
```

**Priority 1: Authentication & Onboarding**
- [ ] /tests/E2E/Auth/LoginFlowTest.spec.ts
- [ ] /tests/E2E/Auth/RegisterFlowTest.spec.ts
- [ ] /tests/E2E/Onboarding/UserOnboardingTest.spec.ts

**Priority 2: Campaign Management**
- [ ] /tests/E2E/Campaigns/CreateCampaignTest.spec.ts
- [ ] /tests/E2E/Campaigns/PublishCampaignTest.spec.ts
- [ ] /tests/E2E/Campaigns/ViewCampaignAnalyticsTest.spec.ts

**Priority 3: Creative Management**
- [ ] /tests/E2E/Creative/CreateBriefTest.spec.ts
- [ ] /tests/E2E/Creative/UploadAssetsTest.spec.ts

**Priority 4: Social Publishing**
- [ ] /tests/E2E/Social/SchedulePostTest.spec.ts
- [ ] /tests/E2E/Social/BulkPublishTest.spec.ts

**Priority 5: Analytics & Reporting**
- [ ] /tests/E2E/Analytics/ViewDashboardTest.spec.ts
- [ ] /tests/E2E/Analytics/GenerateReportTest.spec.ts

---

## المرحلة 7: اختبارات Jobs و Repositories (الأسبوع 16-18)

### 7.1 Jobs Testing
- [ ] /tests/Unit/Jobs/GenerateEmbeddingsJobTest.php
- [ ] /tests/Unit/Jobs/SyncPlatformDataJobTest.php
- [ ] /tests/Unit/Jobs/PublishScheduledPostJobTest.php
- [ ] /tests/Unit/Jobs/SyncMetaAdsJobTest.php
- [ ] /tests/Unit/Jobs/SyncPlatformCampaignsTest.php

### 7.2 Repositories Testing
- [ ] /tests/Unit/Repositories/CampaignRepositoryTest.php ✅ (improve)
- [ ] /tests/Unit/Repositories/CMIS/SocialMediaRepositoryTest.php
- [ ] /tests/Unit/Repositories/Knowledge/KnowledgeRepositoryTest.php
- [ ] /tests/Unit/Repositories/Analytics/AnalyticsRepositoryTest.php

---

## المرحلة 8: تحسين التغطية (الأسبوع 18-20)

### 8.1 Analyze Coverage
- [ ] تشغيل `php artisan test --coverage`
- [ ] تحديد الثغرات المتبقية
- [ ] أولوية الملفات بنسبة تغطية منخفضة

### 8.2 Close Gaps
- [ ] اختبارات إضافية للـ Edge Cases
- [ ] اختبارات للـ Exception Handling
- [ ] اختبارات للـ Boundary Conditions

---

## معايير النجاح

| المرحلة | الهدف | الملفات المختبرة |
|--------|------|-----------------|
| 1 | إعداد البيئة | - |
| 2 | Models | 50+ models |
| 3 | Services | 30+ services |
| 4 | Controllers | 40+ controllers |
| 5 | Integration | 20+ workflows |
| 6 | E2E | 15+ user journeys |
| 7 | Jobs & Repos | 15+ files |
| 8 | Improve Coverage | 70%+ coverage |

---

## أدوات وموارد مساعدة

### Testing Libraries
- PHPUnit (Unit & Feature Tests)
- Pest (Alternative to PHPUnit)
- Mockery (Mocking)
- Faker (Test Data Generation)
- Playwright (E2E Tests)

### Commands
```bash
# تشغيل جميع الاختبارات
php artisan test

# اختبارات محددة
php artisan test --filter=CampaignTest

# مع التغطية
php artisan test --coverage

# E2E Tests
npm run test:e2e

# Specific E2E test
npm run test:e2e -- auth.spec.ts
```

### Best Practices
1. **AAA Pattern**: Arrange, Act, Assert
2. **DRY**: استخدم Helper Methods
3. **Isolation**: كل اختبار مستقل
4. **Speed**: استخدم SQLite في memory للاختبارات
5. **Clarity**: أسماء اختبارات واضحة

---

## Timeline ملخص

| المرحلة | الفترة | التكليفات |
|--------|--------|---------|
| 1 | الأسبوع 1-2 | الإعداد و التكوين |
| 2 | الأسبوع 2-4 | 50+ Model Tests |
| 3 | الأسبوع 4-6 | 30+ Service Tests |
| 4 | الأسبوع 6-10 | 40+ Controller Tests |
| 5 | الأسبوع 10-14 | 20+ Integration Tests |
| 6 | الأسبوع 14-16 | 15+ E2E Tests |
| 7 | الأسبوع 16-18 | 15+ Jobs/Repos Tests |
| 8 | الأسبوع 18-20 | تحسين التغطية |

**المجموع: 5 أشهر تقريباً للوصول إلى 70%+ تغطية**

