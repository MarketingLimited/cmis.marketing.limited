# Issue Audit and Remediation Plan

## 1) Social scheduler lacks backend integration
**Problem**: The social scheduling UI renders simulated data and explicitly notes a missing `SocialSchedulerController` plus REST endpoints for reading and writing posts, so the page cannot persist or publish real schedules.

**Actions**
- Implement a `SocialSchedulerController` with routes for dashboard stats, scheduled/published/draft post lists, CRUD for scheduled posts, and immediate publish endpoints as outlined in the view comments.
- Add API resources/transformers for social posts and wire them to the existing queue and publishing services for persistence and scheduling logic.
- Replace the front-end mock data with Axios calls to the new endpoints and include CSRF handling; add error/loading states and write feature tests that cover scheduling, updating, publishing, and deleting posts.

**Evidence**: `resources/views/channels/index.blade.php` lines 457-508 flag the missing controller and enumerate the required endpoints while returning static data instead.【F:resources/views/channels/index.blade.php†L457-L508】

## 2) Analytics dashboard uses placeholder data and no exports
**Problem**: KPI calculations, platform performance, and filter-based fetching are mocked in the analytics view, and export actions are TODOs. The page does not call backend analytics APIs, so charts and KPI deltas are unreliable and PDF/Excel exports are unavailable.

**Actions**
- Expose analytics endpoints (e.g., summary, metrics by date range, platform performance) that read from the reporting tables, applying organization/platform filters and historical comparisons for change metrics.
- Update the front-end `fetchAnalytics`, KPI processing, and platform performance sections to consume live API responses and compute deltas using previous-period data returned by the backend.
- Implement PDF/Excel export endpoints and wire the UI actions to POST with CSRF tokens; add feature/integration tests that validate filter handling and export responses.

**Evidence**: `resources/views/analytics/index.blade.php` keeps KPI derivations and platform performance simulated and marks the data fetch call as TODO (lines 242-319) while export handlers are stubs (lines 382-418).【F:resources/views/analytics/index.blade.php†L242-L319】【F:resources/views/analytics/index.blade.php†L382-L418】

## 3) Approval workflow notifications are only logged
**Problem**: Approval workflow events call `sendNotification`, but the method only logs and leaves a TODO to use `NotificationRepository`, so reviewers and creators will not receive in-app notifications.

**Actions**
- Implement the `NotificationRepositoryInterface` methods and inject the repository to create notification records for approval requested/approved/rejected events.
- Add queueable notification dispatchers (e.g., mail/web push) where appropriate and cover them with unit tests to ensure errors are surfaced and logged.
- Update the workflow service to capture failures via the repository instead of silently continuing to log-only behavior.

**Evidence**: `sendNotification` logs notification attempts and leaves the repository call commented with a TODO (lines 426-438).【F:app/Services/ApprovalWorkflowService.php†L426-L438】

## 4) AI content variation stubbed in bulk posting
**Problem**: Bulk post generation returns minimal string tweaks and includes a TODO to integrate Gemini-based AI variations, so "AI-powered" bulk content lacks real variation and tone controls.

**Actions**
- Add a Gemini (or configured LLM) client service with prompts for creative/moderate/conservative tones and pass account context for personalization.
- Wire `generateContentVariation` to call the AI client with retries/fallbacks and configurable timeouts; log structured failures and default to original content when necessary.
- Extend bulk posting tests to validate AI prompt construction and ensure posts are created even when AI generation fails.

**Evidence**: `generateContentVariation` returns the original content with minimal changes and notes the missing Gemini integration (lines 320-345).【F:app/Services/BulkPostService.php†L320-L345】
