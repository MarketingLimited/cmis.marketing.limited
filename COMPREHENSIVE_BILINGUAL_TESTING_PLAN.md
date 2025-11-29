# Comprehensive Bilingual Browser Testing Plan
**Project:** CMIS Platform
**Date:** 2025-11-28
**Objective:** Test ALL web routes in both Arabic (RTL) and English (LTR)
**Total Estimated Routes:** 120+ pages

---

## Testing Strategy

### Languages to Test
- ✅ **Arabic (ar)** - RTL layout, default language
- ✅ **English (en)** - LTR layout

### Testing Method
1. Login once with admin credentials
2. For each page:
   - Test in Arabic (default user preference)
   - Switch language to English via language switcher
   - Capture screenshots for both languages
   - Verify locale, direction, and i18n compliance
3. Record status codes, errors, and i18n issues

### Organization Context
- **Org ID:** `5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a`
- **Test User:** admin@cmis.test
- **Current Locale:** English (en) - will test switching

---

## Route Categories

### Category 1: Guest Routes (No Authentication)
**Total:** 4 routes

| # | Route | Name | Priority |
|---|-------|------|----------|
| 1 | /login | Login Page | High |
| 2 | /register | Registration Page | High |
| 3 | /invitations/accept/{token} | Accept Invitation | Medium |
| 4 | /invitations/decline/{token} | Decline Invitation | Medium |

**Note:** These pages currently don't have language switcher (issue documented).

---

### Category 2: Authenticated Non-Org Routes
**Total:** 15+ routes

| # | Route | Name | Priority |
|---|-------|------|----------|
| 5 | / | Home (redirects to dashboard) | High |
| 6 | /onboarding | User Onboarding | Medium |
| 7 | /onboarding/step/{step} | Onboarding Steps | Medium |
| 8 | /orgs | Organizations List | High |
| 9 | /orgs/create | Create Organization | High |
| 10 | /offerings | Offerings Overview | Medium |
| 11 | /products | Products List | Medium |
| 12 | /services | Services List | Medium |
| 13 | /bundles | Bundles List | Medium |
| 14 | /users | Users List | Medium |
| 15 | /settings | User Settings (global) | High |
| 16 | /settings/profile | User Profile Settings | High |
| 17 | /settings/notifications | Notification Settings | Medium |
| 18 | /settings/security | Security Settings | Medium |
| 19 | /subscription/plans | Subscription Plans | Medium |
| 20 | /profile | User Profile Page | High |

---

### Category 3: Org-Scoped Routes - Core Pages
**Total:** 49 routes (already tested, need bilingual re-test)

#### 3.1 Dashboard & Home
| # | Route | Name | Status |
|---|-------|------|--------|
| 21 | /orgs/{org} | Org Home | ✅ Tested (en only) |
| 22 | /orgs/{org}/dashboard | Dashboard | ✅ Tested (en only) |

#### 3.2 Campaigns
| # | Route | Name | Status |
|---|-------|------|--------|
| 23 | /orgs/{org}/campaigns | Campaigns List | ✅ Tested (en only) |
| 24 | /orgs/{org}/campaigns/create | Create Campaign | ✅ Tested (en only) |
| 25 | /orgs/{org}/campaigns/performance-dashboard | Performance Dashboard | New |
| 26 | /orgs/{org}/campaigns/compare | Compare Campaigns | New |
| 27 | /orgs/{org}/campaigns/wizard/create | Campaign Wizard | New |

#### 3.3 Analytics
| # | Route | Name | Status |
|---|-------|------|--------|
| 28 | /orgs/{org}/analytics | Analytics Hub | ✅ Tested (en only) |
| 29 | /orgs/{org}/analytics/realtime | Real-Time Analytics | ✅ Tested (en only) |
| 30 | /orgs/{org}/analytics/kpis | KPI Dashboard | ✅ Tested (en only) |
| 31 | /orgs/{org}/analytics/campaigns | Campaign Analytics | New |
| 32 | /orgs/{org}/analytics/legacy | Legacy Analytics | New |
| 33 | /orgs/{org}/analytics/reports | Analytics Reports | New |
| 34 | /orgs/{org}/analytics/metrics | Metrics Overview | New |

