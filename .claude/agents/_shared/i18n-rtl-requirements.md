# Shared i18n & RTL/LTR Requirements for All Agents
**Version:** 1.0
**Last Updated:** 2025-11-27

This is a MANDATORY shared module for ALL Claude Code agents working on CMIS.

## 📋 Table of Contents

1. [Critical Requirements](#-critical-requirements)
2. [Mandatory Pre-Implementation Audit](#-mandatory-pre-implementation-audit)
3. [Laravel Localization Patterns](#-laravel-localization-patterns)
4. [RTL/LTR CSS Patterns](#-rtlltr-css-patterns)
5. [Quick Audit Commands](#-quick-audit-commands)
6. [Zero-Tolerance Rules](#-zero-tolerance-rules)

---

## 🚨 CRITICAL REQUIREMENTS

**CMIS is a bilingual platform:**
- **Primary Language:** Arabic (AR) - RTL (Right-to-Left)
- **Secondary Language:** English (EN) - LTR (Left-to-Right)
- **Default:** Arabic for all new users
- **User Preference:** Users can change language in settings

### Languages Supported
- **Arabic** (`ar`) - Default, RTL direction
- **English** (`en`) - LTR direction

---

## 🔍 MANDATORY PRE-IMPLEMENTATION AUDIT

**⚠️ BEFORE implementing ANY feature or working on ANY page, ALL agents MUST:**

### Step 1: Discover i18n Issues (REQUIRED FIRST STEP)

```bash
# 1. Find hardcoded text in views (English words)
grep -r -E "\b(Campaign|Dashboard|Save|Delete|Edit|Create|Update|Submit|Budget|Status|Name|Description|Total|Active|Inactive|Search|Filter|Export|Import|Settings|Profile|Logout|Login)\b" resources/views/ \
  | grep -v "{{ __(" \
  | grep -v "@lang" \
  | grep -v "<!--"

# 2. Find hardcoded directional CSS classes
grep -r -E "(^|[^m])(ml-|mr-|pl-|pr-|text-left|text-right)" resources/views/

# 3. Check for missing dir attribute in layouts
grep -r "<html" resources/views/layouts/ | grep -v "dir="

# 4. Verify language files exist
ls -la resources/lang/ar/ resources/lang/en/
```

### Step 2: Fix Issues BEFORE New Features (MANDATORY)

**If ANY hardcoded text or directional CSS is found:**

❌ **STOP** - Do NOT proceed with new feature implementation
✅ **FIX** - Resolve ALL i18n issues first
✅ **VERIFY** - Run audit commands again to confirm fixes
✅ **THEN** - Proceed with new feature implementation

### Step 3: Implement Features with i18n from the Start

**All new code MUST:**
- Use `__('translation.key')` for ALL text
- Use logical CSS properties (`ms-`, `me-`, `text-start`)
- Test in BOTH Arabic (RTL) and English (LTR)

---

## 🌍 Laravel Localization Patterns

### Translation File Structure

```
resources/lang/
├── ar/                     # Arabic (Default)
│   ├── common.php         # Common UI elements
│   ├── auth.php           # Authentication
│   ├── campaigns.php      # Campaign module
│   ├── social.php         # Social media module
│   ├── validation.php     # Validation messages
│   └── ...
└── en/                     # English
    ├── common.php
    ├── auth.php
    ├── campaigns.php
    ├── social.php
    ├── validation.php
    └── ...
```

### Translation Key Naming Convention

```
{domain}.{context}_{type}

Examples:
- common.save_button
- campaigns.dashboard_title
- campaigns.create_campaign_button
- campaigns.budget_required_message
- social.post_published_message
```

### Using Translations in Blade

```blade
{{-- Simple translation --}}
<h1>{{ __('campaigns.dashboard_title') }}</h1>
<button>{{ __('common.save_button') }}</button>

{{-- Translation with parameters --}}
<p>{{ __('campaigns.welcome_message', ['name' => $user->name]) }}</p>

{{-- Translation with pluralization --}}
<p>{{ trans_choice('campaigns.items_count', $count) }}</p>

{{-- NEVER do this --}}
<h1>Campaign Dashboard</h1>  ❌ WRONG
<button>Save</button>         ❌ WRONG
```

### Using Translations in Controllers

```php
use App\Http\Controllers\Concerns\ApiResponse;

class CampaignController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $campaign = Campaign::create($request->validated());

        // ✅ CORRECT - Translated message
        return $this->created(
            $campaign,
            __('campaigns.campaign_created')
        );
    }

    public function destroy($id)
    {
        Campaign::findOrFail($id)->delete();

        // ✅ CORRECT - Translated message
        return $this->deleted(__('campaigns.campaign_deleted'));
    }

    // ❌ WRONG - Hardcoded message
    public function update($id)
    {
        return $this->success($data, 'Campaign updated successfully');  // ❌
    }
}
```

### Creating Translation Files

```php
// resources/lang/ar/campaigns.php
<?php

return [
    // Page titles
    'dashboard_title' => 'لوحة تحكم الحملات',
    'create_campaign' => 'إنشاء حملة جديدة',

    // Buttons
    'save_button' => 'حفظ',
    'cancel_button' => 'إلغاء',
    'delete_button' => 'حذف',

    // Messages
    'campaign_created' => 'تم إنشاء الحملة بنجاح',
    'campaign_updated' => 'تم تحديث الحملة بنجاح',
    'campaign_deleted' => 'تم حذف الحملة بنجاح',

    // Form labels
    'campaign_name' => 'اسم الحملة',
    'campaign_budget' => 'ميزانية الحملة',
    'campaign_status' => 'حالة الحملة',
];
```

```php
// resources/lang/en/campaigns.php
<?php

return [
    // Page titles
    'dashboard_title' => 'Campaign Dashboard',
    'create_campaign' => 'Create New Campaign',

    // Buttons
    'save_button' => 'Save',
    'cancel_button' => 'Cancel',
    'delete_button' => 'Delete',

    // Messages
    'campaign_created' => 'Campaign created successfully',
    'campaign_updated' => 'Campaign updated successfully',
    'campaign_deleted' => 'Campaign deleted successfully',

    // Form labels
    'campaign_name' => 'Campaign Name',
    'campaign_budget' => 'Campaign Budget',
    'campaign_status' => 'Campaign Status',
];
```

---

## 🎨 RTL/LTR CSS Patterns

### Layout Direction Attribute

```blade
{{-- Main layout MUST include dir attribute --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('common.app_name') }}</title>
</head>
<body>
    @yield('content')
</body>
</html>
```

### Tailwind CSS - Logical Properties

```html
<!-- ❌ WRONG - Hardcoded directional classes -->
<div class="ml-4 mr-2 text-left">
    <div class="pl-6 pr-4">
        <!-- This BREAKS in RTL mode -->
    </div>
</div>

<!-- ✅ CORRECT - Logical properties (auto-flip for RTL) -->
<div class="ms-4 me-2 text-start">
    <div class="ps-6 pe-4">
        <!-- Works perfectly in both RTL and LTR -->
    </div>
</div>
```

### CSS Property Reference

| ❌ WRONG (Physical) | ✅ CORRECT (Logical) | Behavior |
|---------------------|----------------------|----------|
| `ml-4` | `ms-4` | Margin start (left in LTR, right in RTL) |
| `mr-4` | `me-4` | Margin end (right in LTR, left in RTL) |
| `pl-4` | `ps-4` | Padding start |
| `pr-4` | `pe-4` | Padding end |
| `text-left` | `text-start` | Text align start |
| `text-right` | `text-end` | Text align end |
| `left-0` | `start-0` | Position start |
| `right-0` | `end-0` | Position end |

### Icon Direction Handling

```blade
{{-- Icons that should flip in RTL (arrows, chevrons) --}}
<svg class="{{ app()->isLocale('ar') ? 'transform scale-x-[-1]' : '' }}">
    <!-- Arrow right icon -->
</svg>

{{-- Icons that should NOT flip (logos, numbers) --}}
<img src="/logo.png" class="[dir=rtl]:transform-none">
```

---

## 🔍 Quick Audit Commands

### Before Starting ANY Work

```bash
# Run complete i18n audit
echo "=== CMIS i18n Audit ==="

# 1. Hardcoded text
echo "1. Hardcoded text issues:"
grep -r -E "\b(Campaign|Dashboard|Save|Delete|Edit|Create)\b" resources/views/ \
  | grep -v "{{ __(" \
  | grep -v "@lang" \
  | wc -l

# 2. Directional CSS
echo "2. Directional CSS issues:"
grep -r -E "(ml-|mr-|text-left|text-right)" resources/views/ | wc -l

# 3. Missing dir attribute
echo "3. Missing dir attribute:"
grep -r "<html" resources/views/layouts/ | grep -v "dir=" | wc -l

# 4. Language files
echo "4. Language files:"
echo "   Arabic: $(find resources/lang/ar -type f | wc -l) files"
echo "   English: $(find resources/lang/en -type f | wc -l) files"

echo "=== If any count > 0, FIX BEFORE implementing features! ==="
```

### Find Specific Issues

```bash
# Find all hardcoded text in a specific directory
grep -r -E "\b(Campaign|Dashboard|Save|Delete)\b" resources/views/campaigns/ \
  | grep -v "{{ __("

# Find directional CSS in specific file
grep -E "(ml-|mr-|text-left|text-right)" resources/views/campaigns/index.blade.php

# Check if translation key exists
grep -r "dashboard_title" resources/lang/

# List all views that need translation
find resources/views -name "*.blade.php" -exec grep -L "{{ __(" {} \;
```

---

## 🚫 ZERO-TOLERANCE RULES

### Rule 1: NO Hardcoded Text
```blade
❌ WRONG:
<h1>Campaign Dashboard</h1>
<button>Save Campaign</button>
<p>Welcome back, John</p>

✅ CORRECT:
<h1>{{ __('campaigns.dashboard_title') }}</h1>
<button>{{ __('campaigns.save_button') }}</button>
<p>{{ __('common.welcome_back', ['name' => $user->name]) }}</p>
```

### Rule 2: NO Directional CSS
```html
❌ WRONG:
<div class="ml-4 text-left">
<div class="mr-2 pr-6">

✅ CORRECT:
<div class="ms-4 text-start">
<div class="me-2 pe-6">
```

### Rule 3: NO Feature Work Until i18n Issues Fixed
```
❌ WRONG Workflow:
1. User requests feature
2. Agent implements feature immediately
3. Feature has hardcoded text/CSS

✅ CORRECT Workflow:
1. User requests feature
2. Agent runs i18n audit on affected pages
3. Agent fixes ANY i18n issues found
4. Agent verifies fixes with audit commands
5. Agent implements feature with i18n compliance
6. Agent tests in BOTH languages
```

### Rule 4: NO Skipping Language Testing
```
❌ WRONG:
- Implement feature
- Test in English only
- Mark complete

✅ CORRECT:
- Implement feature with i18n
- Test in Arabic (RTL mode)
- Test in English (LTR mode)
- Verify layout integrity in both
- Mark complete
```

---

## 📊 Agent Responsibility Matrix

| Agent Type | i18n Audit Required? | Fix Before Feature? | Both Languages Test? |
|------------|---------------------|---------------------|----------------------|
| UI Frontend | ✅ ALWAYS | ✅ ALWAYS | ✅ ALWAYS |
| Campaign Expert | ✅ ALWAYS | ✅ ALWAYS | ✅ ALWAYS |
| Social Publishing | ✅ ALWAYS | ✅ ALWAYS | ✅ ALWAYS |
| Content Manager | ✅ ALWAYS | ✅ ALWAYS | ✅ ALWAYS |
| Analytics Expert | ✅ ALWAYS | ✅ ALWAYS | ✅ ALWAYS |
| Platform Integration | ✅ For UI work | ✅ For UI work | ✅ For UI work |
| Database Architect | ❌ Not required | ❌ Not required | ❌ Not required |
| Testing Agent | ✅ Test i18n | ✅ Verify i18n | ✅ Test both |

---

## 📚 Additional Resources

**Full Documentation:** `.claude/knowledge/I18N_RTL_REQUIREMENTS.md`

**CMIS Guidelines:** `CLAUDE.md` - See "Internationalization (i18n) & RTL/LTR" section

**Laravel Localization Docs:** https://laravel.com/docs/localization

---

## ✅ Pre-Implementation Checklist

Before working on ANY feature or page:

**Discovery Phase:**
- [ ] Run hardcoded text audit command
- [ ] Run directional CSS audit command
- [ ] Check language files exist for feature domain
- [ ] Identify all views that will be modified

**Resolution Phase (if issues found):**
- [ ] Create/update translation files (ar + en)
- [ ] Replace hardcoded text with `__('key')` calls
- [ ] Convert directional classes to logical properties
- [ ] Add dir attribute if missing
- [ ] Re-run audit commands to verify fixes

**Implementation Phase:**
- [ ] Use translation keys for ALL new text
- [ ] Use logical CSS properties exclusively
- [ ] Test page in Arabic (RTL)
- [ ] Test page in English (LTR)
- [ ] Verify layout integrity in both directions
- [ ] Verify icons flip appropriately (or don't)

**Completion:**
- [ ] Zero hardcoded text remains
- [ ] Zero directional CSS remains
- [ ] Both languages render correctly
- [ ] RTL and LTR layouts are intact

---

## 🎯 Summary: Agent Workflow

```
1. Receive task
   ↓
2. Run i18n audit on affected files
   ↓
3. Issues found? → Fix FIRST (go to step 2 when done)
   ↓
4. No issues? → Proceed with feature
   ↓
5. Implement with i18n compliance
   ↓
6. Test BOTH Arabic (RTL) and English (LTR)
   ↓
7. Mark complete
```

---

**Version:** 1.0
**Last Updated:** 2025-11-27
**Maintained By:** CMIS AI Agent Development Team

**⚠️ CRITICAL: This is NOT optional. i18n compliance is MANDATORY for ALL agents.**
