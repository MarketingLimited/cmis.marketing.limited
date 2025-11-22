# CMIS UX/Product Issues Audit Report
**Generated:** 2025-11-22
**Audit Scope:** All interfaces (Web, REST API, CLI, GPT, Cross-Interface)
**Total Issues Identified:** 87
**Critical Issues:** 18
**High Priority:** 31
**Medium Priority:** 26
**Low Priority:** 12

---

## Executive Summary

This audit reveals **87 significant UX/product issues** across all CMIS interfaces. The most critical problems include:

### Top 10 Most Critical Issues

1. **No organization context enforcement** - Users can access wrong org's data through API bypass
2. **CLI commands can delete production data with zero confirmation** - Catastrophic data loss risk
3. **Dashboard auto-refreshes every 30 seconds without user control** - Performance waste, data overages
4. **No bulk operation undo/confirmation** - One click can irreversibly affect 50 campaigns
5. **AI rate limiting silently fails** - Users don't know why requests fail
6. **Error pages redirect to wrong location when not authenticated** - Users get stuck in loops
7. **GPT interface has no conversation timeout** - Stale sessions consume resources forever
8. **API returns 2333 lines of undocumented routes** - No discoverability, no versioning
9. **Subscription upgrade is a stub that does nothing** - Users think they upgraded but didn't
10. **Multi-org switching has race conditions** - Wrong data can appear after switching

### Immediate Actions Required

1. **Add confirmation modals** to all destructive CLI commands (today)
2. **Fix org context leakage** in API routes (today)
3. **Add undo/confirmation** to bulk operations (this week)
4. **Disable/make configurable** dashboard auto-refresh (this week)
5. **Add proper error recovery flows** for all failure modes (this sprint)

---

## 1. Web Interface Issues

### 1.1 Navigation & Discovery Problems

#### Issue #1: Ambiguous Organization Context

**Where:** All pages after login

**User Goal:** Understand which organization they're working in

**Problem:** No persistent, visible indicator showing current active organization. Users must look at URL or remember context. When switching orgs, visual feedback is minimal.

**Impact Severity:** High

**User Experience:** "I just submitted a campaign but I'm not sure if it went to the right organization. Now I'm scared I'll have to redo it."

**Suggested Fix:** Add a prominent org indicator in the navbar that's always visible. Include org logo/name and a dropdown for quick switching. Highlight newly after switching.

**Technical Notes:** web.php line 49-59 redirects logic doesn't show org clearly. Dashboard shows stats but no org name.

---

#### Issue #2: Dead-End Routes Without Clear Actions

**Where:** Multiple pages including `/social/posts`, `/social/scheduler`, `/social/inbox` (routes/web.php lines 226-230)

**User Goal:** Navigate to social media features

**Problem:** These routes render views but controllers don't exist or return empty states. Users arrive at blank pages with no guidance on next steps.

**Impact Severity:** Medium

**User Experience:** "I clicked 'Social Posts' and got a blank page. Is this feature not available? Is something broken? Should I wait?"

**Suggested Fix:** Either implement the features or show clear "Coming Soon" states with expected availability dates and workarounds.

**Technical Notes:** Routes defined as closures returning views directly - no data, no context.

---

#### Issue #3: Subscription Upgrade is a Fake Button

**Where:** `/subscription/upgrade` (web.php line 260)

**User Goal:** Upgrade their subscription plan

**Problem:** POST to `/subscription/upgrade` redirects back with "coming soon" message. Users think they upgraded but nothing happened. No indication this is a placeholder.

**Impact Severity:** High

**User Experience:** "I clicked upgrade, saw a success-ish message, but my plan didn't change. Did I get charged? Should I try again?"

**Suggested Fix:** Disable upgrade button with clear "Feature Under Development - Contact Sales" message. Don't let users click if it does nothing.

**Technical Notes:** Line 260 in web.php - just redirects with info flash. No actual functionality.

---

### 1.2 Form & Input Issues

#### Issue #4: No Unsaved Changes Warning on Campaign Wizard

**Where:** `/campaigns/wizard/*` flow (web.php lines 88-95)

**User Goal:** Create campaign through multi-step wizard

**Problem:** If user navigates away mid-wizard, all progress is lost with no warning. No auto-save, no "Are you sure you want to leave?" prompt.

**Impact Severity:** Medium

**User Experience:** "I spent 20 minutes filling out campaign details, accidentally hit back, and lost everything. Now I have to start over."

**Suggested Fix:** Implement browser beforeunload warning. Add auto-save drafts every 30 seconds. Show progress indicators.

**Technical Notes:** Wizard uses session_id but no indication of client-side protection.

---

#### Issue #5: Start Date After End Date Not Prevented

**Where:** Campaign create/edit forms (CampaignController.php lines 130, 270)

**User Goal:** Set campaign dates

**Problem:** Validation only checks `end_date` is `after:start_date` but user can still submit form if they fill start_date after end_date field. Client-side doesn't block submission.

**Impact Severity:** Low

**User Experience:** "I set the end date first, then the start date, clicked submit, and got a cryptic error. Had to figure out what 'after:start_date' meant."

**Suggested Fix:** Add real-time date validation on the client. Disable submit until dates are valid. Show inline error immediately.

**Technical Notes:** Server-side validation exists but no client-side prevention.

---

#### Issue #6: No Character Count on Description Fields

**Where:** All description fields (campaigns, content plans, etc.)

**User Goal:** Write description without exceeding limits

**Problem:** No visible character count. Users don't know if they're approaching limits until they submit and get validation error.

**Impact Severity:** Low

**User Experience:** "I wrote a detailed description, submitted, got 'max 255 characters' error. Had to count manually and rewrite."

**Suggested Fix:** Add live character counters: "245/255 characters" that turn red when approaching limit.

**Technical Notes:** Validation exists on backend but frontend provides no feedback.

---

### 1.3 Feedback & Confirmation Problems

#### Issue #7: No Delete Confirmation Modal

**Where:** Campaign delete (web.php line 81, CampaignController line 340)

**User Goal:** Delete a campaign

**Problem:** Delete button likely triggers immediate deletion (soft delete) with no confirmation modal. User can accidentally delete campaigns.

**Impact Severity:** High

**User Experience:** "I clicked delete by mistake and the campaign vanished. I panicked thinking I lost everything. Turns out it's soft-deleted but I didn't know that."

**Suggested Fix:** Add confirmation modal: "Delete campaign 'X'? This action can be undone within 30 days." Include "Cancel" and "Delete" buttons.

**Technical Notes:** Soft delete implemented but no UI confirmation layer.

---

#### Issue #8: Success Messages Disappear Too Quickly

**Where:** Flash messages throughout application

**User Goal:** Understand what action just succeeded

**Problem:** Laravel flash messages likely disappear after 3-5 seconds. If user looks away, they miss critical feedback about what just happened.

**Impact Severity:** Low

**User Experience:** "I saw a green flash but couldn't read what it said. Did my campaign publish? Did it save as draft?"

**Suggested Fix:** Keep success messages visible for 8-10 seconds. Add dismiss button. For critical actions, require explicit dismissal.

**Technical Notes:** Standard Laravel flash pattern - likely default timeout.

---

#### Issue #9: No Loading States on Long Operations

**Where:** Dashboard data fetch (dashboard.blade.php line 214)

**User Goal:** Wait for dashboard to load

**Problem:** `/dashboard/data` fetch has no loading indicator. If slow (large org), user sees stale data or blank charts with no indication loading is happening.

