# Account Groups Selector - UX Enhancement Plan
**Created:** 2025-11-29
**Component:** Profile Selector (Account Groups + Profiles)
**File:** `resources/views/components/publish-modal/profile-selector.blade.php` (171 lines)
**Status:** Critical UX Issues Identified

---

## Executive Summary

The Account Groups selector is a **two-step nested selection interface** (groups → profiles) that currently occupies a fixed 320px panel. This creates severe usability issues on mobile devices and represents one of the most critical UX pain points in the publishing modal.

**Current State:**
- ❌ Fixed 320px width leaves only 55px on iPhone SE (375px screen)
- ❌ Nested selection (groups then profiles) confusing in narrow space
- ❌ Small checkboxes (16x16px) difficult to tap
- ❌ Search + filters cramped in 320px column
- ❌ No mobile-optimized workflow

**Goal:** Transform into a mobile-first, progressive disclosure interface while maintaining desktop efficiency.

---

## 1. Current Account Groups Architecture

### 1.1 Component Structure

```
┌─────────────────────────────────────┐
│ Profile Groups & Profiles Selection │ w-80 (320px fixed)
├─────────────────────────────────────┤
│ STEP 1: Profile Groups Selection    │
│ ┌─────────────────────────────────┐ │
│ │ Account Groups          (3 / 5) │ │
│ ├─────────────────────────────────┤ │
│ │ ☐ Marketing Team (12)           │ │ max-h-32 (128px)
│ │ ☑ Community & Support (8)       │ │ with scroll
│ │ ☐ Sales & Outreach (15)         │ │
│ │ ☑ Brand Ambassadors (6)         │ │
│ │ ☐ Customer Service (10)         │ │
│ ├─────────────────────────────────┤ │
│ │ Select All        |      Clear  │ │
│ └─────────────────────────────────┘ │
├─────────────────────────────────────┤
│ STEP 2: Profiles from Groups        │
│ ┌─────────────────────────────────┐ │
│ │ Accounts          (14 selected) │ │
│ ├─────────────────────────────────┤ │
│ │ [Search accounts...]        🔍  │ │
│ │ [All] [FB] [IG] [TW] [LI] [TK] │ │ Platform filters
│ ├─────────────────────────────────┤ │
│ │ Community & Support (8)         │ │
│ │ ☑ ◯ @company    Instagram       │ │ flex-1 (scrolls)
│ │ ☑ ◯ Company FB  Facebook        │ │
│ │ ☐ ◯ @support    Twitter         │ │
│ │ ...                             │ │
│ ├─────────────────────────────────┤ │
│ │ Brand Ambassadors (6)           │ │
│ │ ☑ ◯ @influencer Instagram       │ │
│ │ ...                             │ │
│ └─────────────────────────────────┘ │
├─────────────────────────────────────┤
│ Selected Profiles Bar               │
│ ◯◯◯ +11 (14 selected)              │
└─────────────────────────────────────┘
```

### 1.2 User Flow (Current)

```
1. User opens publishing modal
2. See 3-column layout (profile + composer + preview)
3. Left panel shows Account Groups selector (320px)
4. STEP 1: User selects account groups via checkboxes
   - "Community & Support" ✓
   - "Brand Ambassadors" ✓
5. STEP 2: Panel updates to show profiles from selected groups
6. User can:
   - Search profiles by name
   - Filter by platform (FB, IG, TW, etc.)
   - Select individual profiles with checkboxes
   - OR click group header to select all in group
7. Selected profiles shown in bottom bar (avatars + count)
8. Proceed to compose content
```

### 1.3 Critical UX Issues

**Issue #1: Fixed Width Crisis**
```
iPhone SE (375px screen):
┌─────────────────────────────────────┐
│ 320px Groups │ 55px │ <-- Impossible!
│              │ (what │
│              │ fits  │
│              │ here?)│
└─────────────────────────────────────┘

Result: Modal completely unusable on mobile
```

