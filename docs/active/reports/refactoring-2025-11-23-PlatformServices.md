# Refactoring Report: Platform Services (God Classes)

**Date:** 2025-11-23
**Refactored By:** laravel-refactor-specialist agent
**Target Files:**
- app/Services/AdPlatforms/Google/GoogleAdsPlatform.php
- app/Services/AdPlatforms/LinkedIn/LinkedInAdsPlatform.php
- app/Services/AdPlatforms/TikTok/TikTokAdsPlatform.php

---

## 1. Discovery Phase

### Initial Metrics

#### GoogleAdsPlatform.php
- **Lines of Code:** 2,413
- **Method Count:** 50+ methods
- **Average Method Length:** ~48 lines
- **Complexity Indicators:**
  - Control Structures: 150+
  - Max Nesting Depth: 4
  - Methods >30 lines: 35+
  - Methods >50 lines: 15+
- **Dependencies:** 3 use statements (minimal)
- **Test Coverage:** ❌ **NO TESTS FOUND**

#### LinkedInAdsPlatform.php
- **Lines of Code:** 1,210
- **Method Count:** 30+ methods
- **Average Method Length:** ~40 lines
- **Complexity Indicators:**
  - Control Structures: 80+
  - Max Nesting Depth: 3
  - Methods >30 lines: 20+
- **Dependencies:** 4 use statements
- **Test Coverage:** ❌ **NO TESTS FOUND**

#### TikTokAdsPlatform.php
- **Lines of Code:** 1,097
- **Method Count:** 25+ methods
- **Average Method Length:** ~44 lines
- **Complexity Indicators:**
  - Control Structures: 75+
  - Max Nesting Depth: 3
  - Methods >30 lines: 18+
- **Dependencies:** 3 use statements
- **Test Coverage:** ❌ **NO TESTS FOUND**

### Code Smells Identified

#### 1. God Class (All Three Files)
**Severity:** CRITICAL

All three platform services are massive god classes that violate the Single Responsibility Principle (SRP):

**GoogleAdsPlatform** has 14+ distinct responsibilities:
1. Campaign CRUD operations
2. Ad Group management
3. Keyword management (positive + negative)
4. Ad creation (Responsive Search Ads)
5. Topic targeting
6. Placement targeting
7. Demographic targeting (age, gender, parental status, income)
8. Location & proximity targeting
9. Language targeting
10. Device bid modifiers
11. Ad schedule (day parting)
12. Extensions (9 types: sitelink, callout, structured snippet, call, price, promotion, image, lead form)
13. Audience management (in-market, affinity, custom, remarketing, customer match)
14. Bidding strategies
15. Conversion tracking
16. Data transformation & mapping
17. API client operations
18. Budget management

**LinkedInAdsPlatform** has 10+ distinct responsibilities:
1. Campaign CRUD operations
2. Ad Set/Creative management
3. Sponsored content creation
4. Lead gen form management
5. Targeting builder (comprehensive B2B targeting)
6. Metrics aggregation
7. Data transformation (URN handling)
8. OAuth token refresh
9. RLS context management
10. API client operations

**TikTokAdsPlatform** has 10+ distinct responsibilities:
1. Campaign CRUD operations
2. Ad Group management
3. Ad creation
4. Token management & validation
5. Targeting management (location, age, gender, interests, device, carrier)
6. Creative upload (video, image)
7. Interest categories
8. Data transformation/mapping
9. API client operations
10. Account synchronization

#### 2. Long Methods
**Severity:** HIGH

Examples of methods >50 lines:

**GoogleAdsPlatform:**
- `createCampaign()` - 104 lines (lines 96-200)
- `getCampaignMetrics()` - 36 lines (lines 370-406)
- `createAdSet()` - 38 lines (lines 423-462)
- `addKeywords()` - 34 lines (lines 478-512)
- Extension methods (each 40-80 lines)
- Audience creation methods (50-100 lines)

**LinkedInAdsPlatform:**
- `createCampaign()` - 82 lines (lines 101-182)
- `createAdSet()` - 58 lines (lines 458-516)
- `buildTargeting()` - 88 lines (lines 1009-1096)
- `aggregateMetrics()` - 41 lines (lines 1104-1144)