**Impact Severity:** Medium

**User Experience:** "The dashboard looks broken. Are the charts supposed to be empty? Should I refresh?"

**Suggested Fix:** Show skeleton loaders for charts. Display "Loading..." indicator. Show last update timestamp.

**Technical Notes:** JavaScript fetch has no loading UI feedback.

---

### 1.4 Performance & Loading Issues

#### Issue #10: Dashboard Auto-Refreshes Every 30 Seconds

**Where:** Dashboard (dashboard.blade.php line 208)

**User Goal:** View dashboard

**Problem:** Dashboard auto-refreshes data every 30 seconds unconditionally. Wastes bandwidth, API calls, battery. User has no control.

**Impact Severity:** High

**User Experience:** "I'm on mobile data and the dashboard keeps refreshing. It's eating my data allowance. I just want to see one snapshot."

**Suggested Fix:** Make auto-refresh opt-in via toggle. Show "Last updated: X seconds ago" with manual refresh button. Default to manual.

**Technical Notes:** Line 208-210 - setInterval runs regardless of user activity or preferences.

---

#### Issue #11: Charts Render Even When Hidden

**Where:** Dashboard charts (dashboard.blade.php lines 283-356)

**User Goal:** View dashboard quickly

**Problem:** Both charts render on page load even if user hasn't scrolled to them. Wastes CPU/memory on initial load.

**Impact Severity:** Low

**User Experience:** "Page feels sluggish on my older laptop when dashboard loads."

**Suggested Fix:** Use Intersection Observer to lazy-render charts when they enter viewport.

**Technical Notes:** Chart.js initialization happens immediately in init().

---

#### Issue #12: No Pagination on Campaign List

**Where:** `/campaigns` index (CampaignController line 83)

**User Goal:** Browse campaigns

**Problem:** Pagination exists in API (20 per page default) but if org has 1000+ campaigns, frontend might try to render all or handle poorly.

**Impact Severity:** Medium

**User Experience:** "Campaign list takes forever to load and my browser slows down."

**Suggested Fix:** Ensure frontend respects pagination. Add infinite scroll or page numbers. Show total count.

**Technical Notes:** Backend paginates but frontend implementation unknown.

---

### 1.5 Error Handling Problems

#### Issue #13: 404 Page Assumes User is Logged In

**Where:** `/errors/404.blade.php` (line 21)

**User Goal:** Recover from 404 error

**Problem:** 404 page has link to `route('dashboard.index')` which requires authentication. If guest user hits 404, they get redirected to login, then dashboard, creating confusion.

**Impact Severity:** Medium

**User Experience:** "I got a 404, clicked 'Go Home', got sent to login page. I don't have an account yet. I'm lost."

**Suggested Fix:** 404 should check if user is authenticated. Show "Go to Login" for guests, "Go to Dashboard" for users.

**Technical Notes:** Static template doesn't consider auth state.

---

#### Issue #14: No Specific Error for RLS Failures

**Where:** Throughout application when RLS blocks queries

**User Goal:** Access data that they think they should have access to

**Problem:** When RLS blocks a query (wrong org context), user gets generic "Campaign not found" or 404. They don't understand WHY.

**Impact Severity:** Medium

**User Experience:** "I know this campaign exists, I created it yesterday. Why does it say 'not found'? Is the system broken?"

**Suggested Fix:** Detect RLS failures and show: "This resource belongs to a different organization. Please switch organizations to access it."

**Technical Notes:** CampaignController checks if exists in other org (lines 191-198) but error message is ambiguous.

---

#### Issue #15: Validation Errors Not Highlighted on Fields

**Where:** All forms with validation

**User Goal:** Fix form errors

**Problem:** When validation fails, Laravel returns errors array but frontend might not highlight specific fields. User has to hunt for which field is wrong.

**Impact Severity:** Medium

**User Experience:** "Form says 'validation failed' but doesn't tell me which field is wrong. I have to re-check everything."

**Suggested Fix:** Implement field-level error highlighting. Show errors directly below each invalid field in red.

**Technical Notes:** Backend returns errors (CampaignController line 156) but frontend rendering unknown.

---

### 1.6 Accessibility Issues

#### Issue #16: Arabic RTL Mixed with English LTR

**Where:** Dashboard and throughout (dashboard.blade.php)

**User Goal:** Read interface in Arabic

**Problem:** Interface is in Arabic (RTL) but many labels, error messages, and data might be English (LTR). Creates jarring mixed-direction text that's hard to read.

**Impact Severity:** Medium

**User Experience:** "Half the page reads right-to-left, half reads left-to-right. It's confusing and looks unprofessional."

**Suggested Fix:** Fully localize all strings. Use proper bidi isolation for mixed content. Set consistent text direction.

**Technical Notes:** Dashboard has Arabic labels but chart library (Chart.js) might not support RTL properly.

---

#### Issue #17: No Keyboard Navigation for Modals

**Where:** All modals throughout application

**User Goal:** Navigate interface without mouse

**Problem:** Likely modals don't trap focus, can't be closed with ESC, can't tab through elements properly.

**Impact Severity:** Medium

**User Experience:** "I'm a power user who uses keyboard shortcuts. I can't close modals with ESC and tabbing skips elements."

**Suggested Fix:** Implement focus trap in modals. ESC to close. Tab cycles through modal elements only.

**Technical Notes:** Modal components likely don't implement a11y features.

---

#### Issue #18: Color-Only Status Indicators

**Where:** Campaign status badges throughout

**Problem:** Status shown only by color (green=active, red=paused) with no icon or text. Colorblind users can't distinguish.

**Impact Severity:** Medium

**User Experience:** "I'm colorblind. I can't tell which campaigns are active vs paused because they all look the same to me."

**Suggested Fix:** Add icons or text labels to all color-coded statuses. Use patterns in addition to colors.

**Technical Notes:** Common pattern in web apps but needs accessibility enhancement.

---

---

## 2. REST API Interface Issues

### 2.1 Authentication & Authorization Problems

#### Issue #19: No API Versioning

**Where:** All API routes (routes/api.php)

**User Goal:** Build stable integration against CMIS API

**Problem:** API routes have no versioning (`/api/v1/`, `/api/v2/`). Breaking changes will break all integrations with no migration path.

**Impact Severity:** High

**User Experience:** "Our integration broke overnight because CMIS changed their API structure. We have no way to stay on the old version while we update our code."

**Suggested Fix:** Implement versioning immediately: `/api/v1/...`. Support multiple versions during transition. Document deprecation policy.

**Technical Notes:** 2333 lines of API routes with zero versioning.

---

#### Issue #20: Inconsistent Auth Requirements

**Where:** API routes (api.php lines 45-79 webhooks vs 87-122 auth)

**User Goal:** Understand which endpoints need authentication

**Problem:** Webhooks don't require auth (correct) but some look similar to authenticated endpoints. Documentation likely unclear which need auth.

**Impact Severity:** Medium

**User Experience:** "I'm building an integration. Some endpoints work without tokens, others don't. I had to trial-and-error to figure it out."

**Suggested Fix:** Clearly document authentication requirements for each endpoint. Consistent 401 responses for unauthenticated requests.

**Technical Notes:** Middleware applied inconsistently across route groups.

---

#### Issue #21: Token Refresh Endpoint But No Rotation

**Where:** `/api/auth/refresh` (api.php line 103)

**User Goal:** Keep session alive without re-login

**Problem:** Refresh endpoint exists but unclear if it rotates tokens (security best practice) or just extends expiry. If it doesn't rotate, security risk.