#### 3.4 Creative
| # | Route | Name | Status |
|---|-------|------|--------|
| 35 | /orgs/{org}/creative | Creative Overview | New |
| 36 | /orgs/{org}/creative/assets | Creative Assets | ✅ Tested (en only) |
| 37 | /orgs/{org}/creative/briefs | Creative Briefs | ✅ Tested (en only) |
| 38 | /orgs/{org}/creative/briefs/create | Create Brief | ✅ Tested (en only) |
| 39 | /orgs/{org}/creative/ads | Creative Ads | New |
| 40 | /orgs/{org}/creative/templates | Creative Templates | New |

#### 3.5 Social Media
| # | Route | Name | Status |
|---|-------|------|--------|
| 41 | /orgs/{org}/social | Social Hub | ✅ Tested (en only) |
| 42 | /orgs/{org}/social/posts | Social Posts | ❌ 500 Error |
| 43 | /orgs/{org}/social/scheduler | Social Scheduler | ✅ Tested (en only) |
| 44 | /orgs/{org}/social/inbox | Social Inbox | New |
| 45 | /orgs/{org}/social/history | Historical Content | ✅ Tested (en only) |
| 46 | /orgs/{org}/social/history/analytics | History Analytics | New |
| 47 | /orgs/{org}/social/history/knowledge-base | Knowledge Base | New |

#### 3.6 Influencer Marketing
| # | Route | Name | Status |
|---|-------|------|--------|
| 48 | /orgs/{org}/influencer | Influencer Hub | ✅ Tested (en only) |
| 49 | /orgs/{org}/influencer/create | Create Influencer | ✅ Tested (en only) |

#### 3.7 Campaign Orchestration
| # | Route | Name | Status |
|---|-------|------|--------|
| 50 | /orgs/{org}/orchestration | Orchestration Hub | ✅ Tested (en only) |

#### 3.8 Social Listening
| # | Route | Name | Status |
|---|-------|------|--------|
| 51 | /orgs/{org}/listening | Social Listening | ✅ Tested (en only) |

#### 3.9 AI & Intelligence
| # | Route | Name | Status |
|---|-------|------|--------|
| 52 | /orgs/{org}/ai | AI Center | ✅ Tested (en only) |
| 53 | /orgs/{org}/ai/campaigns | AI Campaigns | New |
| 54 | /orgs/{org}/ai/recommendations | AI Recommendations | New |
| 55 | /orgs/{org}/ai/models | AI Models | New |
| 56 | /orgs/{org}/predictive | Predictive Analytics | ✅ Tested (en only) |

#### 3.10 Testing & Optimization
| # | Route | Name | Status |
|---|-------|------|--------|
| 57 | /orgs/{org}/experiments | A/B Testing | ✅ Tested (en only) |
| 58 | /orgs/{org}/optimization | Optimization Engine | ✅ Tested (en only) |

#### 3.11 Automation & Workflows
| # | Route | Name | Status |
|---|-------|------|--------|
| 59 | /orgs/{org}/automation | Automation Hub | ✅ Tested (en only) |
| 60 | /orgs/{org}/workflows | Workflows | ✅ Tested (en only) |

#### 3.12 System & Management
| # | Route | Name | Status |
|---|-------|------|--------|
| 61 | /orgs/{org}/alerts | Alerts | ✅ Tested (en only) |
| 62 | /orgs/{org}/exports | Data Exports | ✅ Tested (en only) |
| 63 | /orgs/{org}/dashboard-builder | Dashboard Builder | ✅ Tested (en only) |
| 64 | /orgs/{org}/feature-flags | Feature Flags | ✅ Tested (en only) |

#### 3.13 Knowledge Base
| # | Route | Name | Status |
|---|-------|------|--------|
| 65 | /orgs/{org}/knowledge | Knowledge Base | ✅ Tested (en only) |
| 66 | /orgs/{org}/knowledge/create | Create Knowledge | ✅ Tested (en only) |

#### 3.14 Products & Team
| # | Route | Name | Status |
|---|-------|------|--------|
| 67 | /orgs/{org}/products | Products | ✅ Tested (en only) |
| 68 | /orgs/{org}/team | Team Management | ✅ Tested (en only) |

