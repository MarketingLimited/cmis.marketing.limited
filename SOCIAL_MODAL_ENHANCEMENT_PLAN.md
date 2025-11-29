# Social Post Modal Enhancement Plan
**Date:** 2025-11-29
**Competitor Analysis:** VistaSocial Publishing Modal
**Target:** `/orgs/{org_id}/social` - New Post Modal

---

## 📊 Competitor Analysis Summary

### VistaSocial Key Features Identified (39 screenshots analyzed - Nov 29, 2025):

1. **Three-Column Layout**
   - Left: Profile/Account selector with groups, search, select all
   - Center: Content editor with AI assistant, formatting tools
   - Right: Customize panel with platform-specific options

2. **Profile Selection (Left Sidebar)**
   - Hierarchical grouping (e.g., "Marketing Dot Limited", "Mohamed Al Moalef")
   - Search functionality
   - "Select all" / "Clear selection" options
   - Profile avatars with platform icons
   - Account follower counts displayed
   - Expandable/collapsible groups

3. **Content Area (Center)**
   - AI Assistant button with modal:
     - Guided mode vs Advanced mode
     - Tone selection (No tone, Funny, Formal, Informal, Promotional, Engaging, Assertive, Catchy, Inspirational, Shocking)
     - Format options (No format, Shorten, Expand, Rephrase)
     - Brand voice integration
   - Rich text editor with toolbar:
     - Emoji picker
     - Hashtag (#)
     - @Mentions
     - Link insertion
     - Camera/media upload
     - Poll creation
     - GIF selector
   - Platform-specific character counters (FB: 5000, IG: 2200, LinkedIn: 3000, YouTube: 5000, TikTok: 2000, Snapchat: 80)
   - Media upload options dropdown:
     - Upload from computer
     - Upload from URL
     - Choose from library
     - Discover
     - Google Drive
     - Dropbox
     - OneDrive
     - Canva
     - Dynamic image
     - Dynamic video
   - Attached media preview with remove option
   - Image/video processing indicator
   - Labels selection
   - "Add to advocacy" toggle

4. **Customize Panel (Right Sidebar)**
   - "Customize your post" header with expand/collapse
   - Caption editing with character counter (per platform)
   - "Click to edit media" section
   - "Auto publish" dropdown:
     - Publish as Feed post (default)
     - Publish as Story
     - Publish as Reel
     - Publish as Public Story
     - Publish as Profile Story
   - Post comments section:
     - "Leave first comment (pages only)" input
     - Character counter (0/8000)
     - "Add another comment" button
   - Comments enabled toggle
   - Location toggle with search
   - Country targeting
   - Relationship status dropdown
   - Gender dropdown
   - Min age / Max age inputs
   - "Select a boost" dropdown
   - Platform-specific apply links (e.g., "Apply to all Instagram profiles")
   - Preview showing how post looks on each platform

5. **Warnings & Alerts**
   - Orange warning banner: "Captions and Media for Instagram and YouTube were changed and need to be managed on the right"
   - "Changes saved" indicator in top-right

6. **Bottom Actions Bar**
   - Selected profiles carousel (left side with < > navigation arrows)
   - Radio buttons: Save draft, Add to queue, Schedule, Publish now (with blue selection)
   - "Next" button (primary action, blue)
   - Clean separation from modal content with subtle shadow

7. **Scheduling Interface**
   - Calendar view with monthly grid
   - Timezone selector (GMT +03:00 Asia/Bahrain)
   - Date picker
   - Time picker (hour, minute, AM/PM)
   - "Add more schedule times" link
   - "Show optimal times" link
   - "Evergreen settings" link
   - "Need to schedule a lot at once? Try Bulk Scheduling" link
   - Visual preview of scheduled posts on calendar
   - Previous/Assign/Schedule buttons

8. **Platform Icons at Top**
   - Facebook, Instagram, LinkedIn, YouTube, Bluesky, TikTok, Snapchat
   - Shows connected platforms
   - Icons are clickable to filter/view platform-specific settings

9. **Video-Specific Options** (YouTube/TikTok/Reels)
   - Video title field (required, with character counter)
   - Video processing toggle and indicator
   - "Create first like" toggle
   - "Embeddable" toggle
   - "Notify subscribers" toggle
   - Category selector dropdown
   - Tag your video input
   - Public/Private visibility dropdown

10. **Mobile Preview Mockup**
   - Right sidebar shows realistic mobile phone preview
   - Platform-specific UI elements (Instagram story controls, Give button, Share, etc.)
   - Profile picture and account name display
   - Caption preview with character trimming
   - Interactive elements visible but disabled

11. **Brand Safety & Compliance**
   - "Brand Safety & Compliance Policies" modal (premium feature)
   - Upgrade prompts for advanced features
   - Content compliance checking

---

## 🎯 Current CMIS Implementation Gaps

### ✅ What CMIS Has:
- ✅ Basic platform selection with checkboxes
- ✅ Account selection per platform
- ✅ Post type dropdown
- ✅ Simple textarea for content
- ✅ Character counter (single, based on smallest platform limit)
- ✅ File upload (drag & drop)
- ✅ Media preview with remove
- ✅ Publishing options (now, scheduled, queue, draft)
- ✅ Datetime picker for scheduling
- ✅ Validation

### ❌ What CMIS is Missing:
- ❌ Three-column layout (profiles left, content center, customize right)
- ❌ AI Assistant with tone & format options
- ❌ Multiple media upload sources (URL, library, integrations)
- ❌ Rich text formatting toolbar (emoji picker, @mentions, hashtags, bold, italic)
- ❌ Platform-specific character counters (showing all platforms simultaneously)
- ❌ Real-time mobile preview mockup per platform
- ❌ Profile grouping with hierarchy
- ❌ Search in profile selection
- ❌ "Select all" / "Clear selection"
- ❌ Customize panel with platform-specific options
- ❌ Per-platform caption editing
- ❌ Video title, video processing, create first like, embeddable, notify subscribers
- ❌ First comment, location, boost, country/gender/age targeting options
- ❌ Platform-specific warnings/alerts (orange banner)
- ❌ Image/video processing indicators with "Learn more" links
- ❌ Labels/tags selection
- ❌ Calendar view for scheduling with visual post thumbnails
- ❌ Optimal times suggestion
- ❌ Selected profiles carousel at bottom with navigation arrows
- ❌ Expandable/collapsible sections (groups, customize panel)
- ❌ "Changes saved" auto-fade indicator
- ❌ Brand safety & compliance checking

---

## 🚀 Enhancement Plan (Phased Approach)

### **Phase 1: Layout & Structure** [PRIORITY: HIGH]

**Objective:** Transform single-column modal to three-column layout

**Tasks:**
1. **Expand Modal Width**
   - Change from `sm:max-w-4xl` to `sm:max-w-[90vw] xl:max-w-[1400px]`
   - Increase height to `min-h-[85vh]`

2. **Create Three-Column Grid**
   ```html
   <div class="grid grid-cols-12 gap-4 min-h-[75vh]">
     <!-- Left: Profiles (3 cols) -->
     <div class="col-span-3 border-r border-gray-200 overflow-y-auto"></div>

     <!-- Center: Content (6 cols) -->
     <div class="col-span-6 overflow-y-auto"></div>

     <!-- Right: Customize (3 cols) -->
     <div class="col-span-3 border-l border-gray-200 overflow-y-auto"></div>
   </div>
   ```

3. **Left Sidebar: Profile Selection**
   - Move platform/account selection to left sidebar
   - Add "Select Profiles: X" dropdown header
   - Add search input with filter icon
   - Add "Select all" / "Clear selection" buttons
   - Group accounts by organization/team (if applicable)
   - Show profile avatars (circular, 32px)
   - Show platform icon overlay on avatar
   - Display follower count beneath account name
   - Make groups expandable/collapsible with chevron icons

4. **Center: Content Editor**
   - Move content textarea to center
   - Add "Your post" header
   - Add "Use the AI Assistant" button (top-right of editor)
   - Keep media upload section
   - Add character counter badges for each selected platform
   - Add formatting toolbar above textarea

5. **Right Sidebar: Customize Panel**
   - Header: "Customize your post" with collapse arrow
   - Caption editing per platform (if different from main)
   - Media editing section
   - Auto publish dropdown
   - Post comments section
   - Advanced options (location, boost, etc.)

6. **Bottom Actions Bar**
   - Selected profiles carousel (left)
   - Publishing option radio buttons (center-right)
   - Primary action button (right)

**i18n Requirements:**
- All text must use `__('social.key')`
- Use logical CSS: `ms-`, `me-`, `ps-`, `pe-`, `text-start`, `text-end`
- Test both Arabic (RTL) and English (LTR)

**Files to Modify:**
- `resources/views/social/posts.blade.php` (lines 174-422)
- Create new translation keys in `resources/lang/*/social.php`

---

### **Phase 2: Rich Text Editor & AI Assistant** [PRIORITY: HIGH]

**Objective:** Add formatting toolbar and AI assistance

**Tasks:**
1. **Formatting Toolbar**
   - Add toolbar above textarea with buttons:
     - Emoji picker (use emoji-mart or similar)
     - Bold (B) / Italic (I) buttons
     - Hashtag (#) button
     - @Mention button (with autocomplete)
     - Link button
     - GIF selector
   - Handle text insertion at cursor position

2. **AI Assistant Modal**
   - Create nested modal component
   - Two tabs: "Guided mode" | "Advanced mode"
   - Guided mode:
     - "Your original content" textarea
     - Tone selector (9 options with emoji icons)
     - Format selector (4 options)
     - Brand voice message (if not set up)
   - Advanced mode (future: full prompt input)
   - "Cancel" button to close
   - Generate content and insert into main textarea

3. **Character Counters**
   - Show badges for each selected platform
   - Display as: `<platform-icon> <count>` (e.g., "📘 5000", "📷 2200")
   - Highlight in orange when approaching limit (>90%)
   - Highlight in red when exceeding limit

**Dependencies:**
- Consider using Tiptap or similar WYSIWYG editor
- Or build custom toolbar with Alpine.js

**i18n Requirements:**
- AI Assistant UI fully translated
- Tone/format options with translations

**Files to Create:**
- `resources/js/components/emoji-picker.js`
- `resources/views/social/partials/ai-assistant-modal.blade.php`

**Files to Modify:**
- `resources/views/social/posts.blade.php` (content section)

---

### **Phase 3: Media Management** [PRIORITY: MEDIUM]

**Objective:** Multiple upload sources and better media handling

**Tasks:**
1. **Media Upload Dropdown**
   - Replace simple file input with dropdown menu:
     - 📁 Upload from computer
     - 🔗 Upload from URL
     - 📚 Choose from library
     - 🔍 Discover (stock photos)
     - Google Drive integration
     - Dropbox integration
     - OneDrive integration
     - Canva integration
     - 🖼️ Dynamic image
     - 🎥 Dynamic video

2. **Upload from URL Modal**
   - Input field for URL
   - Preview before adding
   - Validate URL format

3. **Media Library Modal**
   - Grid view of previously uploaded media
   - Filter by type (images/videos)
   - Search by filename
   - Multi-select capability
   - Upload new to library

4. **Dynamic Image/Video**
   - Template selection for branded content
   - Personalization fields

5. **Image Processing**
   - Toggle for "Image processing"
   - Show spinner/progress during upload
   - Display "Image processing. Learn more" message

6. **Attached Media Section**
   - Show all attached media as thumbnails
   - Display in left column under editor
   - Remove button (X icon)
   - Reorder capability (drag & drop)

**i18n Requirements:**
- All upload option labels translated
- Error messages for invalid URLs

**Files to Create:**
- `resources/views/social/partials/media-library-modal.blade.php`
- `resources/views/social/partials/upload-url-modal.blade.php`
- `app/Services/Social/MediaLibraryService.php`

**Files to Modify:**
- `resources/views/social/posts.blade.php` (media section)

---

### **Phase 4: Customize Panel** [PRIORITY: MEDIUM]

**Objective:** Platform-specific customization options

**Tasks:**
1. **Customize Header**
   - "Customize your post" with expand/collapse arrow
   - Collapsible state management

2. **Caption Editing**
   - "Click to edit caption" button
   - Character counter (X / XXXX)
   - Per-platform caption override
   - "Apply to all <Platform> profiles" link

3. **Media Editing**
   - "Click to edit media" button
   - Crop/resize interface
   - Platform-specific aspect ratios

4. **Auto Publish Dropdown**
   - Options:
     - Publish as Feed post
     - Publish as Story
     - Publish as Reel (Instagram)
     - Publish as Public Story (Facebook)
     - Publish as Profile Story (Facebook)
   - Dynamically show based on selected platforms

5. **Post Comments Section**
   - Collapsible section
   - "Leave first comment (pages only)" input
   - Character counter (0/8000)
   - "+ Add another comment" button
   - Multi-comment support

6. **Comments Enabled Toggle**
   - Simple toggle switch
   - Default: ON

7. **Location Toggle & Search**
   - Toggle to enable location tagging
   - Search input for location (Google Places API)
   - Show selected location

8. **Advanced Options** (Facebook-specific)
   - Country targeting dropdown
   - Relationship status dropdown
   - Gender dropdown
   - Min age / Max age number inputs

9. **Select a Boost**
   - Dropdown for boost campaigns
   - Link to create new boost

10. **Platform-Specific Apply Links**
    - "Apply to all Instagram profiles"
    - "Apply to all Facebook profiles"
    - Blue hyperlink style

**i18n Requirements:**
- All customize options translated
- Location search in user's language

**Files to Create:**
- `resources/views/social/partials/customize-panel.blade.php`
- `resources/js/components/location-search.js`

**Files to Modify:**
- `resources/views/social/posts.blade.php` (right sidebar)

---

### **Phase 5: Preview & Warnings** [PRIORITY: MEDIUM]

**Objective:** Real-time preview and platform-specific alerts

**Tasks:**
1. **Preview Section**
   - Add to right sidebar (above customize)
   - Mobile preview mockup for each platform
   - Show how post will appear:
     - Profile picture
     - Account name
     - Post content
     - Media preview
     - Like/comment/share buttons (disabled)
   - Tab between platforms

2. **Warning Banner**
   - Orange alert banner below header
   - Icon + message
   - "Reset all customizations" link
   - Examples:
     - "Captions and Media for Instagram and YouTube were changed and need to be managed on the right"
     - "Character limit exceeded for Twitter"

3. **Changes Saved Indicator**
   - Top-right corner
   - "Changes saved" message
   - Auto-fade after 2 seconds

**i18n Requirements:**
- Warning messages translated
- Preview UI labels translated

**Files to Create:**
- `resources/views/social/partials/platform-preview.blade.php`
- `resources/js/components/post-preview.js`

**Files to Modify:**
- `resources/views/social/posts.blade.php` (add preview + warnings)

---

### **Phase 6: Advanced Features** [PRIORITY: LOW]

**Objective:** Labels, search, calendar scheduling, optimal times

**Tasks:**
1. **Labels/Tags**
   - "Select labels" dropdown
   - Multi-select tags
   - Color-coded labels
   - Create new label inline

2. **Search in Profile Selection**
   - Search input at top of left sidebar
   - Filter accounts by name/username
   - Highlight matching text

3. **Select All / Clear Selection**
   - Buttons at top of profile list
   - "Select all" checks all visible (filtered) accounts
   - "Clear selection" unchecks all

4. **Profile Grouping**
   - Group by organization/team
   - Collapsible groups with chevron icons
   - Show count in group header (e.g., "Marketing Team (5)")

5. **Calendar View for Scheduling**
   - Replace datetime-local input with calendar modal
   - Monthly calendar grid
   - Show existing scheduled posts
   - Time picker (hour, minute, AM/PM)
   - Timezone selector
   - "Add more schedule times" for recurring posts

6. **Optimal Times**
   - "Show optimal times" button
   - Analyze past performance
   - Suggest best times to post
   - Quick-select suggestions

7. **Evergreen Settings**
   - Toggle for evergreen content
   - Repeat interval selector
   - End date option

8. **Selected Profiles Bar**
   - Bottom-left of modal
   - Scrollable carousel of selected profile avatars
   - Show count: "Selected profiles <" (count) ">"
   - Click avatar to jump to that account in list

**i18n Requirements:**
- All advanced feature labels translated
- Calendar in user's locale (ar-SA / en-US)
- Date/time formatting per locale

**Files to Create:**
- `resources/views/social/partials/calendar-scheduler.blade.php`
- `resources/js/components/calendar-scheduler.js`
- `app/Services/Social/OptimalTimesService.php`

**Files to Modify:**
- `resources/views/social/posts.blade.php` (add all advanced features)

---

## 📋 Translation Keys Required

Add to `resources/lang/en/social.php` and `resources/lang/ar/social.php`:

```php
// Profile Selection
'select_profiles' => 'Select Profiles',
'select_all' => 'Select all',
'clear_selection' => 'Clear selection',
'search_profiles' => 'Search',
'followers' => 'followers',

// AI Assistant
'use_ai_assistant' => 'Use the AI Assistant',
'ai_assistant' => 'AI Assistant',
'guided_mode' => 'Guided mode',
'advanced_mode' => 'Advanced mode',
'your_original_content' => 'Your original content',
'tone' => 'Tone',
'format' => 'Format',
'brand_voice' => 'Brand voice',
'brand_voice_setup' => 'Selected profile groups do not have brand voice setup. Please navigate to profile group settings to configure it.',
'tone_options' => [
    'no_tone' => 'No tone',
    'funny' => 'Funny',
    'formal' => 'Formal',
    'informal' => 'Informal',
    'promotional' => 'Promotional',
    'engaging' => 'Engaging',
    'assertive' => 'Assertive',
    'catchy' => 'Catchy',
    'inspirational' => 'Inspirational',
    'shocking' => 'Shocking',
],
'format_options' => [
    'no_format' => 'No format',
    'shorten' => 'Shorten',
    'expand' => 'Expand',
    'rephrase' => 'Rephrase',
],

// Formatting Toolbar
'add_emoji' => 'Add emoji',
'add_hashtag' => 'Add hashtag',
'mention_user' => 'Mention user',
'insert_link' => 'Insert link',
'add_gif' => 'Add GIF',

// Media Upload
'upload_from_computer' => 'Upload from computer',
'upload_from_url' => 'Upload from URL',
'choose_from_library' => 'Choose from library',
'discover' => 'Discover',
'google_drive' => 'Google drive',
'dropbox' => 'Dropbox',
'onedrive' => 'OneDrive',
'canva' => 'Canva',
'dynamic_image' => 'Dynamic image',
'dynamic_video' => 'Dynamic video',
'image_processing' => 'Image processing',
'video_processing' => 'Video processing',

// Customize Panel
'customize_your_post' => 'Customize your post',
'click_to_edit_caption' => 'Click to edit caption',
'click_to_edit_media' => 'Click to edit media',
'auto_publish' => 'Auto publish',
'publish_as_feed_post' => 'Publish as Feed post',
'publish_as_story' => 'Publish as Story',
'publish_as_reel' => 'Publish as Reel',
'publish_as_public_story' => 'Publish as Public Story',
'publish_as_profile_story' => 'Publish as Profile Story',
'post_comments' => 'Post comments',
'leave_first_comment' => 'Leave first comment (pages only)',
'add_another_comment' => 'Add another comment',
'comments_enabled' => 'Comments enabled',
'location' => 'Location',
'search_for_location' => 'Search for location',
'country_targeting' => 'Country targeting',
'relationship_status' => 'Relationship status',
'gender' => 'Gender',
'min_age' => 'Min age',
'max_age' => 'Max age',
'select_a_boost' => 'Select a boost',
'apply_to_all_platform' => 'Apply to all :platform profiles',

// Warnings
'captions_media_changed' => 'Captions and Media for :platforms were changed and need to be managed on the right',
'reset_all_customizations' => 'Reset all customizations',
'changes_saved' => 'Changes saved',

// Labels
'select_labels' => 'Select labels',
'add_to_advocacy' => 'Add to advocacy',

// Scheduling
'show_optimal_times' => 'Show optimal times',
'add_more_schedule_times' => 'Add more schedule times',
'evergreen_settings' => 'Evergreen settings',
'bulk_scheduling' => 'Try Bulk Scheduling',
'timezone' => 'Timezone',

// Actions
'selected_profiles_count' => 'Selected profiles',
'previous' => 'Previous',
'assign' => 'Assign',
'next' => 'Next',
```

---

## 🧪 Testing Plan

### Browser Testing (MANDATORY)

**Test Command:**
```bash
node scripts/browser-tests/mobile-responsive-comprehensive.js --quick
node test-bilingual-comprehensive.cjs
```

**Test Scenarios:**
1. **Arabic (RTL)**
   - Modal layout correct (three columns)
   - Text alignment: start (right in RTL)
   - Icons positioned correctly
   - Margins/padding using logical properties
   - Forms aligned correctly

2. **English (LTR)**
   - Modal layout correct (three columns)
   - Text alignment: start (left in LTR)
   - Icons positioned correctly
   - Margins/padding using logical properties
   - Forms aligned correctly

3. **Responsive (Mobile)**
   - Modal adapts to small screens
   - Columns stack vertically on mobile
   - Touch targets >= 44x44px
   - No horizontal overflow

4. **Functionality**
   - Platform selection works
   - Account selection works
   - Character counter updates
   - Media upload works
   - Publishing options work
   - Validation works

---

## 📦 Implementation Order

**Week 1:**
- Phase 1: Layout & Structure (3 days)
- Test: Browser testing (AR/EN, RTL/LTR, mobile)

**Week 2:**
- Phase 2: Rich Text Editor & AI Assistant (4 days)
- Test: AI functionality, character counters

**Week 3:**
- Phase 3: Media Management (4 days)
- Test: Upload sources, library

**Week 4:**
- Phase 4: Customize Panel (5 days)
- Test: Platform-specific options

**Week 5:**
- Phase 5: Preview & Warnings (3 days)
- Test: Preview accuracy, warning triggers

**Week 6:**
- Phase 6: Advanced Features (5 days)
- Test: Calendar, optimal times, labels

**Week 7:**
- Full integration testing
- Browser testing (all scenarios)
- Performance optimization
- Bug fixes

---

## ✅ Success Criteria

1. ✅ Modal matches VistaSocial's three-column layout
2. ✅ AI Assistant generates content with tone/format options
3. ✅ Multiple media upload sources functional
4. ✅ Platform-specific customization working
5. ✅ Real-time preview accurate
6. ✅ All features fully translated (AR/EN)
7. ✅ RTL/LTR layout perfect in both languages
8. ✅ Mobile responsive (no overflow, proper stacking)
9. ✅ Character counters accurate for each platform
10. ✅ Validation prevents invalid submissions
11. ✅ Performance: Modal opens in <500ms
12. ✅ Browser tests pass (AR/EN, desktop/mobile)

---

## 🚧 Known Limitations & Future Enhancements

**Current Limitations:**
- No Canva integration (requires API setup)
- No Google Drive/Dropbox integration (requires OAuth)
- Stock photo "Discover" needs third-party API (Unsplash/Pexels)
- Dynamic image/video requires template system

**Future Enhancements (Phase 7+):**
- Video editing (trim, crop, filters)
- Bulk scheduling interface
- Analytics preview (predicted reach/engagement)
- Content calendar grid view
- Team collaboration (approval workflows)
- Draft sharing & commenting
- Post templates library
- Hashtag suggestions based on content
- Image auto-tagging with AI
- Competitor post analysis

---

**END OF ENHANCEMENT PLAN**