**TikTokAdsPlatform:**
- `createAdSet()` - 71 lines (lines 453-524)
- `createAd()` - 75 lines (lines 540-614)
- `addTargeting()` - 57 lines (lines 968-1024)

#### 3. Feature Envy
**Severity:** MEDIUM

All platform services extensively manipulate `Integration` model data:
- Extracting credentials
- Decrypting tokens
- Updating token expiration
- Managing metadata

This suggests the need for an `IntegrationCredentialService` to encapsulate credential management.

#### 4. Duplicate Code
**Severity:** HIGH

**Common patterns across all three platforms:**
1. **OAuth Token Refresh Logic** - Nearly identical in all three:
   - Check token expiration
   - Make HTTP request to OAuth endpoint
   - Decrypt/encrypt tokens
   - Update integration model
   - Handle errors

2. **Status Mapping** - All three have similar `mapStatus()` methods:
   - GoogleAdsPlatform: `mapStatus()` (lines 2287-2295)
   - LinkedInAdsPlatform: `mapStatus()` (lines 1200-1209)
   - TikTokAdsPlatform: `mapStatus()` (lines 1089-1096)

3. **Error Response Structure** - All return similar arrays:
   ```php
   return [
       'success' => false,
       'error' => $e->getMessage(),
   ];
   ```

4. **URL Building** - Custom URL builders in each:
   - GoogleAdsPlatform: `buildUrl()` (lines 2149-2155)
   - LinkedInAdsPlatform: inherits from AbstractAdPlatform
   - TikTokAdsPlatform: inherits from AbstractAdPlatform

5. **Data Validation** - Similar validation patterns:
   - GoogleAdsPlatform: `validateCampaignData()` (custom)
   - Others: Use AbstractAdPlatform default

#### 5. Magic Numbers/Strings
**Severity:** MEDIUM

**GoogleAdsPlatform:**
- `$this->apiVersion = 'v15'` - Hardcoded API version
- Budget conversion `* 1000000` - Magic number for micros conversion (appears 20+ times)
- Status strings: 'ENABLED', 'PAUSED', 'REMOVED'
- Campaign types: 'SEARCH', 'DISPLAY', 'SHOPPING', etc.

**LinkedInAdsPlatform:**
- `'LinkedIn-Version' => '202401'` - Hardcoded API version
- Budget conversion `* 100` - Magic number for cents conversion (appears 10+ times)
- URN prefixes: 'urn:li:sponsoredAccount:', 'urn:li:sponsoredCampaign:'

**TikTokAdsPlatform:**
- `$response['code'] == 0` - Magic number for success code (appears 15+ times)
- Budget conversion `* 100` - Magic number for cents conversion
- Status codes without constants

#### 6. Primitive Obsession
**Severity:** MEDIUM

All platforms pass primitive arrays instead of value objects:
- Campaign data passed as raw arrays
- Targeting criteria as arrays
- Budget amounts as floats (should be Money value objects)
- No DTOs (Data Transfer Objects) for structured data

---

## 2. Refactoring Strategy

### CRITICAL BLOCKER: No Test Coverage

**⚠️ REFACTORING CANNOT PROCEED WITHOUT TESTS ⚠️**

According to safe refactoring principles:
- **Rule 1:** NEVER refactor without tests
- **Rule 2:** All tests must pass before refactoring begins
- **Rule 3:** Tests must pass after EVERY change

**Current Status:** 0 tests exist for any platform service.

**Recommended Actions Before Refactoring:**

#### Phase 0: Test Creation (MANDATORY)

1. **Create Characterization Tests** for existing behavior:
   ```
   tests/Unit/Services/AdPlatforms/GoogleAdsPlatformTest.php
   tests/Unit/Services/AdPlatforms/LinkedInAdsPlatformTest.php
   tests/Unit/Services/AdPlatforms/TikTokAdsPlatformTest.php
   ```

2. **Test Coverage Requirements:**
   - All interface methods (14 methods × 3 platforms = 42 test methods minimum)
   - OAuth token refresh logic
   - Error handling paths
   - Rate limiting behavior
   - Data transformation/mapping methods

3. **Use Test Doubles:**
   - Mock HTTP responses from platforms
   - Mock Integration model
   - Mock Cache facade
   - No real API calls in tests

