# Social Publishing System - TODO List

## CRITICAL - DO IMMEDIATELY

### 1. Fix Immediate Publishing (publishNow)
**File:** `app/Http/Controllers/Social/SocialSchedulerController.php:304-347`

**Current Status:** Simulating publishing (not actually publishing)

**Action Required:**
- [ ] Remove simulation code (lines 325-329)
- [ ] Add ConnectorFactory::make() integration
- [ ] Add proper error handling
- [ ] Add logging
- [ ] Test with real Facebook/Instagram account

**Code Template:** See `SOCIAL_PUBLISHING_CRITICAL_ISSUES.md` section 1

**Priority:** CRITICAL
**Time Estimate:** 2-3 hours

---

### 2. Create Scheduled Publishing Job
**Files to Create:**
- `app/Jobs/PublishScheduledSocialPostJob.php` (NEW)
- `app/Console/Commands/PublishScheduledSocialPostsCommand.php` (NEW)

**Current Status:** ScheduledSocialPost has no job to publish it

**Action Required:**
- [ ] Create PublishScheduledSocialPostJob
- [ ] Create PublishScheduledSocialPostsCommand
- [ ] Add command to Kernel.php schedule
- [ ] Test scheduling workflow

**Code Template:** See `SOCIAL_PUBLISHING_CRITICAL_ISSUES.md` section 2

**Priority:** CRITICAL
**Time Estimate:** 4-5 hours

---

### 3. Add integration_ids Field
**Files to Modify:**
- Database migration (NEW)
- `app/Models/ScheduledSocialPost.php`
- `app/Http/Controllers/Social/SocialSchedulerController.php`
- Frontend component

**Current Status:** Can't determine which account to publish to

**Action Required:**
- [ ] Create migration to add integration_ids jsonb column
- [ ] Update ScheduledSocialPost model
- [ ] Update schedule() validation
- [ ] Update frontend to select specific accounts
- [ ] Test with multiple accounts per platform

**Code Template:** See `SOCIAL_PUBLISHING_CRITICAL_ISSUES.md` section 4

**Priority:** CRITICAL
**Time Estimate:** 2-3 hours

---

### 4. Implement Media Upload
**File:** `app/Services/Connectors/Providers/MetaConnector.php:275-294`

**Current Status:** Sending link only, not uploading actual media

**Action Required:**
- [ ] Add publishImage() method
- [ ] Add publishVideo() method
- [ ] Add publishCarousel() method
- [ ] Update publishPost() to route by media type
- [ ] Test with actual images/videos

**Code Template:** See `SOCIAL_PUBLISHING_CRITICAL_ISSUES.md` section 3

**Priority:** CRITICAL
**Time Estimate:** 3-4 hours

---

## HIGH PRIORITY - THIS WEEK

### 5. Handle Platforms Without Native Scheduling
**Files:**
- `app/Services/Connectors/Providers/TwitterConnector.php:219`
- `app/Services/Connectors/Providers/LinkedInConnector.php`
- `app/Services/Connectors/Providers/SnapchatConnector.php`

**Current Status:** Throws exception for platforms without native scheduling

**Action Required:**
- [ ] Modify schedulePost() to return null instead of throwing
- [ ] Update PublishScheduledSocialPostJob to handle null
- [ ] Use Laravel scheduler instead of platform API
- [ ] Test Twitter scheduling

**Priority:** HIGH
**Time Estimate:** 2 hours

---

### 6. Implement Metrics Collection
**Files to Create:**
- `app/Jobs/CollectPostMetricsJob.php` (NEW)
- `app/Console/Commands/CollectPostMetricsCommand.php` (NEW)

**Current Status:** No periodic metrics collection

**Action Required:**
- [ ] Create CollectPostMetricsJob
- [ ] Add getPostMetrics() to ConnectorInterface
- [ ] Implement for all connectors
- [ ] Schedule hourly collection
- [ ] Test metrics storage

**Priority:** HIGH
**Time Estimate:** 4-5 hours

---

### 7. Add Media Processing Service
**File to Create:** `app/Services/MediaProcessorService.php` (NEW)

**Current Status:** No image optimization or validation

**Action Required:**
- [ ] Create MediaProcessorService
- [ ] Add image compression
- [ ] Add video transcoding
- [ ] Add size validation per platform
- [ ] Add format validation
- [ ] Test with various file sizes

**Priority:** HIGH
**Time Estimate:** 3-4 hours

---

### 8. Create Account Selector UI
**Frontend Component:** (NEW)

**Current Status:** Can only select platform, not specific account

**Action Required:**
- [ ] Create account selector component
- [ ] Load integrations per platform
- [ ] Display account info (name, picture)
- [ ] Allow multi-account selection
- [ ] Update API calls to include integration_ids

**Priority:** HIGH
**Time Estimate:** 3-4 hours

---

## MEDIUM PRIORITY - THIS MONTH

### 9. Consolidate Publishing Jobs
**Files:**
- `app/Jobs/PublishScheduledPostJob.php` (REMOVE or RENAME)
- `app/Jobs/PublishScheduledPost.php` (REMOVE or RENAME)
- New unified job

**Action Required:**
- [ ] Decide on single job implementation
- [ ] Migrate existing scheduled posts
- [ ] Remove duplicate jobs
- [ ] Update all references

**Priority:** MEDIUM
**Time Estimate:** 2-3 hours

---