#### 3.15 Unified Inbox
| # | Route | Name | Status |
|---|-------|------|--------|
| 69 | /orgs/{org}/inbox | Unified Inbox | ✅ Tested (en only) |
| 70 | /orgs/{org}/inbox/comments | Unified Comments | New |

#### 3.16 Channels
| # | Route | Name | Status |
|---|-------|------|--------|
| 71 | /orgs/{org}/channels | Channels List | New |

---

### Category 4: Org Settings Routes
**Total:** 30+ routes

#### 4.1 Core Settings
| # | Route | Name | Status |
|---|-------|------|--------|
| 72 | /orgs/{org}/settings/user | User Settings | ✅ Tested (en only) |
| 73 | /orgs/{org}/settings/organization | Organization Settings | ✅ Tested (en only) |

#### 4.2 Platform Connections
| # | Route | Name | Status |
|---|-------|------|--------|
| 74 | /orgs/{org}/settings/platform-connections | Platform Connections | ✅ Tested (en only) |
| 75 | /orgs/{org}/settings/platform-connections/meta/add | Add Meta Token | New |
| 76 | /orgs/{org}/settings/platform-connections/google/add | Add Google Token | New |

#### 4.3 Profile Groups
| # | Route | Name | Status |
|---|-------|------|--------|
| 77 | /orgs/{org}/settings/profile-groups | Profile Groups List | ✅ Tested (en only) |
| 78 | /orgs/{org}/settings/profile-groups/create | Create Profile Group | ✅ Tested (en only) |

#### 4.4 Brand Voices
| # | Route | Name | Status |
|---|-------|------|--------|
| 79 | /orgs/{org}/settings/brand-voices | Brand Voices List | ✅ Tested (en only) |
| 80 | /orgs/{org}/settings/brand-voices/create | Create Brand Voice | ✅ Tested (en only) |

#### 4.5 Brand Safety
| # | Route | Name | Status |
|---|-------|------|--------|
| 81 | /orgs/{org}/settings/brand-safety | Brand Safety Policies | ✅ Tested (en only) |
| 82 | /orgs/{org}/settings/brand-safety/create | Create Brand Safety Policy | ✅ Tested (en only) |

#### 4.6 Approval Workflows
| # | Route | Name | Status |
|---|-------|------|--------|
| 83 | /orgs/{org}/settings/approval-workflows | Approval Workflows | ✅ Tested (en only) |
| 84 | /orgs/{org}/settings/approval-workflows/create | Create Approval Workflow | ✅ Tested (en only) |

#### 4.7 Boost Rules
| # | Route | Name | Status |
|---|-------|------|--------|
| 85 | /orgs/{org}/settings/boost-rules | Boost Rules | ✅ Tested (en only) |
| 86 | /orgs/{org}/settings/boost-rules/create | Create Boost Rule | ✅ Tested (en only) |

#### 4.8 Ad Accounts
| # | Route | Name | Status |
|---|-------|------|--------|
| 87 | /orgs/{org}/settings/ad-accounts | Ad Accounts | ✅ Tested (en only) |
| 88 | /orgs/{org}/settings/ad-accounts/create | Create Ad Account | New |

---

## Testing Phases

### Phase 1: Guest Routes (No Auth Required)
- **Duration:** 15 minutes
- **Routes:** 4
- **Languages:** ar, en
- **Total Tests:** 8 screenshots
- **Method:** Direct URL access with locale cookie/parameter

### Phase 2: Authenticated Non-Org Routes
- **Duration:** 30 minutes
- **Routes:** 16
- **Languages:** ar, en
- **Total Tests:** 32 screenshots
- **Method:** Login → Test each route → Language switcher

### Phase 3: Org-Scoped Core Pages (Already Tested)
- **Duration:** 1 hour
- **Routes:** 49 (re-test with bilingual)
- **Languages:** ar, en
- **Total Tests:** 98 screenshots
- **Method:** Login → Test each route → Switch language → Re-test

### Phase 4: New Org-Scoped Routes
- **Duration:** 45 minutes
- **Routes:** 25+ new routes
- **Languages:** ar, en
- **Total Tests:** 50+ screenshots

### Phase 5: Settings Routes
- **Duration:** 30 minutes
- **Routes:** 17 (already tested, re-test bilingual)
- **Languages:** ar, en
- **Total Tests:** 34 screenshots

---

## Test Execution Plan