4. **Baseline Tests Must Pass:**
   - Run: `vendor/bin/phpunit --filter=AdPlatform`
   - All tests GREEN before proceeding

### Phase 1: Extract Common Services (IF Tests Pass)

**Only proceed if Phase 0 complete and tests passing.**

#### 1.1: Extract OAuthTokenService
**Pattern:** Extract Class
**Files to Create:**
- `app/Services/AdPlatforms/Common/OAuthTokenService.php`

**Responsibilities:**
- Token expiration checking
- Token refresh coordination
- Encryption/decryption of tokens
- Integration model updates

**Code to Extract:**
- GoogleAdsPlatform: `refreshAccessToken()` (lines 2381-2412)
- LinkedInAdsPlatform: `refreshAccessToken()` (lines 914-1001)
- TikTokAdsPlatform: `refreshAccessToken()` (lines 754-821)

**Lines Saved:** ~200 lines (eliminate duplication)

#### 1.2: Extract CredentialManager
**Pattern:** Extract Class
**Files to Create:**
- `app/Services/AdPlatforms/Common/CredentialManager.php`

**Responsibilities:**
- Extract credentials from Integration
- Decrypt access tokens
- Validate credential presence
- Handle credential metadata

**Lines Saved:** ~150 lines

#### 1.3: Extract ResponseFormatter
**Pattern:** Extract Class
**Files to Create:**
- `app/Services/AdPlatforms/Common/ResponseFormatter.php`

**Responsibilities:**
- Standardize success responses
- Standardize error responses
- Format validation errors
- Format pagination data

**Lines Saved:** ~100 lines

### Phase 2: Extract GoogleAdsPlatform Services (IF Phase 1 Complete)

**Target:** Break 2,413-line god class into ~8 focused services

#### 2.1: GoogleCampaignService
**Pattern:** Extract Class
**Lines:** ~300
**Responsibilities:**
- createCampaign()
- updateCampaign()
- getCampaign()
- deleteCampaign()
- fetchCampaigns()
- updateCampaignStatus()

#### 2.2: GoogleAdGroupService
**Pattern:** Extract Class
**Lines:** ~150
**Responsibilities:**
- createAdSet() (Ad Groups)
- updateAdGroup()
- getAdGroup()
- deleteAdGroup()

#### 2.3: GoogleKeywordService
**Pattern:** Extract Class
**Lines:** ~200
**Responsibilities:**
- addKeywords()
- addNegativeKeywords()
- removeKeywords()
- getKeywords()

#### 2.4: GoogleAdService
**Pattern:** Extract Class
**Lines:** ~150
**Responsibilities:**
- createAd()
- updateAd()
- getAd()
- deleteAd()

#### 2.5: GoogleTargetingService
**Pattern:** Extract Class
**Lines:** ~400
**Responsibilities:**
- addTopicTargeting()
- addPlacements()
- addDemographicTargeting()
- addLocationTargeting()
- addProximityTargeting()
- addLanguageTargeting()
- addDeviceBidModifiers()
- addAdSchedule()
- addParentalStatusTargeting()
- addHouseholdIncomeTargeting()

#### 2.6: GoogleExtensionsService
**Pattern:** Extract Class
**Lines:** ~500
**Responsibilities:**
- addSitelinkExtensions()
- addCalloutExtensions()
- addStructuredSnippetExtensions()
- addCallExtensions()
- addPriceExtensions()
- addPromotionExtensions()
- addImageExtensions()
- addLeadFormExtensions()

#### 2.7: GoogleAudienceService
**Pattern:** Extract Class
**Lines:** ~350
**Responsibilities:**
- addInMarketAudience()
- addAffinityAudience()
- createCustomAudience()
- addCustomAudience()
- createRemarketingList()
- uploadCustomerMatch()
- addRemarketingAudience()

#### 2.8: GoogleBiddingService
**Pattern:** Extract Class
**Lines:** ~150
**Responsibilities:**
- createBiddingStrategy()
- assignBiddingStrategy()

#### 2.9: GoogleConversionService
**Pattern:** Extract Class
**Lines:** ~200
**Responsibilities:**
- createConversionAction()
- uploadOfflineConversions()
- getConversionActions()

