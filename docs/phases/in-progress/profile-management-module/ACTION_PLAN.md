# Profile Management Module - Action Plan

**Created:** 2025-12-01
**Status:** In Progress
**Reference:** VistaSocial Profile Management

---

## 1. Overview

This module provides comprehensive profile management capabilities similar to VistaSocial, allowing users to:
- View all connected social profiles in a unified list
- Manage individual profile settings
- Configure publishing queues per profile
- Set up boost rules for automatic post promotion
- Assign profiles to profile groups
- Configure custom fields per profile

### Key Difference from VistaSocial
Profiles come from the **Platform Connections** page (`/settings/platform-connections`), so we don't need add/remove functionality. This module manages settings for already-connected profiles.

---

## 2. Feature Analysis (from VistaSocial Screenshots)

### 2.1 Main List Page Features
- **Header:** "Profile Management" title with subtitle
- **Filters:**
  - Search by name (text input)
  - Network dropdown (Select network) - filters by platform
  - Status dropdown (Select status) - Active/Inactive
  - Group dropdown (Select group) - filters by profile group
- **Table Columns:**
  - Name (with platform icon + avatar + name link)
  - Edit icon (pencil) for inline name edit
  - Profile group (clickable link)
  - Connected date
  - Status badge (Active = green)
  - 3-dot action menu

### 2.2 Three-Dot Menu Actions
- Refresh connection
- View profile (navigate to detail page)
- Manage groups (assign to profile groups)
- Remove profile (disconnect)

### 2.3 Single Profile Management Page Features
- **Breadcrumb:** Profiles / {Profile Name}
- **Subtitle:** "Configure name, profile groups, publishing queue, custom fields and boost settings."
- **Action buttons:** Update image, Remove

#### Profile Card Section
- Large avatar with platform icon badge
- Profile name (editable)
- Username (@handle)
- Bio/Description
- Status (Active badge)
- Type (business/personal)
- Connected date
- Team member (who connected it)
- Facebook/Platform user info
- Industry (editable dropdown, "Not set" default)

#### Profile Groups Section
- "PROFILE GROUPS" header with icon
- "Manage profile groups" link
- List of assigned groups with:
  - Group icon
  - Group name
  - Group type (Client)
  - Group location (Asia/Bahrain)

#### Publishing Queues Section
- "PUBLISHING QUEUES" header with icon
- Queue configuration per profile

#### Boost Settings Section
- "BOOST SETTINGS" header
- "Add boost" button
- List of configured boosts

### 2.4 Boost Settings Modal (Create/Edit)
**Basic Settings:**
- Boost name (text input)
- Boost delay after publishing (number + unit dropdown: Hours/Days)
- Profile dropdown (select which profile)
- Ad account dropdown (select ad account)

**Budget & Duration:**
- Budget (number input)
- Campaign days (number input)
- Note: "All amounts are expressed in (selected ad account's currency)."

**Audience Targeting:**
- Included audiences (multi-select)
- Excluded audiences (multi-select)
- Interests (searchable multi-select)
- Work positions (searchable multi-select)
- Countries (searchable multi-select)
- Cities (searchable multi-select)
- Genders (multi-select)
- Min age (number input)
- Max age (number input)

**Actions:**
- Close button
- Save boost button

---

## 3. Database Schema Design

### 3.1 Existing Tables (No Changes Needed)
- `cmis.integrations` - Connected profiles/accounts
- `cmis.profile_groups` - Profile grouping
- `cmis.profile_group_members` - Team member assignments
- `cmis.boost_rules` - Boost automation rules (already comprehensive)

### 3.2 New Tables Required

#### Table: `cmis.profile_settings`
Extended profile settings for connected integrations.

