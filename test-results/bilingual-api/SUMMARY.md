# Bilingual API Testing Report

**Date:** 2025-11-28T20:30:55.511Z
**Total Endpoints:** 37
**Total Tests:** 74 (37 endpoints × 2 languages)

## Summary

- ✅ Successful: 1
- ❌ Failed: 35
- ⏭️  Skipped: 1
- 📊 Success Rate: 2.8%

## By Category

| Category | Total | Tested | Success | Partial | Failed | Success Rate |
|----------|-------|--------|---------|---------|--------|-------------|
| authentication | 3 | 3 | 0 | 1 | 2 | 0.0% |
| campaigns | 3 | 3 | 0 | 0 | 3 | 0.0% |
| ad-campaigns | 2 | 2 | 0 | 0 | 2 | 0.0% |
| social | 3 | 3 | 0 | 0 | 3 | 0.0% |
| analytics | 4 | 4 | 0 | 0 | 4 | 0.0% |
| ai | 3 | 3 | 0 | 0 | 3 | 0.0% |
| creative | 3 | 3 | 0 | 0 | 3 | 0.0% |
| team | 3 | 3 | 0 | 0 | 3 | 0.0% |
| platforms | 3 | 3 | 0 | 0 | 3 | 0.0% |
| audiences | 2 | 2 | 0 | 0 | 2 | 0.0% |
| settings | 3 | 3 | 0 | 0 | 3 | 0.0% |
| webhooks | 2 | 2 | 0 | 0 | 2 | 0.0% |
| exports | 2 | 2 | 0 | 0 | 2 | 0.0% |

## Endpoints Tested

⚠️ **POST /api/auth/logout** - authentication
   - Arabic: status=200, success=true, i18n=No
   - English: status=401, success=false, i18n=Yes

❌ **GET /api/user** - authentication
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **POST /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/context** - authentication
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/campaigns** - campaigns
   - Arabic: status=401, success=false, i18n=Yes
   - English: status=401, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/campaigns/stats** - campaigns
   - Arabic: status=401, success=false, i18n=Yes
   - English: status=401, success=false, i18n=Yes

❌ **POST /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/campaigns** - campaigns
   - Arabic: status=401, success=false, i18n=Yes
   - English: status=401, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/ad-campaigns** - ad-campaigns
   - Arabic: status=401, success=false, i18n=Yes
   - English: status=401, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/ad-campaigns/active** - ad-campaigns
   - Arabic: status=401, success=false, i18n=Yes
   - English: status=401, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/social/posts** - social
   - Arabic: status=401, success=false, i18n=Yes
   - English: status=401, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/social/posts/scheduled** - social
   - Arabic: status=401, success=false, i18n=Yes
   - English: status=401, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/social/analytics** - social
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/analytics/dashboard** - analytics
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/analytics/realtime** - analytics
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/analytics/kpis** - analytics
   - Arabic: status=401, success=false, i18n=Yes
   - English: status=401, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/analytics/metrics** - analytics
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **POST /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/ai/semantic-search** - ai
   - Arabic: status=401, success=false, i18n=Yes
   - English: status=401, success=false, i18n=Yes

❌ **POST /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/ai/content-generation** - ai
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/ai/insights** - ai
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/creative/assets** - creative
   - Arabic: status=401, success=false, i18n=Yes
   - English: status=401, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/creative/briefs** - creative
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/creative/templates** - creative
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/team/members** - team
   - Arabic: status=401, success=false, i18n=Yes
   - English: status=401, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/team/invitations** - team
   - Arabic: status=401, success=false, i18n=Yes
   - English: status=401, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/team/roles** - team
   - Arabic: status=401, success=false, i18n=Yes
   - English: status=401, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/platforms/connections** - platforms
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/platforms/meta/status** - platforms
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/platforms/google/status** - platforms
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/audiences** - audiences
   - Arabic: status=401, success=false, i18n=Yes
   - English: status=401, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/audiences/segments** - audiences
   - Arabic: status=401, success=false, i18n=Yes
   - English: status=401, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings** - settings
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/brand-voices** - settings
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/approval-workflows** - settings
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/webhooks** - webhooks
   - Arabic: status=405, success=false, i18n=Yes
   - English: status=405, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/webhooks/logs** - webhooks
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/exports** - exports
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

❌ **GET /api/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/exports/history** - exports
   - Arabic: status=404, success=false, i18n=Yes
   - English: status=404, success=false, i18n=Yes

