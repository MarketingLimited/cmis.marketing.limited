# CMIS Internationalization & RTL/LTR Requirements

**Last Updated:** 2025-11-27
**Priority:** CRITICAL - Must be addressed BEFORE implementing new features
**Languages:** Arabic (Default), English
**Direction Support:** RTL (Arabic), LTR (English)

---

## 🌍 Overview

CMIS is a **bilingual platform** supporting Arabic and English with full RTL/LTR optimization. All agents MUST ensure internationalization compliance before implementing new features.

---

## 🚨 Critical Rules (AGENTS MUST FOLLOW)

### 1. Pre-Implementation i18n Audit (MANDATORY)
Before implementing ANY feature or working on ANY page, agents MUST:

✅ **Step 1: Audit for Hardcoded Text**
```bash
# Search for hardcoded text in views
grep -r "hardcoded English text" resources/views/

# Check blade files for non-translated strings
grep -r "<h1>" resources/views/ | grep -v "@lang" | grep -v "{{ __("
```

✅ **Step 2: Check for Missing RTL/LTR Support**
- Verify `dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}"` in layout
- Check for hardcoded `text-left`, `ml-`, `mr-` classes
- Verify use of `ms-` (margin-start) and `me-` (margin-end)

✅ **Step 3: Verify Language Files Exist**
- Check `resources/lang/ar/` and `resources/lang/en/` directories
- Ensure all translation keys are present in both languages

❌ **NEVER proceed with feature implementation if i18n issues are found**
❌ **NEVER add new hardcoded text**
❌ **NEVER use directional CSS classes without RTL/LTR variants**

### 2. Default Language
- **Default Language:** Arabic (`ar`)
- **Fallback Language:** English (`en`)
- **User Preference:** Users can change language in settings
- **Storage:** User language preference stored in `users.locale` column

### 3. Zero Tolerance for Hardcoded Text
```php
// ❌ WRONG - Hardcoded text
<h1>Campaign Dashboard</h1>
<button>Save Campaign</button>

// ✅ CORRECT - Using Laravel localization
<h1>{{ __('campaigns.dashboard_title') }}</h1>
<button>{{ __('campaigns.save_button') }}</button>
```

### 4. RTL/LTR Optimization Required
All pages MUST support both text directions without breaking layout.

---

## 📋 Laravel Localization Pattern

### Directory Structure
```
resources/
├── lang/
│   ├── ar/                    # Arabic translations (Default)
│   │   ├── auth.php
│   │   ├── campaigns.php
│   │   ├── common.php
│   │   ├── dashboard.php
│   │   ├── social.php
│   │   └── validation.php
│   └── en/                    # English translations
│       ├── auth.php
│       ├── campaigns.php
│       ├── common.php
│       ├── dashboard.php
│       ├── social.php
│       └── validation.php
```

### Translation File Format
```php
// resources/lang/ar/campaigns.php
<?php

return [
    // Page titles
    'dashboard_title' => 'لوحة تحكم الحملات',
    'create_campaign' => 'إنشاء حملة جديدة',
    'edit_campaign' => 'تعديل الحملة',

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

    // Validation
    'name_required' => 'اسم الحملة مطلوب',
    'budget_required' => 'ميزانية الحملة مطلوبة',
];
```

```php
// resources/lang/en/campaigns.php
<?php

return [
    // Page titles
    'dashboard_title' => 'Campaign Dashboard',
    'create_campaign' => 'Create New Campaign',
    'edit_campaign' => 'Edit Campaign',

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

    // Validation
    'name_required' => 'Campaign name is required',
    'budget_required' => 'Campaign budget is required',
];
```

### Using Translations in Blade
```blade
{{-- Simple translation --}}
<h1>{{ __('campaigns.dashboard_title') }}</h1>

{{-- Translation with parameters --}}
<p>{{ __('campaigns.welcome_message', ['name' => $user->name]) }}</p>

{{-- Translation with choice (pluralization) --}}
<p>{{ trans_choice('campaigns.items_count', $count) }}</p>

{{-- Translation in Alpine.js --}}
<div x-data="{ message: '{{ __('campaigns.loading') }}' }">
    <span x-text="message"></span>
</div>
```

### Using Translations in Controllers
```php
use Illuminate\Support\Facades\App;

class CampaignController extends Controller
{
    public function store(Request $request)
    {
        $campaign = Campaign::create($request->validated());

        return $this->created(
            $campaign,
            __('campaigns.campaign_created') // Translated message
        );
    }

    public function destroy($id)
    {
        Campaign::findOrFail($id)->delete();

        return $this->deleted(
            __('campaigns.campaign_deleted') // Translated message
        );
    }
}
```