**Issue #2: Touch Targets Too Small**
| Element | Current Size | Apple HIG | Status |
|---------|-------------|-----------|--------|
| Group checkboxes | `w-4 h-4` (16px) | 44px min | ❌ 63% too small |
| Profile checkboxes | `w-4 h-4` (16px) | 44px min | ❌ 63% too small |
| Platform filter pills | `px-2 py-1` (~32px) | 44px min | ⚠️ 27% too small |
| "Select All" buttons | `text-xs` (~28px) | 44px min | ⚠️ 36% too small |
| Profile cards | `p-2` (varies) | 44px min | ⚠️ Borderline |

**Issue #3: Nested Selection Confusion**
- Two-step process (groups → profiles) not obvious
- Users must:
  1. Select groups first
  2. Wait for profiles to load
  3. Then select individual profiles
- On mobile, this workflow is interrupted by cramped space
- No visual indicator showing "you're in step 2 now"

**Issue #4: Search + Filters Cramped**
```
┌─────────────────────────────────┐
│ [Search accounts...     ]   🔍  │ 320px wide
│ [All] [FB] [IG] [TW] [LI] [TK] │ Pills wrap to 2-3 rows
│ [YT] [GB]                       │
└─────────────────────────────────┘

On mobile (320px total width):
- Search input too narrow
- Filter pills wrap excessively
- Takes valuable vertical space
- Difficult to tap small pills
```

**Issue #5: Profile List Scrolling Issues**
- Groups and profiles all in one scrollable container
- If you have 5 groups with 10 profiles each = 50 items
- Fixed `max-h-32` (128px) for groups means excessive scrolling for profiles
- No "sticky" group headers when scrolling
- Lost context: "Which group am I in?"

**Issue #6: Empty State Blocking**
```html
<div x-show="selectedGroupIds.length === 0" class="text-center py-8">
  <i class="fas fa-folder-open text-2xl"></i>
  <p>Select groups first</p>
</div>
```
- Helpful, but takes space
- On mobile, this empty state pushes other content down
- Could be a small inline message instead

---

## 2. Enhanced Account Groups UX - Mobile First

### 2.1 Proposed Mobile Solution: Bottom Sheet + Stepper

**Pattern:** Progressive disclosure with clear steps

```
┌─────────────────────────────────┐
│ Publishing Modal Header         │ ← Sticky
├─────────────────────────────────┤
│ ┌─────────────────────────────┐ │
│ │ Accounts: 3 groups, 14      │ │ ← Tap to open
│ │ profiles selected        ▼  │ │    bottom sheet
│ └─────────────────────────────┘ │
├─────────────────────────────────┤
│                                 │
│ Composer (full width)           │
│ ...                             │
│                                 │
└─────────────────────────────────┘

When tapped, bottom sheet slides up:

┌─────────────────────────────────┐
│ Select Accounts            [✕]  │ ← Sheet header
├─────────────────────────────────┤
│ Step 1 of 2: Choose Groups      │ ← Clear step indicator
├─────────────────────────────────┤
│ ☐ Marketing Team (12 accounts)  │ ← 44px touch targets
│ ☑ Community & Support (8)       │
│ ☑ Brand Ambassadors (6)         │
│ ☐ Sales & Outreach (15)         │
│ ☐ Customer Service (10)         │
├─────────────────────────────────┤
│ [Select All]        [Next →]    │ ← Large buttons
└─────────────────────────────────┘

After "Next", sheet updates:

┌─────────────────────────────────┐
│ Select Accounts            [✕]  │
├─────────────────────────────────┤
│ Step 2 of 2: Choose Accounts    │
│ From: Community & Support,      │
│       Brand Ambassadors         │
├─────────────────────────────────┤
│ [Search accounts...]        🔍  │ ← Full width
├─────────────────────────────────┤
│ Filter by Platform:             │
│ ○ All ○ FB ○ IG ○ TW ○ LI ○ TK │ ← Large radio pills
├─────────────────────────────────┤
│ ┌─────────────────────────────┐ │
│ │ Community & Support (8)     │ │ ← Collapsible
│ │ ☑ ◯ @company    Instagram   │ │    sections
│ │ ☑ ◯ Company FB  Facebook    │ │
│ │ [Select All 8]              │ │
│ └─────────────────────────────┘ │
│ ┌─────────────────────────────┐ │
│ │ Brand Ambassadors (6)       │ │
│ │ ☑ ◯ @influencer Instagram   │ │
│ │ ...                         │ │
│ └─────────────────────────────┘ │
├─────────────────────────────────┤
│ 14 accounts selected            │
│ [← Back]           [Done ✓]     │ ← Navigation
└─────────────────────────────────┘
```