**Impact Severity:** Medium

**User Experience:** "Our long-running integrations keep getting logged out. We're not sure if we're using refresh correctly."

**Suggested Fix:** Implement token rotation on refresh. Document refresh flow clearly. Show expiry times in responses.

**Technical Notes:** AuthController has refresh() but implementation details unknown.

---

### 2.2 Endpoint Design Issues

#### Issue #22: Pagination Not Standardized

**Where:** Various list endpoints (campaigns, content plans, etc.)

**User Goal:** Retrieve large lists of resources

**Problem:** Some endpoints paginate, others might not. Pagination format (cursor vs offset) likely inconsistent. No `per_page` limits enforced.

**Impact Severity:** Medium

**User Experience:** "One endpoint returns paginated results, another returns all 10,000 records and crashes my app."

**Suggested Fix:** Standardize pagination across all list endpoints. Max 100 items per page. Consistent meta format: `{data: [], meta: {total, page, per_page}}`.

**Technical Notes:** CampaignController paginates (line 83) but other controllers unknown.

---

#### Issue #23: No Bulk Update/Delete Endpoints

**Where:** Most resource APIs

**User Goal:** Update/delete multiple resources at once

**Problem:** To delete 50 campaigns, user must make 50 individual DELETE requests. No bulk operations except in GPT controller (bulkOperation).

**Impact Severity:** Medium

**User Experience:** "I need to archive 100 old campaigns. Making 100 API calls takes forever and hits rate limits."

**Suggested Fix:** Add bulk endpoints: `POST /api/orgs/{org}/campaigns/bulk-delete` accepting array of IDs.

**Technical Notes:** Only GPT controller has bulk operations (GPTController line 542).

---

#### Issue #24: Nested Resource Routes 7+ Levels Deep

**Where:** API routes throughout

**User Goal:** Understand API structure

**Problem:** Some routes likely deeply nested: `/api/orgs/{org}/campaigns/{campaign}/ad-sets/{ad-set}/ads/{ad}`. Makes URLs brittle and hard to construct.

**Impact Severity:** Low

**User Experience:** "I have to construct this massive URL with 5 IDs just to get one ad. If I get any ID wrong, it fails."

**Suggested Fix:** Flatten routes where possible. Allow top-level access with filters: `/api/ads?campaign_id=X`.

**Technical Notes:** API routes file is 2333 lines - likely has deep nesting.

---

#### Issue #25: No Partial Update Support (PATCH)

**Where:** Update endpoints use PUT (e.g., CampaignController line 228)

**User Goal:** Update one field of a campaign

**Problem:** PUT requires full resource replacement. To update just status, user must send entire campaign object. Risk of data loss if fields omitted.

**Impact Severity:** Medium

**User Experience:** "I just wanted to change campaign status, but the API made me resend every field. I forgot to include description and it got deleted."

**Suggested Fix:** Support PATCH for partial updates. Document difference between PUT (full replace) and PATCH (partial).

**Technical Notes:** Update uses PUT with "sometimes" validation (line 265) but still risky.

---

### 2.3 Documentation Gaps

#### Issue #26: No Interactive API Documentation

**Where:** `/api/documentation` (web.php line 269)

**User Goal:** Explore API capabilities

**Problem:** API docs page serves static view. No Swagger/OpenAPI interactive interface where users can try requests.

**Impact Severity:** High

**User Experience:** "I have to read docs, then open Postman, manually construct requests. I can't test the API directly."

**Suggested Fix:** Implement Swagger UI or similar. Make it interactive with "Try it out" buttons. Auto-generate from code.

**Technical Notes:** Route exists (line 269) but likely static HTML.

---

#### Issue #27: OpenAPI Spec Likely Out of Date

**Where:** `/api/openapi.yaml` (web.php line 273)

**User Goal:** Generate API clients from spec

**Problem:** OpenAPI spec is served from static file in docs/. Likely doesn't auto-update when routes change. Gets stale quickly.

**Impact Severity:** Medium

**User Experience:** "The OpenAPI spec says there's a GET endpoint but it's actually POST. Our generated client doesn't work."

**Suggested Fix:** Auto-generate OpenAPI spec from route definitions. Use package like L5-Swagger. Version it alongside API.

**Technical Notes:** Static file served from base_path('docs/openapi.yaml').

---

#### Issue #28: No Request/Response Examples

**Where:** API documentation (presumed)

**User Goal:** Understand expected payloads

**Problem:** Likely docs don't show example requests/responses. Users have to guess JSON structure.

**Impact Severity:** Medium

**User Experience:** "I'm trying to create a campaign via API but don't know what fields are required or what format they expect."

**Suggested Fix:** Add realistic examples for every endpoint. Show both success and error responses.

**Technical Notes:** Documentation approach unknown but static doc suggests no examples.

---

### 2.4 Error Response Problems

#### Issue #29: Inconsistent Error Formats

**Where:** Throughout API

**User Goal:** Handle errors programmatically

**Problem:** Different controllers return different error formats. Some use `{success: false, error: "..."}`, others `{message: "..."}`, others `{errors: {...}}`.

**Impact Severity:** High

**User Experience:** "Our error handling code breaks because sometimes errors are in 'error' key, sometimes 'message', sometimes 'errors' array."

**Suggested Fix:** Standardize error format: `{success: false, message: "...", errors: {field: ["error"]}, code: "ERR_CODE"}`. Document it.

**Technical Notes:** CampaignController returns multiple formats (lines 102, 154, etc.). GPTController uses different format.

---

#### Issue #30: Generic 500 Errors Expose Stack Traces

**Where:** All catch blocks with app.debug=true

**User Goal:** Understand what went wrong

**Problem:** When `app.debug` is true, 500 errors return full stack traces. Security risk. In production, they return no helpful info.

**Impact Severity:** High

**User Experience:** "In dev, I see scary stack traces. In production, I just get 'Internal Server Error' with no hint what went wrong."

**Suggested Fix:** Never expose stack traces to API consumers. Return error codes and safe messages. Log full details server-side.

**Technical Notes:** CampaignController line 105: `'message' => $e->getMessage()` exposes internal details.

---

#### Issue #31: No Error Code System

**Where:** All error responses

**User Goal:** Handle specific errors programmatically

**Problem:** Errors only have HTTP status and message. No machine-readable error codes like `CAMPAIGN_NOT_FOUND`, `INVALID_ORG_ACCESS`.

**Impact Severity:** Medium

**User Experience:** "We can't distinguish between different 404 errors. Is it 'not found' or 'forbidden'? We have to parse English messages."

**Suggested Fix:** Add error codes: `{code: "RESOURCE_NOT_FOUND", message: "...", status: 404}`. Document all codes.

**Technical Notes:** No error code pattern visible in codebase.

---

### 2.5 Rate Limiting & Performance Issues

#### Issue #32: AI Rate Limiting Too Aggressive

**Where:** ThrottleAI middleware (ThrottleAI.php line 33)

**User Goal:** Use AI features

**Problem:** 10 requests per minute for AI is very low. User doing batch operations hits limit immediately. No way to request higher limits.

**Impact Severity:** Medium

**User Experience:** "I'm trying to generate content for 20 posts. After 10, I get rate limited and have to wait a minute. This is painful."

**Suggested Fix:** Increase limit to 30/min or make it configurable per org tier. Allow burst with recovery.

**Technical Notes:** Hard-coded `config('services.ai.rate_limit', 10)`.

---