### Automated Testing Script
Create `test-bilingual-comprehensive.cjs` with:

```javascript
const PAGES = [
  // Guest routes (test without login)
  { path: '/login', name: 'guest-login', requiresAuth: false },
  { path: '/register', name: 'guest-register', requiresAuth: false },

  // Authenticated non-org routes
  { path: '/orgs', name: 'orgs-list', requiresAuth: true },
  { path: '/profile', name: 'profile', requiresAuth: true },
  { path: '/settings', name: 'settings-user-global', requiresAuth: true },

  // Org-scoped routes (49 + new ones)
  { path: `/orgs/${ORG_ID}`, name: 'org-home', requiresAuth: true },
  { path: `/orgs/${ORG_ID}/dashboard`, name: 'dashboard', requiresAuth: true },
  // ... all 100+ routes
];

// For each page:
// 1. Test in Arabic (ar)
// 2. Switch to English (en)
// 3. Take screenshots for both
// 4. Verify locale, direction, status code
// 5. Check for i18n issues
```

### Language Switching Method

**For Authenticated Pages:**
```javascript
// Method 1: Click language switcher in UI
await page.evaluate(() => {
  const switcher = document.querySelector('[data-language-switcher]');
  switcher.click();
});

await page.waitForSelector('[data-language="en"]');
await page.click('[data-language="en"]');

// Method 2: POST to /language/{locale}
await page.evaluate(() => {
  fetch('/language/en', { method: 'POST' }).then(() => location.reload());
});
```

**For Guest Pages:**
```javascript
// Use cookie injection
await page.setCookie({ name: 'app_locale', value: 'en', domain: 'cmis-test.kazaaz.com' });
await page.reload();
```

---

## Success Criteria

### Per Page
- ✅ Status code 200 (or appropriate redirect)
- ✅ `lang="ar"` or `lang="en"` on `<html>` tag
- ✅ `dir="rtl"` for Arabic, `dir="ltr"` for English
- ✅ Language switcher present and functional
- ✅ No hardcoded text (all text via translation keys)
- ✅ Proper RTL/LTR CSS (ms-, me-, text-start, etc.)
- ✅ Both screenshots captured successfully

### Overall
- ✅ 95%+ pages load successfully in both languages
- ✅ All pages have proper locale detection
- ✅ Language switcher works consistently
- ✅ No server errors (500)
- ✅ Comprehensive report generated

---

## Known Issues to Fix

### High Priority
1. **Social Posts Page (500 Error)**
   - File: `resources/views/social/posts.blade.php:253`
   - Error: `Undefined variable $currentOrg`
   - Impact: Page completely broken

2. **Guest Pages No Language Switcher**
   - Files: `resources/views/auth/login.blade.php`, `register.blade.php`
   - Issue: Standalone HTML without Alpine.js or language switcher
   - Impact: Cannot switch language on login/register pages

### Medium Priority
3. **Knowledge Create Page Mixed Language**
   - Title: `"إضافة معرفة جديدة - Dashboard"`
   - Issue: Arabic title + English suffix
   - Impact: Inconsistent UX

### Low Priority
4. **URL Parameter Language Switching Not Supported**
   - Issue: `?lang=ar` doesn't change language
   - Workaround: Use language switcher or cookie
   - Impact: Manual testing inconvenience

---

## Deliverables

### Test Reports
1. **JSON Report:** `test-results/bilingual-comprehensive/test-report.json`
   - Complete test data for all pages
   - Status codes, locales, errors, timings

2. **Markdown Summary:** `test-results/bilingual-comprehensive/SUMMARY.md`
   - Executive summary with statistics
   - Breakdown by category and language
   - List of issues found

3. **Screenshots:** `test-results/bilingual-comprehensive/screenshots/`
   - Format: `{page-name}-{lang}.png`
   - Example: `dashboard-ar.png`, `dashboard-en.png`

### Documentation
4. **Testing Documentation:** `BILINGUAL_TESTING_REPORT.md`
   - Methodology
   - Results analysis
   - i18n compliance audit
   - Recommendations

5. **Issue Tracker:** `BILINGUAL_TESTING_ISSUES.md`
   - All issues found during testing
   - Priority, severity, file locations
   - Fix recommendations

---

## Timeline