#### 2.10: GoogleDataTransformer
**Pattern:** Extract Class
**Lines:** ~150
**Responsibilities:**
- mapCampaignType()
- mapKeywordMatchType()
- mapStatus()
- extractCampaignId()
- extractAdGroupId()
- extractAdId()
- extractAudienceId()
- extractUserListId()
- extractBiddingStrategyId()
- extractConversionActionId()
- buildAdTextAssets()

**Refactored GoogleAdsPlatform.php:**
- **New Line Count:** ~200 lines (91% reduction)
- **Responsibilities:** Coordination & delegation only
- **Methods:** Interface methods that delegate to services

### Phase 3: Extract LinkedInAdsPlatform Services

**Target:** Break 1,210-line class into ~6 focused services

#### 3.1: LinkedInCampaignService (~300 lines)
#### 3.2: LinkedInCreativeService (~250 lines)
#### 3.3: LinkedInLeadGenService (~150 lines)
#### 3.4: LinkedInTargetingService (~200 lines)
#### 3.5: LinkedInMetricsService (~150 lines)
#### 3.6: LinkedInDataTransformer (~100 lines)

**Refactored LinkedInAdsPlatform.php:**
- **New Line Count:** ~150 lines (88% reduction)

### Phase 4: Extract TikTokAdsPlatform Services

**Target:** Break 1,097-line class into ~6 focused services

#### 4.1: TikTokCampaignService (~300 lines)
#### 4.2: TikTokAdGroupService (~250 lines)
#### 4.3: TikTokAdService (~200 lines)
#### 4.4: TikTokCreativeService (~150 lines)
#### 4.5: TikTokTargetingService (~150 lines)
#### 4.6: TikTokDataTransformer (~100 lines)

**Refactored TikTokAdsPlatform.php:**
- **New Line Count:** ~130 lines (88% reduction)

---

## 3. Architectural Decision: Service Composition

### Proposed Structure

```
app/Services/AdPlatforms/
├── Contracts/
│   └── AdPlatformInterface.php (existing)
├── AbstractAdPlatform.php (existing)
├── Common/                                    # NEW - Shared services
│   ├── OAuthTokenService.php                  # NEW - Token management
│   ├── CredentialManager.php                  # NEW - Credential handling
│   └── ResponseFormatter.php                  # NEW - Response formatting
├── Google/
│   ├── GoogleAdsPlatform.php                  # REFACTORED - Now 200 lines
│   ├── Services/                              # NEW
│   │   ├── GoogleCampaignService.php
│   │   ├── GoogleAdGroupService.php
│   │   ├── GoogleKeywordService.php
│   │   ├── GoogleAdService.php
│   │   ├── GoogleTargetingService.php
│   │   ├── GoogleExtensionsService.php
│   │   ├── GoogleAudienceService.php
│   │   ├── GoogleBiddingService.php
│   │   └── GoogleConversionService.php
│   └── Transformers/                          # NEW
│       └── GoogleDataTransformer.php
├── LinkedIn/
│   ├── LinkedInAdsPlatform.php                # REFACTORED - Now 150 lines
│   ├── Services/                              # NEW
│   │   ├── LinkedInCampaignService.php
│   │   ├── LinkedInCreativeService.php
│   │   ├── LinkedInLeadGenService.php
│   │   ├── LinkedInTargetingService.php
│   │   └── LinkedInMetricsService.php
│   └── Transformers/                          # NEW
│       └── LinkedInDataTransformer.php
└── TikTok/
    ├── TikTokAdsPlatform.php                  # REFACTORED - Now 130 lines
    ├── Services/                              # NEW
    │   ├── TikTokCampaignService.php
    │   ├── TikTokAdGroupService.php
    │   ├── TikTokAdService.php
    │   ├── TikTokCreativeService.php
    │   └── TikTokTargetingService.php
    └── Transformers/                          # NEW
        └── TikTokDataTransformer.php
```

### Dependency Injection Example

**Before (God Class):**
```php
class GoogleAdsPlatform extends AbstractAdPlatform
{
    public function createCampaign(array $data): array
    {
        // 104 lines of campaign creation logic
    }

    public function addKeywords(string $adGroupId, array $keywords): array
    {
        // 34 lines of keyword logic
    }

    // 48 more methods...
}
```