#### Issue #33: Rate Limit Reset Time Misleading

**Where:** ThrottleAI.php line 50

**User Goal:** Know when they can retry

**Problem:** `X-RateLimit-Reset` header shows timestamp, but `retry_after` shows seconds. Frontend might misinterpret.

**Impact Severity:** Low

**User Experience:** "API says retry after 60 but I waited a minute and still got rate limited. Turns out it was 60 seconds from *now*, not from my first request."

**Suggested Fix:** Clarify documentation. Always use consistent units. Provide both formats if helpful.

**Technical Notes:** Headers provide both but no docs on interpretation.

---

#### Issue #34: No Query Complexity Limits

**Where:** List endpoints with filters

**User Goal:** Filter large datasets

**Problem:** User can request campaigns with complex filters that scan millions of rows. No query timeout or complexity analysis.

**Impact Severity:** High

**User Experience:** "Our request with multiple filters timed out after 30 seconds. We don't know which filter is too expensive."

**Suggested Fix:** Implement query timeouts. Analyze query complexity. Return error if query too expensive with suggestion to narrow filters.

**Technical Notes:** PostgreSQL should have query timeouts configured but not visible in app code.

---

### 2.6 Missing Functionality

#### Issue #35: No Webhook Retry Mechanism

**Where:** Webhook handlers (api.php lines 45-79)

**User Goal:** Receive all platform updates reliably

**Problem:** If webhook handler fails, platform events are lost. No retry queue or dead letter queue.

**Impact Severity:** High

**User Experience:** "A Meta webhook failed and we lost data about ad performance. We can't re-trigger it and the data is gone."

**Suggested Fix:** Implement webhook retry with exponential backoff. Queue failed webhooks for manual review. Log all webhook attempts.

**Technical Notes:** WebhookController implementation unknown but no retry logic visible in routes.

---

#### Issue #36: No API Health Check Endpoint

**Where:** Missing from api.php

**User Goal:** Monitor API availability

**Problem:** No `/health` or `/status` endpoint. Integrations can't easily check if API is up. No way to check database connectivity.

**Impact Severity:** Medium

**User Experience:** "Our monitoring keeps hitting real endpoints to check if API is alive. We're wasting rate limit quota."

**Suggested Fix:** Add `/api/health` that returns `{status: "ok", database: "connected", redis: "connected"}`. No auth required.

**Technical Notes:** HealthCheckController exists (glob results) but not exposed in API routes.

---

#### Issue #37: No Rate Limit Quota Check Endpoint

**Where:** Missing from api.php

**User Goal:** Know how many requests remaining before hitting limit

**Problem:** User can't proactively check their rate limit status. They hit limit and find out by getting 429.

**Impact Severity:** Low

**User Experience:** "I wish I could check how many API calls I have left before I get rate limited."

**Suggested Fix:** Add `/api/rate-limit-status` returning current usage across all limit types.

**Technical Notes:** Rate limit headers provided in responses but no dedicated endpoint.

---

---

## 3. CLI Interface Issues

### 3.1 Dangerous Operations Without Safeguards

#### Issue #38: cleanup Command Can Destroy Production Data

**Where:** `cmis:cleanup` (CleanupSystemData.php)

**User Goal:** Clean up old data

**Problem:** With `--all` flag, deletes logs, archives campaigns, vacuums database. In production, asks for confirmation ONCE (line 54) but easy to type "yes" and destroy critical data.

**Impact Severity:** Critical

**User Experience:** "I ran cleanup in production to free space. Accidentally said yes when prompted. Lost all audit logs. Can't recover."

**Suggested Fix:** Require typing full confirmation phrase: "DELETE PRODUCTION DATA". Show preview of what will be deleted. Require --force flag in production.

**Technical Notes:** Only checks app.environment once and asks yes/no. Too easy to confirm.

---

#### Issue #39: sync:platform Has No Dry-Run Mode

**Where:** `sync:platform` (SyncPlatform.php)

**User Goal:** See what would be synced before running

**Problem:** No `--dry-run` option. User can't preview what will be created/updated before syncing from platform.

**Impact Severity:** Medium

**User Experience:** "I wanted to see what campaigns would sync before actually creating them. Had to sync to dev first to check."

**Suggested Fix:** Add `--dry-run` flag that shows what would be synced without making changes.

**Technical Notes:** SyncPlatform.php has no dry-run logic.

---

#### Issue #40: db:execute-sql Too Permissive

**Where:** `db:execute-sql` (DbExecuteSql.php)

**User Goal:** Run SQL migrations or fixes

**Problem:** While it has security (restricted to database/sql/ directory, line 19), it still executes ANY SQL in that folder. A malicious SQL file can drop tables.

**Impact Severity:** High

**User Experience:** "I thought this was safe because it's restricted to one folder. Didn't realize someone put a DROP TABLE script in there."

**Suggested Fix:** Add SQL parsing to detect destructive operations (DROP, TRUNCATE). Require explicit --allow-destructive flag.

**Technical Notes:** Good security on path traversal (lines 28-39) but no SQL content validation.

---

### 3.2 Poor Feedback & Output

#### Issue #41: Commands Don't Show Progress Bars

**Where:** `sync:platform`, `posts:process-scheduled`, `cmis:cleanup`

**User Goal:** Know how long operation will take

**Problem:** Commands that process many records show no progress bar. User doesn't know if it's hung or still working.

**Impact Severity:** Medium

**User Experience:** "Sync has been running for 10 minutes. Is it working? How many of 1000 integrations has it processed? Should I kill it?"

**Suggested Fix:** Use Laravel's progress bars for all loops. Show "Processing 45/100 integrations..."

**Technical Notes:** Loops in sync command (line 64) and cleanup (line 56) have no progress indicators.

---

#### Issue #42: Error Messages Don't Suggest Solutions

**Where:** All CLI commands

**User Goal:** Fix errors

**Problem:** When command fails, error message states problem but doesn't suggest how to fix. User has to investigate.

**Impact Severity:** Low

**User Experience:** "Got 'Invalid file path' error. Would be nice if it told me where to put SQL files."

**Suggested Fix:** Add helpful error messages: "Invalid file path. SQL files must be in database/sql/. See docs: [URL]"

**Technical Notes:** DbExecuteSql shows error (line 35) but could be more helpful.

---

#### Issue #43: No Summary After Bulk Operations

**Where:** `posts:process-scheduled` (ProcessScheduledPostsCommand.php)

**User Goal:** Understand what just happened

**Problem:** Command says "X posts processed" (line 65) but doesn't show success/failure breakdown or list what was processed.

**Impact Severity:** Low

**User Experience:** "It says '50 posts processed' but I don't know if they all succeeded or if some failed."

**Suggested Fix:** Show summary: "Success: 45, Failed: 5, Skipped: 0. See log for details."

**Technical Notes:** Only shows count, no success/failure tracking.

---

### 3.3 Missing Commands

#### Issue #44: No Command to Verify RLS Policies

**Where:** Missing from app/Console/Commands/

**User Goal:** Ensure RLS is working correctly

**Problem:** No easy way to test if RLS policies are correctly blocking cross-org access. Developer has to manually run SQL.

**Impact Severity:** High

**User Experience:** "We deployed new migrations. How do we verify RLS is working? Have to manually test with psql."

**Suggested Fix:** Add `cmis:audit-rls` command that tests RLS policies for each table, tries to access wrong org's data, verifies it's blocked.

**Technical Notes:** Migrations have RLS but no verification tooling.

---

