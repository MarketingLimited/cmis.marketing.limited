# Bilingual Web Testing Report

**Date:** 2025-11-28T20:15:32.202Z
**Total Pages:** 76
**Total Tests:** 152 (76 pages × 2 languages)

## Summary

- ✅ Successful: 53
- ❌ Failed: 23
- 📊 Success Rate: 69.7%

## By Category

| Category | Total | Tested | Success | Failed | Success Rate |
|----------|-------|--------|---------|--------|-------------|
| guest | 4 | 4 | 4 | 0 | 100.0% |
| authenticated-non-org | 16 | 16 | 2 | 14 | 12.5% |
| org-core | 36 | 36 | 34 | 2 | 94.4% |
| org-settings | 20 | 20 | 13 | 7 | 65.0% |

## Pages Tested

✅ **guest-login** - guest
   - Path: `/login`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **guest-register** - guest
   - Path: `/register`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **guest-invitation-accept** - guest
   - Path: `/invitation/accept/dummy-token`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **guest-invitation-decline** - guest
   - Path: `/invitation/decline/dummy-token`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **auth-orgs-list** - authenticated-non-org
   - Path: `/orgs`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **auth-home** - authenticated-non-org
   - Path: `/home`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **auth-onboarding** - authenticated-non-org
   - Path: `/onboarding`
   - Arabic: locale=en, dir=unknown, i18n=No
   - English: No language switcher available

❌ **auth-onboarding-industry** - authenticated-non-org
   - Path: `/onboarding/industry`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **auth-onboarding-goals** - authenticated-non-org
   - Path: `/onboarding/goals`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **auth-onboarding-complete** - authenticated-non-org
   - Path: `/onboarding/complete`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **auth-profile** - authenticated-non-org
   - Path: `/profile`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **auth-profile-edit** - authenticated-non-org
   - Path: `/profile/edit`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **auth-settings** - authenticated-non-org
   - Path: `/settings`
   - Arabic: locale=en, dir=unknown, i18n=No
   - English: No language switcher available

❌ **auth-offerings** - authenticated-non-org
   - Path: `/offerings`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **auth-products** - authenticated-non-org
   - Path: `/products`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **auth-services** - authenticated-non-org
   - Path: `/services`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **auth-organizations-create** - authenticated-non-org
   - Path: `/organizations/create`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **auth-subscriptions** - authenticated-non-org
   - Path: `/subscriptions`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **auth-subscriptions-manage** - authenticated-non-org
   - Path: `/subscriptions/manage`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **auth-subscriptions-payment** - authenticated-non-org
   - Path: `/subscriptions/payment`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-home** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-dashboard** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/dashboard`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-campaigns** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/campaigns`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-campaigns-create** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/campaigns/create`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-analytics** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/analytics`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-analytics-realtime** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/analytics/realtime`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-analytics-kpis** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/analytics/kpis`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **org-analytics-reports** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/analytics/reports`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-creative-assets** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/creative/assets`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-creative-briefs** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/creative/briefs`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-creative-briefs-create** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/creative/briefs/create`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-creative-ads** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/creative/ads`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-creative-templates** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/creative/templates`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-social** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/social`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **org-social-posts** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/social/posts`
   - Arabic: locale=en, dir=unknown, i18n=No
   - English: No language switcher available
   - ⚠️  Known Issue: 500 error - undefined $currentOrg

✅ **org-social-scheduler** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/social/scheduler`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-social-history** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/social/history`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-influencer** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/influencer`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-influencer-create** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/influencer/create`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-orchestration** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/orchestration`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-listening** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/listening`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-ai** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/ai`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-knowledge** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/knowledge`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-knowledge-create** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/knowledge/create`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-predictive** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/predictive`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-experiments** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/experiments`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-optimization** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/optimization`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-automation** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/automation`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-workflows** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/workflows`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-team** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/team`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-products** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/products`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-inbox** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/inbox`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-alerts** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/alerts`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-exports** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/exports`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-dashboard-builder** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/dashboard-builder`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-feature-flags** - org-core
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/feature-flags`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-settings-user** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/user`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-settings-organization** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/organization`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **org-settings-platforms** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platforms`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **org-settings-platforms-meta** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platforms/meta`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **org-settings-platforms-google** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platforms/google`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **org-settings-platforms-tiktok** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platforms/tiktok`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **org-settings-platforms-linkedin** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platforms/linkedin`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **org-settings-platforms-twitter** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platforms/twitter`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

❌ **org-settings-platforms-snapchat** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/platforms/snapchat`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-settings-profile-groups** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/profile-groups`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-settings-profile-groups-create** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/profile-groups/create`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-settings-brand-voices** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/brand-voices`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-settings-brand-voices-create** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/brand-voices/create`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-settings-brand-safety** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/brand-safety`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-settings-brand-safety-create** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/brand-safety/create`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-settings-approval-workflows** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/approval-workflows`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-settings-approval-workflows-create** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/approval-workflows/create`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-settings-boost-rules** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/boost-rules`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-settings-boost-rules-create** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/boost-rules/create`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

✅ **org-settings-ad-accounts** - org-settings
   - Path: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/settings/ad-accounts`
   - Arabic: locale=en, dir=ltr, i18n=No
   - English: locale=en, dir=ltr, i18n=No

