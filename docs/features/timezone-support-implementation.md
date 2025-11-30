# Social Media Timezone Support Implementation

**Feature:** Profile Group Timezone Support for Social Media Scheduling
**Date:** 2025-11-30
**Status:** In Progress

---

## 🎯 Objective

Enable social media posts to be scheduled in the **Profile Group's timezone** instead of browser's local timezone. Each social media account belongs to a Profile Group which has a configured timezone (e.g., "Asia/Dubai", "America/New_York").

---

## 📋 Requirements

1. ✅ Publishing modal should use Profile Group timezone
2. ✅ Edit Schedule modal should use Profile Group timezone
3. ✅ All date/time displays should show in Profile Group timezone
4. ✅ Posts should publish at correct time in Profile Group's local time
5. ✅ Timezone should be clearly displayed in UI

---

## 🏗️ Architecture

### Database Schema

**Profile Groups Table:** `cmis.profile_groups`
- `timezone` column (VARCHAR 100, default: 'UTC')
- Examples: 'Asia/Dubai', 'America/New_York', 'Europe/London'

**Social Posts Table:** `cmis.social_posts`
- `profile_group_id` column (UUID, FK to profile_groups)
- `scheduled_at` column (TIMESTAMP WITH TIME ZONE)
  - Stores in UTC
  - PostgreSQL handles timezone conversions automatically

**Integrations Table:** `cmis.integrations`
- `profile_group_id` column (UUID, FK to profile_groups)
- Links social accounts to profile groups

### Data Flow

```
Profile Group (Asia/Dubai, GMT+4)
    ↓
Integration (Facebook Page)
    ↓
Social Post (scheduled_at: 2025-12-01 10:00:00+04)
    ↓
PostgreSQL stores as UTC: 2025-12-01 06:00:00+00
    ↓
Scheduler checks: scheduled_at <= NOW() (both in UTC)
    ↓
Publishes at: 10:00 AM Dubai time (06:00 AM UTC)
```

---

## ✅ Backend Implementation (Completed)

### 1. Controller Updates

**File:** `app/Http/Controllers/Social/SocialPostController.php`

**Changes:**
- Added `profile_group_id` storage in `store()` method
- Created `getTimezone()` API endpoint
- Updated `getScheduledTime()` to accept timezone parameter

**New API Endpoint:**
```php
POST /api/orgs/{org}/social/timezone
Request: { integration_ids: ['uuid1', 'uuid2'] }
Response: {
    timezone: 'Asia/Dubai',
    profile_group_id: 'uuid',
    profile_group_name: 'Community & Support',
    integrations: ['uuid1', 'uuid2']
}
```

### 2. Route Registration

**File:** `routes/api.php`

**Added:**
```php
Route::post('/social/timezone', [SocialPostController::class, 'getTimezone'])
    ->name('social.timezone');
```

---

## 🎨 Frontend Implementation (Pending)

### Changes Needed in `social-manager.blade.php`

#### 1. Add State Variables

```javascript
// Add near line 100 (with other state variables)
profileGroupTimezone: null,  // e.g., 'Asia/Dubai'
profileGroupName: null,      // e.g., 'Community & Support'
timezoneOffset: null,        // e.g., '+04:00'
timezoneAbbr: null,          // e.g., 'GST' (Gulf Standard Time)
```

#### 2. Add Watcher for Platform Selection

```javascript
// Add in init() method after line 620
this.$watch('selectedPlatformIds', async (ids) => {
    if (ids.length > 0) {
        await this.fetchProfileGroupTimezone();
    }
});
```

#### 3. Add Method to Fetch Timezone

```javascript
// Add around line 700 (after other async methods)
async fetchProfileGroupTimezone() {
    if (this.selectedPlatformIds.length === 0) {
        this.profileGroupTimezone = null;
        return;
    }

    try {
        // Get integration IDs from selected platforms
        const integrationIds = this.selectedPlatformIds
            .map(id => {
                const platform = this.connectedPlatforms.find(p => p.id === id);
                return platform?.integrationId;
            })
            .filter(Boolean);

        if (integrationIds.length === 0) return;

        const response = await fetch(`/api/orgs/${this.orgId}/social/timezone`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ integration_ids: integrationIds })
        });

        const result = await response.json();

        if (result.success && result.data) {
            this.profileGroupTimezone = result.data.timezone;
            this.profileGroupName = result.data.profile_group_name;

            // Calculate timezone offset for display
            const tz = result.data.timezone;
            const now = new Date();
            const tzDate = new Date(now.toLocaleString('en-US', { timeZone: tz }));
            const offset = (tzDate - now) / 1000 / 60; // minutes
            const hours = Math.floor(Math.abs(offset) / 60);
            const mins = Math.abs(offset) % 60;
            this.timezoneOffset = `${offset >= 0 ? '+' : '-'}${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;

            console.log('[CMIS Social] Timezone loaded:', tz, this.timezoneOffset);
        }
    } catch (error) {
        console.error('[CMIS Social] Failed to fetch timezone:', error);
        this.profileGroupTimezone = 'UTC';
        this.timezoneOffset = '+00:00';
    }
},
```

#### 4. Update Date Formatting

```javascript
// Update formatDate() method around line 1137
formatDate(date) {
    if (!date) return '';
    const dateObj = new Date(date);
    const options = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
        timeZoneName: 'short',
        timeZone: this.profileGroupTimezone || 'UTC'  // KEY CHANGE
    };
    return dateObj.toLocaleString('en-GB', options);
},
```

#### 5. Update Modal UI

**In `create-modal.blade.php` (line 214-223):**

```html
<template x-if="postData.publish_type === 'scheduled'">
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ __('social.schedule_datetime') }}
            <span x-show="profileGroupTimezone"
                  x-text="`(${profileGroupName} - ${profileGroupTimezone})`"
                  class="text-xs text-gray-500 font-normal"></span>
        </label>
        <input
            type="datetime-local"
            x-model="postData.scheduled_at"
            :min="minDateTime"
            class="w-full border border-gray-300 rounded-lg px-4 py-2">
        <p x-show="profileGroupTimezone" class="text-xs text-gray-500 mt-1">
            <i class="fas fa-clock ms-1"></i>
            Times are in <span x-text="profileGroupName"></span> timezone
            <span x-text="`(UTC${timezoneOffset})`"></span>
        </p>
    </div>