```sql
CREATE TABLE cmis.profile_settings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    integration_id UUID NOT NULL REFERENCES cmis.integrations(integration_id),

    -- Display Settings
    display_name VARCHAR(255),          -- Custom display name
    custom_avatar_url TEXT,             -- Custom avatar override
    industry VARCHAR(100),              -- Industry category

    -- Profile Metadata
    profile_type VARCHAR(50) DEFAULT 'business', -- business/personal/creator
    bio TEXT,
    website_url TEXT,

    -- Status & Visibility
    is_visible BOOLEAN DEFAULT true,    -- Show in profile list
    is_enabled BOOLEAN DEFAULT true,    -- Enable for publishing

    -- Custom Fields (JSONB for flexibility)
    custom_fields JSONB DEFAULT '{}',

    -- Publishing Settings
    default_publishing_queue_id UUID,
    auto_boost_enabled BOOLEAN DEFAULT false,

    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),

    UNIQUE(integration_id)
);
```

#### Table: `cmis.profile_group_assignments`
Many-to-many relationship between integrations and profile groups.

```sql
CREATE TABLE cmis.profile_group_assignments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    integration_id UUID NOT NULL REFERENCES cmis.integrations(integration_id),
    profile_group_id UUID NOT NULL REFERENCES cmis.profile_groups(group_id),

    -- Assignment metadata
    assigned_by UUID REFERENCES cmis.users(user_id),
    assigned_at TIMESTAMPTZ DEFAULT NOW(),

    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT NOW(),

    UNIQUE(integration_id, profile_group_id)
);
```

#### Table: `cmis.profile_publishing_queues`
Per-profile publishing queue configuration.

```sql
CREATE TABLE cmis.profile_publishing_queues (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    integration_id UUID NOT NULL REFERENCES cmis.integrations(integration_id),

    -- Queue Settings
    name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT true,

    -- Schedule (day of week + times)
    schedule JSONB DEFAULT '{}',
    -- Format: {"monday": ["09:00", "12:00", "18:00"], "tuesday": [...]}

    -- Timezone for schedule
    timezone VARCHAR(100) DEFAULT 'UTC',

    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);
```

### 3.3 RLS Policies
All new tables will have RLS policies using the `HasRLSPolicies` trait in migrations.

---

## 4. Backend Implementation

### 4.1 Models

#### New Models:
1. `App\Models\Social\ProfileSettings` - Extended profile settings
2. `App\Models\Social\ProfileGroupAssignment` - Profile-to-group assignments
3. `App\Models\Social\ProfilePublishingQueue` - Per-profile queues

#### Model Relationships:
```php
// Integration model additions
public function profileSettings(): HasOne
public function profileGroupAssignments(): HasMany
public function profileGroups(): BelongsToMany (through assignments)
public function publishingQueues(): HasMany

// ProfileGroup model additions
public function integrations(): BelongsToMany (through assignments)
```

### 4.2 API Controllers

#### `ProfileManagementController`
```php
// List all profiles with filtering
GET    /api/orgs/{org}/profiles
       Query params: search, platform, status, group_id, page, per_page

// Get single profile details
GET    /api/orgs/{org}/profiles/{integration_id}

// Update profile settings
PATCH  /api/orgs/{org}/profiles/{integration_id}
       Body: display_name, industry, custom_fields, is_enabled, etc.

// Update profile avatar
POST   /api/orgs/{org}/profiles/{integration_id}/avatar
       Body: multipart file upload

// Refresh connection
POST   /api/orgs/{org}/profiles/{integration_id}/refresh

// Remove profile (disconnect)
DELETE /api/orgs/{org}/profiles/{integration_id}
```

#### `ProfileGroupAssignmentController`
```php
// Get profile's groups
GET    /api/orgs/{org}/profiles/{integration_id}/groups

// Assign profile to groups
POST   /api/orgs/{org}/profiles/{integration_id}/groups
       Body: { group_ids: [...] }

// Remove profile from group
DELETE /api/orgs/{org}/profiles/{integration_id}/groups/{group_id}
```