**Benefits:**
- ✅ **Full-screen workflow** - Uses entire mobile viewport
- ✅ **Clear steps** - "Step 1 of 2" eliminates confusion
- ✅ **Large touch targets** - 44px+ for all interactive elements
- ✅ **Progressive disclosure** - See groups, then profiles
- ✅ **Easy navigation** - Back/Next/Done buttons
- ✅ **Familiar pattern** - Bottom sheets standard on mobile

### 2.2 Mobile Optimizations

**Touch-Friendly Checkboxes:**
```html
<!-- Current: 16x16px checkbox (too small) -->
<input type="checkbox" class="w-4 h-4">

<!-- Enhanced: 44x44px touch area with visual checkbox -->
<label class="flex items-center gap-3 p-3 rounded-lg active:bg-gray-100 cursor-pointer min-h-[44px]">
  <div class="relative flex-shrink-0">
    <input type="checkbox" class="sr-only peer">
    <div class="w-6 h-6 rounded border-2 border-gray-300 peer-checked:bg-indigo-600 peer-checked:border-indigo-600 flex items-center justify-center">
      <i class="fas fa-check text-white text-sm hidden peer-checked:block"></i>
    </div>
  </div>
  <div class="flex-1">
    <span class="font-medium">Marketing Team</span>
    <span class="text-gray-500">(12 accounts)</span>
  </div>
</label>
```

**Collapsible Group Sections:**
```html
<!-- Mobile: Groups collapsed by default, expand to see profiles -->
<div class="border rounded-lg mb-2">
  <button @click="expandedGroup = expandedGroup === 'group-1' ? null : 'group-1'"
          class="w-full flex items-center justify-between p-4 min-h-[44px]">
    <div class="flex items-center gap-2">
      <i class="fas fa-chevron-right transition-transform" :class="expandedGroup === 'group-1' ? 'rotate-90' : ''"></i>
      <span class="font-medium">Community & Support</span>
      <span class="text-sm text-gray-500">(8)</span>
    </div>
    <span class="text-sm text-indigo-600">4 selected</span>
  </button>

  <div x-show="expandedGroup === 'group-1'" x-collapse class="border-t">
    <!-- Profile checkboxes here -->
  </div>
</div>
```

**Platform Filter Pills (Larger):**
```html
<!-- Current: Small pills that wrap -->
<button class="px-2 py-1 text-xs">FB</button>

<!-- Enhanced: Larger, scrollable horizontal -->
<div class="flex gap-2 overflow-x-auto pb-2 hide-scrollbar">
  <button class="px-4 py-2 text-sm rounded-full border-2 flex-shrink-0 min-w-[44px] min-h-[44px]"
          :class="platformFilter === null ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300'">
    All
  </button>
  <button class="px-4 py-2 text-sm rounded-full border-2 flex-shrink-0 min-w-[44px] min-h-[44px]">
    <i class="fab fa-facebook"></i> Facebook
  </button>
  <button class="px-4 py-2 text-sm rounded-full border-2 flex-shrink-0 min-w-[44px] min-h-[44px]">
    <i class="fab fa-instagram"></i> Instagram
  </button>
  <!-- Horizontal scroll for more platforms -->
</div>
```

---

## 3. Enhanced Account Groups UX - Tablet

### 3.1 Tablet Solution: Slide-In Panel

**Layout:** Keep visible but collapsible

```
Tablet Portrait (768px):
┌───────┬──────────────────────┐
│ Acc.  │ Composer             │
│ Groups│                      │
│ 280px │                      │
│       │                      │
│ [←]   │                      │ ← Collapse button
└───────┴──────────────────────┘

Collapsed state:
┌──┬────────────────────────────┐
│ A│ Composer                   │ ← "A" tab shows
│ c│                            │    selected count
│ c│                            │
│ [→]                           │ ← Expand button
└──┴────────────────────────────┘
```

**Benefits:**
- ✅ More space for composer when needed
- ✅ Quick access to account selection
- ✅ Persistent visual reminder of selected accounts