| Phase | Duration | Total Tests | Completion |
|-------|----------|-------------|------------|
| Planning | 30 min | - | ✅ Done |
| Script Development | 1 hour | - | Pending |
| Phase 1: Guest Routes | 15 min | 8 | Pending |
| Phase 2: Auth Non-Org | 30 min | 32 | Pending |
| Phase 3: Org Core (Re-test) | 1 hour | 98 | Pending |
| Phase 4: New Org Routes | 45 min | 50 | Pending |
| Phase 5: Settings | 30 min | 34 | Pending |
| **Total Execution** | **4 hours** | **222 tests** | **0%** |
| Report Generation | 30 min | - | Pending |
| Issue Documentation | 30 min | - | Pending |
| **Grand Total** | **5 hours** | **222 tests** | **0%** |

---

## Next Steps

1. ✅ **Create this testing plan** (Complete)
2. **Develop bilingual test script** (`test-bilingual-comprehensive.cjs`)
3. **Fix social/posts 500 error** (blocker)
4. **Execute Phase 1:** Guest routes testing
5. **Execute Phase 2:** Authenticated non-org routes
6. **Execute Phase 3:** Re-test org-scoped routes with bilingual
7. **Execute Phase 4:** Test new org routes
8. **Execute Phase 5:** Re-test settings routes
9. **Generate comprehensive reports**
10. **Document all i18n issues**
11. **Update CLAUDE.md** with testing results

---

**Plan Created:** 2025-11-28
**Status:** Planning Complete - Ready for Execution
**Estimated Completion:** 5 hours automated testing + manual review

---

# API ROUTES TESTING EXTENSION

## API Routes Overview

**Total API Routes:** 870
**Route Types:**
- GET: 424 endpoints
- POST: 329 endpoints
- PUT: 57 endpoints
- PATCH: 2 endpoints
- DELETE: 58 endpoints

---

## API Testing Categories

### Category 5: Core API Endpoints
**Purpose:** Test all REST API endpoints for functionality, authentication, and data integrity

#### 5.1 Authentication & Context
- POST /api/auth/login
- POST /api/auth/logout
- POST /api/auth/refresh
- GET /api/context/current
- POST /api/context/switch

#### 5.2 Campaign Management APIs
**Prefix:** `/api/campaigns`
- GET /api/campaigns (list all campaigns)
- POST /api/campaigns (create campaign)
- GET /api/campaigns/{id} (get campaign)
- PUT /api/campaigns/{id} (update campaign)
- DELETE /api/campaigns/{id} (delete campaign)
- GET /api/campaigns/{id}/metrics
- POST /api/campaigns/{id}/duplicate
- PATCH /api/campaigns/{id}/status

#### 5.3 Ad Campaign APIs
**Prefix:** `/api/ad-campaigns`
- CRUD operations for ad campaigns
- Ad sets management
- Ad creatives management
- Performance metrics

#### 5.4 Social Media APIs
**Prefix:** `/api/social`
- GET /api/social/posts
- POST /api/social/posts
- PUT /api/social/posts/{id}
- DELETE /api/social/posts/{id}
- POST /api/social/posts/{id}/schedule
- POST /api/social/posts/{id}/publish

#### 5.5 Analytics & Reporting APIs
**Prefix:** `/api/analytics`
- GET /api/analytics/dashboard
- GET /api/analytics/campaign/{id}
- GET /api/campaign-analytics/performance
- POST /api/reports/generate
- GET /api/performance/metrics

#### 5.6 AI & Intelligence APIs
**Prefix:** `/api/ai`
- POST /api/ai/generate-content
- POST /api/ai/recommendations
- POST /api/ai/insights/analyze
- GET /api/ai/insights/summary

#### 5.7 Platform Integration APIs
**Prefix:** `/api/integrations`
- GET /api/integrations/meta
- GET /api/integrations/google
- GET /api/integrations/tiktok
- POST /api/integrations/{platform}/sync

#### 5.8 Audience & Targeting APIs
**Prefix:** `/api/audiences`
- GET /api/audiences
- POST /api/audiences
- GET /api/audience-targeting/options
- POST /api/audience-targeting/validate

#### 5.9 Content & Creative APIs
**Prefix:** `/api/creative`
- GET /api/creative/assets
- POST /api/creative/assets/upload
- GET /api/content-library
- POST /api/briefs