#### `ProfileBoostController`
```php
// List profile boosts
GET    /api/orgs/{org}/profiles/{integration_id}/boosts

// Create boost rule
POST   /api/orgs/{org}/profiles/{integration_id}/boosts
       Body: { name, delay, ad_account_id, budget, duration_days, targeting... }

// Update boost rule
PATCH  /api/orgs/{org}/profiles/{integration_id}/boosts/{boost_id}

// Delete boost rule
DELETE /api/orgs/{org}/profiles/{integration_id}/boosts/{boost_id}

// Toggle boost active status
POST   /api/orgs/{org}/profiles/{integration_id}/boosts/{boost_id}/toggle
```

#### `ProfilePublishingQueueController`
```php
// List queues
GET    /api/orgs/{org}/profiles/{integration_id}/queues

// Create queue
POST   /api/orgs/{org}/profiles/{integration_id}/queues

// Update queue
PATCH  /api/orgs/{org}/profiles/{integration_id}/queues/{queue_id}

// Delete queue
DELETE /api/orgs/{org}/profiles/{integration_id}/queues/{queue_id}
```

### 4.3 Services

#### `ProfileManagementService`
- `getProfiles(orgId, filters)` - Get filtered profile list
- `getProfile(orgId, integrationId)` - Get single profile with all relations
- `updateProfile(orgId, integrationId, data)` - Update profile settings
- `refreshConnection(orgId, integrationId)` - Refresh platform connection
- `disconnectProfile(orgId, integrationId)` - Soft-delete/disconnect profile

#### `ProfileBoostService`
- `getBoostRules(integrationId)` - Get boosts for profile
- `createBoostRule(integrationId, data)` - Create new boost rule
- `updateBoostRule(boostId, data)` - Update boost rule
- `deleteBoostRule(boostId)` - Delete boost rule
- `toggleBoostRule(boostId)` - Toggle active status

---

## 5. Frontend Implementation

### 5.1 Routes

```php
// routes/web.php
Route::prefix('orgs/{org}/settings')->group(function () {
    Route::get('/profiles', [ProfileManagementController::class, 'index'])->name('settings.profiles.index');
    Route::get('/profiles/{integration_id}', [ProfileManagementController::class, 'show'])->name('settings.profiles.show');
});
```

### 5.2 Views

#### Main List Page: `resources/views/settings/profiles/index.blade.php`
- Page header with title and subtitle
- Filter bar (search, network, status, group dropdowns)
- Profiles table with columns
- Pagination
- Alpine.js component for interactivity

#### Single Profile Page: `resources/views/settings/profiles/show.blade.php`
- Breadcrumb navigation
- Profile card section
- Profile groups section with manage link
- Publishing queues section
- Boost settings section

#### Partials:
- `_profile-card.blade.php` - Profile header card
- `_profile-groups-section.blade.php` - Groups management section
- `_publishing-queues-section.blade.php` - Queue configuration
- `_boost-settings-section.blade.php` - Boost rules list

#### Modals (Alpine.js components):
- `_manage-groups-modal.blade.php` - Assign/remove groups
- `_boost-modal.blade.php` - Create/edit boost rule
- `_queue-modal.blade.php` - Create/edit publishing queue

### 5.3 Alpine.js Components

```javascript
// Profile list component
function profileList() {
    return {
        profiles: [],
        filters: { search: '', platform: '', status: '', group_id: '' },
        loading: false,
        pagination: {},

        async loadProfiles() {},
        async refreshConnection(id) {},
        async removeProfile(id) {},
        openManageGroups(profile) {},
    }
}

// Profile detail component
function profileDetail() {
    return {
        profile: null,
        editingName: false,
        editingIndustry: false,

        async updateProfile(field, value) {},
        async uploadAvatar(file) {},
    }
}

// Boost settings component
function boostSettings() {
    return {
        boosts: [],
        showModal: false,
        editingBoost: null,
        form: { /* boost form fields */ },

        async loadBoosts() {},
        async saveBoost() {},
        async deleteBoost(id) {},
        async toggleBoost(id) {},
    }
}
```