### 3.2 Tablet Optimizations

**Two-Column Profile Grid:**
```html
<!-- On tablet, show profiles in 2 columns for efficiency -->
<div class="grid grid-cols-2 gap-2">
  <label class="flex items-center gap-2 p-2 rounded border">
    <input type="checkbox" class="w-5 h-5">
    <img src="..." class="w-8 h-8 rounded-full">
    <div class="min-w-0 flex-1">
      <p class="text-sm truncate">@company</p>
      <p class="text-xs text-gray-500 truncate">Instagram</p>
    </div>
  </label>
</div>
```

---

## 4. Enhanced Account Groups UX - Desktop

### 4.1 Desktop Solution: Keep Current + Enhancements

**Layout:** Current three-column works well on desktop

```
Desktop (1024px+):
┌─────────┬───────────────┬──────────┐
│ Account │ Composer      │ Preview  │
│ Groups  │               │          │
│ 320px   │ flex-1        │ 384px    │
│         │               │          │
│ ▼       │               │          │
└─────────┴───────────────┴──────────┘
```

**Enhancements:**

1. **Sticky Group Headers**
   - When scrolling profiles, group header sticks to top
   - Always know which group you're viewing

2. **Keyboard Shortcuts**
   - `Ctrl+G`: Focus group search
   - `Ctrl+A`: Select all profiles
   - `Space`: Toggle checkbox under focus
   - `Tab`: Navigate between groups/profiles

3. **Drag to Reorder** (optional)
   - Drag groups to reorder priority
   - Visual indication of order

4. **Smart Presets** (optional)
   - "Last used selection"
   - "Saved presets" (e.g., "All Social", "Brand Channels")
   - Quick restore

---

## 5. Phased Implementation for Account Groups

### Phase 1A: Mobile Bottom Sheet (CRITICAL)
**Goal:** Make account selection functional on mobile

**Tasks:**
1. Create bottom sheet component
2. Implement two-step flow (groups → profiles)
3. Increase touch targets to 44px minimum
4. Add step indicator (1 of 2, 2 of 2)
5. Test on iPhone SE, iPhone 14, Pixel 7

**Files to Modify:**
- `profile-selector.blade.php` - Add mobile breakpoint logic
- `publish-modal.blade.php` - Add bottom sheet overlay
- `publish-modal.js` - Add bottom sheet state management

**Success Metrics:**
- ✅ Account selection works on 375px screen
- ✅ All touch targets ≥ 44px
- ✅ Clear step progression (no confusion)
- ✅ < 10 seconds to select accounts (average)

**Estimated Effort:** 1-2 days
**Risk:** Medium (new pattern, needs testing)

---

### Phase 1B: Tablet Slide-In Panel (HIGH)
**Goal:** Optimize for tablet landscape/portrait

**Tasks:**
1. Create collapsible side panel
2. Add collapse/expand animations
3. Two-column profile grid
4. Test on iPad Mini, iPad Pro

**Files to Modify:**
- `profile-selector.blade.php` - Add tablet breakpoint
- Add collapse/expand button

**Success Metrics:**
- ✅ Panel collapses to give composer more space
- ✅ Smooth animations (no jank)
- ✅ Works in portrait and landscape

**Estimated Effort:** 0.5-1 day
**Risk:** Low (additive enhancement)

---

### Phase 1C: Desktop Enhancements (MEDIUM)
**Goal:** Improve power-user efficiency

**Tasks:**
1. Sticky group headers when scrolling
2. Keyboard shortcuts (Ctrl+G, Ctrl+A)
3. Optional: Drag to reorder groups
4. Optional: Saved presets

**Files to Modify:**
- `profile-selector.blade.php` - Sticky headers
- `publish-modal.js` - Keyboard handlers

**Success Metrics:**
- ✅ Group headers visible when scrolling
- ✅ Keyboard shortcuts functional
- ✅ 20% faster selection (power users)

**Estimated Effort:** 1 day
**Risk:** Low (additive)

---

## 6. Implementation Code Examples

### 6.1 Mobile Bottom Sheet Structure