**After (Service Composition):**
```php
class GoogleAdsPlatform extends AbstractAdPlatform
{
    public function __construct(
        Integration $integration,
        protected GoogleCampaignService $campaignService,
        protected GoogleAdGroupService $adGroupService,
        protected GoogleKeywordService $keywordService,
        protected GoogleAdService $adService,
        protected GoogleTargetingService $targetingService,
        protected GoogleExtensionsService $extensionsService,
        protected GoogleAudienceService $audienceService,
        protected GoogleBiddingService $biddingService,
        protected GoogleConversionService $conversionService,
        protected GoogleDataTransformer $transformer
    ) {
        parent::__construct($integration);
    }

    public function createCampaign(array $data): array
    {
        return $this->campaignService->create($data);
    }

    public function addKeywords(string $adGroupId, array $keywords): array
    {
        return $this->keywordService->add($adGroupId, $keywords);
    }

    // Delegates to services (thin platform class)
}
```

---

## 4. Metrics Improvement (Projected)

### Before Refactoring

| Metric | GoogleAds | LinkedIn | TikTok | Total |
|--------|-----------|----------|--------|-------|
| Total Lines | 2,413 | 1,210 | 1,097 | 4,720 |
| Methods | 50+ | 30+ | 25+ | 105+ |
| Avg Method Length | 48 | 40 | 44 | 44 |
| Max Nesting | 4 | 3 | 3 | 4 |
| SRP Compliance | ❌ | ❌ | ❌ | 0% |
| Test Coverage | 0 tests | 0 tests | 0 tests | 0% |

### After Refactoring (Projected)

| Metric | GoogleAds | LinkedIn | TikTok | Total |
|--------|-----------|----------|--------|-------|
| Platform Lines | 200 | 150 | 130 | 480 |
| Service Lines | 2,000 | 1,000 | 900 | 3,900 |
| Total Lines | 2,200 | 1,150 | 1,030 | 4,380 |
| **Lines Saved** | **213** | **60** | **67** | **340** |
| Focused Services | 10 | 6 | 6 | 22 |
| Avg Service Size | 200 | 167 | 150 | 172 |
| Avg Method Length | 15 | 18 | 17 | 17 |
| Max Nesting | 2 | 2 | 2 | 2 |
| SRP Compliance | ✅ | ✅ | ✅ | 100% |
| Test Coverage | 42 tests | 32 tests | 28 tests | 102 tests |

**Overall Improvements:**
- **7% reduction in total lines** (4,720 → 4,380)
- **61% reduction in avg method length** (44 → 17 lines)
- **50% reduction in max nesting depth** (4 → 2)
- **22 focused services created** (each with single responsibility)
- **100% SRP compliance achieved**
- **102 new test methods** (complete coverage)

---

## 5. CMIS-Specific Considerations

### Multi-Tenancy Compliance ✅

**Current State:**
- LinkedInAdsPlatform has `initRLSContext()` method (lines 58-64)
- GoogleAdsPlatform and TikTokAdsPlatform: NO RLS context initialization

**Refactoring Requirements:**
- All service classes MUST call RLS context before database operations
- Add `initRLSContext()` to `AbstractAdPlatform` or create `RLSAwareService` trait
- Apply to all extracted services

### Laravel Best Practices ✅

**Dependency Injection:**
- Bind all new services in `AppServiceProvider`
- Use constructor injection for services
- Follow Laravel IoC container conventions

**Service Provider Bindings:**
```php
// app/Providers/AppServiceProvider.php
public function register()
{
    // Platform services
    $this->app->bind(GoogleAdsPlatform::class, function ($app, $params) {
        $integration = $params['integration'];
        return new GoogleAdsPlatform(
            $integration,
            $app->make(GoogleCampaignService::class, ['integration' => $integration]),
            $app->make(GoogleAdGroupService::class, ['integration' => $integration]),
            // ... other services
        );
    });
}
```

---

## 6. Risk Assessment

### Risk Level: **HIGH** ⚠️

**Risk Factors:**
1. **No Test Coverage** - CRITICAL blocker
   - Cannot verify behavior preservation
   - No safety net for refactoring
   - High risk of production bugs