---

## 6. Internationalization (i18n)

### Translation Keys Required

```php
// lang/ar/profiles.php & lang/en/profiles.php
return [
    'title' => 'Profile Management',
    'subtitle' => 'This is a list of connected social profiles across your profile groups.',

    // Filters
    'search_placeholder' => 'Search by name',
    'select_network' => 'Select network',
    'select_status' => 'Select status',
    'select_group' => 'Select group',

    // Table headers
    'name' => 'Name',
    'profile_group' => 'Profile group',
    'connected' => 'Connected',
    'status' => 'Status',

    // Status badges
    'status_active' => 'Active',
    'status_inactive' => 'Inactive',
    'status_error' => 'Error',

    // Actions
    'refresh_connection' => 'Refresh connection',
    'view_profile' => 'View profile',
    'manage_groups' => 'Manage groups',
    'remove_profile' => 'Remove profile',
    'update_image' => 'Update image',
    'remove' => 'Remove',

    // Profile detail
    'configure_subtitle' => 'Configure name, profile groups, publishing queue, custom fields and boost settings.',
    'type' => 'Type',
    'team_member' => 'Team member',
    'industry' => 'Industry',
    'not_set' => 'Not set',

    // Sections
    'profile_groups' => 'PROFILE GROUPS',
    'manage_profile_groups' => 'Manage profile groups',
    'publishing_queues' => 'PUBLISHING QUEUES',
    'boost_settings' => 'BOOST SETTINGS',
    'add_boost' => 'Add boost',

    // Boost modal
    'create_boost' => 'Create boost',
    'edit_boost' => 'Edit boost',
    'boost_name' => 'Boost name',
    'boost_name_placeholder' => 'Describe your boost',
    'boost_delay' => 'Boost delay after publishing',
    'ad_account' => 'Ad account',
    'budget' => 'Budget',
    'campaign_days' => 'Campaign days',
    'budget_note' => 'All amounts are expressed in (selected ad account\'s currency).',
    'included_audiences' => 'Included audiences',
    'excluded_audiences' => 'Excluded audiences',
    'interests' => 'Interests',
    'work_positions' => 'Work positions',
    'countries' => 'Countries',
    'cities' => 'Cities',
    'genders' => 'Genders',
    'min_age' => 'Min age',
    'max_age' => 'Max age',
    'save_boost' => 'Save boost',
    'close' => 'Close',

    // Messages
    'profile_updated' => 'Profile updated successfully',
    'connection_refreshed' => 'Connection refreshed successfully',
    'profile_removed' => 'Profile removed successfully',
    'boost_saved' => 'Boost saved successfully',
    'boost_deleted' => 'Boost deleted successfully',
];
```

---

## 7. Implementation Steps

### Phase 1: Database & Models
1. Create migration for `profile_settings` table
2. Create migration for `profile_group_assignments` table
3. Create migration for `profile_publishing_queues` table
4. Create `ProfileSettings` model
5. Create `ProfileGroupAssignment` model
6. Create `ProfilePublishingQueue` model
7. Update `Integration` model with relationships
8. Update `ProfileGroup` model with relationships
9. Run migrations and test

### Phase 2: Backend Services & API
1. Create `ProfileManagementService`
2. Create `ProfileBoostService`
3. Create `ProfileManagementController`
4. Create `ProfileGroupAssignmentController`
5. Create `ProfileBoostController`
6. Create `ProfilePublishingQueueController`
7. Register API routes
8. Write API tests

### Phase 3: Frontend - Main List Page
1. Create `index.blade.php` view
2. Create Alpine.js `profileList` component
3. Implement filters (search, platform, status, group)
4. Implement profile table with actions
5. Implement 3-dot menu functionality
6. Add pagination
7. Add i18n translations