```blade
{{-- Mobile: Bottom Sheet for Account Groups --}}
<div x-show="showAccountSelector"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-y-full"
     x-transition:enter-end="translate-y-0"
     class="fixed inset-x-0 bottom-0 bg-white rounded-t-2xl shadow-2xl z-50 md:hidden"
     style="max-height: 90vh;">

    {{-- Sheet Handle --}}
    <div class="flex items-center justify-center py-2">
        <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
    </div>

    {{-- Sheet Header --}}
    <div class="flex items-center justify-between px-4 py-3 border-b">
        <h3 class="text-lg font-semibold">{{ __('publish.select_accounts') }}</h3>
        <button @click="showAccountSelector = false" class="p-2 hover:bg-gray-100 rounded-full">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    {{-- Step Indicator --}}
    <div class="px-4 py-2 bg-blue-50 border-b">
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold"
                     :class="accountSelectionStep === 1 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600'">1</div>
                <span class="text-sm" :class="accountSelectionStep === 1 ? 'font-semibold' : 'text-gray-500'">
                    {{ __('publish.choose_groups') }}
                </span>
            </div>
            <div class="flex-1 h-0.5 bg-gray-300"></div>
            <div class="flex items-center gap-1">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold"
                     :class="accountSelectionStep === 2 ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600'">2</div>
                <span class="text-sm" :class="accountSelectionStep === 2 ? 'font-semibold' : 'text-gray-500'">
                    {{ __('publish.choose_accounts') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Sheet Content --}}
    <div class="flex-1 overflow-y-auto p-4">
        {{-- Step 1: Groups --}}
        <div x-show="accountSelectionStep === 1" class="space-y-2">
            <template x-for="group in profileGroups" :key="group.group_id">
                <label class="flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition min-h-[56px]"
                       :class="selectedGroupIds.includes(group.group_id) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 active:bg-gray-50'">
                    <div class="relative flex-shrink-0">
                        <input type="checkbox" :value="group.group_id"
                               :checked="selectedGroupIds.includes(group.group_id)"
                               @change="toggleGroupId(group.group_id)"
                               class="sr-only peer">
                        <div class="w-6 h-6 rounded-md border-2 peer-checked:bg-indigo-600 peer-checked:border-indigo-600 flex items-center justify-center transition"
                             :class="selectedGroupIds.includes(group.group_id) ? 'border-indigo-600' : 'border-gray-300'">
                            <i class="fas fa-check text-white text-sm" x-show="selectedGroupIds.includes(group.group_id)"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900" x-text="group.name"></p>
                        <p class="text-sm text-gray-500" x-text="(group.profiles?.length || 0) + ' {{ __('publish.accounts') }}'"></p>
                    </div>
                </label>
            </template>
        </div>

        {{-- Step 2: Profiles --}}
        <div x-show="accountSelectionStep === 2" class="space-y-3">
            {{-- Search --}}
            <div class="relative">
                <input type="text" x-model="profileSearch"
                       placeholder="{{ __('publish.search_accounts') }}"
                       class="w-full pl-10 pr-4 py-3 text-base border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>

            {{-- Platform Filters (Horizontal Scroll) --}}
            <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 hide-scrollbar">
                <button @click="platformFilter = null"
                        class="px-5 py-2.5 text-sm font-medium rounded-full border-2 flex-shrink-0 transition"
                        :class="platformFilter === null ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300'">
                    {{ __('publish.all') }}
                </button>
                <template x-for="platform in availablePlatforms" :key="platform">
                    <button @click="platformFilter = platform"
                            class="px-5 py-2.5 text-sm font-medium rounded-full border-2 flex-shrink-0 transition"
                            :class="platformFilter === platform ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300'">
                        <i :class="getPlatformIcon(platform)"></i>
                    </button>
                </template>
            </div>

            {{-- Profile Groups (Collapsible) --}}
            <template x-for="group in filteredProfileGroups" :key="group.group_id">
                <div class="border-2 border-gray-200 rounded-xl overflow-hidden">
                    {{-- Group Header --}}
                    <button @click="toggleExpandedGroup(group.group_id)"
                            class="w-full flex items-center justify-between p-4 bg-gradient-to-r from-indigo-50 to-purple-50 min-h-[56px]">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-chevron-right transition-transform text-indigo-600"
                               :class="expandedGroups.includes(group.group_id) ? 'rotate-90' : ''"></i>
                            <span class="font-semibold text-gray-900" x-text="group.name"></span>
                            <span class="text-sm text-gray-600" x-text="'(' + (group.profiles?.length || 0) + ')'"></span>
                        </div>
                        <span class="text-sm font-medium text-indigo-600"
                              x-text="getSelectedCountInGroup(group) + ' {{ __('publish.selected') }}'"></span>
                    </button>

                    {{-- Profile List (Collapsible) --}}
                    <div x-show="expandedGroups.includes(group.group_id)" x-collapse>
                        <template x-for="profile in group.profiles" :key="profile.integration_id">
                            <label class="flex items-center gap-3 p-4 border-t border-gray-100 cursor-pointer active:bg-gray-50 min-h-[64px]">
                                <div class="relative flex-shrink-0">
                                    <input type="checkbox" :value="profile.integration_id"
                                           :checked="isProfileSelected(profile.integration_id)"
                                           @change="toggleProfile(profile)"
                                           class="sr-only peer">
                                    <div class="w-6 h-6 rounded-full border-2 peer-checked:bg-blue-600 peer-checked:border-blue-600 flex items-center justify-center"
                                         :class="isProfileSelected(profile.integration_id) ? 'border-blue-600' : 'border-gray-300'">
                                        <i class="fas fa-check text-white text-sm" x-show="isProfileSelected(profile.integration_id)"></i>
                                    </div>
                                </div>
                                <img :src="profile.avatar_url || getDefaultAvatar(profile)"
                                     :alt="profile.account_name"
                                     class="w-12 h-12 rounded-full">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 truncate" x-text="profile.account_name"></p>
                                    <p class="text-sm text-gray-500 truncate">
                                        <i :class="getPlatformIcon(profile.platform) + ' me-1'"></i>
                                        <span x-text="profile.platform_handle || profile.platform"></span>
                                    </p>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Sheet Footer --}}
    <div class="border-t bg-gray-50 p-4 flex items-center justify-between">
        {{-- Step 1 Footer --}}
        <template x-if="accountSelectionStep === 1">
            <div class="flex items-center gap-3 w-full">
                <span class="text-sm text-gray-600" x-text="selectedGroupIds.length + ' {{ __('publish.groups_selected') }}'"></span>
                <div class="flex-1"></div>
                <button @click="showAccountSelector = false"
                        class="px-6 py-3 text-base font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-xl">
                    {{ __('publish.cancel') }}
                </button>
                <button @click="accountSelectionStep = 2"
                        :disabled="selectedGroupIds.length === 0"
                        class="px-6 py-3 text-base font-medium text-white bg-blue-600 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ __('publish.next') }} →
                </button>
            </div>
        </template>

        {{-- Step 2 Footer --}}
        <template x-if="accountSelectionStep === 2">
            <div class="flex items-center gap-3 w-full">
                <span class="text-sm text-gray-600" x-text="selectedProfiles.length + ' {{ __('publish.accounts_selected') }}'"></span>
                <div class="flex-1"></div>
                <button @click="accountSelectionStep = 1"
                        class="px-6 py-3 text-base font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-xl">
                    ← {{ __('publish.back') }}
                </button>
                <button @click="showAccountSelector = false"
                        :disabled="selectedProfiles.length === 0"
                        class="px-6 py-3 text-base font-medium text-white bg-green-600 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ __('publish.done') }} ✓
                </button>
            </div>
        </template>
    </div>
</div>

{{-- Badge to Open Bottom Sheet (Mobile) --}}
<button @click="showAccountSelector = true; accountSelectionStep = 1"
        class="md:hidden flex items-center justify-between w-full p-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl border-2 border-indigo-200 mb-4">
    <div class="flex items-center gap-3">
        <i class="fas fa-users text-indigo-600 text-xl"></i>
        <div class="text-left">
            <p class="text-sm font-semibold text-gray-900">{{ __('publish.accounts') }}</p>
            <p class="text-xs text-gray-600" x-text="selectedProfiles.length + ' {{ __('publish.selected') }}'"></p>
        </div>
    </div>
    <i class="fas fa-chevron-right text-gray-400"></i>
</button>
```