### Using Translations in JavaScript
```javascript
// Pass translations to JavaScript
<script>
    const translations = {
        save: '{{ __('common.save') }}',
        cancel: '{{ __('common.cancel') }}',
        confirm: '{{ __('common.confirm_action') }}'
    };

    // Use in Alpine.js
    Alpine.data('campaignForm', () => ({
        saveButtonText: translations.save,
        cancelButtonText: translations.cancel
    }));
</script>
```

---

## 🎨 RTL/LTR CSS Patterns

### 1. Layout Direction
```blade
{{-- Main layout must include dir attribute --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('common.app_name') }}</title>

    {{-- Load RTL or LTR CSS --}}
    @if(app()->isLocale('ar'))
        <link rel="stylesheet" href="{{ asset('css/app-rtl.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @endif
</head>
<body>
    @yield('content')
</body>
</html>
```

### 2. Tailwind CSS RTL/LTR Classes

**❌ WRONG - Hardcoded directional classes:**
```html
<div class="ml-4 mr-2 text-left">
    <!-- This breaks in RTL -->
</div>
```

**✅ CORRECT - Use logical properties:**
```html
{{-- Margin start/end (auto-flips for RTL) --}}
<div class="ms-4 me-2">
    <!-- ms-4 = margin-left in LTR, margin-right in RTL -->
    <!-- me-2 = margin-right in LTR, margin-left in RTL -->
</div>

{{-- Text alignment --}}
<div class="text-start">
    <!-- text-start = text-left in LTR, text-right in RTL -->
</div>

{{-- Padding start/end --}}
<div class="ps-6 pe-4">
    <!-- ps-6 = padding-left in LTR, padding-right in RTL -->
</div>
```

### 3. Tailwind RTL Plugin Configuration
```javascript
// tailwind.config.js
module.exports = {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {},
    },
    plugins: [
        require('tailwindcss-rtl'), // RTL support plugin
    ],
}
```

### 4. Custom RTL CSS
```css
/* resources/css/app.css */

/* Use logical properties */
.card {
    margin-inline-start: 1rem; /* Auto-flips for RTL */
    margin-inline-end: 1rem;
    padding-inline-start: 1.5rem;
    padding-inline-end: 1.5rem;
}

/* RTL-specific styles */
[dir="rtl"] .custom-element {
    transform: scaleX(-1); /* Flip icons/images */
}

/* LTR-specific styles */
[dir="ltr"] .custom-element {
    /* LTR-specific overrides */
}
```

### 5. Icon Direction Handling
```blade
{{-- Icons that should flip in RTL --}}
<svg class="{{ app()->isLocale('ar') ? 'transform scale-x-[-1]' : '' }}">
    <!-- Arrow icon -->
</svg>

{{-- Icons that should NOT flip (numbers, logos) --}}
<svg class="[dir=rtl]:transform-none">
    <!-- Logo or numerical icon -->
</svg>
```

---

## 🔍 Agent i18n Audit Checklist

Before working on ANY page or feature, agents MUST complete this checklist:

### Phase 1: Discovery (MANDATORY FIRST STEP)
- [ ] List all blade files in the feature area
- [ ] Grep for hardcoded text patterns (English words, phrases)
- [ ] Identify missing translation keys
- [ ] Check for hardcoded directional CSS classes (`ml-`, `mr-`, `text-left`, `text-right`)
- [ ] Verify language files exist in both `lang/ar/` and `lang/en/`

### Phase 2: Resolution (BEFORE NEW FEATURES)
- [ ] Create/update translation files for missing keys
- [ ] Replace ALL hardcoded text with `__('key')` calls
- [ ] Convert directional classes to logical properties (`ms-`, `me-`, `text-start`)
- [ ] Add `dir` attribute to layout if missing
- [ ] Test page in both Arabic (RTL) and English (LTR)

### Phase 3: Implementation (ONLY AFTER PHASES 1-2)
- [ ] Implement new features using translation keys from the start
- [ ] Use logical CSS properties exclusively
- [ ] Test RTL/LTR rendering for all new components
- [ ] Document any special RTL considerations

