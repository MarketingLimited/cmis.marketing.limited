# CMIS Publish Modal & Profile Groups - Complete Specification

**Version:** 3.0
**Date:** November 2025
**Status:** Comprehensive Specification Document
**Benchmark:** Vista Social + Enhanced CMIS Innovations

---

## Executive Summary

This document specifies a complete redesign of CMIS's social publishing capabilities to match and surpass Vista Social's publishing experience. The specification covers:

1. **Publishing Modal Redesign** - 3-column layout with advanced features
2. **Profile Groups System** - Client/brand organization with brand voice and safety policies
3. **Advanced Publishing Features** - Per-network customization, AI assistance, targeting, boosting
4. **Complete Data Architecture** - Database schema, API design, integration patterns
5. **Implementation Roadmap** - Phased delivery with concrete artifacts

**Key Innovations:**
- RTL-first design (Arabic native, works perfectly in LTR)
- Multi-tenant profile groups with brand voice and safety policies
- Advanced AI assistant with brand voice integration
- Per-network content customization and previews
- Integrated boost rules and ad account management
- Comprehensive approval workflows for team collaboration

---

## Table of Contents

1. [Gap Analysis](#1-gap-analysis)
2. [Profile Groups System](#2-profile-groups-system)
3. [Publishing Modal UX Specification](#3-publishing-modal-ux-specification)
4. [Data Model & Database Schema](#4-data-model--database-schema)
5. [API Architecture](#5-api-architecture)
6. [Publishing Flow & Logic](#6-publishing-flow--logic)
7. [Implementation Plan](#7-implementation-plan)
8. [Concrete Artifacts](#8-concrete-artifacts)
9. [Acceptance Criteria](#9-acceptance-criteria)
10. [RTL & Localization](#10-rtl--localization)
11. [Appendices](#11-appendices)

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

## 2. Profile Groups System

### 2.1 Overview & Current State

**What CMIS Currently Has:**
- Social integrations table storing connected accounts
- Basic organization-level multi-tenancy
- Individual profile OAuth connections
- No grouping or client organization structure

**What's Missing (Based on Vista Social):**
- Profile Groups to organize accounts by client/brand
- Brand Voice configuration per group
- Brand Safety & Compliance policies
- Team member assignments to groups
- Approval workflows
- Ad Account connections and boost rules
- Group-level settings and defaults

### 2.2 Profile Groups Core Concept

A **Profile Group** represents a client, brand, or business entity within CMIS. It serves as the central organizational unit for:

1. **Social Profiles** - All connected accounts for this client
2. **Brand Identity** - Brand voice, tone, and messaging guidelines
3. **Compliance** - Brand safety rules and content policies
4. **Team Structure** - Assigned team members and roles
5. **Advertising** - Connected ad accounts and boost automation
6. **Workflows** - Approval processes and publishing rules

**Key Benefits:**
- **Multi-brand agencies** can manage multiple clients independently
- **Brand consistency** through voice and safety policies
- **Team collaboration** with role-based access
- **Automated boosting** with pre-configured rules
- **Approval workflows** for quality control

### 2.3 Profile Groups Data Model

#### Entity: ProfileGroup

```typescript
interface ProfileGroup {
  group_id: UUID;
  org_id: UUID;  // Multi-tenant isolation

  // Basic Info
  name: string;  // e.g., "3bs.gents.saloon", "Acme Corporation"
  description?: string;
  client_location?: {
    country: string;
    city?: string;
  };

  // Visual Identity
  logo_url?: string;
  color: string;  // Hex color for UI

  // Settings
  default_link_shortener?: 'bitly' | 'custom' | 'none';
  timezone: string;
  language: string;  // Default content language

  // Relationships
  brand_voice_id?: UUID;
  brand_safety_policy_id?: UUID;

  // Metadata
  created_by: UUID;
  created_at: timestamp;
  updated_at: timestamp;
  deleted_at?: timestamp;  // Soft delete
}
```

#### Entity: BrandVoice

```typescript
interface BrandVoice {
  voice_id: UUID;
  org_id: UUID;
  profile_group_id?: UUID;  // NULL = org-wide default

  // Identity
  name: string;  // e.g., "Official Brand Voice", "Casual Instagram Voice"
  description: string;  // Free-text brand demeanor

  // AI Generation Parameters
  tone: 'formal' | 'informal' | 'friendly' | 'professional' | 'casual' | 'custom';
  personality_traits: string[];  // ["positive", "informative", "helpful"]
  inspired_by: string[];  // ["Nike", "Apple", "Local Brand X"]
  target_audience: string;  // "Young professionals 25-35"

  // Content Guidelines
  keywords_to_use: string[];  // Preferred terminology
  keywords_to_avoid: string[];  // Banned words/phrases
  emojis_preference: 'none' | 'minimal' | 'moderate' | 'frequent';
  hashtag_strategy: 'none' | 'minimal' | 'moderate' | 'extensive';

  // Examples (for AI training)
  example_posts: Array<{
    platform: string;
    content: string;
    rating: 'perfect' | 'good' | 'bad';  // For training
  }>;

  // Language Settings
  primary_language: string;  // 'ar', 'en', etc.
  secondary_languages: string[];
  dialect_preference?: string;  // For Arabic: 'gulf', 'levantine', 'egyptian', 'formal'

  // AI Model Config (advanced)
  ai_system_prompt?: string;  // Custom system prompt override
  temperature?: number;  // Creativity level (0-1)

  created_by: UUID;
  created_at: timestamp;
  updated_at: timestamp;
}
```

#### Entity: BrandSafetyPolicy

```typescript
interface BrandSafetyPolicy {
  policy_id: UUID;
  org_id: UUID;
  profile_group_id?: UUID;  // NULL = org-wide default

  // Identity
  name: string;
  description: string;
  is_active: boolean;

  // Automated Rules
  prohibit_derogatory_language: boolean;
  prohibit_profanity: boolean;
  prohibit_offensive_content: boolean;

  // Custom Rules
  custom_banned_words: string[];  // Specific words to block
  custom_banned_phrases: string[];
  custom_requirements: string;  // Free-text additional rules

  // Content Requirements
  require_disclosure: boolean;  // For sponsored content
  disclosure_text?: string;  // e.g., "#ad #sponsored"

  require_fact_checking: boolean;
  require_source_citation: boolean;

  // Industry-Specific
  industry_regulations?: string[];  // e.g., ["HIPAA", "Financial Services"]
  compliance_regions?: string[];  // e.g., ["EU-GDPR", "US-FTC"]

  // Enforcement
  enforcement_level: 'warning' | 'block' | 'review';
  auto_reject_violations: boolean;

  // Templates
  use_default_template: boolean;
  template_name?: string;

  created_by: UUID;
  created_at: timestamp;
  updated_at: timestamp;
}
```

#### Entity: ProfileGroupMember

```typescript
interface ProfileGroupMember {
  id: UUID;
  profile_group_id: UUID;
  user_id: UUID;

  // Role & Permissions
  role: 'owner' | 'admin' | 'editor' | 'contributor' | 'viewer';
  permissions: {
    can_publish: boolean;
    can_schedule: boolean;
    can_edit_drafts: boolean;
    can_delete: boolean;
    can_manage_team: boolean;
    can_manage_brand_voice: boolean;
    can_manage_ad_accounts: boolean;
    requires_approval: boolean;
  };

  // Assignment
  assigned_by: UUID;
  joined_at: timestamp;
  last_active_at: timestamp;
}
```

#### Entity: ApprovalWorkflow

```typescript
interface ApprovalWorkflow {
  workflow_id: UUID;
  org_id: UUID;
  profile_group_id: UUID;

  // Configuration
  name: string;
  description: string;
  is_active: boolean;

  // Triggers
  apply_to_platforms: string[];  // [] = all platforms
  apply_to_users: UUID[];  // [] = all users (except admins)
  apply_to_post_types: string[];  // ['promotional', 'announcement', etc.]

  // Approval Chain
  approval_steps: Array<{
    step_number: number;
    approver_user_ids: UUID[];  // Any one can approve
    require_all: boolean;  // If true, all must approve
    timeout_hours?: number;  // Auto-approve if no action
  }>;

  // Notifications
  notify_on_submission: boolean;
  notify_on_approval: boolean;
  notify_on_rejection: boolean;

  created_by: UUID;
  created_at: timestamp;
  updated_at: timestamp;
}
```

#### Entity: AdAccount

```typescript
interface AdAccount {
  ad_account_id: UUID;
  org_id: UUID;
  profile_group_id?: UUID;  // NULL = org-wide

  // Platform Connection
  platform: 'meta' | 'google' | 'tiktok' | 'linkedin' | 'twitter' | 'snapchat';
  platform_account_id: string;  // External ID
  account_name: string;
  currency: string;  // USD, AED, SAR, etc.

  // Status
  status: 'active' | 'paused' | 'disconnected' | 'error';
  connection_status: 'connected' | 'needs_reauth' | 'expired';

  // Balance & Limits (cached)
  balance?: number;
  daily_spend_limit?: number;

  // OAuth Tokens (encrypted)
  access_token_encrypted: string;
  refresh_token_encrypted?: string;
  token_expires_at?: timestamp;

  // Metadata
  connected_by: UUID;
  connected_at: timestamp;
  last_synced_at: timestamp;
}
```

#### Entity: BoostRule

```typescript
interface BoostRule {
  boost_rule_id: UUID;
  org_id: UUID;
  profile_group_id: UUID;

  // Identity
  name: string;  // e.g., "Auto-boost high-performers"
  description?: string;
  is_active: boolean;

  // Triggers
  trigger_type: 'manual' | 'auto_after_publish' | 'auto_performance';
  delay_after_publish?: {
    value: number;
    unit: 'minutes' | 'hours' | 'days';
  };

  // Performance Trigger (if auto_performance)
  performance_threshold?: {
    metric: 'likes' | 'comments' | 'shares' | 'engagement_rate';
    operator: 'greater_than' | 'less_than';
    value: number;
    time_window_hours: number;
  };

  // Target Profiles
  apply_to_social_profiles: UUID[];  // Specific profiles

  // Ad Account
  ad_account_id: UUID;

  // Boost Configuration
  boost_config: {
    objective: 'reach' | 'engagement' | 'traffic' | 'conversions';
    budget_amount: number;
    budget_type: 'daily' | 'lifetime';
    duration_days: number;

    // Audience Template
    audience?: {
      locations: string[];  // Country codes
      age_min?: number;
      age_max?: number;
      genders?: ('male' | 'female' | 'all')[];
      interests?: string[];
      languages?: string[];
    };
  };

  created_by: UUID;
  created_at: timestamp;
  updated_at: timestamp;
}
```

### 2.4 Profile Groups UX Specification

#### 2.4.1 Profile Groups List Page

**Route:** `/orgs/{org}/settings/profile-groups`

**Layout:**
```
┌────────────────────────────────────────────────────────────────┐
│ ⚙️ الإعدادات > مجموعات الملفات الشخصية               [+ مجموعة جديدة] │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ 🔍 بحث في المجموعات...                    [⚡ نشط] [📊 الكل]  │
│                                                                │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ 📘 3bs.gents.saloon                              [⚙️] [🗑️] │   │
│ │ 📍 دبي، الإمارات                                          │   │
│ │                                                          │   │
│ │ 👥 5 حسابات  •  📊 45.2K متابع  •  🎯 4 منصات          │   │
│ │                                                          │   │
│ │ Profiles: [IG] [FB] [TW] [LI]                           │   │
│ │ Team: Ahmed, Sara +3                                    │   │
│ │ Brand Voice: ✅ Configured  •  Safety: ✅ Active         │   │
│ └──────────────────────────────────────────────────────────┘   │
│                                                                │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ 🏢 Acme Corporation                              [⚙️] [🗑️] │   │
│ │ 📍 الرياض، السعودية                                       │   │
│ │                                                          │   │
│ │ 👥 12 حسابات  •  📊 128K متابع  •  🎯 6 منصات          │   │
│ │                                                          │   │
│ │ Profiles: [IG] [FB] [TW] [LI] [TT] [SC]                │   │
│ │ Team: Mohammed, Fatima +8                               │   │
│ │ Brand Voice: ⚠️ Not set  •  Safety: ⚠️ Not set          │   │
│ └──────────────────────────────────────────────────────────┘   │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

**Card Actions:**
- **⚙️ Settings** → Opens group detail page
- **🗑️ Delete** → Confirmation modal (can't delete if has profiles)

#### 2.4.2 Single Profile Group Page

**Route:** `/orgs/{org}/settings/profile-groups/{group_id}`

**Sections:**

##### Section 1: Overview
```
┌────────────────────────────────────────────────────────────────┐
│ ← رجوع إلى المجموعات                                         │
├────────────────────────────────────────────────────────────────┤
│ [Logo] 3bs.gents.saloon                              [تعديل]  │
│        دبي، الإمارات                                          │
│                                                                │
│ 📝 الوصف: صالون حلاقة رجالية فاخر في دبي                      │
│ 🌐 اللغة الافتراضية: العربية                                 │
│ ⏰ المنطقة الزمنية: Asia/Dubai (GMT+4)                        │
│ 🔗 أداة اختصار الروابط: Bitly                                │
└────────────────────────────────────────────────────────────────┘
```

##### Section 2: Brand Voice
```
┌────────────────────────────────────────────────────────────────┐
│ 🎙️ صوت العلامة التجارية                            [تعديل السياسة] │
├────────────────────────────────────────────────────────────────┤
│ ✅ تم تكوين صوت العلامة التجارية                              │
│                                                                │
│ النبرة: ودية ومهنية                                          │
│ الجمهور المستهدف: رجال 25-45 في دبي                          │
│ الكلمات المفضلة: فخامة، احترافية، عناية                       │
│ تجنب: رخيص، عادي                                             │
│ الإيموجي: معتدل                                               │
│                                                                │
│ 📋 آخر تحديث: منذ 5 أيام بواسطة Ahmed                        │
└────────────────────────────────────────────────────────────────┘

OR (if not configured):

┌────────────────────────────────────────────────────────────────┐
│ 🎙️ صوت العلامة التجارية                            [إنشاء صوت] │
├────────────────────────────────────────────────────────────────┤
│ ⚠️ لم يتم تعيين صوت للعلامة التجارية لهذه المجموعة            │
│                                                                │
│ سيستخدم مساعد AI صوت العلامة لإنشاء محتوى متسق ومتوافق        │
│ مع هوية علامتك التجارية.                                     │
│                                                                │
│               [إنشاء صوت العلامة التجارية]                    │
└────────────────────────────────────────────────────────────────┘
```

##### Section 3: Brand Safety & Compliance
```
┌────────────────────────────────────────────────────────────────┐
│ 🛡️ الأمان والامتثال                                 [تعديل السياسة] │
├────────────────────────────────────────────────────────────────┤
│ ✅ السياسة نشطة                                               │
│                                                                │
│ القواعد المفعّلة:                                             │
│ ✅ منع اللغة المسيئة                                          │
│ ✅ منع الألفاظ البذيئة                                        │
│ ✅ منع المحتوى المسيء                                         │
│ ✅ يتطلب إفصاح للمحتوى المدفوع                                │
│                                                                │
│ الكلمات المحظورة: 15 كلمة                                     │
│ مستوى الإنفاذ: حظر النشر                                      │
│                                                                │
│ 📋 آخر تحديث: منذ 10 أيام بواسطة Sara                        │
└────────────────────────────────────────────────────────────────┘
```

##### Section 4: Team Members
```
┌────────────────────────────────────────────────────────────────┐
│ 👥 الفريق                                              [إضافة عضو] │
├────────────────────────────────────────────────────────────────┤
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ 👤 Ahmed Al-Mansouri                          [المالك]   │   │
│ │    ahmed@company.com                                     │   │
│ │    انضم: 15 نوفمبر 2024  •  آخر نشاط: منذ ساعتين       │   │
│ │    الصلاحيات: كاملة                        [تعديل] [حذف] │   │
│ └──────────────────────────────────────────────────────────┘   │
│                                                                │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ 👤 Sara Hassan                                  [محرر]   │   │
│ │    sara@company.com                                      │   │
│ │    انضم: 18 نوفمبر 2024  •  آخر نشاط: منذ 5 ساعات      │   │
│ │    الصلاحيات: نشر، جدولة، تعديل           [تعديل] [حذف] │   │
│ └──────────────────────────────────────────────────────────┘   │
│                                                                │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ 👤 Mohammed Ali                              [مشارك]    │   │
│ │    mohammed@company.com                                  │   │
│ │    انضم: 20 نوفمبر 2024  •  آخر نشاط: منذ يومين         │   │
│ │    الصلاحيات: إنشاء مسودات (يتطلب موافقة) [تعديل] [حذف] │   │
│ └──────────────────────────────────────────────────────────┘   │
└────────────────────────────────────────────────────────────────┘
```

##### Section 5: Social Profiles
```
┌────────────────────────────────────────────────────────────────┐
│ 📱 الحسابات والمنصات                                   [ربط حساب] │
├────────────────────────────────────────────────────────────────┤
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ [Avatar] @3bs.gents.saloon                   [IG Logo]   │   │
│ │          Instagram Business                              │   │
│ │          👥 25.4K متابع  •  ✅ متصل                       │   │
│ │          المسؤول: Sara Hassan                            │   │
│ │          آخر نشر: منذ 3 ساعات                [⚙️] [🔌]  │   │
│ └──────────────────────────────────────────────────────────┘   │
│                                                                │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ [Avatar] 3BS Gents Saloon                    [FB Logo]   │   │
│ │          Facebook Page                                   │   │
│ │          👥 19.8K متابع  •  ⚠️ يحتاج إعادة ربط           │   │
│ │          المسؤول: Ahmed Al-Mansouri                      │   │
│ │          آخر نشر: منذ يومين                  [⚙️] [🔌]  │   │
│ └──────────────────────────────────────────────────────────┘   │
│                                                                │
│ ... more profiles ...                                         │
└────────────────────────────────────────────────────────────────┘
```

##### Section 6: Ad Accounts & Boost Rules
```
┌────────────────────────────────────────────────────────────────┐
│ 🎯 حسابات الإعلانات                                  [ربط حساب] │
├────────────────────────────────────────────────────────────────┤
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ Meta Ad Account #123456789                   [Meta Logo] │   │
│ │ الرصيد: $1,245.00  •  العملة: USD  •  ✅ نشط           │   │
│ │ الحد اليومي: $500.00                                    │   │
│ │ آخر تحديث: منذ ساعة                         [⚙️] [🗑️]  │   │
│ └──────────────────────────────────────────────────────────┘   │
└────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────┐
│ 🚀 قواعد التعزيز التلقائي                            [إضافة قاعدة] │
├────────────────────────────────────────────────────────────────┤
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ ✅ تعزيز تلقائي بعد النشر                               │   │
│ │    التأخير: 2 ساعات بعد النشر                          │   │
│ │    الحساب الإعلاني: Meta Ad Account #123456789          │   │
│ │    الميزانية: $50 يومياً لمدة 3 أيام                   │   │
│ │    الهدف: زيادة التفاعل                                │   │
│ │    الحسابات: @3bs.gents.saloon (Instagram)              │   │
│ │                                             [تعديل] [حذف] │   │
│ └──────────────────────────────────────────────────────────┘   │
│                                                                │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ ⏸️ تعزيز المنشورات عالية الأداء                         │   │
│ │    الشرط: > 500 إعجاب في 6 ساعات                       │   │
│ │    الحساب الإعلاني: Meta Ad Account #123456789          │   │
│ │    الميزانية: $100 يومياً لمدة 5 أيام                  │   │
│ │    الهدف: وصول أوسع                                    │   │
│ │    الحسابات: جميع حسابات Instagram                      │   │
│ │                                             [تعديل] [حذف] │   │
│ └──────────────────────────────────────────────────────────┘   │
└────────────────────────────────────────────────────────────────┘
```

##### Section 7: Approval Workflows
```
┌────────────────────────────────────────────────────────────────┐
│ ✓ سير عمل الموافقات                                   [إضافة سير] │
├────────────────────────────────────────────────────────────────┤
│ ⚠️ لم يتم إعداد سير عمل للموافقات                             │
│                                                                │
│ أضف سير عمل موافقة لضمان مراجعة المحتوى قبل النشر.           │
│                                                                │
│               [إنشاء سير عمل موافقة]                          │
└────────────────────────────────────────────────────────────────┘

OR (if configured):

┌────────────────────────────────────────────────────────────────┐
│ ✓ سير عمل الموافقات                                   [إضافة سير] │
├────────────────────────────────────────────────────────────────┤
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ ✅ موافقة المحتوى الترويجي                             │   │
│ │    يُطبق على: المشاركون، المحررون                      │   │
│ │    المنصات: جميع المنصات                                │   │
│ │    خطوات الموافقة:                                      │   │
│ │    1️⃣ Sara Hassan أو Ahmed (أي منهما)                  │   │
│ │    المهلة: 24 ساعة (موافقة تلقائية)                    │   │
│ │                                             [تعديل] [حذف] │   │
│ └──────────────────────────────────────────────────────────┘   │
└────────────────────────────────────────────────────────────────┘
```

#### 2.4.3 Brand Voice Modal

**Triggered by:** "تعديل السياسة" / "إنشاء صوت" button

**Layout (Two-Column):**
```
┌────────────────────────────────────────────────────────────────────┐
│ 🎙️ صوت العلامة التجارية                                      ✕  │
├───────────────────────────────┬────────────────────────────────────┤
│                               │                                    │
│ الشخصية والأسلوب              │  🤖 مُنشئ صوت العلامة الذكي        │
│                               │                                    │
│ اكتب وصفاً حراً للشخصية      │  اسم العلامة التجارية:            │
│ والأسلوب الفريد لعلامتك:     │  ┌──────────────────────────────┐ │
│ ┌───────────────────────────┐ │  │ 3BS Gents Saloon           │ │
│ │ صالون 3BS يقدم تجربة     │ │  └──────────────────────────────┘ │
│ │ حلاقة فاخرة للرجال في    │ │                                    │
│ │ دبي. نحن نركز على        │ │  كيف تصف علامتك التجارية؟        │
│ │ الاحترافية والعناية      │ │  ┌──────────────────────────────┐ │
│ │ الشخصية مع لمسة من        │ │  │ ☑ إيجابية                   │ │
│ │ الفخامة...                │ │  │ ☑ مفيدة                     │ │
│ │                           │ │  │ ☑ احترافية                  │ │
│ │                           │ │  │ ☐ مرحة                      │ │
│ │                           │ │  │ ☐ عصرية                     │ │
│ │                           │ │  └──────────────────────────────┘ │
│ │                           │ │                                    │
│ │                           │ │  ما هي العلامات التي تلهمك؟      │
│ └───────────────────────────┘ │  ┌──────────────────────────────┐ │
│                               │  │ Rolex, Emirates Airlines,  │ │
│ النبرة:                       │  │ Burj Al Arab               │ │
│ [○ رسمي] [●ودي] [○احترافي]   │  └──────────────────────────────┘ │
│                               │                                    │
│ الكلمات المفضلة:              │  من هم عملاؤك؟                   │
│ ┌───────────────────────────┐ │  ┌──────────────────────────────┐ │
│ │ فخامة، احترافية، عناية   │ │  │ رجال أعمال 25-45 في دبي   │ │
│ │ جودة، تميز                │ │  │ يبحثون عن خدمة راقية      │ │
│ └───────────────────────────┘ │  └──────────────────────────────┘ │
│                               │                                    │
│ تجنب الكلمات:                 │  نبرة الصوت:                      │
│ ┌───────────────────────────┐ │  [▼ ودي ومهني                   ] │
│ │ رخيص، عادي، بسيط          │ │     • رسمي                       │ │
│ └───────────────────────────┘ │     • ودي ومهني (محدد)          │ │
│                               │     • غير رسمي                   │ │
│ الإيموجي:                     │     • مرح                        │ │
│ [○ بدون] [●معتدل] [○كثير]    │                                    │
│                               │                        [🔄 إنشاء] │
│ الهاشتاقات:                   │                                    │
│ [○ بدون] [●معتدل] [○كثير]    │                                    │
│                               │                                    │
├───────────────────────────────┴────────────────────────────────────┤
│                    [مسح]              [حفظ صوت العلامة التجارية] │
└────────────────────────────────────────────────────────────────────┘
```

**Behavior:**
1. User can write free-text description OR use AI generator
2. AI generator asks structured questions
3. Clicking "إنشاء" generates brand voice text from answers
4. Generated text appears in left panel
5. User can edit before saving
6. Saving creates/updates BrandVoice record

#### 2.4.4 Brand Safety & Compliance Modal

**Layout (Two-Column):**
```
┌────────────────────────────────────────────────────────────────────┐
│ 🛡️ سياسة الأمان والامتثال                                     ✕  │
├───────────────────────────────┬────────────────────────────────────┤
│                               │                                    │
│ سياسة الأمان الخاصة بك        │  🤖 مُنشئ السياسة الذكي           │
│                               │                                    │
│ ☑ تفعيل سياسة الأمان          │  منع اللغة المسيئة؟               │
│                               │  [● نعم] [○ لا]                    │
│ أدخل سياسة الأمان والامتثال  │                                    │
│ الخاصة بعلامتك:              │  منع الألفاظ البذيئة؟             │
│ ┌───────────────────────────┐ │  [● نعم] [○ لا]                    │
│ │ - منع أي لغة مسيئة أو     │ │                                    │
│ │   تمييزية                 │ │  منع المحتوى المسيء؟              │
│ │ - منع الألفاظ البذيئة     │ │  [● نعم] [○ لا]                    │
│ │ - يجب الإفصاح عن المحتوى  │ │                                    │
│ │   المدفوع بعلامة #ad      │ │  متطلبات إضافية:                  │
│ │ - التأكد من صحة المعلومات │ │  ┌──────────────────────────────┐ │
│ │   قبل النشر               │ │  │ - يجب ذكر المصدر للأخبار   │ │
│ │ - احترام الخصوصية         │ │  │ - منع المحتوى السياسي       │ │
│ │                           │ │  │ - التركيز على الخدمات فقط   │ │
│ │                           │ │  └──────────────────────────────┘ │
│ │                           │ │                                    │
│ │                           │ │                    [🔄 إنشاء سياسة] │
│ └───────────────────────────┘ │                                    │
│                               │  أو استخدم نموذج جاهز:            │
│ الكلمات المحظورة:             │  [▼ اختر نموذج...              ]  │
│ ┌───────────────────────────┐ │     • عام (أعمال)                │ │
│ │ كلمة1، كلمة2، كلمة3       │ │     • الرعاية الصحية (HIPAA)    │ │
│ └───────────────────────────┘ │     • الخدمات المالية            │ │
│                               │     • التعليم                     │ │
│ مستوى الإنفاذ:                │                                    │
│ [○ تحذير] [●حظر] [○مراجعة]   │                                    │
│                               │                                    │
│ ☑ رفض تلقائي عند الانتهاك    │                                    │
│                               │                                    │
├───────────────────────────────┴────────────────────────────────────┤
│            [استخدام النموذج الافتراضي]         [حفظ السياسة]     │
└────────────────────────────────────────────────────────────────────┘
```

#### 2.4.5 Ad Account Connection Modal

**Triggered by:** "ربط حساب" in Ad Accounts section

```
┌────────────────────────────────────────────────────────────────┐
│ 🎯 ربط حساب إعلانات                                      ✕   │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ اختر المنصة الإعلانية:                                        │
│                                                                │
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐            │
│ │ Meta         │ │ Google Ads   │ │ TikTok Ads   │            │
│ │ [Meta Logo]  │ │ [Goog Logo]  │ │ [TikTok Logo]│            │
│ │              │ │              │ │              │            │
│ └──────────────┘ └──────────────┘ └──────────────┘            │
│                                                                │
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐            │
│ │ LinkedIn     │ │ Twitter Ads  │ │ Snapchat     │            │
│ │ [LI Logo]    │ │ [TW Logo]    │ │ [SC Logo]    │            │
│ │              │ │              │ │              │            │
│ └──────────────┘ └──────────────┘ └──────────────┘            │
│                                                                │
│ سيتم توجيهك إلى المنصة للتفويض وربط حسابك الإعلاني.           │
│                                                                │
├────────────────────────────────────────────────────────────────┤
│                           [إلغاء]                [متابعة]     │
└────────────────────────────────────────────────────────────────┘
```

After clicking platform, redirect to OAuth flow, then:

```
┌────────────────────────────────────────────────────────────────┐
│ 🎯 اختر حساب Meta الإعلاني                                ✕   │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ تم العثور على 3 حسابات إعلانية:                              │
│                                                                │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ ○ 3BS Marketing Account (#123456789)                     │   │
│ │   الرصيد: $1,245.50 USD  •  الحد اليومي: $500          │   │
│ └──────────────────────────────────────────────────────────┘   │
│                                                                │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ ○ Dubai Clients Account (#987654321)                     │   │
│ │   الرصيد: $3,892.00 USD  •  الحد اليومي: $1,000        │   │
│ └──────────────────────────────────────────────────────────┘   │
│                                                                │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ ○ Test Account (#111222333)                              │   │
│ │   الرصيد: $50.00 USD  •  الحد اليومي: $50              │   │
│ └──────────────────────────────────────────────────────────┘   │
│                                                                │
├────────────────────────────────────────────────────────────────┤
│                           [إلغاء]             [ربط المحدد]    │
└────────────────────────────────────────────────────────────────┘
```

#### 2.4.6 Create Boost Rule Modal

**Triggered by:** "إضافة قاعدة" in Boost Rules section

```
┌────────────────────────────────────────────────────────────────┐
│ 🚀 قاعدة تعزيز جديدة                                     ✕   │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ اسم القاعدة:                                                  │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ تعزيز تلقائي بعد النشر                                  │   │
│ └──────────────────────────────────────────────────────────┘   │
│                                                                │
│ متى يتم التعزيز؟                                              │
│ [● بعد النشر مباشرة] [○ عند تحقيق أداء معين]               │
│                                                                │
│ التأخير بعد النشر:                                            │
│ ┌─────┐  [▼ ساعات]                                            │
│ │  2  │      • دقائق                                          │
│ └─────┘      • ساعات (محدد)                                   │
│              • أيام                                           │
│                                                                │
│ الحساب الإعلاني:                                              │
│ [▼ Meta Ad Account #123456789                              ]  │
│                                                                │
│ الحسابات المطبقة:                                             │
│ ☑ @3bs.gents.saloon (Instagram)                               │
│ ☑ 3BS Gents Saloon (Facebook)                                 │
│ ☐ @3BSsaloon (Twitter)                                        │
│                                                                │
│ ─── إعدادات التعزيز ───                                       │
│                                                                │
│ الهدف:                                                        │
│ [● التفاعل] [○ الوصول] [○ الزيارات] [○ التحويلات]           │
│                                                                │
│ الميزانية:                                                    │
│ ┌─────┐  USD  [● يومية] [○ مدى الحياة]                       │
│ │ 50  │                                                        │
│ └─────┘                                                        │
│                                                                │
│ المدة:                                                        │
│ ┌─────┐  أيام                                                 │
│ │  3  │                                                        │
│ └─────┘                                                        │
│                                                                │
│ ─── الجمهور (اختياري) ───                                     │
│                                                                │
│ المواقع: [+ إضافة دول]                                        │
│ العمر: [ 25 ] إلى [ 45 ]                                      │
│ الجنس: [○ الكل] [○ ذكور] [○ إناث]                            │
│                                                                │
│ ☑ تفعيل هذه القاعدة                                          │
│                                                                │
├────────────────────────────────────────────────────────────────┤
│                           [إلغاء]               [إنشاء قاعدة] │
└────────────────────────────────────────────────────────────────┘
```

### 2.5 Integration with Publishing Modal

#### 2.5.1 Profile Selector with Groups

In the publishing modal's left column, profiles are now grouped:

```
┌────────────────────────────────────┐
│ اختر الحسابات                     │
├────────────────────────────────────┤
│ 🔍 بحث...                         │
├────────────────────────────────────┤
│ [All] [FB] [IG] [TW] [LI]         │
├────────────────────────────────────┤
│ ▼ 📘 3BS Gents Saloon (5)          │
│   ☑ [Avatar] @3bs.gents.saloon IG │
│   ☑ [Avatar] 3BS Saloon        FB │
│   ☐ [Avatar] @3BSsaloon        TW │
│   ☐ [Avatar] 3BS Gents Saloon  LI │
│   ☐ [Avatar] 3BS Saloon        GBP│
│                                    │
│ ▼ 🏢 Acme Corporation (12)         │
│   ☐ [Avatar] @acmecorp         IG │
│   ☐ [Avatar] Acme Corp         FB │
│   ... 10 more ...                 │
│                                    │
│ ▼ 📁 بدون مجموعة (2)              │
│   ☐ [Avatar] @personal         IG │
│   ☐ [Avatar] Personal          TW │
├────────────────────────────────────┤
│ المحدد: 2                         │
│ [●] [●]         [إلغاء الكل]      │
└────────────────────────────────────┘
```

**Features:**
- Collapsible groups
- Quick "select all in group" checkbox in header
- Groups sorted by: active profiles count, last used
- Ungrouped profiles in separate section at bottom
- Search filters across all groups

#### 2.5.2 Brand Voice in AI Assistant

When AI Assistant is opened, it automatically loads brand voice:

```
┌────────────────────────────────────────────────────────────────┐
│ 🤖 مساعد الكتابة الذكي                                    ✕  │
├────────────────────────────────────────────────────────────────┤
│ صوت العلامة التجارية:                                        │
│ ┌──────────────────────────────────────────────────────────┐   │
│ │ ✅ 3BS Gents Saloon Brand Voice                          │   │
│ │ (ودي ومهني، موجه للرجال 25-45، استخدام إيموجي معتدل)   │   │
│ └──────────────────────────────────────────────────────────┘   │
│                                                                │
│ ⚠️ تم اختيار حسابات من مجموعات مختلفة:                      │
│ • 3BS Gents Saloon Brand Voice                                │
│ • Acme Corporation Brand Voice                                │
│ [▼ اختر صوت العلامة لهذا المنشور                          ]  │
│                                                                │
│ ... rest of AI assistant ...                                  │
└────────────────────────────────────────────────────────────────┘
```

**Logic:**
1. If all selected profiles belong to same group → use that group's brand voice
2. If profiles from multiple groups → let user choose which voice to use
3. If no brand voice configured → show warning with link to configure
4. Brand voice parameters are passed to AI generation API

#### 2.5.3 Brand Safety Validation

Before publishing/scheduling, validate content against brand safety policy:

```
┌────────────────────────────────────────────────────────────────┐
│ ⚠️ تحذير: مشكلة في الأمان                                    │
├────────────────────────────────────────────────────────────────┤
│ المحتوى الخاص بك ينتهك سياسة الأمان لـ "3BS Gents Saloon":    │
│                                                                │
│ ❌ تم العثور على كلمة محظورة: "رخيص"                         │
│                                                                │
│ السياسة تمنع:                                                 │
│ • الكلمات التي تقلل من قيمة الخدمة                            │
│                                                                │
│ الاقتراح: استبدل "رخيص" بـ "بأسعار معقولة" أو "قيمة ممتازة"   │
│                                                                │
├────────────────────────────────────────────────────────────────┤
│         [تجاوز (المسؤولون فقط)]     [تعديل المحتوى]          │
└────────────────────────────────────────────────────────────────┘
```

**Validation Triggers:**
- On publish/schedule button click
- When AI generates content (pre-filter)
- When saving draft (warning only, doesn't block)

#### 2.5.4 Boost Rules Indication

In the publishing modal footer, show if boost rules will apply:

```
┌────────────────────────────────────────────────────────────────┐
│ Footer                                                         │
├────────────────────────────────────────────────────────────────┤
│ 🚀 سيتم تعزيز هذا المنشور تلقائياً بعد ساعتين                │
│    الحساب: @3bs.gents.saloon (Instagram)                      │
│    الميزانية: $50 يومياً لمدة 3 أيام                         │
│    [عرض التفاصيل] [تعطيل للمنشور الحالي]                     │
│                                                                │
│ [إلغاء]        [حفظ مسودة]  [جدولة]  [نشر الآن]              │
└────────────────────────────────────────────────────────────────┘
```

#### 2.5.5 Approval Workflow Indication

If approval is required:

```
┌────────────────────────────────────────────────────────────────┐
│ ✓ يتطلب موافقة                                                │
├────────────────────────────────────────────────────────────────┤
│ هذا المنشور يتطلب موافقة من:                                  │
│ • Sara Hassan أو Ahmed Al-Mansouri                            │
│                                                                │
│ سيتم إرسال إشعار عند الإرسال للموافقة.                       │
│ مهلة الموافقة: 24 ساعة (موافقة تلقائية بعدها)                │
│                                                                │
├────────────────────────────────────────────────────────────────┤
│ [إلغاء]                              [إرسال للموافقة]        │
└────────────────────────────────────────────────────────────────┘
```

### 2.6 Profile Groups Summary

**New Database Tables (7):**
1. `cmis.profile_groups`
2. `cmis.brand_voices`
3. `cmis.brand_safety_policies`
4. `cmis.profile_group_members`
5. `cmis.approval_workflows`
6. `cmis.ad_accounts`
7. `cmis.boost_rules`

**New API Endpoints (25+):**
- Profile Groups CRUD
- Brand Voice CRUD + AI generator
- Brand Safety Policy CRUD + AI generator + validation
- Team members management
- Approval workflows CRUD
- Ad Accounts OAuth + sync
- Boost Rules CRUD + execution

**New UI Pages (5):**
1. Profile Groups list page
2. Single Profile Group detail page
3. Brand Voice modal
4. Brand Safety modal
5. Boost Rule configuration modal

**Publishing Modal Enhancements:**
- Grouped profile selector
- Brand voice integration in AI
- Brand safety validation
- Boost rules indication
- Approval workflow handling

---

## 3. Publishing Modal UX Specification

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