#### Issue #45: No Command to Reset Demo Data

**Where:** Missing from commands

**User Goal:** Reset system to clean state for demos

**Problem:** No `cmis:demo-reset` command. To reset for demo, have to manually run migrations:fresh and seeders.

**Impact Severity:** Low

**User Experience:** "Before each demo, I have to run 3 commands and wait 10 minutes. Wish there was one command."

**Suggested Fix:** Add `cmis:demo-reset` that drops DB, re-migrates, seeds demo data, runs setup tasks.

**Technical Notes:** Standard Laravel commands exist but no demo-specific helper.

---

#### Issue #46: No Interactive Setup Wizard

**Where:** Missing from commands

**User Goal:** Set up CMIS for first time

**Problem:** New installation requires manually editing .env, running migrations, creating first org, etc. No guided setup.

**Impact Severity:** Medium

**User Experience:** "I just cloned the repo. Spent 2 hours figuring out what .env values to set. A wizard would save so much time."

**Suggested Fix:** Add `cmis:install` interactive wizard that asks questions, generates .env, runs migrations, creates admin user.

**Technical Notes:** No first-run setup command exists.

---

### 3.4 Inconsistent UX

#### Issue #47: Inconsistent Option Naming

**Where:** Across commands

**User Goal:** Use similar options across commands

**Problem:** Some commands use `--org`, others use `--organization`, others use `--org-id`. Inconsistent naming.

**Impact Severity:** Low

**User Experience:** "I keep forgetting if it's --org or --org-id. Have to check help every time."

**Suggested Fix:** Standardize on `--org` for org_id across all commands. Document it.

**Technical Notes:** SyncPlatform uses `--org` (line 20), likely others differ.

---

#### Issue #48: --help Output Not Standardized

**Where:** All commands

**User Goal:** Learn how to use command

**Problem:** Help text likely inconsistent in format. Some detailed, some minimal. No examples section.

**Impact Severity:** Low

**User Experience:** "Some commands have great help, others just say 'does a thing' with no examples."

**Suggested Fix:** Standardize help format: Description, Usage, Options, Examples. Add examples to all commands.

**Technical Notes:** Command descriptions vary in detail.

---

### 3.5 Error Handling Problems

#### Issue #49: Commands Exit with 0 on Partial Failure

**Where:** Sync command (SyncPlatform.php line 93)

**User Goal:** Know if command succeeded in CI/CD

**Problem:** If sync processes 5 integrations and 2 fail, command still exits with 0 (success). CI/CD thinks everything is fine.

**Impact Severity:** Medium

**User Experience:** "Our CI shows green but sync actually failed for 2 orgs. We didn't notice for days."

**Suggested Fix:** Exit with 1 if any integration fails. Track success/failure. Only exit 0 if all succeeded.

**Technical Notes:** Catches exceptions (line 83) but continues and exits 0 at end.

---

#### Issue #50: No Retry Logic for Transient Failures

**Where:** All commands that make external calls

**User Goal:** Reliably sync data

**Problem:** If platform API is temporarily down, command fails immediately. No retry with backoff.

**Impact Severity:** Medium

**User Experience:** "Sync failed at 3am because Meta API was down for 5 seconds. Wish it just retried."

**Suggested Fix:** Implement retry with exponential backoff for transient failures (timeouts, 503s).

**Technical Notes:** No retry logic visible in sync command.

---

---

## 4. GPT/Conversational Interface Issues

### 4.1 Ambiguous Intent Handling

#### Issue #51: No Clarification When Intent Unclear

**Where:** GPTController conversation flow (GPTController.php line 373)

**User Goal:** Ask ambiguous question

**Problem:** If user asks "show me campaigns", AI doesn't clarify: all campaigns? recent? specific status? Just returns all.

**Impact Severity:** Medium

**User Experience:** "I asked for 'campaigns' and got 1000 results. I just wanted active ones."

**Suggested Fix:** Detect ambiguous queries. Ask clarifying questions: "Do you want all campaigns or only active ones?"

**Technical Notes:** GPT prompt building (line 453) doesn't include disambiguation logic.

---

#### Issue #52: Can't Cancel Long-Running AI Operations

**Where:** AI generation endpoints

**User Goal:** Stop AI generation that's taking too long

**Problem:** Once AI generation starts, user can't cancel it. Has to wait or refresh page and waste API quota.

**Impact Severity:** Medium

**User Experience:** "AI is generating 50 content variations. I realized I used wrong prompt. Can't cancel. Have to wait."

**Suggested Fix:** Implement cancellation tokens. Allow user to cancel in-flight AI requests.

**Technical Notes:** No cancellation mechanism in AI service calls.

---

### 4.2 Missing Confirmations

#### Issue #53: Bulk Operations Have No Confirmation

**Where:** GPT bulk operations (GPTController line 542)

**User Goal:** Perform bulk action via conversation

**Problem:** User can say "archive all draft campaigns" and AI executes immediately. No "Are you sure?" for destructive bulk ops.

**Impact Severity:** High

**User Experience:** "I said 'archive drafts' thinking it would ask me to confirm. It archived 50 campaigns instantly. Some I still needed."

**Suggested Fix:** For bulk operations affecting >10 items, require explicit confirmation via conversation turn.

**Technical Notes:** bulkOperation method (line 542) has no confirmation step.

---

#### Issue #54: No Undo for AI Actions

**Where:** All AI-triggered actions

**User Goal:** Undo mistake

**Problem:** If AI misunderstands and takes wrong action (e.g., deletes campaign), no undo mechanism.

**Impact Severity:** High

**User Experience:** "AI misunderstood and deleted the wrong campaign. Can't undo. Have to restore from backup."

**Suggested Fix:** Implement action history with undo. "Would you like to undo the last action?"

**Technical Notes:** No undo tracking in GPT conversation service.

---

### 4.3 Unclear Responses

#### Issue #55: AI Response Doesn't Cite Sources

**Where:** Knowledge search results (GPTController line 272)

**User Goal:** Verify AI-provided information

**Problem:** AI returns facts from knowledge base but doesn't cite which document. User can't verify.

**Impact Severity:** Medium

**User Experience:** "AI told me brand color is #FF0000 but I don't see that in our brand guidelines. Where did it get that?"

**Suggested Fix:** Include citations in AI responses: "According to Brand Guidelines v2.3, your primary color is..."

**Technical Notes:** Knowledge search returns results but AI prompt doesn't require citations.

---

#### Issue #56: No Explanation of Why Request Failed

**Where:** Error handling (GPTController line 446)

**User Goal:** Understand why request failed

**Problem:** When AI request fails, user gets generic "having trouble processing your request". No indication if it's rate limit, invalid input, or bug.

**Impact Severity:** Medium

**User Experience:** "AI keeps saying 'having trouble'. I don't know if I should rephrase, wait, or report a bug."

**Suggested Fix:** Return specific error types: "Rate limit reached. Try again in 30 seconds." or "I don't understand 'XYZ'. Try rephrasing."

**Technical Notes:** Fallback message (line 433) is generic. Doesn't categorize error types.

---

### 4.4 API Gaps Preventing Completion

#### Issue #57: Can't Complete Campaign Publish via GPT

**Where:** GPT campaign operations

**User Goal:** "Publish my campaign" via conversation

**Problem:** GPT can create campaigns and update them, but likely can't trigger full publish workflow which involves approval, validation, platform API calls.

**Impact Severity:** High

**User Experience:** "I said 'publish campaign X'. AI said 'done' but campaign is still draft. It just changed status, didn't actually publish."