### Phase 4: Validation (FINAL STEP)
- [ ] Verify no hardcoded text remains
- [ ] Confirm all text renders correctly in both languages
- [ ] Test layout integrity in RTL and LTR modes
- [ ] Verify icons/images flip appropriately (or don't when shouldn't)

---

## 🛠️ Common i18n Audit Commands

```bash
# Find hardcoded English text in views
grep -r -E "\b(Campaign|Dashboard|Save|Delete|Edit|Create|Update|Submit)\b" resources/views/ \
  | grep -v "{{ __(" \
  | grep -v "@lang"

# Find hardcoded directional classes
grep -r -E "(ml-|mr-|text-left|text-right|pl-|pr-)" resources/views/

# Find views missing dir attribute
grep -r "<html" resources/views/ | grep -v "dir="

# List all translation files
find resources/lang -type f -name "*.php"

# Check for missing Arabic translations
diff <(ls resources/lang/en/) <(ls resources/lang/ar/)
```

---

## 📊 Example: Converting Non-i18n Page to i18n

### Before (❌ WRONG):
```blade
{{-- resources/views/campaigns/index.blade.php --}}
<div class="container ml-4">
    <h1 class="text-left">Campaign Dashboard</h1>

    <button class="btn ml-2">Create New Campaign</button>

    <table>
        <thead>
            <tr>
                <th class="text-left">Name</th>
                <th class="text-left">Budget</th>
                <th class="text-left">Status</th>
            </tr>
        </thead>
    </table>
</div>
```

### After (✅ CORRECT):
```blade
{{-- resources/views/campaigns/index.blade.php --}}
<div class="container ms-4">
    <h1 class="text-start">{{ __('campaigns.dashboard_title') }}</h1>

    <button class="btn ms-2">{{ __('campaigns.create_button') }}</button>

    <table>
        <thead>
            <tr>
                <th class="text-start">{{ __('campaigns.name_column') }}</th>
                <th class="text-start">{{ __('campaigns.budget_column') }}</th>
                <th class="text-start">{{ __('campaigns.status_column') }}</th>
            </tr>
        </thead>
    </table>
</div>
```

```php
// resources/lang/ar/campaigns.php
return [
    'dashboard_title' => 'لوحة تحكم الحملات',
    'create_button' => 'إنشاء حملة جديدة',
    'name_column' => 'الاسم',
    'budget_column' => 'الميزانية',
    'status_column' => 'الحالة',
];
```

```php
// resources/lang/en/campaigns.php
return [
    'dashboard_title' => 'Campaign Dashboard',
    'create_button' => 'Create New Campaign',
    'name_column' => 'Name',
    'budget_column' => 'Budget',
    'status_column' => 'Status',
];
```

---

## 🔧 Language Switcher Implementation

### User Settings Table
```php
// Migration: Add locale column to users table
Schema::table('users', function (Blueprint $table) {
    $table->string('locale', 5)->default('ar')->after('email');
});
```

### Middleware: SetLocale
```php
// app/Http/Middleware/SetLocale.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        // Check if user is authenticated
        if (Auth::check()) {
            $locale = Auth::user()->locale ?? 'ar';
        } else {
            // Guest users get Arabic (default)
            $locale = session('locale', 'ar');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
```

### Language Switcher Component
```blade
{{-- resources/views/components/language-switcher.blade.php --}}
<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" class="btn">
        {{ app()->getLocale() === 'ar' ? 'العربية' : 'English' }}
    </button>

    <div x-show="open" @click.away="open = false" class="dropdown">
        <a href="{{ route('language.switch', 'ar') }}"
           class="dropdown-item {{ app()->isLocale('ar') ? 'active' : '' }}">
            العربية
        </a>
        <a href="{{ route('language.switch', 'en') }}"
           class="dropdown-item {{ app()->isLocale('en') ? 'active' : '' }}">
            English
        </a>
    </div>
</div>
```

### Language Switch Controller
```php
// app/Http/Controllers/LanguageController.php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    public function switch($locale)
    {
        if (!in_array($locale, ['ar', 'en'])) {
            abort(400);
        }

        // Update user preference if authenticated
        if (Auth::check()) {
            Auth::user()->update(['locale' => $locale]);
        }

        // Store in session for guests
        Session::put('locale', $locale);

        return redirect()->back();
    }
}
```

---

## 🎯 Agent-Specific Guidelines

### UI Frontend Agent
- **Priority 1:** Audit all views for hardcoded text
- **Priority 2:** Convert directional classes to logical properties
- **Priority 3:** Implement new features with i18n from the start
- **Tool:** Use Grep to find hardcoded text patterns