#### 5.10 Team & Collaboration APIs
**Prefix:** `/api/team`
- GET /api/team/members
- POST /api/team/invite
- GET /api/comments
- POST /api/comments/{id}/reply
- GET /api/approvals
- POST /api/approvals/{id}/approve

---

## API Testing Strategy

### Authentication Testing
**Method:** API Token (Bearer)
```javascript
const headers = {
  'Authorization': `Bearer ${apiToken}`,
  'Content-Type': 'application/json',
  'Accept': 'application/json',
  'X-Organization-Context': orgId
};
```

### Language Testing for APIs
**Method:** Accept-Language Header
```javascript
// Arabic
headers['Accept-Language'] = 'ar';

// English
headers['Accept-Language'] = 'en';
```

### Test Cases Per Endpoint

#### 1. Success Response
- Verify 200/201 status code
- Validate JSON structure
- Check data integrity

#### 2. Authentication
- Verify 401 for unauthenticated requests
- Verify 403 for unauthorized access

#### 3. Validation
- Verify 422 for invalid data
- Check error messages in both languages

#### 4. Not Found
- Verify 404 for non-existent resources

#### 5. Rate Limiting
- Verify 429 for rate limit exceeded (AI endpoints)

---

## API Testing Script Structure

```javascript
// test-api-comprehensive.cjs
const axios = require('axios');

const CONFIG = {
  baseUrl: 'https://cmis-test.kazaaz.com/api',
  orgId: '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a',
  apiToken: null, // Will be obtained via login
  languages: ['ar', 'en']
};

// API Endpoints to test
const ENDPOINTS = [
  // Authentication
  { method: 'POST', path: '/auth/login', requiresAuth: false, testData: { email: 'admin@cmis.test', password: 'password' } },
  
  // Campaigns
  { method: 'GET', path: '/campaigns', requiresAuth: true },
  { method: 'POST', path: '/campaigns', requiresAuth: true, testData: { name: 'Test Campaign' } },
  
  // Social
  { method: 'GET', path: '/social/posts', requiresAuth: true },
  
  // Analytics
  { method: 'GET', path: '/analytics/dashboard', requiresAuth: true },
  
  // AI (with throttling)
  { method: 'POST', path: '/ai/generate-content', requiresAuth: true, throttled: true },
  
  // ... all 870 endpoints
];

async function testEndpoint(endpoint, lang) {
  const headers = {
    'Accept-Language': lang,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Organization-Context': CONFIG.orgId
  };
  
  if (endpoint.requiresAuth) {
    headers['Authorization'] = `Bearer ${CONFIG.apiToken}`;
  }
  
  const result = {
    method: endpoint.method,
    path: endpoint.path,
    lang,
    status: null,
    responseTime: 0,
    success: false,
    error: null
  };
  
  const startTime = Date.now();
  
  try {
    const response = await axios({
      method: endpoint.method,
      url: `${CONFIG.baseUrl}${endpoint.path}`,
      headers,
      data: endpoint.testData || {}
    });
    
    result.status = response.status;
    result.responseTime = Date.now() - startTime;
    result.success = response.status >= 200 && response.status < 300;
    result.dataStructure = Object.keys(response.data);
    
  } catch (error) {
    result.status = error.response?.status || 500;
    result.responseTime = Date.now() - startTime;
    result.error = error.message;
  }
  
  return result;
}

async function runAPITests() {
  // 1. Login to get API token
  const loginResponse = await testEndpoint({ method: 'POST', path: '/auth/login', requiresAuth: false, testData: { email: 'admin@cmis.test', password: 'password' } }, 'en');
  CONFIG.apiToken = loginResponse.token;
  
  // 2. Test each endpoint in both languages
  for (const endpoint of ENDPOINTS) {
    for (const lang of CONFIG.languages) {
      const result = await testEndpoint(endpoint, lang);
      results.push(result);
      
      // Rate limiting for AI endpoints
      if (endpoint.throttled) {
        await sleep(2000); // 2 second delay
      }
    }
  }
  
  // 3. Generate report
  generateReport();
}
```

---

## API Testing Phases

### Phase 6: Core API Endpoints (High Priority)
**Duration:** 2 hours
**Endpoints:** ~200 core endpoints
**Languages:** ar, en (via Accept-Language header)
**Tests:** ~400