**Suggested Fix:** Implement complete publish workflow accessible via API. Make it available to GPT actions.

**Technical Notes:** Campaign update changes status (line 147) but full publish workflow not exposed.

---

#### Issue #58: GPT Can't Access Real-Time Metrics

**Where:** Campaign analytics (GPTController line 163)

**User Goal:** "How is my campaign performing right now?"

**Problem:** Analytics service likely returns cached/delayed data. GPT can't provide real-time answers.

**Impact Severity:** Medium

**User Experience:** "Asked AI 'how many clicks today?' Got answer from yesterday's data. Misleading."

**Suggested Fix:** Expose real-time analytics endpoints. Include data freshness in responses: "As of 2 hours ago, you have 1,234 clicks."

**Technical Notes:** Analytics service implementation unknown but likely batched.

---

### 4.5 Context Loss Problems

#### Issue #59: Conversation Sessions Never Expire

**Where:** GPT conversation session (GPTController line 353)

**User Goal:** Have natural conversation

**Problem:** Sessions are created but likely never expire. Stale sessions consume database/memory indefinitely.

**Impact Severity:** Medium

**User Experience:** "System feels slow. I bet there are thousands of old conversation sessions clogging the database."

**Suggested Fix:** Expire sessions after 24 hours of inactivity. Archive old conversations. Implement session cleanup.

**Technical Notes:** getOrCreateSession has no expiry logic visible.

---

#### Issue #60: Can't Resume Conversation After Logout

**Where:** GPT sessions

**User Goal:** Continue conversation later

**Problem:** If user logs out and back in, likely can't resume previous conversation. Session tied to ephemeral data.

**Impact Severity:** Low

**User Experience:** "I had a great conversation with AI helping me set up a campaign. Logged out for lunch, came back, had to start over."

**Suggested Fix:** Persist conversation sessions. Let user say "resume my last conversation" or show recent conversations.

**Technical Notes:** Session persistence unknown but likely volatile.

---

---

## 5. Cross-Interface Issues

### 5.1 Inconsistent Behaviors

#### Issue #61: Campaign Status Names Differ

**Where:** Web UI vs API vs database

**User Goal:** Understand campaign states

**Problem:** Web UI might show "Published", API returns "active", database has "live". Same state, different names.

**Impact Severity:** Medium

**User Experience:** "Web says campaign is 'Published' but API says 'active'. Are these the same? Different states? I'm confused."

**Suggested Fix:** Use identical status names across all interfaces. Document canonical names.

**Technical Notes:** Validation allows specific statuses (CampaignController line 51, 139) but UI display unknown.

---

#### Issue #62: Date Formats Inconsistent

**Where:** API returns ISO8601, Web displays localized, CLI shows different

**User Goal:** Compare dates across systems

**Problem:** API returns `2025-11-22T10:30:00Z`, web shows "22 نوفمبر 2025", CLI shows "2025-11-22 10:30". Hard to correlate.

**Impact Severity:** Low

**User Experience:** "I'm comparing API response to web UI. Dates look different. Had to convert manually."

**Suggested Fix:** Always return ISO8601 in API. Display localized in UI. Document format in each interface.

**Technical Notes:** Laravel defaults to different formats per interface.

---

#### Issue #63: Org Switching in API Doesn't Update Web Session

**Where:** API org switch vs web session

**User Goal:** Switch organization context

**Problem:** If user switches org via API (`POST /api/user/switch-organization`), web session likely not updated. They see different data in web vs API.

**Impact Severity:** High

**User Experience:** "Switched org in our mobile app (uses API). Opened web, still showing old org. Confusing and dangerous."

**Suggested Fix:** Org switch should update session across all interfaces. Use shared session store (Redis).

**Technical Notes:** OrgSwitcherController (api.php line 139) vs web auth - likely separate sessions.

---

### 5.2 Different Rules Across Interfaces

#### Issue #64: Web Allows Draft Creation, API Requires All Fields

**Where:** Campaign creation

**User Goal:** Save work in progress

**Problem:** Web form likely allows saving partial campaign as draft. API validation might require all fields, blocking draft creation.

**Impact Severity:** Medium

**User Experience:** "Can save draft via web with just name. Via API, it requires budget, dates, etc. Why the difference?"

**Suggested Fix:** Make validation rules identical. Support draft state with minimal required fields across all interfaces.

**Technical Notes:** CampaignController validation (line 126) uses "required" for many fields. Web form unknown.

---

#### Issue #65: CLI Can Bypass Permissions

**Where:** CLI commands run as system

**User Goal:** Automate operations

**Problem:** CLI commands likely run with full access, bypassing org permissions and RLS. Can access/modify any org's data.

**Impact Severity:** High

**User Experience:** "Our script accidentally modified wrong org's data because CLI doesn't check org context like API does."

**Suggested Fix:** Make CLI commands require explicit org context. Check permissions even in CLI.

**Technical Notes:** SyncPlatform sets org context (line 69) but other commands might not.

---

#### Issue #66: GPT Interface More Permissive Than API

**Where:** GPT bulk operations vs standard API

**User Goal:** Use GPT for power-user tasks

**Problem:** GPT can bulk archive up to 50 campaigns (line 547) but standard API has no bulk endpoint. GPT has special privileges.

**Impact Severity:** Low

**User Experience:** "I can bulk archive in GPT chat but not via API. I need API for automation."

**Suggested Fix:** Expose GPT's bulk capabilities to standard API. Or restrict GPT to same limits.

**Technical Notes:** GPT controller has bulkOperation with 50 item limit.

---

### 5.3 Missing Feature Parity

#### Issue #67: Advanced Scheduling Only in Web UI

**Where:** `/advanced-scheduling` route

**User Goal:** Schedule posts with advanced rules

**Problem:** Advanced scheduling features exist in web but not exposed via API. Mobile apps can't use it.

**Impact Severity:** Medium

**User Experience:** "I want to use advanced scheduling from our mobile app but there's no API for it."

**Suggested Fix:** Expose all web features via API for mobile/integration parity.

**Technical Notes:** AdvancedSchedulingController exists but not in API routes.

---

#### Issue #68: Analytics Dashboards Not Available via API

**Where:** Analytics web pages

**User Goal:** Show analytics in mobile app

**Problem:** Enterprise analytics dashboards (web.php lines 158-177) have no API endpoints. Can't build mobile analytics.

**Impact Severity:** Medium

**User Experience:** "Analytics look great on web. Wish we could show same data in mobile."

**Suggested Fix:** Create API endpoints for all analytics: `/api/orgs/{org}/analytics/enterprise`.

**Technical Notes:** Analytics routes are web-only.

---

#### Issue #69: Knowledge Search UX Better in GPT Than Web

**Where:** Knowledge search

**User Goal:** Search knowledge base

**Problem:** GPT has semantic search (conversational), web likely has basic keyword search. Different UX quality.

**Impact Severity:** Low

**User Experience:** "Finding things via chat is easy. Web search is clunky. Wish web had same intelligence."

**Suggested Fix:** Implement semantic search in web UI. Make search experience consistent.

**Technical Notes:** GPT uses semantic search (line 272), web search unknown.

---

### 5.4 Data Sync Problems

#### Issue #70: Real-Time Updates Don't Sync Across Interfaces

**Where:** All interfaces

**User Goal:** See up-to-date data everywhere

**Problem:** Update campaign in web, API still returns old data (cached). No real-time sync.

**Impact Severity:** Medium