</template>
```

**In `edit-post-modal.blade.php` (line 92-112):**

```html
<template x-if="editingPost.status === 'draft' || editingPost.status === 'scheduled'">
    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            <i class="fas fa-clock ms-1"></i>
            {{ __('social.schedule_datetime') }}
            <span x-show="profileGroupTimezone"
                  x-text="`(${profileGroupTimezone})`"
                  class="text-xs text-gray-500 font-normal"></span>
        </label>
        <div class="grid grid-cols-2 gap-4">
            <!-- existing date/time inputs -->
        </div>
        <p x-show="profileGroupTimezone" class="text-xs text-gray-500 mt-2">
            <i class="fas fa-info-circle ms-1"></i>
            Timezone: <span x-text="profileGroupName"></span>
            <span x-text="`(UTC${timezoneOffset})`"></span>
        </p>
    </div>
</template>
```

---

## 🔄 Date Conversion Logic

### JavaScript to Server (Saving)

When saving a scheduled post:

```javascript
// Frontend already provides datetime in local format
// e.g., "2025-12-01T10:00"

// Laravel will receive this and store it
// PostgreSQL automatically handles timezone conversion to UTC
```

**No conversion needed!** The `datetime-local` input provides the date/time, and we're telling the user it's in their Profile Group timezone. When Laravel stores it in PostgreSQL with `timestamp with time zone`, PostgreSQL handles the UTC conversion.

### Server to JavaScript (Loading)

When loading a scheduled post for editing:

```javascript
// Backend returns: "2025-12-01 06:00:00+00" (UTC)
// Frontend converts to profile group timezone:

if (post.scheduled_at) {
    const scheduled = new Date(post.scheduled_at);
    // Convert to profile group timezone for display
    const localDate = scheduled.toLocaleString('en-CA', {
        timeZone: this.profileGroupTimezone || 'UTC',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
    const localTime = scheduled.toLocaleString('en-GB', {
        timeZone: this.profileGroupTimezone || 'UTC',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    });

    this.editingPost.scheduledDate = localDate;  // "2025-12-01"
    this.editingPost.scheduledTime = localTime;  // "10:00"
}
```

---

## 🧪 Testing Plan

### Test Scenarios

1. **Create Post Scheduled for Future**
   - Profile Group: "Community & Support" (Asia/Dubai, GMT+4)
   - Schedule for: 2025-12-01 10:00 AM
   - Expected: Post shows "10:00 AM GST" in UI
   - Expected: DB stores "2025-12-01 06:00:00+00" (UTC)

2. **Edit Scheduled Post**
   - Load post scheduled for "2025-12-01 06:00:00+00"
   - Profile Group: Asia/Dubai
   - Expected: UI shows "10:00 AM" (converted to Dubai time)

3. **Multiple Timezones Warning**
   - Select accounts from different profile groups
   - Expected: Warning message showing which timezone is being used

4. **Scheduler Publishes at Correct Time**
   - Post scheduled for "2025-12-01 10:00 AM Dubai time"
   - Server time: Any timezone
   - Expected: Post publishes at 10:00 AM Dubai time (06:00 AM UTC)

---

## 📝 Next Steps

1. [IN PROGRESS] Update Alpine.js component with timezone support
2. [PENDING] Update create-modal.blade.php to display timezone
3. [PENDING] Update edit-post-modal.blade.php to display timezone
4. [PENDING] Test with different timezones
5. [PENDING] Verify scheduler respects timezone
6. [PENDING] Add timezone indicator to grid/list views
7. [PENDING] Update documentation

---

## ⚠️ Important Notes

- PostgreSQL's `timestamp with time zone` always stores in UTC internally
- Timezone conversions happen automatically at query time
- JavaScript `Date` objects are timezone-aware
- The `datetime-local` input doesn't include timezone info, so we must show it separately
- Users must see which timezone they're scheduling in (critical UX)

---

## 🔗 Related Files

- `app/Http/Controllers/Social/SocialPostController.php` - ✅ Updated
- `routes/api.php` - ✅ Updated
- `resources/views/social/scripts/social-manager.blade.php` - 🔄 In Progress
- `resources/views/social/posts/components/create-modal.blade.php` - ⏳ Pending
- `resources/views/social/components/modals/edit-post-modal.blade.php` - ⏳ Pending
- `app/Console/Commands/PublishScheduledSocialPostsCommand.php` - ✅ Already correct (uses UTC comparison)

---

**Last Updated:** 2025-11-30 17:45 UTC