**Endpoints to test:**
- Authentication & Context
- Campaigns CRUD
- Social Posts CRUD
- Analytics endpoints
- Team & User Management

### Phase 7: Platform Integration APIs
**Duration:** 1 hour
**Endpoints:** ~150 endpoints
**Tests:** ~300

**Endpoints to test:**
- Meta APIs
- Google APIs
- TikTok APIs
- LinkedIn APIs
- Platform sync operations

### Phase 8: AI & Intelligence APIs
**Duration:** 1 hour (slower due to throttling)
**Endpoints:** ~100 endpoints
**Tests:** ~200

**Endpoints to test:**
- Content generation
- Recommendations
- Insights analysis
- Semantic search

### Phase 9: Specialized APIs
**Duration:** 2 hours
**Endpoints:** ~420 remaining endpoints
**Tests:** ~840

**Endpoints to test:**
- Creative assets
- Audience management
- Budget & billing
- Approvals & workflows
- Webhooks
- Reports generation

---

## Updated Timeline

| Phase | Type | Duration | Total Tests | Status |
|-------|------|----------|-------------|--------|
| **WEB ROUTES** | | | | |
| Planning | - | 30 min | - | ✅ Done |
| Script Dev | Web | 1 hour | - | Pending |
| Phase 1 | Web Guest | 15 min | 8 | Pending |
| Phase 2 | Web Auth | 30 min | 32 | Pending |
| Phase 3 | Web Org Core | 1 hour | 98 | Pending |
| Phase 4 | Web Org New | 45 min | 50 | Pending |
| Phase 5 | Web Settings | 30 min | 34 | Pending |
| **API ROUTES** | | | | |
| Script Dev | API | 1 hour | - | Pending |
| Phase 6 | API Core | 2 hours | 400 | Pending |
| Phase 7 | API Platforms | 1 hour | 300 | Pending |
| Phase 8 | API AI | 1 hour | 200 | Pending |
| Phase 9 | API Specialized | 2 hours | 840 | Pending |
| **REPORTING** | | | | |
| Report Gen | Both | 1 hour | - | Pending |
| Documentation | Both | 1 hour | - | Pending |
| **TOTALS** | | **13 hours** | **1,962 tests** | **0%** |

---

## Updated Success Criteria

### Web Routes
- ✅ 120+ pages tested in ar/en
- ✅ 95%+ success rate
- ✅ All pages have proper i18n
- ✅ Language switcher functional

### API Routes
- ✅ 870 endpoints tested in ar/en (Accept-Language)
- ✅ 95%+ success rate
- ✅ Proper JSON responses
- ✅ Error messages in correct language
- ✅ Authentication works correctly
- ✅ Org context respected (RLS)
- ✅ Rate limiting enforced for AI endpoints

---

## Updated Deliverables

### Web Testing
1. `test-results/bilingual-web/test-report.json`
2. `test-results/bilingual-web/SUMMARY.md`
3. `test-results/bilingual-web/screenshots/` (240+ screenshots)

### API Testing
4. `test-results/bilingual-api/test-report.json`
5. `test-results/bilingual-api/SUMMARY.md`
6. `test-results/bilingual-api/response-samples/` (sample responses)

### Comprehensive
7. `COMPREHENSIVE_TEST_RESULTS.md` (combined web + API)
8. `I18N_COMPLIANCE_AUDIT.md` (i18n issues found)
9. `API_ENDPOINT_DOCUMENTATION.md` (auto-generated from tests)

---

## Next Steps (Updated)

1. ✅ **Create comprehensive testing plan** (Complete)
2. ✅ **Add API routes testing** (Complete)
3. **Develop bilingual web test script**
4. **Develop API test script**
5. **Fix social/posts 500 error** (blocker for web tests)
6. **Execute all web testing phases** (1-5)
7. **Execute all API testing phases** (6-9)
8. **Generate comprehensive reports**
9. **Document all issues**
10. **Update CLAUDE.md**

---

**Plan Updated:** 2025-11-28  
**Total Scope:** 120+ web pages + 870 API endpoints  
**Total Tests:** 1,962 bilingual tests  
**Estimated Duration:** 13 hours  
**Status:** Ready for execution