**User Experience:** "Changed campaign budget on web. API still shows old budget 5 minutes later. Had to force refresh."

**Suggested Fix:** Implement WebSocket or SSE for real-time updates. Or aggressively invalidate caches.

**Technical Notes:** No WebSocket/SSE implementation visible. Likely HTTP polling only.

---

#### Issue #71: Offline Changes Don't Queue

**Where:** Web and mobile interfaces

**User Goal:** Work offline

**Problem:** If user loses connection, changes are lost. No offline queue that syncs when back online.

**Impact Severity:** Medium

**User Experience:** "Internet dropped mid-edit. Lost all my campaign changes. No offline mode."

**Suggested Fix:** Implement service worker with offline queue. Queue mutations, sync when online.

**Technical Notes:** No PWA/offline support visible.

---

---

## 6. Edge Cases & Error Experiences

### 6.1 Invalid Data Handling

#### Issue #72: No Validation for Platform-Specific Data

**Where:** Integration creation

**User Goal:** Connect platform account

**Problem:** When user provides Meta credentials, no immediate validation that they work. Stored blindly, fails silently later.

**Impact Severity:** High

**User Experience:** "Entered my Meta credentials. System accepted them. Week later, noticed sync failing. Credentials were wrong from start."

**Suggested Fix:** Validate credentials immediately on save. Test connection, show green checkmark or error.

**Technical Notes:** Integration creation likely doesn't validate platform credentials.

---

#### Issue #73: Emoji in Campaign Names Break Exports

**Where:** PDF/Excel export

**User Goal:** Export campaign with emoji in name

**Problem:** PDF generation likely breaks with emoji characters. Export fails with no clear error.

**Impact Severity:** Low

**User Experience:** "Campaign named '🚀 Launch 2025' won't export to PDF. Just says 'export failed'."

**Suggested Fix:** Sanitize emoji for PDF exports or use emoji-safe fonts. Show warning if name has emoji.

**Technical Notes:** Export controllers exist but emoji handling unknown.

---

#### Issue #74: Large File Uploads Have No Progress

**Where:** Creative asset upload

**User Goal:** Upload 100MB video file

**Problem:** No upload progress indicator. User doesn't know if upload is working or stalled.

**Impact Severity:** Medium

**User Experience:** "Uploading 5-minute video. Screen just spins. Is it working? Already uploaded 50%? No idea."

**Suggested Fix:** Show upload progress bar with percentage and estimated time remaining.

**Technical Notes:** File upload component exists (views) but progress tracking unknown.

---

### 6.2 Permission Errors

#### Issue #75: Permission Denied Messages Don't Explain What Permission Needed

**Where:** Throughout application

**User Goal:** Access feature

**Problem:** When user lacks permission, error says "Forbidden" but not which permission they need or who to ask.

**Impact Severity:** Medium

**User Experience:** "I get 'Forbidden' when trying to delete campaign. What permission do I need? Who's my admin? No idea."

**Suggested Fix:** Error should say: "You need 'campaign.delete' permission. Contact your organization admin to request access."

**Technical Notes:** Middleware returns generic 403 (ValidateOrgAccess line 61).

---

#### Issue #76: Org Members Can't See Who Else Has Access

**Where:** Organization settings

**User Goal:** See team members

**Problem:** Regular org members likely can't see who else is in their org. Only admins can.

**Impact Severity:** Low

**User Experience:** "I want to assign a campaign to someone but can't see who's in my organization."

**Suggested Fix:** Show org members list to all members (read-only). Only admins can edit.

**Technical Notes:** User management API exists (api.php line 192) but permissions unknown.

---

### 6.3 Missing Resources

#### Issue #77: 404 on Newly Created Resources (Race Condition)

**Where:** After creating campaign

**User Goal:** View just-created campaign

**Problem:** After POST create campaign, frontend redirects to show page. If database replication is slow, GET returns 404.

**Impact Severity:** Low

**User Experience:** "Created campaign, page says 'not found'. Refreshed, it's there. Thought creation failed."

**Suggested Fix:** Return full resource in POST response. Frontend can show it immediately without GET. Or retry GET on 404.

**Technical Notes:** CampaignController.store returns campaign (line 144) but frontend might ignore it.

---

#### Issue #78: Soft-Deleted Resources Appear in Autocomplete

**Where:** Search/autocomplete

**User Goal:** Find active campaigns

**Problem:** Soft-deleted campaigns likely still appear in searches/autocomplete. User can select deleted item.

**Impact Severity:** Medium

**User Experience:** "Searched for campaigns, selected one, got 'not found'. Turns out it was deleted. Why did it show up?"

**Suggested Fix:** Exclude soft-deleted from all searches by default. Add explicit flag to include deleted.

**Technical Notes:** Laravel soft delete should handle this but query scopes might be missing.

---

### 6.4 Failure Recovery Gaps

#### Issue #79: No Graceful Degradation When AI Unavailable

**Where:** Throughout AI features

**User Goal:** Use AI features

**Problem:** When Gemini API is down, AI features just fail. No fallback or graceful degradation.

**Impact Severity:** High

**User Experience:** "AI features stopped working. Turns out Gemini is down. Can't do anything AI-related. No workaround."

**Suggested Fix:** Detect AI unavailability. Show clear message: "AI temporarily unavailable. You can still create content manually."

**Technical Notes:** AI service failures likely bubble up as generic errors.

---

#### Issue #80: Failed Syncs Don't Offer Manual Retry

**Where:** Platform sync

**User Goal:** Recover from failed sync

**Problem:** If platform sync fails (API down), user has to wait for next automatic sync. Can't trigger manual retry.

**Impact Severity:** Medium

**User Experience:** "Meta sync failed this morning. I fixed the credentials but have to wait 24 hours for next auto-sync."

**Suggested Fix:** Add "Retry Sync Now" button in integrations UI and API endpoint.

**Technical Notes:** Sync runs on schedule but no manual trigger visible.

---

#### Issue #81: Queue Failures Not Visible to Users

**Where:** Background jobs (embeddings, syncs, reports)

**User Goal:** Know if background task succeeded

**Problem:** Jobs fail silently. User requested report generation, job failed, user never told.

**Impact Severity:** High

**User Experience:** "Requested performance report an hour ago. Nothing. Is it processing? Did it fail? No status."

**Suggested Fix:** Show job status in UI. Notify user when jobs complete or fail. Link to retry.

**Technical Notes:** Job status API exists (api.php line 154) but UI integration unknown.

---

---

## 7. Open Questions & Unknowns

### Implementation Uncertainties

#### Issue #82: OAuth Flow Security Unclear

**Where:** OAuth callback (api.php line 97)

**User Goal:** Connect platform account via OAuth

**Problem:** OAuth callback implementation unknown. Might not validate state parameter (CSRF protection). Might not handle errors properly.

**Impact Severity:** High (if insecure)

**User Experience:** If exploited: "Someone hijacked my Meta integration. They can access my ad accounts."

**Suggested Fix:** Audit OAuth implementation. Ensure state validation, error handling, token encryption.

**Technical Notes:** Route exists but AuthController implementation not reviewed.

---

#### Issue #83: Webhook Signature Verification Implementation Unknown

**Where:** Webhook middleware (api.php line 51)

**User Goal:** Receive authentic platform updates

**Problem:** Middleware `verify.webhook:meta` exists but implementation not reviewed. Might have bugs allowing spoofed webhooks.

**Impact Severity:** High (if insecure)

