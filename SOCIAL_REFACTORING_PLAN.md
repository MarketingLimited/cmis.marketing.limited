# Social Module Refactoring Plan

**Date:** 2025-11-29
**Objective:** Refactor all social module files over 500 lines into modular components

---

## Files to Refactor (Prioritized)

### Priority 1: Critical Backend Files
1. **SocialPostController.php** (1777 lines) - 21 methods
2. **HistoricalContentController.php** (890 lines)
3. **SocialListeningController.php** (657 lines)
4. **ProfileGroupController.php** (605 lines)

### Priority 2: Critical Frontend Files
1. **social/index.blade.php** (2360 lines) - Main social dashboard
2. **social/history/index.blade.php** (1473 lines)
3. **social/posts.blade.php** (791 lines)

### Priority 3: JavaScript Components
1. **publish-modal.js** (1736 lines)
2. **predictiveAnalytics.js** (756 lines)
3. **userManagement.js** (730 lines)
4. **realtimeDashboard.js** (552 lines)

### Priority 4: Platform Services
1. **LinkedInSocialService.php** (724 lines)
2. **RedditSocialService.php** (714 lines)
3. **TumblrSocialService.php** (644 lines)
4. **GoogleBusinessService.php** (586 lines)
5. **PinterestSocialService.php** (577 lines)
6. **TwitterSocialService.php** (549 lines)
7. **YouTubeSocialService.php** (541 lines)

### Priority 5: Other Services
1. **HistoricalContentService.php** (596 lines)
2. **KnowledgeBaseConversionService.php** (558 lines)
3. **KnowledgeBaseContentGenerationService.php** (507 lines)

---

## Refactoring Strategy

### 1. SocialPostController.php (1777 lines → ~200 lines)

**Current Methods (21 total):**
- Account Management: `getConnectedAccounts`, `addMetaAccounts`
- CRUD: `index`, `store`, `show`, `update`, `destroy`, `destroyAllFailed`
- Publishing: `publish`, `publishToMeta`, `publishToFacebook`, `publishToInstagram`
- Queue: `getQueueSettings`, `saveQueueSettings`, `getNextQueueSlot`, `reschedule`
- Platform Data: `getPostTypes`, `searchLocations`, `getTrendingHashtags`
- Collaborators: `getCollaboratorSuggestions`, `validateInstagramUsername`, `storeCollaborator`
- Scheduling: `getScheduledPosts`

**New Structure:**

```
app/Services/Social/
├── SocialAccountService.php          (~150 lines)
│   ├── getConnectedAccounts()
│   └── formatAccountsByPlatform()
│
├── SocialPostPublishService.php      (~300 lines)
│   ├── publishPost()
│   ├── publishToMeta()
│   ├── publishToFacebook()
│   └── publishToInstagram()
│
├── SocialQueueService.php            (~200 lines)
│   ├── getQueueSettings()
│   ├── saveQueueSettings()
│   ├── getNextQueueSlot()
│   └── reschedulePost()
│
├── SocialPlatformDataService.php     (~150 lines)
│   ├── getPostTypes()
│   ├── searchLocations()
│   └── getTrendingHashtags()
│
└── SocialCollaboratorService.php     (~120 lines)
    ├── getSuggestions()
    ├── validateUsername()
    └── storeCollaborator()
```

**Refactored Controller (~200 lines):**
```php
class SocialPostController extends Controller
{
    public function __construct(
        protected SocialAccountService $accountService,
        protected SocialPostPublishService $publishService,
        protected SocialQueueService $queueService,
        protected SocialPlatformDataService $platformDataService,
        protected SocialCollaboratorService $collaboratorService
    ) {}

    // CRUD methods only (thin controllers)
    // All business logic delegated to services
}
```

---

### 2. social/index.blade.php (2360 lines → ~50 lines)

**Current Structure:**
- Inline Alpine.js component (socialManager)
- Stats dashboard cards
- Controls panel
- Platform filters
- Post grid/list/calendar views
- Modals and overlays

**New Structure:**

```
resources/views/social/
├── index.blade.php                   (~50 lines - container only)
├── components/
│   ├── stats-dashboard.blade.php     (~120 lines)
│   ├── controls-panel.blade.php      (~180 lines)
│   ├── platform-filters.blade.php    (~60 lines)
│   ├── post-type-filters.blade.php   (~80 lines)
│   ├── status-filters.blade.php      (~50 lines)
│   ├── grid-view.blade.php           (~400 lines)
│   ├── list-view.blade.php           (~350 lines)
│   ├── calendar-view.blade.php       (~300 lines)
│   ├── post-card.blade.php           (~250 lines)
│   └── modals/
│       ├── queue-settings.blade.php  (~150 lines)
│       ├── edit-post.blade.php       (~200 lines)
│       └── post-preview.blade.php    (~120 lines)
```