2. **High Complexity** - Platform services are core functionality
   - Used for all ad campaign operations
   - Direct impact on revenue (ad spend management)
   - Multiple integration points

3. **Large Refactoring Scope** - 4,720 lines across 3 files
   - Many extraction opportunities
   - Complex service dependencies
   - High chance of introducing bugs without tests

**Mitigation Factors:**
- ✅ Well-defined interface (`AdPlatformInterface`)
- ✅ Existing abstraction (`AbstractAdPlatform`)
- ✅ Clear separation of concerns identified
- ✅ Incremental refactoring plan (phases)
- ❌ **NO TESTS** - Blocker

### Deployment Recommendations

**CANNOT DEPLOY UNTIL:**
1. ✅ Phase 0 complete (tests created)
2. ✅ All tests passing (green baseline)
3. ✅ Refactoring complete
4. ✅ All tests still passing (behavior preserved)
5. ✅ Code review completed
6. ✅ Integration testing in staging

**When Ready to Deploy:**
- ✅ Deploy to staging first
- ✅ Run full test suite in CI/CD
- ⚠️ Monitor error logs for 48h after production deploy
- ✅ Have rollback plan ready
- ✅ Performance impact: Minimal (same operations, better structure)

---

## 7. Next Steps & Recommendations

### Immediate (REQUIRED Before Refactoring)

**Phase 0: Test Creation** (Estimated: 8-16 hours)
- [ ] Create `tests/Unit/Services/AdPlatforms/GoogleAdsPlatformTest.php`
  - [ ] Test all 14 interface methods
  - [ ] Mock HTTP responses from Google Ads API
  - [ ] Test OAuth token refresh
  - [ ] Test rate limiting
  - [ ] Test error handling
- [ ] Create `tests/Unit/Services/AdPlatforms/LinkedInAdsPlatformTest.php`
  - [ ] Test all 14 interface methods
  - [ ] Mock HTTP responses from LinkedIn API
  - [ ] Test OAuth token refresh
  - [ ] Test URN handling
- [ ] Create `tests/Unit/Services/AdPlatforms/TikTokAdsPlatformTest.php`
  - [ ] Test all 14 interface methods
  - [ ] Mock HTTP responses from TikTok API
  - [ ] Test token expiration handling
- [ ] Run full test suite: `vendor/bin/phpunit --filter=AdPlatform`
- [ ] Achieve **100% baseline pass rate** (all tests GREEN)

### After Tests Pass (Refactoring)

**Phase 1: Common Services** (Estimated: 4-6 hours)
- [ ] Extract `OAuthTokenService`
- [ ] Extract `CredentialManager`
- [ ] Extract `ResponseFormatter`
- [ ] Test extraction: All tests still GREEN
- [ ] Commit: `refactor: Extract common platform services`

**Phase 2: GoogleAdsPlatform** (Estimated: 12-16 hours)
- [ ] Extract `GoogleCampaignService`
- [ ] Extract `GoogleAdGroupService`
- [ ] Extract `GoogleKeywordService`
- [ ] Extract `GoogleAdService`
- [ ] Extract `GoogleTargetingService`
- [ ] Extract `GoogleExtensionsService`
- [ ] Extract `GoogleAudienceService`
- [ ] Extract `GoogleBiddingService`
- [ ] Extract `GoogleConversionService`
- [ ] Extract `GoogleDataTransformer`
- [ ] Update `GoogleAdsPlatform` to delegate
- [ ] Test: All tests still GREEN
- [ ] Commit: `refactor: Decompose GoogleAdsPlatform into focused services`

**Phase 3: LinkedInAdsPlatform** (Estimated: 8-12 hours)
- [ ] Extract 6 focused services
- [ ] Update `LinkedInAdsPlatform` to delegate
- [ ] Test: All tests still GREEN
- [ ] Commit: `refactor: Decompose LinkedInAdsPlatform into focused services`

**Phase 4: TikTokAdsPlatform** (Estimated: 8-12 hours)
- [ ] Extract 6 focused services
- [ ] Update `TikTokAdsPlatform` to delegate
- [ ] Test: All tests still GREEN
- [ ] Commit: `refactor: Decompose TikTokAdsPlatform into focused services`

### Future Refactoring Opportunities