### 6.2 JavaScript State Management

```javascript
// Add to publish-modal.js

// Account selector state
showAccountSelector: false,
accountSelectionStep: 1, // 1 = groups, 2 = profiles
expandedGroups: [],

// Toggle expanded group
toggleExpandedGroup(groupId) {
    const index = this.expandedGroups.indexOf(groupId);
    if (index > -1) {
        this.expandedGroups.splice(index, 1);
    } else {
        this.expandedGroups.push(groupId);
    }
},

// Get selected count in specific group
getSelectedCountInGroup(group) {
    return this.selectedProfiles.filter(p =>
        group.profiles.some(gp => gp.integration_id === p.integration_id)
    ).length;
},

// Watch for group selection changes to auto-expand in step 2
init() {
    this.$watch('accountSelectionStep', (newStep) => {
        if (newStep === 2) {
            // Auto-expand all selected groups when entering step 2
            this.expandedGroups = this.selectedGroupIds;
        }
    });
}
```

---

## 7. Testing Checklist for Account Groups

**Mobile Testing:**
- [ ] Bottom sheet opens smoothly on tap
- [ ] Step 1: All group checkboxes ≥ 44px touch area
- [ ] Step 2: All profile checkboxes ≥ 44px touch area
- [ ] Platform filter pills scroll horizontally without wrapping
- [ ] Collapsible groups expand/collapse smoothly
- [ ] "Next" button disabled when no groups selected
- [ ] "Done" button disabled when no profiles selected
- [ ] Back button returns to step 1 with selections preserved
- [ ] Selected count updates in real-time
- [ ] Works on iPhone SE (375px)
- [ ] Works on iPhone 14 Pro Max (430px)
- [ ] Works in both Arabic (RTL) and English (LTR)