### Campaign Expert Agent
- **Focus:** Campaign-related translation keys
- **Files:** `resources/lang/*/campaigns.php`
- **Validation:** All campaign status, messages, labels translated

### Social Publishing Agent
- **Focus:** Social media related translations
- **Files:** `resources/lang/*/social.php`
- **Validation:** All post templates, engagement labels translated

### Laravel Testing Agent
- **Focus:** Test i18n functionality
- **Tests:** Language switching, RTL/LTR rendering, translation fallbacks

---

## 📈 i18n Compliance Metrics

Track these metrics for every feature:
- [ ] 0 hardcoded text strings
- [ ] 100% translation key coverage
- [ ] 100% RTL/LTR CSS compliance
- [ ] Both languages tested manually
- [ ] All validation messages translated

---

## 🚫 Common Mistakes to Avoid

### 1. Partial Translation
```blade
❌ WRONG:
<h1>{{ __('campaigns.title') }}: Campaign Dashboard</h1>

✅ CORRECT:
<h1>{{ __('campaigns.dashboard_title') }}</h1>
```

### 2. Concatenating Translations
```blade
❌ WRONG:
<p>{{ __('common.welcome') }} {{ $user->name }}</p>

✅ CORRECT:
<p>{{ __('common.welcome_user', ['name' => $user->name]) }}</p>
```

### 3. Hardcoded Dates/Times
```blade
❌ WRONG:
<span>Created: {{ $campaign->created_at->format('Y-m-d') }}</span>

✅ CORRECT:
<span>{{ __('common.created') }}: {{ $campaign->created_at->locale(app()->getLocale())->isoFormat('LL') }}</span>
```

### 4. Mixing Directional Classes
```blade
❌ WRONG:
<div class="ms-4 mr-2">  <!-- Mixed logical and physical -->

✅ CORRECT:
<div class="ms-4 me-2">  <!-- Consistent logical properties -->
```

---

## 📝 Translation Naming Conventions

### Key Structure
```
{domain}.{context}_{type}

Examples:
- campaigns.dashboard_title
- campaigns.create_button
- campaigns.name_label
- campaigns.success_message
- social.post_created_message
```

### Key Types
- `_title` - Page titles, section headers
- `_button` - Button labels
- `_label` - Form field labels
- `_message` - Success/error/info messages
- `_description` - Help text, descriptions
- `_placeholder` - Input placeholders
- `_column` - Table column headers

---

## 🔍 Pre-Implementation i18n Audit Script

Agents should run this before working on any feature:

```bash
#!/bin/bash
# i18n-audit.sh - Run before implementing features

echo "=== CMIS i18n Audit ==="
echo ""

# 1. Find hardcoded text
echo "1. Searching for hardcoded text..."
grep -r -E "\b(Campaign|Dashboard|Save|Delete|Edit|Create|Update|Submit|Budget|Status|Name|Description)\b" resources/views/ \
  | grep -v "{{ __(" \
  | grep -v "@lang" \
  | grep -v "<!--" \
  | wc -l

# 2. Find directional CSS
echo "2. Searching for hardcoded directional CSS..."
grep -r -E "(^|[^m])(ml-|mr-|pl-|pr-|text-left|text-right)" resources/views/ | wc -l

# 3. Check for missing dir attribute
echo "3. Checking for missing dir attribute..."
grep -r "<html" resources/views/ | grep -v "dir=" | wc -l

# 4. Verify language files
echo "4. Verifying language files..."
echo "   Arabic files: $(find resources/lang/ar -type f | wc -l)"
echo "   English files: $(find resources/lang/en -type f | wc -l)"

echo ""
echo "=== Audit Complete ==="
echo "If any counts above are > 0, fix i18n issues before implementing new features!"
```

---

## ✅ Summary: Agent Workflow

**MANDATORY WORKFLOW FOR ALL AGENTS:**

1. **Receive task** → Run i18n audit on affected files
2. **If i18n issues found** → Fix them FIRST (hardcoded text, RTL/LTR)
3. **After i18n compliance** → Proceed with feature implementation
4. **During implementation** → Use translation keys and logical CSS
5. **Before completion** → Verify both Arabic and English rendering

**Remember:** i18n compliance is NOT optional. It's a prerequisite for all work on CMIS.

---

**Last Updated:** 2025-11-27
**Version:** 1.0
**Status:** ACTIVE - All agents must comply
