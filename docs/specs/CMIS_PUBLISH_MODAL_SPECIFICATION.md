# CMIS Publish Modal - Complete Specification

**Version:** 2.0
**Date:** November 2025
**Status:** Specification Document
**Benchmark:** Vista Social + CMIS Innovations

---

## Table of Contents

1. [Gap Analysis](#1-gap-analysis)
2. [Phased Roadmap](#2-phased-roadmap)
3. [Detailed UX Specification](#3-detailed-ux-specification)
4. [Data Model & API Architecture](#4-data-model--api-architecture)
5. [Acceptance Criteria](#5-acceptance-criteria)

---

## 1. Gap Analysis

### 1.1 Layout & Navigation

| Feature | Current CMIS | Vista Social | Gap / Action Required |
|---------|--------------|--------------|----------------------|
| Column Layout | 2 columns (content + preview) | 3 columns (profiles + composer + customizer) | **Add 3-column layout** |
| Modal Size | max-w-4xl | Full-width responsive | Expand to max-w-7xl |
| Navigation | Single view | Tabs for different sections | Add tab navigation |
| Mobile Layout | Basic responsive | Stacked with collapsible panels | **Improve mobile UX** |

### 1.2 Profile Selection

| Feature | Current CMIS | Vista Social | Gap / Action Required |
|---------|--------------|--------------|----------------------|
| Social Icons | ✅ Present (fab icons) | ✅ Present | No change |
| Platform Avatars | ❌ Missing | ✅ Profile picture per account | **Add account avatars** |
| Profile Grouping | ❌ Missing | ✅ By client/brand | **Add profile groups** |
| Search Profiles | ❌ Missing | ✅ Search bar | **Add search** |
| Select All/Clear | ❌ Missing | ✅ Bulk actions | **Add bulk selection** |
| Warning Icons | ❌ Missing | ✅ Disconnected/error states | **Add status indicators** |
| Bottom Selected Bar | ❌ Missing | ✅ Shows selected profiles | **Add selection bar** |
| Filter by Network | ❌ Missing | ✅ Filter buttons | **Add network filters** |

### 1.3 Global Composer

| Feature | Current CMIS | Vista Social | Gap / Action Required |
|---------|--------------|--------------|----------------------|
| Text Area | ✅ Basic textarea | ✅ Rich textarea | Enhance with toolbar |
| Emoji Picker | ❌ Missing | ✅ Full picker | **Add emoji picker** |
| Saved Captions | ❌ Missing | ✅ Save/load captions | **Add saved captions** |
| Hashtag Manager | ❌ Missing | ✅ Saved + suggestions | **Add hashtag manager** |
| Saved Mentions | ❌ Missing | ✅ @handle lists | **Add saved mentions** |
| Custom Fields | ❌ Missing | ✅ Variables merge | **Add custom fields** |
| Link Mode | ❌ Missing | ✅ URL input | **Add link mode** |
| Character Counter | ✅ Per-platform | ✅ Per-platform | Keep, enhance visuals |
| AI Assistant | ✅ Basic (6 options) | ✅ Full tone/format/voice | **Expand AI options** |

### 1.4 Media Handling

| Feature | Current CMIS | Vista Social | Gap / Action Required |
|---------|--------------|--------------|----------------------|
| Upload from Computer | ✅ Drag & drop | ✅ Drag & drop | Keep |
| Upload from URL | ❌ Missing | ✅ URL input | **Add URL upload** |
| Asset Library | ❌ Missing | ✅ Built-in library | **Add asset library** |
| Google Drive | ❌ Missing | ✅ Integration | **Phase 2** |
| Dropbox | ❌ Missing | ✅ Integration | **Phase 2** |
| OneDrive | ❌ Missing | ✅ Integration | **Phase 2** |
| Canva | ❌ Missing | ✅ Integration | **Phase 2** |
| Stock Images | ❌ Missing | ✅ Discover feature | **Phase 2** |
| Dynamic Images | ❌ Missing | ✅ Templates | **Phase 3** |
| Video Processing | ❌ No indicator | ✅ Progress bar | **Add processing UI** |
| Per-Network Media | ❌ Missing | ✅ Customize per platform | **Phase 2** |

### 1.5 Per-Network Customization

| Feature | Current CMIS | Vista Social | Gap / Action Required |
|---------|--------------|--------------|----------------------|
| Independent Captions | ❌ Single global | ✅ Per-network | **Add per-network captions** |
| Independent Media | ❌ Single set | ✅ Per-network | **Phase 2** |
| **Instagram** |
| - Reel/Story/Post | ✅ Present | ✅ Present | Keep |
| - Collaborators | ✅ Present | ✅ Present | Keep |
| - Location | ✅ Autocomplete | ✅ Present | Keep |
| - First Comment | ✅ Present | ✅ Present | Keep |
| - Product Tags | ✅ Present | ✅ Present | Keep |
| **Facebook** |
| - Reel/Video/Story | ⚠️ Partial | ✅ Full | Enhance |
| - Country Targeting | ❌ Missing | ✅ Present | **Phase 3** |
| - Demographics | ❌ Missing | ✅ Age/gender/relationship | **Phase 3** |
| - Boosting | ❌ Missing | ✅ Promote option | **Phase 3** |
| **TikTok** |
| - Viewer Setting | ✅ Present | ✅ Present | Keep |
| - Interaction Controls | ✅ Present | ✅ Present | Keep |
| - Disclosure | ✅ Present | ✅ Present | Keep |
| **LinkedIn** |
| - Visibility | ✅ Present | ✅ Present | Keep |
| - Article Options | ✅ Present | ✅ Present | Keep |
| **Twitter** |
| - Reply Restriction | ✅ Present | ✅ Present | Keep |
| - Thread Support | ✅ Present | ✅ Present | Keep |
| **Google Business** |
| - Post Types | ❌ Missing | ✅ Standard/Event/Offer/Alert | **Phase 3** |
| - CTAs | ❌ Missing | ✅ Book/Order/Shop/etc | **Phase 3** |

### 1.6 Scheduling & Queues

| Feature | Current CMIS | Vista Social | Gap / Action Required |
|---------|--------------|--------------|----------------------|
| Publish Now | ✅ Present | ✅ Present | Keep |
| Schedule | ✅ Date/time picker | ✅ Present | Keep |
| Queue | ✅ Basic | ✅ Advanced per-profile | Enhance |
| Best Times | ✅ Suggestions | ✅ AI-powered | Enhance with ML |
| Save Draft | ✅ Present | ✅ Present | Keep |
| Repeat Schedule | ❌ Missing | ✅ Recurring posts | **Phase 2** |

### 1.7 Advanced Features

| Feature | Current CMIS | Vista Social | Gap / Action Required |
|---------|--------------|--------------|----------------------|
| Labels | ❌ Missing | ✅ Tag posts | **Add labels** |
| Advocacy | ❌ Missing | ✅ Employee advocacy | **Phase 3** |
| Brand Voice | ❌ Missing | ✅ Per profile group | **Add to AI** |
| Approval Workflow | ❌ Missing | ✅ Team approvals | **Phase 3** |
| UTM Builder | ❌ Missing | ⚠️ External | **Phase 2** |

### 1.8 AI Assistant

| Feature | Current CMIS | Vista Social | Gap / Action Required |
|---------|--------------|--------------|----------------------|
| Input Area | ⚠️ Uses post content | ✅ Dedicated input | **Add dedicated input** |
| Tone Options | ⚠️ 2 (formal/casual) | ✅ 10+ tones | **Expand tones** |
| Format Options | ⚠️ 2 (shorter/longer) | ✅ Multiple formats | **Expand formats** |
| Brand Voice | ❌ Missing | ✅ Custom voices | **Add brand voices** |
| Multiple Results | ❌ Single result | ✅ Multiple suggestions | **Show alternatives** |
| Arabic Quality | ✅ Present | ⚠️ Generic | **Enhance Arabic** |

### 1.9 RTL & Localization

| Feature | Current CMIS | Vista Social | Gap / Action Required |
|---------|--------------|--------------|----------------------|
| RTL Layout | ✅ Present | ⚠️ LTR only | CMIS advantage |
| Arabic Labels | ✅ Present | ❌ English only | CMIS advantage |
| Mixed Content | ⚠️ Basic | ⚠️ Basic | **Improve handling** |
| RTL Preview | ⚠️ Partial | ❌ N/A | **Enhance previews** |

---

## 2. Phased Roadmap

### Phase 1: Core UI Restructure (4-6 weeks)

**Goals:**
- Transform to 3-column layout
- Add profile management enhancements
- Implement toolbar and basic tools
- Improve mobile responsiveness

**Features:**

#### 1.1 Three-Column Layout
- Left: Profile selector (collapsible on mobile)
- Center: Global composer with toolbar
- Right: Per-network customizer + preview

#### 1.2 Profile Selector Enhancements
- Add profile avatars from connected accounts
- Profile grouping by brand/client
- Search within profiles
- Select all / Clear selection
- Network filter buttons (All, Facebook, Instagram, etc.)
- Status icons (connected, warning, disconnected)
- Bottom bar showing selected profiles as avatars

#### 1.3 Composer Toolbar
- Emoji picker (Arabic-friendly with recent/favorites)
- Basic saved captions (create, save, load)
- Basic hashtag insertion
- Link URL mode
- Character limit visualization

#### 1.4 Per-Network Customization Panel
- Tab navigation with platform icons
- Independent caption per network (optional override)
- Settings specific to each platform
- Live preview that updates in real-time

#### 1.5 Mobile Optimization
- Stacked layout with collapsible sections
- Touch-friendly controls
- Swipe navigation between sections

**Dependencies:**
- Profile avatar storage in database
- Client/brand grouping data model

**Risks:**
- Breaking changes to existing post creation flow
- Performance on lower-end devices

---

### Phase 2: Power User Features (6-8 weeks)

**Goals:**
- Complete content management tools
- External media integrations
- Advanced AI assistant
- Queue management

**Features:**

#### 2.1 Saved Content Management
- Saved captions with categories
- Saved hashtag sets
- Saved mention lists
- Custom fields/variables

#### 2.2 Media Sources
- Upload from URL
- Asset library integration
- Google Drive picker
- Dropbox picker
- OneDrive picker
- Canva design import
- Stock image search (Unsplash/Pexels)

#### 2.3 Enhanced AI Assistant
- Dedicated popup modal
- 10+ tone options
- Format options (shorten, expand, rephrase, translate)
- Brand voice configuration
- Multiple result suggestions
- Copy/insert buttons
- Regenerate option

#### 2.4 Queue Management
- Per-profile queue settings
- Queue overview calendar
- Drag-to-reorder queue
- Queue slot management

#### 2.5 Labels System
- Create/manage labels
- Assign labels to posts
- Filter by labels in post list

#### 2.6 UTM Builder
- Automatic UTM parameter addition
- Campaign/source/medium templates
- Per-network UTM settings

**Dependencies:**
- OAuth for cloud storage providers
- AI API integration for enhanced features
- Asset library database schema

**Risks:**
- Third-party API rate limits
- Storage costs for media library

---

### Phase 3: Advanced Targeting & Network Features (8-10 weeks)

**Goals:**
- Platform-specific advanced features
- Targeting and boosting
- Employee advocacy
- Approval workflows

**Features:**

#### 3.1 Facebook Advanced
- Country targeting
- Relationship status targeting
- Gender targeting
- Age range targeting
- Boosting/promotion hooks
- Scheduled boost settings

#### 3.2 Google Business Profile
- Post types (Standard, Event, Offer, Alert)
- Call-to-action buttons
- Event date/time
- Offer code/terms
- Product catalog links

#### 3.3 Instagram Advanced
- Branded content tags
- Partnership labels
- Music selection (where API allows)
- Reminder stickers setup

#### 3.4 Employee Advocacy
- Create advocacy posts from published content
- Delay options (immediate, 1hr, 24hr)
- Advocacy draft creation
- Employee notification system
- Advocacy analytics dashboard

#### 3.5 Approval Workflows
- Submit for approval
- Approval queue
- Reviewer comments
- Approve/reject actions
- Notification system

#### 3.6 Analytics Hooks
- Post performance prediction
- Optimal time AI suggestions
- Competitor comparison (basic)

**Dependencies:**
- Facebook/Meta advanced API permissions
- Google Business Profile API
- Advocacy module development
- Notification system

**Risks:**
- API permission requirements
- Complex approval logic

---

## 3. Detailed UX Specification

### 3.1 Modal Layout

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ ◀ Header: إنشاء منشور جديد                                            ✕ │
├────────────┬─────────────────────────────────┬───────────────────────────────┤
│            │                                 │                               │
│  PROFILES  │      GLOBAL COMPOSER            │   PER-NETWORK CUSTOMIZER      │
│            │                                 │                               │
│  ┌──────┐  │  ┌─────────────────────────┐   │  ┌─────────────────────────┐  │
│  │Search│  │  │ Toolbar: 😀 # @ {} 🔗 🤖│   │  │ [FB] [IG] [TW] [LI]     │  │
│  └──────┘  │  └─────────────────────────┘   │  └─────────────────────────┘  │
│            │                                 │                               │
│  [All][FB] │  ┌─────────────────────────┐   │  Caption Override:            │
│  [IG][TW]  │  │                         │   │  ┌─────────────────────────┐  │
│            │  │   محتوى المنشور...      │   │  │ تخصيص لهذه المنصة...    │  │
│  ☑ Page 1  │  │                         │   │  └─────────────────────────┘  │
│    (FB)    │  │                         │   │                               │
│  ☑ Account │  └─────────────────────────┘   │  Platform-specific options:   │
│    (IG)    │                                 │  - Location                   │
│  ☐ Profile │  Media:                        │  - First comment              │
│    (LI)    │  ┌─────────────────────────┐   │  - Tags                       │
│            │  │ [📷] [🔗] [📁] [☁️]     │   │                               │
│  ──────    │  └─────────────────────────┘   │  ───────────────────────────  │
│  Selected: │                                 │                               │
│  [●][●][●] │  Schedule:                     │  LIVE PREVIEW                 │
│            │  ○ نشر الآن  ○ جدولة          │  ┌─────────────────────────┐  │
│            │  ○ طابور    ○ مسودة          │  │    📱 Instagram          │  │
│            │                                 │  │    ┌─────────────┐      │  │
│            │                                 │  │    │   Preview   │      │  │
│            │                                 │  │    └─────────────┘      │  │
│            │                                 │  └─────────────────────────┘  │
│            │                                 │                               │
├────────────┴─────────────────────────────────┴───────────────────────────────┤
│ Footer: [إلغاء]                          [حفظ مسودة] [جدولة] [نشر الآن]     │
└──────────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Profile Selector (Left Column)

#### Structure:
```html
<div class="profile-selector w-72 border-l border-gray-200 bg-gray-50 flex flex-col h-full">
    <!-- Search -->
    <div class="p-4 border-b">
        <input type="text" placeholder="بحث في الحسابات..." />
    </div>

    <!-- Network Filters -->
    <div class="p-3 border-b flex gap-2 flex-wrap">
        <button class="filter-btn active">الكل</button>
        <button class="filter-btn"><i class="fab fa-facebook"></i></button>
        <button class="filter-btn"><i class="fab fa-instagram"></i></button>
        <!-- ... -->
    </div>

    <!-- Profile Groups -->
    <div class="flex-1 overflow-y-auto">
        <!-- Group Header -->
        <div class="profile-group">
            <button class="group-header">
                <span>العميل: شركة ABC</span>
                <span class="count">(5)</span>
                <i class="fas fa-chevron-down"></i>
            </button>

            <!-- Profiles in Group -->
            <div class="profiles">
                <label class="profile-item">
                    <input type="checkbox" />
                    <img src="avatar.jpg" class="avatar" />
                    <i class="fab fa-instagram platform-icon"></i>
                    <div class="info">
                        <span class="name">@company_ig</span>
                        <span class="status connected">متصل</span>
                    </div>
                    <i class="fas fa-exclamation-triangle warning" title="يحتاج إعادة ربط"></i>
                </label>
                <!-- ... more profiles -->
            </div>
        </div>
    </div>

    <!-- Selected Profiles Bar -->
    <div class="selected-bar p-3 border-t bg-white">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium">المحدد: 3</span>
            <button class="text-xs text-red-600">إلغاء الكل</button>
        </div>
        <div class="flex gap-1 flex-wrap">
            <img src="avatar1.jpg" class="w-8 h-8 rounded-full" />
            <img src="avatar2.jpg" class="w-8 h-8 rounded-full" />
            <img src="avatar3.jpg" class="w-8 h-8 rounded-full" />
        </div>
    </div>
</div>
```

#### States:
- **No profiles connected:** Show empty state with CTA to connect
- **Profiles loading:** Show skeleton loaders
- **Profile disconnected:** Show warning icon, dimmed style
- **Search no results:** Show "لا توجد نتائج"

### 3.3 Global Composer (Center Column)

#### Toolbar Icons & Behaviors:

| Icon | Label | Behavior |
|------|-------|----------|
| 😀 | إيموجي | Opens emoji picker popup |
| 📝 | تعليقات محفوظة | Opens saved captions panel |
| # | هاشتاقات | Opens hashtag manager |
| @ | إشارات | Opens saved mentions |
| {} | حقول مخصصة | Opens custom fields picker |
| 🔗 | رابط | Toggles link input field |
| 🤖 | مساعد AI | Opens AI assistant modal |
| 📷 | وسائط | Opens media source menu |

#### Emoji Picker:
```
┌─────────────────────────────────────┐
│ 🔍 بحث...                          │
├─────────────────────────────────────┤
│ الأخيرة: 😀 🎉 ❤️ 🔥 ✨ 👍       │
├─────────────────────────────────────┤
│ [😀 وجوه] [👋 إيماءات] [❤️ قلوب]  │
│ [🐱 حيوانات] [🍕 طعام] [⚽ رياضة]  │
├─────────────────────────────────────┤
│ 😀😁😂🤣😃😄😅😆😉😊              │
│ 😋😎😍😘🥰😗😙😚☺️🙂              │
│ ...                                 │
└─────────────────────────────────────┘
```

#### Saved Captions Panel:
```
┌─────────────────────────────────────┐
│ التعليقات المحفوظة            [+]   │
├─────────────────────────────────────┤
│ 🔍 بحث...                          │
├─────────────────────────────────────┤
│ 📁 الفئات:                         │
│ [الكل] [ترويجية] [تعليمية] [موسمية]│
├─────────────────────────────────────┤
│ ┌───────────────────────────────┐   │
│ │ 🛍️ عرض خاص!                   │   │
│ │ "احصل على خصم 20% على..."    │   │
│ │ [إدراج] [تعديل] [حذف]         │   │
│ └───────────────────────────────┘   │
│ ┌───────────────────────────────┐   │
│ │ 📚 نصيحة اليوم                │   │
│ │ "هل تعلم أن..."              │   │
│ │ [إدراج] [تعديل] [حذف]         │   │
│ └───────────────────────────────┘   │
└─────────────────────────────────────┘
```

#### Hashtag Manager:
```
┌─────────────────────────────────────┐
│ مدير الهاشتاقات                     │
├─────────────────────────────────────┤
│ المحفوظة:                          │
│ ┌───────────────────────────────┐   │
│ │ مجموعة: عقارات دبي            │   │
│ │ #دبي #عقارات #استثمار ...     │   │
│ │ [إدراج الكل]                  │   │
│ └───────────────────────────────┘   │
├─────────────────────────────────────┤
│ اقتراحات ذكية:          [🔄 تحديث] │
│ بناءً على المحتوى:                 │
│ #تسويق_رقمي (85%)                  │
│ #ريادة_أعمال (72%)                 │
│ #نجاح (65%)                        │
│ [إضافة المحدد]                     │
└─────────────────────────────────────┘
```

#### Character Counter Enhanced:
```html
<div class="char-counters flex gap-4 p-2 bg-gray-50 rounded-lg">
    <!-- Twitter -->
    <div class="counter" :class="{ 'text-red-500': overLimit.twitter }">
        <i class="fab fa-twitter text-sky-500"></i>
        <div class="progress-ring" :style="{ '--progress': twitterProgress }">
            <span class="count">245</span>
        </div>
    </div>
    <!-- Instagram -->
    <div class="counter">
        <i class="fab fa-instagram text-pink-500"></i>
        <span class="count">1,850 / 2,200</span>
    </div>
    <!-- Facebook -->
    <div class="counter">
        <i class="fab fa-facebook text-blue-600"></i>
        <span class="count">1,850</span>
    </div>
</div>
```

### 3.4 Per-Network Customization Panel (Right Column)

#### Tab Navigation:
```html
<div class="network-tabs flex border-b">
    <template x-for="platform in selectedPlatforms">
        <button
            @click="activeNetwork = platform.type"
            :class="{ 'active': activeNetwork === platform.type }"
            class="tab-btn">
            <img :src="platform.avatar" class="w-6 h-6 rounded-full" />
            <i :class="platformIcon(platform.type)"></i>
        </button>
    </template>
</div>
```

#### Platform-Specific Panels:

##### Instagram Panel:
```
┌─────────────────────────────────────┐
│ 📷 Instagram - @account_name        │
├─────────────────────────────────────┤
│ تخصيص المحتوى (اختياري):           │
│ ┌───────────────────────────────┐   │
│ │ اكتب محتوى مخصص لـ Instagram │   │
│ │ أو اتركه فارغاً لاستخدام     │   │
│ │ المحتوى العام...              │   │
│ └───────────────────────────────┘   │
│                                     │
│ نوع المنشور:                       │
│ [○ منشور] [○ ريل] [○ قصة] [○ كاروسيل]│
│                                     │
│ 📍 الموقع: [بحث عن موقع...]        │
│ 💬 أول تعليق: [هاشتاقات...]        │
│ 👥 المتعاونون: [@user1] [+]        │
│ 🏷️ إشارات: [إضافة إشارات]          │
│                                     │
│ ☑️ مشاركة للقصص                     │
│ ☑️ عرض في الصفحة الرئيسية          │
├─────────────────────────────────────┤
│ معاينة:                            │
│ ┌───────────────────────────────┐   │
│ │         📱                     │   │
│ │    [Instagram Post]           │   │
│ │                               │   │
│ └───────────────────────────────┘   │
└─────────────────────────────────────┘
```

##### Facebook Panel:
```
┌─────────────────────────────────────┐
│ 📘 Facebook - صفحة الشركة          │
├─────────────────────────────────────┤
│ تخصيص المحتوى (اختياري):           │
│ ┌───────────────────────────────┐   │
│ │ [                           ] │   │
│ └───────────────────────────────┘   │
│                                     │
│ نوع المنشور:                       │
│ [○ منشور] [○ ريل] [○ فيديو] [○ قصة]│
│                                     │
│ 📍 الموقع: [اختياري]               │
│                                     │
│ ── استهداف الجمهور ──              │
│ 🌍 الدول: [اختر الدول...]          │
│ 👫 الجنس: [○ الكل] [○ ذكور] [○ إناث]│
│ 📅 العمر: [18] إلى [65+]           │
│                                     │
│ ── الترويج ──                      │
│ ☐ ترويج هذا المنشور                │
│   الميزانية: [___] USD             │
│   المدة: [3 أيام ▼]                │
└─────────────────────────────────────┘
```

##### Google Business Panel:
```
┌─────────────────────────────────────┐
│ 🏢 Google Business - اسم النشاط    │
├─────────────────────────────────────┤
│ نوع المنشور:                       │
│ [○ قياسي] [○ حدث] [○ عرض] [○ تنبيه]│
│                                     │
│ ── خيارات الحدث ──                 │
│ 📅 تاريخ البدء: [____]             │
│ 📅 تاريخ الانتهاء: [____]          │
│                                     │
│ ── خيارات العرض ──                 │
│ 🏷️ كود الخصم: [____]               │
│ 📋 الشروط: [____]                  │
│ 🔗 رابط الاسترداد: [____]          │
│                                     │
│ زر الإجراء (CTA):                  │
│ [▼ احجز الآن]                      │
│   • احجز الآن                      │
│   • اطلب الآن                      │
│   • تسوق الآن                      │
│   • اعرف المزيد                    │
│   • سجل الآن                       │
│   • اتصل                          │
│                                     │
│ ⚠️ ملاحظة: الفيديو غير مدعوم      │
└─────────────────────────────────────┘
```

### 3.5 AI Assistant Modal

```
┌─────────────────────────────────────────────────────────────┐
│ 🤖 مساعد الكتابة الذكي                                 ✕ │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ المحتوى الأصلي:                                            │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ اكتب المحتوى الذي تريد تحسينه أو اتركه فارغاً        │ │
│ │ لإنشاء محتوى جديد من الصفر...                         │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                             │
│ النبرة:                                                    │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ [بدون] [رسمي] [ودي] [مرح] [ترويجي] [ملهم]            │ │
│ │ [جذاب] [حازم] [صادم] [احترافي] [بسيط]                │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                             │
│ التنسيق:                                                   │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ [○ كما هو] [○ اختصار] [○ توسيع] [○ إعادة صياغة]      │ │
│ │ [○ نقاط] [○ سؤال] [○ قصة]                            │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                             │
│ صوت العلامة التجارية:                                      │
│ [▼ اختر صوت العلامة أو أنشئ جديد...]                      │
│                                                             │
│ المنصة المستهدفة:                                          │
│ [☑️ Instagram] [☑️ Twitter] [☐ Facebook] [☐ LinkedIn]      │
│                                                             │
│                               [🔄 إنشاء]                    │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│ النتائج:                                                   │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 💡 الاقتراح 1:                                         │ │
│ │ "🚀 هل أنت مستعد لتحويل أفكارك إلى واقع؟              │ │
│ │ اكتشف كيف يمكننا مساعدتك في تحقيق أهدافك..."         │ │
│ │                     [نسخ] [إدراج] [تعديل]              │ │
│ └─────────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 💡 الاقتراح 2:                                         │ │
│ │ "أهلاً بك في رحلة النجاح! 🌟                          │ │
│ │ نحن هنا لنساعدك خطوة بخطوة..."                        │ │
│ │                     [نسخ] [إدراج] [تعديل]              │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                             │
│                          [🔄 إنشاء المزيد]                  │
└─────────────────────────────────────────────────────────────┘
```

#### Tone Options (Arabic):

| Code | Arabic Label | Description |
|------|--------------|-------------|
| `none` | بدون تحديد | No specific tone |
| `formal` | رسمي | Professional, business-like |
| `informal` | غير رسمي | Casual, relaxed |
| `friendly` | ودي | Warm and approachable |
| `funny` | مرح | Humorous, light-hearted |
| `promotional` | ترويجي | Sales-focused, persuasive |
| `engaging` | جذاب | Attention-grabbing |
| `assertive` | حازم | Confident, direct |
| `catchy` | لافت | Memorable, punchy |
| `inspirational` | ملهم | Motivating, uplifting |
| `shocking` | صادم | Surprising, provocative |
| `professional` | احترافي | Expert, authoritative |
| `simple` | بسيط | Easy to understand |

#### Format Options (Arabic):

| Code | Arabic Label | Description |
|------|--------------|-------------|
| `none` | كما هو | Keep original format |
| `shorten` | اختصار | Make it shorter |
| `expand` | توسيع | Make it longer |
| `rephrase` | إعادة صياغة | Say it differently |
| `bullets` | نقاط | Convert to bullet points |
| `question` | سؤال | Frame as a question |
| `story` | قصة | Tell as a story |
| `translate_en` | ترجمة للإنجليزية | Translate to English |

### 3.6 Footer & Scheduling

#### Footer Layout:
```html
<div class="modal-footer flex items-center justify-between p-4 border-t bg-gray-50">
    <!-- Left: Cancel -->
    <button class="btn-ghost">
        <i class="fas fa-times ml-2"></i>
        إلغاء
    </button>

    <!-- Center: Schedule Info (when scheduled) -->
    <div x-show="publishType === 'scheduled'" class="text-sm text-gray-600">
        <i class="fas fa-calendar-alt ml-1"></i>
        سيتم النشر في: <strong>25 نوفمبر 2025 - 10:00 ص</strong>
    </div>

    <!-- Right: Actions -->
    <div class="flex gap-3">
        <!-- Save Draft (always visible) -->
        <button class="btn-secondary" @click="saveDraft()">
            <i class="fas fa-save ml-2"></i>
            حفظ مسودة
        </button>

        <!-- Dynamic Primary Action -->
        <button class="btn-primary" @click="submitPost()">
            <template x-if="publishType === 'now'">
                <span><i class="fas fa-paper-plane ml-2"></i>نشر الآن</span>
            </template>
            <template x-if="publishType === 'scheduled'">
                <span><i class="fas fa-clock ml-2"></i>جدولة النشر</span>
            </template>
            <template x-if="publishType === 'queue'">
                <span><i class="fas fa-list-ol ml-2"></i>إضافة للطابور</span>
            </template>
        </button>
    </div>
</div>
```

#### Conflict Handling:
- If any network has validation errors, show error badge on tab
- Disable submit until all errors resolved
- Show summary of errors in footer

---

## 4. Data Model & API Architecture

### 4.1 Database Schema

#### Profile Groups Table
```sql
CREATE TABLE cmis.profile_groups (
    group_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.organizations(org_id),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    color VARCHAR(7), -- hex color for UI
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
);

-- RLS Policy
ALTER TABLE cmis.profile_groups ENABLE ROW LEVEL SECURITY;
CREATE POLICY profile_groups_org_policy ON cmis.profile_groups
    USING (org_id = current_setting('app.current_org_id')::uuid);
```

#### Saved Captions Table
```sql
CREATE TABLE cmis.saved_captions (
    caption_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.organizations(org_id),
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(100),
    tags JSONB DEFAULT '[]',
    use_count INTEGER DEFAULT 0,
    created_by UUID REFERENCES cmis.users(user_id),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
);

CREATE INDEX idx_saved_captions_org ON cmis.saved_captions(org_id);
CREATE INDEX idx_saved_captions_category ON cmis.saved_captions(category);
```

#### Saved Hashtag Sets Table
```sql
CREATE TABLE cmis.saved_hashtag_sets (
    set_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.organizations(org_id),
    name VARCHAR(255) NOT NULL,
    hashtags JSONB NOT NULL, -- ["#tag1", "#tag2", ...]
    platform VARCHAR(50), -- NULL = all platforms
    use_count INTEGER DEFAULT 0,
    created_by UUID REFERENCES cmis.users(user_id),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
);
```

#### Saved Mentions Table
```sql
CREATE TABLE cmis.saved_mentions (
    mention_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.organizations(org_id),
    name VARCHAR(255) NOT NULL,
    platform VARCHAR(50) NOT NULL, -- instagram, twitter, etc.
    handles JSONB NOT NULL, -- ["@user1", "@user2", ...]
    created_by UUID REFERENCES cmis.users(user_id),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
);
```

#### Custom Fields Table
```sql
CREATE TABLE cmis.custom_fields (
    field_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.organizations(org_id),
    field_key VARCHAR(100) NOT NULL, -- e.g., "company_name", "promo_code"
    field_name VARCHAR(255) NOT NULL, -- Arabic display name
    field_value TEXT,
    field_type VARCHAR(50) DEFAULT 'text', -- text, number, date, url
    profile_id UUID REFERENCES cmis_social.social_integrations(integration_id), -- NULL = org-wide
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),

    UNIQUE(org_id, field_key, profile_id)
);
```

#### Brand Voices Table
```sql
CREATE TABLE cmis.brand_voices (
    voice_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.organizations(org_id),
    profile_group_id UUID REFERENCES cmis.profile_groups(group_id),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    tone VARCHAR(50), -- formal, casual, etc.
    keywords JSONB DEFAULT '[]', -- words to use
    avoid_words JSONB DEFAULT '[]', -- words to avoid
    example_posts JSONB DEFAULT '[]', -- sample posts for AI training
    language VARCHAR(10) DEFAULT 'ar',
    created_by UUID REFERENCES cmis.users(user_id),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
);
```

#### Post Labels Table
```sql
CREATE TABLE cmis.post_labels (
    label_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.organizations(org_id),
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) NOT NULL, -- hex color
    created_at TIMESTAMP DEFAULT NOW(),

    UNIQUE(org_id, name)
);

CREATE TABLE cmis.post_label_assignments (
    post_id UUID REFERENCES cmis_social.social_posts(post_id),
    label_id UUID REFERENCES cmis.post_labels(label_id),
    PRIMARY KEY (post_id, label_id)
);
```

#### Per-Network Content Table
```sql
CREATE TABLE cmis_social.post_network_content (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    post_id UUID NOT NULL REFERENCES cmis_social.social_posts(post_id),
    platform VARCHAR(50) NOT NULL,
    integration_id UUID REFERENCES cmis_social.social_integrations(integration_id),

    -- Override content
    custom_content TEXT, -- NULL = use global content
    custom_media JSONB, -- NULL = use global media

    -- Platform-specific settings stored as JSONB
    platform_settings JSONB DEFAULT '{}',

    -- Status per network
    status VARCHAR(50) DEFAULT 'pending',
    published_at TIMESTAMP,
    platform_post_id VARCHAR(255),
    error_message TEXT,

    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),

    UNIQUE(post_id, integration_id)
);
```

#### Asset Library Tables
```sql
CREATE TABLE cmis.media_library (
    asset_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    org_id UUID NOT NULL REFERENCES cmis.organizations(org_id),

    -- File info
    file_name VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL, -- image, video, gif
    mime_type VARCHAR(100),
    file_size BIGINT,

    -- Storage
    storage_provider VARCHAR(50) DEFAULT 'local', -- local, s3, cloudinary
    storage_path TEXT NOT NULL,
    cdn_url TEXT,
    thumbnail_url TEXT,

    -- Metadata
    width INTEGER,
    height INTEGER,
    duration DECIMAL, -- for videos, in seconds
    alt_text TEXT,
    tags JSONB DEFAULT '[]',

    -- Source tracking
    source VARCHAR(50), -- upload, canva, drive, dropbox, stock
    source_id VARCHAR(255), -- external ID if from integration

    created_by UUID REFERENCES cmis.users(user_id),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP
);

CREATE INDEX idx_media_library_org ON cmis.media_library(org_id);
CREATE INDEX idx_media_library_type ON cmis.media_library(file_type);
CREATE INDEX idx_media_library_tags ON cmis.media_library USING GIN(tags);
```

### 4.2 API Endpoints

#### Profile Management

```
GET /api/orgs/{org}/profiles
    Query params: ?group_id=&platform=&search=&status=
    Response: { profiles: [...], groups: [...] }

POST /api/orgs/{org}/profile-groups
    Body: { name, description, color }
    Response: { group }

PUT /api/orgs/{org}/profile-groups/{group_id}
    Body: { name, description, color, profile_ids }

DELETE /api/orgs/{org}/profile-groups/{group_id}
```

#### Saved Content

```
# Captions
GET /api/orgs/{org}/saved-captions
    Query: ?category=&search=
POST /api/orgs/{org}/saved-captions
    Body: { title, content, category, tags }
PUT /api/orgs/{org}/saved-captions/{id}
DELETE /api/orgs/{org}/saved-captions/{id}

# Hashtags
GET /api/orgs/{org}/saved-hashtags
POST /api/orgs/{org}/saved-hashtags
PUT /api/orgs/{org}/saved-hashtags/{id}
DELETE /api/orgs/{org}/saved-hashtags/{id}
POST /api/orgs/{org}/hashtag-suggestions
    Body: { content, platform }
    Response: { suggestions: [{ tag, relevance }] }

# Mentions
GET /api/orgs/{org}/saved-mentions
POST /api/orgs/{org}/saved-mentions
PUT /api/orgs/{org}/saved-mentions/{id}
DELETE /api/orgs/{org}/saved-mentions/{id}

# Custom Fields
GET /api/orgs/{org}/custom-fields
POST /api/orgs/{org}/custom-fields
PUT /api/orgs/{org}/custom-fields/{id}
DELETE /api/orgs/{org}/custom-fields/{id}
```

#### Post Creation (Enhanced)

```
POST /api/orgs/{org}/posts
{
    // Global content
    "content": "المحتوى العام للمنشور...",
    "media": [
        { "asset_id": "uuid" },
        { "url": "https://..." }
    ],

    // Scheduling
    "publish_type": "scheduled", // now, scheduled, queue, draft
    "scheduled_at": "2025-11-25T10:00:00Z",

    // Labels
    "label_ids": ["uuid1", "uuid2"],

    // Per-network content
    "networks": [
        {
            "integration_id": "uuid",
            "platform": "instagram",
            "custom_content": "محتوى مخصص لإنستغرام...", // optional
            "custom_media": [...], // optional
            "settings": {
                "post_type": "reel",
                "location_id": "123456",
                "first_comment": "#hashtags here",
                "collaborators": ["@user1"],
                "share_to_feed": true
            }
        },
        {
            "integration_id": "uuid",
            "platform": "facebook",
            "settings": {
                "post_type": "post",
                "targeting": {
                    "countries": ["AE", "SA"],
                    "age_min": 25,
                    "age_max": 45,
                    "genders": ["male", "female"]
                }
            }
        },
        {
            "integration_id": "uuid",
            "platform": "google_business",
            "settings": {
                "post_type": "offer",
                "cta_type": "shop_now",
                "cta_url": "https://...",
                "offer_code": "SAVE20",
                "terms": "Valid until..."
            }
        }
    ]
}

Response:
{
    "success": true,
    "post_id": "uuid",
    "network_results": [
        { "integration_id": "uuid", "status": "scheduled", "scheduled_at": "..." },
        { "integration_id": "uuid", "status": "published", "platform_post_id": "..." },
        { "integration_id": "uuid", "status": "error", "error": "..." }
    ]
}
```

#### AI Assistant

```
POST /api/orgs/{org}/ai/generate-caption
{
    "original_content": "المحتوى الأصلي...",
    "tone": "engaging",
    "format": "expand",
    "brand_voice_id": "uuid", // optional
    "platforms": ["instagram", "twitter"],
    "language": "ar",
    "count": 3 // number of suggestions
}

Response:
{
    "suggestions": [
        {
            "content": "🚀 المحتوى المُنشأ...",
            "platform_variants": {
                "instagram": "...",
                "twitter": "..."
            },
            "hashtags": ["#suggested", "#tags"],
            "emojis_used": ["🚀", "✨"]
        },
        // ... more suggestions
    ],
    "tokens_used": 150
}
```

#### Media Library

```
GET /api/orgs/{org}/media-library
    Query: ?type=image&tags[]=product&search=&page=1

POST /api/orgs/{org}/media-library/upload
    Multipart form: file, tags[], alt_text

POST /api/orgs/{org}/media-library/upload-url
    Body: { url, tags[], alt_text }

POST /api/orgs/{org}/media-library/import-canva
    Body: { design_id, export_format }

POST /api/orgs/{org}/media-library/import-drive
    Body: { file_id }

DELETE /api/orgs/{org}/media-library/{asset_id}
```

### 4.3 Integration Mappings

#### Instagram API Mapping

| CMIS Field | Instagram API Field |
|------------|-------------------|
| `content` | `caption` |
| `post_type: 'reel'` | `media_type: 'REELS'` |
| `post_type: 'story'` | `media_type: 'STORIES'` |
| `post_type: 'carousel'` | `media_type: 'CAROUSEL'` |
| `location_id` | `location_id` |
| `collaborators` | `collaborators` (Business only) |
| `share_to_feed` | `share_to_feed` |
| `cover_url` | `cover_url` (Reels) |
| `thumb_offset` | `thumb_offset` (Reels) |

#### Facebook API Mapping

| CMIS Field | Facebook API Field |
|------------|-------------------|
| `content` | `message` |
| `media` | `attached_media` or `source` |
| `targeting.countries` | `targeting.geo_locations.countries` |
| `targeting.age_min` | `targeting.age_min` |
| `targeting.genders` | `targeting.genders` |
| `post_type: 'reel'` | Published as Reel via Graph API |

#### Google Business API Mapping

| CMIS Field | Google Business API Field |
|------------|--------------------------|
| `content` | `summary` |
| `post_type: 'standard'` | `topicType: 'STANDARD'` |
| `post_type: 'event'` | `topicType: 'EVENT'` + `event` object |
| `post_type: 'offer'` | `topicType: 'OFFER'` + `offer` object |
| `cta_type` | `callToAction.actionType` |
| `cta_url` | `callToAction.url` |

---

## 5. Acceptance Criteria

### 5.1 Phase 1: Core UI

#### AC-1.1: Three-Column Layout
```gherkin
Given I am creating a new post
When the modal opens
Then I see three columns:
  - Left: Profile selector (width ~280px)
  - Center: Global composer (flexible)
  - Right: Per-network customizer (width ~350px)
And on mobile (<768px), columns stack vertically with collapsible sections
```

#### AC-1.2: Profile Selection with Avatars
```gherkin
Given I have connected Instagram account "@mycompany"
When I view the profile selector
Then I see:
  - The account avatar image
  - Instagram icon
  - Account name "@mycompany"
  - Connection status indicator
And I can check/uncheck to select for posting
```

#### AC-1.3: Profile Search
```gherkin
Given I have 20 connected profiles
When I type "شركة" in the search box
Then only profiles containing "شركة" in their name are shown
And clearing the search shows all profiles again
```

#### AC-1.4: Profile Groups
```gherkin
Given I have created a group "عملاء الإمارات"
When I view the profile selector
Then I see profiles grouped under "عملاء الإمارات"
And I can collapse/expand the group
And I can select all profiles in the group at once
```

#### AC-1.5: Network Filter Buttons
```gherkin
Given I have profiles from Instagram, Facebook, and LinkedIn
When I click the Instagram filter button
Then only Instagram profiles are shown
And clicking "الكل" shows all profiles again
```

#### AC-1.6: Selected Profiles Bar
```gherkin
Given I have selected 3 profiles
Then at the bottom of the profile selector I see:
  - "المحدد: 3"
  - Avatar thumbnails of selected profiles
  - "إلغاء الكل" button
And clicking "إلغاء الكل" deselects all profiles
```

#### AC-1.7: Emoji Picker
```gherkin
Given I am writing post content
When I click the emoji button in the toolbar
Then an emoji picker popup appears with:
  - Search box
  - Recent emojis section
  - Category tabs
  - Emoji grid
And clicking an emoji inserts it at cursor position
And the picker supports Arabic search terms
```

#### AC-1.8: Per-Network Caption Override
```gherkin
Given I have selected Instagram and Twitter
When I go to the Instagram customization tab
And I enter custom content in the override field
Then Instagram will use the custom content
And Twitter will use the global content
And the character counter updates accordingly
```

#### AC-1.9: Platform-Specific Preview
```gherkin
Given I have entered post content
And uploaded an image
When I view the Instagram preview tab
Then I see a mock Instagram post layout with:
  - Profile avatar and name
  - The image in square aspect ratio
  - Caption truncated with "... المزيد"
  - Engagement icons (heart, comment, share, bookmark)
```

### 5.2 Phase 2: Power Features

#### AC-2.1: Saved Captions
```gherkin
Given I have saved a caption "عرض الجمعة البيضاء"
When I click the saved captions toolbar button
Then I see a panel listing my saved captions
And I can search and filter by category
And clicking "إدراج" inserts the caption into the composer
```

#### AC-2.2: Hashtag Suggestions
```gherkin
Given I have written content about "التسويق الرقمي"
When I click "اقتراح هاشتاقات"
Then the system analyzes my content
And suggests relevant hashtags like:
  - #تسويق_رقمي (85%)
  - #تسويق_الكتروني (78%)
And I can add suggested hashtags to my content
```

#### AC-2.3: AI Assistant Full Modal
```gherkin
Given I want to improve my post content
When I click the AI assistant button
Then a modal opens with:
  - Original content input
  - Tone selector (12+ options)
  - Format selector (7+ options)
  - Brand voice selector
  - Platform checkboxes
  - Generate button
And clicking generate shows 3 suggestions
And I can insert or copy any suggestion
```

#### AC-2.4: Media Library
```gherkin
Given I have uploaded images to the library
When I click the media button and select "من المكتبة"
Then I see a grid of my uploaded assets
And I can filter by type (image/video)
And I can search by tags or filename
And selecting an asset attaches it to the post
```

#### AC-2.5: Labels
```gherkin
Given I have created labels "ترويجي" and "موسمي"
When creating a new post
Then I can assign one or more labels to the post
And labels appear in the posts list for filtering
```

### 5.3 Phase 3: Advanced

#### AC-3.1: Facebook Targeting
```gherkin
Given I am posting to Facebook
When I open the Facebook customization panel
Then I can set targeting:
  - Countries (multi-select)
  - Age range (18-65+)
  - Gender (all/male/female)
And the targeting is applied when posting via Facebook API
```

#### AC-3.2: Google Business Post Types
```gherkin
Given I am posting to Google Business
When I select post type "عرض"
Then additional fields appear:
  - كود الخصم
  - الشروط والأحكام
  - رابط الاسترداد
And I must select a CTA button
And the post is created as an Offer type in Google
```

#### AC-3.3: Brand Voice
```gherkin
Given I have configured brand voice "العلامة الرسمية"
When I use the AI assistant with this voice selected
Then generated content follows the brand guidelines:
  - Uses formal Arabic
  - Includes configured keywords
  - Avoids configured blocked words
```

---

## Appendix A: Arabic Microcopy Reference

### Buttons & Actions
| English | Arabic |
|---------|--------|
| Publish Now | نشر الآن |
| Schedule | جدولة |
| Add to Queue | إضافة للطابور |
| Save Draft | حفظ مسودة |
| Cancel | إلغاء |
| Insert | إدراج |
| Copy | نسخ |
| Edit | تعديل |
| Delete | حذف |
| Search | بحث |
| Select All | تحديد الكل |
| Clear Selection | إلغاء التحديد |
| Generate | إنشاء |
| Regenerate | إعادة الإنشاء |

### Labels & Headers
| English | Arabic |
|---------|--------|
| Select Profiles | اختر الحسابات |
| Connected Accounts | الحسابات المتصلة |
| Post Content | محتوى المنشور |
| Media | الوسائط |
| Scheduling | الجدولة |
| Preview | معاينة |
| Customize | تخصيص |
| Settings | الإعدادات |
| AI Assistant | مساعد الذكاء الاصطناعي |
| Saved Captions | التعليقات المحفوظة |
| Hashtag Manager | مدير الهاشتاقات |
| Suggestions | اقتراحات |

### Status Messages
| English | Arabic |
|---------|--------|
| Publishing... | جاري النشر... |
| Scheduled successfully | تمت الجدولة بنجاح |
| Post published | تم نشر المنشور |
| Draft saved | تم حفظ المسودة |
| Error occurred | حدث خطأ |
| Connection lost | انقطع الاتصال |
| Reconnect required | يتطلب إعادة الربط |

### Tooltips & Help
| English | Arabic |
|---------|--------|
| Character limit | عدد الأحرف المسموح |
| Required field | حقل مطلوب |
| Optional | اختياري |
| Maximum file size | الحد الأقصى لحجم الملف |
| Supported formats | الصيغ المدعومة |

---

## Appendix B: Technical Assumptions

1. **Stack:** Laravel 11, PostgreSQL 16, Alpine.js 3.x, Tailwind CSS 3.x
2. **Storage:** Local storage for development, S3-compatible for production
3. **AI Provider:** OpenAI GPT-4 or equivalent with Arabic support
4. **OAuth:** Existing platform OAuth flows maintained
5. **RLS:** All new tables follow existing RLS pattern
6. **Multi-tenancy:** `org_id` isolation on all data

---

*Document prepared for CMIS Development Team - November 2025*