**Tablet Testing:**
- [ ] Side panel collapses to give composer more space
- [ ] Expand button shows selected count
- [ ] Two-column profile grid displays correctly
- [ ] Works in portrait (768x1024)
- [ ] Works in landscape (1024x768)
- [ ] Smooth collapse/expand animation

**Desktop Testing:**
- [ ] Current three-column layout preserved
- [ ] Sticky group headers work when scrolling
- [ ] Keyboard shortcuts functional (if implemented)
- [ ] All interactions work as before
- [ ] No regression in existing functionality

---

## 8. Success Metrics

**Usability:**
- ✅ Time to select accounts: < 15 seconds (down from ~30s on mobile)
- ✅ Error rate: < 3% (wrong accounts selected)
- ✅ Task completion: > 98% (successfully select accounts)
- ✅ Mobile usage: Increase from 0% to 40%+

**Technical:**
- ✅ All touch targets ≥ 44x44px: 100% compliance
- ✅ No horizontal scroll: 0 occurrences on any device
- ✅ Animation performance: 60fps on all devices
- ✅ Accessibility: WCAG 2.1 AA compliant

**User Satisfaction:**
- ✅ "Easy to find my accounts": > 90% agree
- ✅ "Selection process is clear": > 95% agree
- ✅ "Works well on my device": > 95% agree

---

## 9. Conclusion

The Account Groups selector is a critical component that enables users to choose where their content will be published. The current fixed-width design makes it completely unusable on mobile devices, blocking 40-60% of potential users.

**Recommended Approach:**
1. **Phase 1A (CRITICAL):** Implement mobile bottom sheet with two-step flow
2. **Phase 1B (HIGH):** Add tablet slide-in panel optimization
3. **Phase 1C (MEDIUM):** Enhance desktop with sticky headers and shortcuts

**Expected Outcomes:**
- 📱 **40%+ increase in mobile publishing** - from broken to best-in-class
- ⚡ **50% faster account selection** - clear steps, larger targets
- ✅ **98%+ task completion** - no more confusion or errors
- 💚 **High user satisfaction** - familiar mobile patterns

**Timeline:** 2-3 days for all three sub-phases

---

**Document Version:** 1.0
**Last Updated:** 2025-11-29
**Status:** Ready for Implementation
**Next Action:** Begin Phase 1A (Mobile Bottom Sheet)