### 10. Implement Token Auto-Refresh
**File to Create:** `app/Jobs/RefreshExpiredTokensJob.php` (NEW)

**Action Required:**
- [ ] Create RefreshExpiredTokensJob
- [ ] Schedule daily execution
- [ ] Add notifications for failed refresh
- [ ] Test with expiring tokens

**Priority:** MEDIUM
**Time Estimate:** 2 hours

---

### 11. Add Failed Posts Dashboard
**Files:**
- Backend: Controller + Route
- Frontend: Dashboard component

**Action Required:**
- [ ] Create failed posts endpoint
- [ ] Add retry mechanism
- [ ] Create dashboard UI
- [ ] Add notifications

**Priority:** MEDIUM
**Time Estimate:** 4-5 hours

---

### 12. Implement Webhook Handling
**File to Create:** `app/Http/Controllers/WebhookController.php` (NEW)

**Action Required:**
- [ ] Create webhook routes
- [ ] Implement Meta webhooks
- [ ] Implement Twitter webhooks
- [ ] Add signature verification
- [ ] Handle various events

**Priority:** MEDIUM
**Time Estimate:** 5-6 hours

---

## LOW PRIORITY - FUTURE

### 13. Add Advanced Content Types
- Instagram Reels
- Instagram Stories
- Facebook Stories
- Carousel posts
- Tagged locations
- Product tags

### 14. Implement Engagement Analytics
- Best posting times
- Content performance analysis
- Platform comparison
- Audience insights

### 15. Add Bulk Operations
- Bulk scheduling
- Bulk editing
- Bulk deletion
- CSV import

---

## Testing Checklist

After each fix, verify:

### Publishing Tests
- [ ] Immediate publishing works (publishNow)
- [ ] Scheduled publishing works (after 5 minutes)
- [ ] Multi-platform publishing works
- [ ] Error handling works
- [ ] Retries work on failure

### Media Tests
- [ ] Image upload works
- [ ] Video upload works
- [ ] Large files are handled
- [ ] Invalid formats are rejected
- [ ] Optimization works

### Account Tests
- [ ] Multiple accounts per platform work
- [ ] Correct account is used
- [ ] Token refresh works
- [ ] Expired tokens are handled

### Metrics Tests
- [ ] Metrics are collected hourly
- [ ] Dashboard shows correct data
- [ ] Historical data is preserved

---

## Commands to Run

```bash
# After implementing fixes

# Run migrations
php artisan migrate

# Test job
php artisan tinker
>>> PublishScheduledSocialPostJob::dispatch($post);

# Test command
php artisan cmis:publish-scheduled-social

# Check scheduled tasks
php artisan schedule:list

# Run queue worker
php artisan queue:work --queue=publishing

# Run scheduler (in development)
php artisan schedule:work

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## Files Structure Summary

```
New/Modified Files:

app/
  Jobs/
    ✓ PublishScheduledSocialPostJob.php (EXISTING - MODIFY)
    ✗ PublishScheduledPost.php (EXISTING - REMOVE?)
    + PublishScheduledSocialPostJob.php (NEW - CREATE)
    + CollectPostMetricsJob.php (NEW)
    + RefreshExpiredTokensJob.php (NEW)

  Console/Commands/
    + PublishScheduledSocialPostsCommand.php (NEW)
    + CollectPostMetricsCommand.php (NEW)

  Http/Controllers/
    Social/
      ✓ SocialSchedulerController.php (MODIFY publishNow)
    + WebhookController.php (NEW)

  Models/
    ✓ ScheduledSocialPost.php (ADD integration_ids)

  Services/
    Connectors/Providers/
      ✓ MetaConnector.php (ADD media methods)
      ✓ TwitterConnector.php (MODIFY schedulePost)
      ✓ LinkedInConnector.php (MODIFY schedulePost)
    + MediaProcessorService.php (NEW)

database/
  migrations/
    + xxxx_add_integration_ids_to_scheduled_social_posts.php (NEW)
    + xxxx_create_social_post_metrics_table.php (NEW)

Frontend:
  + AccountSelectorComponent.vue (NEW)
  ✓ SchedulePostModal.vue (MODIFY - add account selector)
```

---

## Estimated Total Time

| Phase | Tasks | Time |
|-------|-------|------|
| Critical (Must Do Now) | 1-4 | 11-15 hours (2 days) |
| High Priority (This Week) | 5-8 | 15-20 hours (3 days) |
| Medium Priority (This Month) | 9-12 | 13-16 hours (2 days) |
| **TOTAL** | | **39-51 hours (1 week)** |

---

## Success Criteria

System is considered "fixed" when:
- [ ] Posts can be published immediately to all platforms
- [ ] Posts can be scheduled and auto-publish at scheduled time
- [ ] Images and videos upload correctly
- [ ] Multiple accounts per platform are supported
- [ ] Metrics are collected and displayed
- [ ] Failed posts are tracked and can be retried
- [ ] All existing tests pass
- [ ] New tests are written and pass

---

## Next Steps

1. **TODAY:** Fix items 1-4 (Critical)
2. **THIS WEEK:** Complete items 5-8 (High Priority)
3. **THIS MONTH:** Address items 9-12 (Medium Priority)
4. **FUTURE:** Plan items 13-15 (Low Priority)

---

**Last Updated:** 2025-11-18
**Status:** CRITICAL - System Non-Functional
**Priority:** Fix ASAP