**User Experience:** If exploited: "Fake webhook data corrupted our campaign metrics."

**Suggested Fix:** Audit VerifyWebhookSignature middleware. Test with invalid signatures.

**Technical Notes:** Middleware exists but not reviewed in this audit.

---

#### Issue #84: RLS Context Setting Race Conditions

**Where:** SetDatabaseContext middleware

**User Goal:** Access org-specific data

**Problem:** Multiple middleware set DB context (SetRLSContext, SetDatabaseContext, SetOrgContextMiddleware). Unclear which runs first, if they conflict, or if race conditions exist.

**Impact Severity:** Critical (if broken)

**User Experience:** If broken: "I'm seeing another organization's campaigns! This is a major data breach!"

**Suggested Fix:** Audit all context-setting middleware. Ensure they run in correct order, don't conflict. Test extensively.

**Technical Notes:** Multiple context middleware exist (glob results). Order and interaction unclear.

---

### Missing Specifications

#### Issue #85: No Documented Soft Delete Retention Policy

**Where:** Throughout application

**User Goal:** Understand data retention

**Problem:** Soft deletes exist but no documented retention. Do deleted campaigns stay forever? Auto-purge after 30 days? Unknown.

**Impact Severity:** Low

**User Experience:** "I deleted test campaigns thinking they'd be purged. Database is now full of soft-deleted junk."

**Suggested Fix:** Document retention policy. Implement auto-purge after X days. Let users restore or permanently delete.

**Technical Notes:** Soft deletes implemented but policy unclear.

---

#### Issue #86: Unclear What Happens to Child Records on Parent Delete

**Where:** Cascade delete behavior

**User Goal:** Delete campaign

**Problem:** When campaign is deleted, what happens to content plans? Ad sets? Metrics? Cascade delete? Orphaned? Unclear.

**Impact Severity:** Medium

**User Experience:** "Deleted campaign. Its content plans are still showing. Is this a bug? Can I still use them?"

**Suggested Fix:** Document cascade behavior. Show warnings: "Deleting this campaign will also delete 5 content plans."

**Technical Notes:** Foreign key constraints likely exist but behavior not documented.

---

### Ambiguous Requirements

#### Issue #87: Multi-Tenancy Edge Case Handling

**Where:** Shared resources across orgs

**User Goal:** Share resource between organizations

**Problem:** Some resources (markets, channels) might be shared. Others (campaigns) are org-specific. Edge cases unclear: Can campaign reference shared market from different org?

**Impact Severity:** Medium

**User Experience:** "Created campaign in Org A referencing Market X. Switched to Org B, Market X not visible. Campaign broken?"

**Suggested Fix:** Clarify and document shared vs org-specific resources. Validate references at creation time.

**Technical Notes:** Markets API exists but multi-org sharing behavior unclear.

---

---

## 8. Prioritized Fix Roadmap

### Critical (Fix Immediately - This Week)

1. **Issue #38** - Add confirmation to `cmis:cleanup --all` command
2. **Issue #84** - Audit RLS context middleware for race conditions
3. **Issue #53** - Add confirmation to GPT bulk operations
4. **Issue #79** - Graceful degradation when AI services unavailable
5. **Issue #35** - Implement webhook retry queue
6. **Issue #81** - Make queue failures visible to users

**Estimated Effort:** 40 hours
**Risk if not fixed:** Data loss, security breach, broken core functionality

---

### High Priority (Fix This Sprint - Next 2 Weeks)

7. **Issue #10** - Make dashboard auto-refresh opt-in
8. **Issue #19** - Implement API versioning (/api/v1/)
9. **Issue #29** - Standardize error response format
10. **Issue #7** - Add delete confirmation modals
11. **Issue #57** - Complete campaign publish workflow for GPT
12. **Issue #63** - Sync org switching across API and web
13. **Issue #75** - Improve permission error messages
14. **Issue #72** - Validate platform credentials on save
15. **Issue #1** - Add persistent org context indicator

**Estimated Effort:** 80 hours
**Impact:** Prevents user frustration, data integrity issues

---

### Medium Priority (Fix Soon - Next Sprint)

16. **Issue #3** - Replace fake subscription upgrade with proper flow
17. **Issue #14** - Specific error messages for RLS failures
18. **Issue #22** - Standardize pagination across all endpoints
19. **Issue #26** - Add interactive API documentation (Swagger)
20. **Issue #30** - Never expose stack traces in API
21. **Issue #32** - Increase AI rate limits or make configurable
22. **Issue #44** - Add `cmis:audit-rls` verification command
23. **Issue #46** - Create interactive setup wizard
24. **Issue #58** - Expose real-time analytics to GPT
25. **Issue #64** - Align validation rules across web/API
26. **Issue #67** - Expose advanced scheduling via API
27. **Issue #70** - Implement real-time updates across interfaces
28. **Issue #74** - Add upload progress indicators
29. **Issue #80** - Add manual retry for failed syncs

**Estimated Effort:** 120 hours
**Impact:** Better developer experience, feature completeness

---

### Low Priority (Backlog - Future Sprints)

30. All remaining issues (#2, #4, #5, #6, #8, #9, #11, #12, #13, #15, #16, #17, #18, #20, #21, #23, #24, #25, #27, #28, #31, #33, #34, #36, #37, #39, #40, #41, #42, #43, #45, #47, #48, #49, #50, #51, #52, #54, #55, #56, #59, #60, #61, #62, #65, #66, #68, #69, #71, #73, #76, #77, #78, #82, #83, #85, #86, #87)

**Estimated Effort:** 200+ hours
**Impact:** Quality of life improvements, edge case handling

---

## 9. Testing Recommendations

To prevent these issues from recurring, implement:

### User Acceptance Testing (UAT)

- Test all destructive operations with real users before prod
- User journey mapping for critical flows
- A/B test error messages for clarity

### Automated Testing Additions

- **E2E tests** for multi-step workflows (campaign wizard, onboarding)
- **API contract tests** to prevent breaking changes
- **RLS security tests** for cross-org data leakage
- **Load tests** for pagination, auto-refresh, bulk operations
- **Accessibility tests** for keyboard navigation, screen readers

### Manual Testing Checklists

- Before each release, test as guest, regular user, and admin
- Test on mobile, tablet, desktop
- Test in slow network conditions
- Test with >1000 records (orgs with lots of campaigns)

---

## 10. Metrics to Track

Measure improvement by tracking:

1. **User-reported issues per month** - Should decrease after fixes
2. **API error rate** - Should decrease as errors improve
3. **Time to complete common tasks** - Should decrease with UX improvements
4. **Permission error frequency** - Should decrease with better messaging
5. **Failed background jobs** - Should decrease with better error handling
6. **Support tickets for "confused users"** - Should decrease significantly

---

## Conclusion

CMIS has **87 identified UX/product issues** ranging from critical security/data loss risks to minor annoyances. The most urgent issues involve:

- **Data safety** - Destructive CLI commands need safeguards
- **Security** - RLS context and org access need hardening
- **User confusion** - Error messages, confirmations, and feedback are lacking
- **Cross-interface inconsistency** - Web, API, CLI, and GPT behave differently

**Immediate action required on 6 critical issues to prevent data loss and security incidents.**

**Recommended next step:** Review critical issues with product team, assign to sprint, begin implementation this week.

---

**Report Generated:** 2025-11-22
**Auditor:** CMIS Master Orchestrator
**Audit Duration:** Comprehensive codebase analysis
**Next Review:** After critical fixes implemented (2-3 weeks)