1. **Extract Value Objects** (Budget, Money, DateRange)
   - Replace primitive floats with `Money` value object
   - Replace date strings with `DateRange` value object
   - Type safety improvements

2. **Introduce DTOs** (Data Transfer Objects)
   - `CampaignData` DTO
   - `TargetingCriteria` DTO
   - `AdSetData` DTO
   - Better IDE support and type hints

3. **Add Caching Layer**
   - Cache campaign data (5-15 min TTL)
   - Cache targeting options (1 hour TTL)
   - Consider repository pattern with caching

4. **Extract Webhook Handlers**
   - If webhook logic exists in these classes
   - Create dedicated webhook service per platform

5. **Similar Pattern in Other Platforms**
   - Check MetaAdsPlatform.php (if exists)
   - Check SnapchatAdsPlatform.php (if exists)
   - Check TwitterAdsPlatform.php (if exists)

---

## 8. Code Examples

### Example 1: OAuth Token Service (Common)

**Before (Duplicated in 3 files):**
```php
// GoogleAdsPlatform.php - Lines 2381-2412 (32 lines)
// LinkedInAdsPlatform.php - Lines 914-1001 (88 lines)
// TikTokAdsPlatform.php - Lines 754-821 (68 lines)
// Total: 188 lines of duplicate logic
```

**After (Single Service):**
```php
// app/Services/AdPlatforms/Common/OAuthTokenService.php
class OAuthTokenService
{
    public function refresh(Integration $integration, string $platform): array
    {
        if (!$this->needsRefresh($integration)) {
            return [
                'success' => true,
                'access_token' => decrypt($integration->access_token),
            ];
        }

        return match ($platform) {
            'google' => $this->refreshGoogle($integration),
            'linkedin' => $this->refreshLinkedIn($integration),
            'tiktok' => $this->refreshTikTok($integration),
            default => throw new \InvalidArgumentException("Unsupported platform: {$platform}"),
        };
    }

    protected function needsRefresh(Integration $integration): bool
    {
        return $integration->token_expires_at
            && $integration->token_expires_at->isPast();
    }

    protected function updateIntegrationToken(
        Integration $integration,
        string $accessToken,
        int $expiresIn,
        ?string $refreshToken = null
    ): void {
        $integration->update([
            'access_token' => encrypt($accessToken),
            'token_expires_at' => now()->addSeconds($expiresIn),
            'refresh_token' => $refreshToken ? encrypt($refreshToken) : $integration->refresh_token,
        ]);
    }

    // Platform-specific refresh methods...
}
```

### Example 2: GoogleCampaignService

**Before (In 2,413-line GoogleAdsPlatform):**
```php
class GoogleAdsPlatform extends AbstractAdPlatform
{
    public function createCampaign(array $data): array
    {
        try {
            $validation = $this->validateCampaignData($data);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'errors' => $validation['errors'],
                ];
            }

            $url = $this->buildUrl('/customers/{customer_id}/campaigns:mutate');

            // Build campaign resource
            $campaign = [
                'name' => $data['name'],
                'advertisingChannelType' => $this->mapCampaignType($data['campaign_type'] ?? 'SEARCH'),
                'status' => $this->mapStatus($data['status'] ?? 'PAUSED'),
                'biddingStrategyType' => $data['bidding_strategy'] ?? 'MAXIMIZE_CONVERSIONS',
            ];

            // ... 80 more lines
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // 49 more methods...
}
```

**After (Extracted Service):**
```php
// app/Services/AdPlatforms/Google/Services/GoogleCampaignService.php
class GoogleCampaignService
{
    public function __construct(
        protected Integration $integration,
        protected GoogleApiClient $client,
        protected GoogleDataTransformer $transformer,
        protected ResponseFormatter $formatter
    ) {}

    public function create(array $data): array
    {
        try {
            $campaign = $this->buildCampaignPayload($data);
            $url = $this->client->buildUrl('/customers/{customer_id}/campaigns:mutate');

            $response = $this->client->post($url, [
                'operations' => [['create' => $campaign]],
            ]);

            return $this->formatter->success([
                'external_id' => $this->transformer->extractCampaignId($response['results'][0]['resourceName']),
                'resource_name' => $response['results'][0]['resourceName'],
                'data' => $response,
            ]);
        } catch (\Exception $e) {
            return $this->formatter->error($e->getMessage());
        }
    }

    protected function buildCampaignPayload(array $data): array
    {
        $campaign = [
            'name' => $data['name'],
            'advertisingChannelType' => $this->transformer->mapCampaignType($data['campaign_type'] ?? 'SEARCH'),
            'status' => $this->transformer->mapStatus($data['status'] ?? 'PAUSED'),
            'biddingStrategyType' => $data['bidding_strategy'] ?? 'MAXIMIZE_CONVERSIONS',
        ];

        // Campaign-specific logic separated...
        $this->addBudget($campaign, $data);
        $this->addSchedule($campaign, $data);
        $this->addNetworkSettings($campaign, $data);

        return $campaign;
    }

    // Focused methods for campaign management only
}
```