**JavaScript Extraction:**
```
resources/js/components/social/
├── socialManager.js                  (~400 lines - Alpine component)
├── postFilters.js                    (~150 lines)
├── postViews.js                      (~200 lines)
└── queueManager.js                   (~150 lines)
```

---

### 3. publish-modal.js (1736 lines → ~200 lines)

**Current Structure:**
- Single monolithic Alpine component
- Content management
- Platform configuration
- Scheduling
- Media handling
- Validation

**New Structure:**

```
resources/js/components/publish-modal/
├── index.js                          (~200 lines - main orchestrator)
├── contentManager.js                 (~250 lines)
├── platformManager.js                (~300 lines)
├── schedulingManager.js              (~200 lines)
├── mediaManager.js                   (~250 lines)
├── validationManager.js              (~150 lines)
├── previewManager.js                 (~200 lines)
└── utils/
    ├── platformHelpers.js            (~100 lines)
    ├── dateHelpers.js                (~80 lines)
    └── validationHelpers.js          (~100 lines)
```

---

### 4. HistoricalContentController.php (890 lines)

**Split into:**
```
app/Services/Social/Historical/
├── HistoricalContentRetrievalService.php   (~250 lines)
├── HistoricalContentAnalysisService.php    (~200 lines)
├── HistoricalContentImportService.php      (~150 lines)
└── HistoricalContentExportService.php      (~150 lines)
```

---

### 5. social/history/index.blade.php (1473 lines)

**Split into:**
```
resources/views/social/history/
├── index.blade.php                   (~50 lines)
├── components/
│   ├── filters.blade.php             (~150 lines)
│   ├── timeline-view.blade.php       (~300 lines)
│   ├── analytics-panel.blade.php     (~250 lines)
│   ├── import-wizard.blade.php       (~200 lines)
│   ├── export-options.blade.php      (~150 lines)
│   └── content-cards/
│       ├── post-card.blade.php       (~120 lines)
│       ├── engagement-card.blade.php (~100 lines)
│       └── analytics-card.blade.php  (~100 lines)
```

---

### 6. social/posts.blade.php (791 lines)

**Split into:**
```
resources/views/social/posts/
├── index.blade.php                   (~50 lines)
├── components/
│   ├── post-header.blade.php         (~80 lines)
│   ├── post-content.blade.php        (~150 lines)
│   ├── post-media.blade.php          (~120 lines)
│   ├── post-metadata.blade.php       (~100 lines)
│   ├── post-actions.blade.php        (~120 lines)
│   └── post-analytics.blade.php      (~150 lines)
```

---

## Implementation Order

### Phase 1: Backend Refactoring (Day 1-2)
1. ✅ Create SocialAccountService
2. ✅ Create SocialPostPublishService
3. ✅ Create SocialQueueService
4. ✅ Create SocialPlatformDataService
5. ✅ Create SocialCollaboratorService
6. ✅ Refactor SocialPostController to use services
7. ✅ Test all API endpoints

### Phase 2: Frontend Refactoring (Day 2-3)
1. ✅ Extract social/index.blade.php components
2. ✅ Create Alpine.js modules for socialManager
3. ✅ Test social dashboard functionality

### Phase 3: JavaScript Refactoring (Day 3-4)
1. ✅ Modularize publish-modal.js
2. ✅ Create utility modules
3. ✅ Test publish modal functionality

### Phase 4: Additional Controllers (Day 4-5)
1. ✅ Refactor HistoricalContentController
2. ✅ Refactor SocialListeningController
3. ✅ Refactor ProfileGroupController

### Phase 5: Additional Views (Day 5-6)
1. ✅ Refactor social/history/index.blade.php
2. ✅ Refactor social/posts.blade.php

### Phase 6: Testing & Optimization (Day 6-7)
1. ✅ Integration testing
2. ✅ Performance testing
3. ✅ Browser testing (mobile/cross-browser)
4. ✅ Documentation updates

---

## Success Criteria

- ✅ All files under 500 lines
- ✅ Clear separation of concerns
- ✅ Single Responsibility Principle followed
- ✅ All existing functionality preserved
- ✅ All tests passing
- ✅ No breaking changes
- ✅ Improved maintainability and readability

---

## Notes

- Use Laravel's service container for dependency injection
- Apply standardized traits (ApiResponse, HasOrganization)
- Maintain backward compatibility with existing API contracts
- Follow CMIS multi-tenancy patterns (RLS policies)
- Ensure i18n compliance (no hardcoded text)
- Use logical CSS properties for RTL/LTR support