### Phase 4: Frontend - Profile Detail Page
1. Create `show.blade.php` view
2. Create profile card partial
3. Implement inline editing (name, industry)
4. Create profile groups section
5. Create manage groups modal
6. Create publishing queues section
7. Create queue management modal

### Phase 5: Frontend - Boost Settings
1. Create boost settings section
2. Create boost modal component
3. Implement audience targeting fields
4. Implement ad account selection
5. Connect to API endpoints
6. Add validation

### Phase 6: Testing & Verification
1. Create Feature tests for all API endpoints
2. Create Browser tests for UI functionality
3. Test RTL/LTR layouts
4. Test both Arabic and English locales
5. Verify responsive design
6. Performance testing

---

## 8. File Structure

```
app/
├── Models/
│   └── Social/
│       ├── ProfileSettings.php
│       ├── ProfileGroupAssignment.php
│       └── ProfilePublishingQueue.php
├── Services/
│   └── Social/
│       ├── ProfileManagementService.php
│       └── ProfileBoostService.php
├── Http/
│   └── Controllers/
│       └── Settings/
│           ├── ProfileManagementController.php
│           ├── ProfileGroupAssignmentController.php
│           ├── ProfileBoostController.php
│           └── ProfilePublishingQueueController.php

database/
└── migrations/
    ├── 2025_12_01_000001_create_profile_settings_table.php
    ├── 2025_12_01_000002_create_profile_group_assignments_table.php
    └── 2025_12_01_000003_create_profile_publishing_queues_table.php

resources/
├── views/
│   └── settings/
│       └── profiles/
│           ├── index.blade.php
│           ├── show.blade.php
│           └── partials/
│               ├── _profile-card.blade.php
│               ├── _profile-groups-section.blade.php
│               ├── _publishing-queues-section.blade.php
│               ├── _boost-settings-section.blade.php
│               ├── _manage-groups-modal.blade.php
│               ├── _boost-modal.blade.php
│               └── _queue-modal.blade.php
└── lang/
    ├── ar/
    │   └── profiles.php
    └── en/
        └── profiles.php

tests/
├── Feature/
│   └── Settings/
│       ├── ProfileManagementTest.php
│       ├── ProfileGroupAssignmentTest.php
│       └── ProfileBoostTest.php
└── Browser/
    └── Settings/
        └── ProfileManagementTest.php
```

---

## 9. Dependencies & Prerequisites

### Existing Components Used:
- `Integration` model - Source of profile data
- `ProfileGroup` model - Profile grouping
- `BoostRule` model - Boost automation
- `ApiResponse` trait - Standardized API responses
- `HasOrganization` trait - Org relationship
- `HasRLSPolicies` trait - RLS in migrations

### External Services:
- Meta API - For refreshing Facebook/Instagram connections
- Google API - For refreshing Google/YouTube connections
- TikTok API - For refreshing TikTok connections
- LinkedIn API - For refreshing LinkedIn connections
- Twitter API - For refreshing Twitter connections

---

## 10. Success Criteria

1. **List Page:**
   - All connected profiles displayed with correct platform icons
   - Filters work correctly (search, platform, status, group)
   - 3-dot menu actions functional
   - Pagination works

2. **Detail Page:**
   - Profile card displays all information correctly
   - Inline editing works for name and industry
   - Profile groups section shows assigned groups
   - Manage groups modal allows add/remove
   - Publishing queues configurable
   - Boost settings configurable

3. **Boost Modal:**
   - All fields render correctly
   - Validation works
   - Save/update functional
   - Audience targeting works

4. **i18n & RTL:**
   - All text uses translation keys
   - Both Arabic (RTL) and English (LTR) work correctly
   - Layout adapts properly to direction

5. **API:**
   - All endpoints return proper responses
   - Validation works
   - Error handling proper
   - Tests pass

---

## 11. Timeline Estimate

This is a complex feature requiring approximately 15-20 hours of development work across all phases.

---

**Document Author:** Claude Code
**Last Updated:** 2025-12-01