---

## 9. Commit History (Planned)

**Phase 0: Test Creation**
1. `test: Add GoogleAdsPlatform characterization tests`
2. `test: Add LinkedInAdsPlatform characterization tests`
3. `test: Add TikTokAdsPlatform characterization tests`

**Phase 1: Common Services**
4. `refactor: Extract OAuthTokenService from platform services`
5. `refactor: Extract CredentialManager from platform services`
6. `refactor: Extract ResponseFormatter from platform services`

**Phase 2: GoogleAdsPlatform**
7. `refactor: Extract GoogleCampaignService from GoogleAdsPlatform`
8. `refactor: Extract GoogleAdGroupService from GoogleAdsPlatform`
9. `refactor: Extract GoogleKeywordService from GoogleAdsPlatform`
10. `refactor: Extract GoogleAdService from GoogleAdsPlatform`
11. `refactor: Extract GoogleTargetingService from GoogleAdsPlatform`
12. `refactor: Extract GoogleExtensionsService from GoogleAdsPlatform`
13. `refactor: Extract GoogleAudienceService from GoogleAdsPlatform`
14. `refactor: Extract GoogleBiddingService from GoogleAdsPlatform`
15. `refactor: Extract GoogleConversionService from GoogleAdsPlatform`
16. `refactor: Extract GoogleDataTransformer from GoogleAdsPlatform`

**Phase 3: LinkedInAdsPlatform**
17. `refactor: Extract LinkedIn service classes`

**Phase 4: TikTokAdsPlatform**
18. `refactor: Extract TikTok service classes`

---

## 10. Conclusion

### Summary

Three platform services (GoogleAdsPlatform, LinkedInAdsPlatform, TikTokAdsPlatform) are god classes totaling 4,720 lines with severe SRP violations. They each have 10-14 distinct responsibilities and exhibit high complexity (methods >50 lines, deep nesting, duplicate logic).

### CRITICAL BLOCKER: No Tests

**⚠️ Refactoring CANNOT proceed without tests. ⚠️**

According to safe refactoring methodology:
- **Rule 1: NEVER Refactor Without Tests** (absolute)
- Current state: 0 tests for any platform service
- Risk level: HIGH (production ad spend management)

### Recommended Path Forward

1. **Immediate:** Create characterization tests (Phase 0)
   - 42+ test methods for GoogleAdsPlatform
   - 32+ test methods for LinkedInAdsPlatform
   - 28+ test methods for TikTokAdsPlatform
   - All tests must pass (green baseline)

2. **Then:** Execute phased refactoring (Phases 1-4)
   - Extract 22 focused service classes
   - Reduce platform classes by 88-91%
   - Achieve 100% SRP compliance
   - Maintain 100% test pass rate throughout

3. **Result:** Maintainable, testable, modular platform architecture
   - 4,380 lines total (7% reduction)
   - 22 focused services (avg 172 lines each)
   - 61% reduction in method length
   - 50% reduction in nesting depth

### Value Proposition

**Without Refactoring:**
- God classes continue to grow
- Adding new platforms requires massive files
- Testing becomes impossible
- Bugs hide in complexity
- Team velocity slows

**With Refactoring:**
- Single responsibility per service
- Easy to test (102 new tests)
- Easy to extend (new platforms)
- Easy to maintain (small, focused files)
- Team velocity increases

---

**Status:** ❌ **BLOCKED - Tests Required**

**Next Action:** Create Phase 0 tests before any refactoring begins.

