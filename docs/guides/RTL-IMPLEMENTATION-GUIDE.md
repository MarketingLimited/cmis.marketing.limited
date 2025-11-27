# RTL (Right-to-Left) Implementation Guide

**Last Updated:** 2025-11-27
**Status:** Complete
**Applies To:** CMIS Laravel Application (Campaigns Module)

---

## Table of Contents

1. [Overview](#overview)
2. [Core RTL Patterns](#core-rtl-patterns)
3. [Implementation Checklist](#implementation-checklist)
4. [Code Examples](#code-examples)
5. [Translation File Organization](#translation-file-organization)
6. [Testing Guidelines](#testing-guidelines)
7. [Common Pitfalls](#common-pitfalls)
8. [Troubleshooting](#troubleshooting)

---

## Overview

### What is RTL Support?

RTL (Right-to-Left) support enables the application to properly display languages that are written from right to left, primarily Arabic, Hebrew, Persian, and Urdu. This involves:

- **Text Direction:** Reversing text flow and alignment
- **Layout Mirroring:** Flipping UI elements horizontally
- **Localization:** Translating all text content to the target language

### CMIS RTL Strategy

The CMIS application uses a **bidirectional (BiDi)** approach:

- **Dynamic Detection:** Runtime locale detection (`app()->getLocale() === 'ar'`)
- **Conditional Rendering:** Tailwind CSS utility classes with Blade conditionals
- **Translation System:** Laravel's `__()` helper with organized translation files
- **LTR Override:** Selective LTR direction for URLs, numbers, and technical content

---

## Core RTL Patterns

### 1. RTL Detection & Setup

**Always include at component level:**

```php
@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp
```

**Apply direction attribute on main container:**

```blade
<div class="container" dir="{{ $dir }}">
    {{-- Your content --}}
</div>
```

### 2. Text Alignment

**Labels and headings:**

```blade
<label class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">
    {{ __('campaigns.ad_name') }}
</label>

<h1 class="text-2xl font-bold {{ $isRtl ? 'text-right' : '' }}">
    {{ __('campaigns.title') }}
</h1>
```

### 3. Flexbox Reversal

**Horizontal layouts:**

```blade
{{-- Breadcrumb navigation --}}
<nav class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
    <a href="/">{{ __('campaigns.home') }}</a>
    <span>/</span>
    <span>{{ __('campaigns.campaigns') }}</span>
</nav>

{{-- Button groups --}}
<div class="flex {{ $isRtl ? 'flex-row-reverse' : 'justify-between' }} items-center">
    <button>{{ __('campaigns.cancel') }}</button>
    <button>{{ __('campaigns.save') }}</button>
</div>
```

### 4. Spacing & Margins

**Icon positioning:**

```blade
<button class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
    <i class="fas fa-plus {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
    {{ __('campaigns.create') }}
</button>
```

**Horizontal spacing:**

```blade
<div class="flex {{ $isRtl ? 'flex-row-reverse space-x-reverse space-x-3' : 'space-x-3' }}">
    <button>{{ __('campaigns.cancel') }}</button>
    <button>{{ __('campaigns.save') }}</button>
    <button>{{ __('campaigns.publish') }}</button>
</div>
```

**Status badges:**

```blade
<span class="{{ $isRtl ? 'mr-3' : 'ml-3' }} px-2 py-1 text-xs rounded-full">
    {{ __('campaigns.status.active') }}
</span>
```

### 5. Absolute Positioning

**Positioned elements:**

```blade
<div class="relative">
    <span class="absolute top-2 {{ $isRtl ? 'left-2' : 'right-2' }}">
        {{ __('campaigns.new') }}
    </span>
</div>
```

### 6. Text Inputs

**Bidirectional text fields:**

```blade
<input
    type="text"
    name="name"
    value="{{ old('name') }}"
    class="mt-1 block w-full border-gray-300 rounded-md {{ $isRtl ? 'text-right' : '' }}"
    dir="{{ $dir }}"
    placeholder="{{ __('campaigns.ad_name_placeholder') }}"
>
```

**Textareas:**

```blade
<textarea
    name="description"
    rows="4"
    class="mt-1 block w-full border-gray-300 rounded-md {{ $isRtl ? 'text-right' : '' }}"
    dir="{{ $dir }}"
    placeholder="{{ __('campaigns.description_placeholder') }}"
>{{ old('description') }}</textarea>
```

### 7. LTR Override (Critical!)

**URLs, emails, phone numbers, and technical content:**

```blade
{{-- URL input - ALWAYS use dir="ltr" --}}
<input
    type="url"
    name="destination_url"
    value="{{ old('destination_url') }}"
    class="mt-1 block w-full border-gray-300 rounded-md"
    dir="ltr"
    placeholder="{{ __('campaigns.destination_url_placeholder') }}"
>

{{-- Email input --}}
<input
    type="email"
    name="email"
    class="mt-1 block w-full border-gray-300 rounded-md"
    dir="ltr"
>

{{-- Number input --}}
<input
    type="number"
    name="budget"
    class="mt-1 block w-full border-gray-300 rounded-md"
    dir="ltr"
>

{{-- Display URLs in content --}}
<a href="{{ $ad->destination_url }}" target="_blank" dir="ltr">
    {{ $ad->destination_url }}
</a>
```

### 8. Select Dropdowns

```blade
<select
    name="status"
    class="mt-1 block w-full border-gray-300 rounded-md {{ $isRtl ? 'text-right' : '' }}"
    dir="{{ $dir }}"
>
    @foreach(['draft', 'active', 'paused'] as $status)
        <option value="{{ $status }}" {{ old('status') === $status ? 'selected' : '' }}>
            {{ __('campaigns.status.' . $status) }}
        </option>
    @endforeach
</select>
```

### 9. Grid Layouts

**Two-column layout:**

```blade
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Main content (2 columns) --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="{{ $isRtl ? 'text-right' : '' }}">
            <h2>{{ __('campaigns.details') }}</h2>
        </div>
    </div>

    {{-- Sidebar (1 column) --}}
    <div class="space-y-6">
        <div class="{{ $isRtl ? 'text-right' : '' }}">
            <h3>{{ __('campaigns.actions') }}</h3>
        </div>
    </div>
</div>
```

### 10. Alpine.js Components

**Reactive components with RTL:**

```blade
<div x-data="{ cards: [{}] }">
    <template x-for="(card, index) in cards" :key="index">
        <div class="border rounded-lg p-4">
            <div class="flex {{ $isRtl ? 'flex-row-reverse' : 'justify-between' }} items-center">
                <span class="text-sm font-medium">
                    {{ __('campaigns.card') }} <span x-text="index + 1"></span>
                </span>
                <button type="button" @click="cards.splice(index, 1)" x-show="cards.length > 1"
                        class="text-red-600 text-sm flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-times {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                    {{ __('campaigns.remove') }}
                </button>
            </div>
        </div>
    </template>

    <button type="button" @click="if(cards.length < 10) cards.push({})"
            class="text-blue-600 text-sm flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
        <i class="fas fa-plus {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
        {{ __('campaigns.add_card') }}
    </button>
</div>
```

---

## Implementation Checklist

Use this checklist when converting a Blade view to RTL:

### Phase 1: Setup
- [ ] Add RTL detection variables (`$isRtl`, `$dir`)
- [ ] Add `dir="{{ $dir }}"` to main container
- [ ] Identify all hardcoded English text

### Phase 2: Translation Keys
- [ ] Replace all hardcoded text with `{{ __('module.key') }}`
- [ ] Add translation keys to `resources/lang/en/module.php`
- [ ] Add matching translations to `resources/lang/ar/module.php`
- [ ] Test all translation keys render correctly

### Phase 3: Layout Reversal
- [ ] Apply `flex-row-reverse` to flex containers
- [ ] Apply `text-right` to text blocks
- [ ] Swap `ml-*` ↔ `mr-*` margins
- [ ] Swap `pl-*` ↔ `pr-*` padding
- [ ] Swap `left-*` ↔ `right-*` positioning
- [ ] Apply `space-x-reverse` to horizontal spacing

### Phase 4: Form Elements
- [ ] Add `dir="{{ $dir }}"` to text inputs
- [ ] Add `dir="{{ $dir }}"` to textareas
- [ ] Add `dir="{{ $dir }}"` to select dropdowns
- [ ] Add `dir="ltr"` to URL inputs
- [ ] Add `dir="ltr"` to email inputs
- [ ] Add `dir="ltr"` to number inputs

### Phase 5: Components
- [ ] Convert breadcrumbs to RTL
- [ ] Convert navigation to RTL
- [ ] Convert button groups to RTL
- [ ] Convert forms to RTL
- [ ] Convert tables to RTL (if applicable)
- [ ] Convert cards to RTL
- [ ] Convert modals to RTL (if applicable)

### Phase 6: Testing
- [ ] Test in English (LTR)
- [ ] Test in Arabic (RTL)
- [ ] Test form submissions
- [ ] Test validation errors
- [ ] Test success messages
- [ ] Test responsive layouts (mobile, tablet, desktop)

---

## Code Examples

### Complete Form Example

```blade
@extends('layouts.admin')

@section('title', __('campaigns.create_ad'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('content')
<div class="space-y-6" dir="{{ $dir }}">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
        <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600">
            <i class="fas fa-home"></i>
        </a>
        <span class="text-gray-400">/</span>
        <a href="{{ route('orgs.campaigns.index', $currentOrg) }}" class="hover:text-blue-600">
            {{ __('campaigns.campaigns') }}
        </a>
        <span class="text-gray-400">/</span>
        <span class="text-gray-900 font-medium">{{ __('campaigns.create_ad') }}</span>
    </nav>

    {{-- Header --}}
    <div class="{{ $isRtl ? 'text-right' : '' }}">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('campaigns.create_ad') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('campaigns.create_ad_description') }}</p>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="rounded-md bg-red-50 p-4">
            <ul class="text-sm text-red-700 list-disc {{ $isRtl ? 'list-inside text-right' : 'list-inside' }}">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form --}}
    <form action="{{ route('org.campaigns.ads.store', $currentOrg) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Basic Information --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">
                    {{ __('campaigns.basic_information') }}
                </h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">
                            {{ __('campaigns.ad_name') }} *
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name') }}"
                            required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                            dir="{{ $dir }}"
                            placeholder="{{ __('campaigns.ad_name_placeholder') }}"
                        >
                    </div>

                    <div>
                        <label for="destination_url" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">
                            {{ __('campaigns.destination_url_label') }}
                        </label>
                        <input
                            type="url"
                            name="destination_url"
                            id="destination_url"
                            value="{{ old('destination_url') }}"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            dir="ltr"
                            placeholder="{{ __('campaigns.destination_url_placeholder') }}"
                        >
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex {{ $isRtl ? 'flex-row-reverse' : 'justify-end' }} {{ $isRtl ? 'space-x-reverse space-x-3' : 'space-x-3' }}">
            <a href="{{ route('orgs.campaigns.index', $currentOrg) }}"
               class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">
                {{ __('campaigns.cancel') }}
            </a>
            <button type="submit"
                    class="px-6 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                {{ __('campaigns.create') }}
            </button>
        </div>
    </form>
</div>
@endsection
```

### Complete Detail View Example

```blade
@extends('layouts.admin')

@section('title', $ad->name . ' - ' . __('campaigns.ad'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('content')
<div class="space-y-6" dir="{{ $dir }}">
    {{-- Header --}}
    <div class="flex {{ $isRtl ? 'flex-row-reverse' : 'justify-between' }} items-start">
        <div class="{{ $isRtl ? 'text-right' : '' }}">
            <h1 class="text-2xl font-bold text-gray-900 flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                {{ $ad->name }}
                @php
                    $statusColors = [
                        'draft' => 'bg-gray-100 text-gray-800',
                        'active' => 'bg-green-100 text-green-800',
                        'paused' => 'bg-yellow-100 text-yellow-800'
                    ];
                @endphp
                <span class="{{ $isRtl ? 'mr-3' : 'ml-3' }} px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$ad->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ __('campaigns.status.' . $ad->status) }}
                </span>
            </h1>
        </div>
        <div class="flex {{ $isRtl ? 'flex-row-reverse space-x-reverse space-x-3' : 'space-x-3' }}">
            <a href="{{ route('org.campaigns.ads.edit', [$currentOrg, $ad->ad_id]) }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <i class="fas fa-edit {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                {{ __('campaigns.edit') }}
            </a>
        </div>
    </div>

    {{-- Ad Details --}}
    <div class="bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">
                {{ __('campaigns.ad_details') }}
            </h3>
            <dl class="grid grid-cols-1 gap-4">
                <div class="{{ $isRtl ? 'text-right' : '' }}">
                    <dt class="text-sm font-medium text-gray-500">{{ __('campaigns.primary_text_label') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $ad->primary_text ?? __('campaigns.not_set') }}</dd>
                </div>
                <div class="{{ $isRtl ? 'text-right' : '' }}">
                    <dt class="text-sm font-medium text-gray-500">{{ __('campaigns.destination_url_label') }}</dt>
                    <dd class="mt-1 text-sm text-blue-600 break-all">
                        @if($ad->destination_url)
                            <a href="{{ $ad->destination_url }}" target="_blank" dir="ltr">{{ $ad->destination_url }}</a>
                        @else
                            <span class="text-gray-900">{{ __('campaigns.not_set') }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
```

---

## Translation File Organization

### File Structure

```
resources/
└── lang/
    ├── en/
    │   ├── campaigns.php
    │   ├── auth.php
    │   ├── validation.php
    │   └── ...
    └── ar/
        ├── campaigns.php
        ├── auth.php
        ├── validation.php
        └── ...
```

### Translation File Pattern

**English (`resources/lang/en/campaigns.php`):**

```php
<?php

return [
    // Basic Labels
    'campaigns' => 'Campaigns',
    'campaign' => 'Campaign',
    'create_campaign' => 'Create Campaign',
    'edit_campaign' => 'Edit Campaign',

    // Status Values
    'status' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'paused' => 'Paused',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ],

    // Form Fields
    'campaign_name' => 'Campaign Name',
    'campaign_name_placeholder' => 'Enter campaign name',
    'campaign_name_hint' => 'Use a descriptive name for your campaign',

    'budget_label' => 'Budget',
    'budget_placeholder' => 'Enter budget amount',

    // Actions
    'save' => 'Save',
    'cancel' => 'Cancel',
    'delete' => 'Delete',
    'edit' => 'Edit',
    'create' => 'Create',

    // Messages
    'campaign_created' => 'Campaign created successfully',
    'campaign_updated' => 'Campaign updated successfully',
    'campaign_deleted' => 'Campaign deleted successfully',

    // Confirmations
    'delete_campaign_confirm' => 'Are you sure you want to delete this campaign?',
];
```

**Arabic (`resources/lang/ar/campaigns.php`):**

```php
<?php

return [
    // Basic Labels
    'campaigns' => 'الحملات',
    'campaign' => 'حملة',
    'create_campaign' => 'إنشاء حملة',
    'edit_campaign' => 'تعديل الحملة',

    // Status Values
    'status' => [
        'draft' => 'مسودة',
        'active' => 'نشط',
        'paused' => 'متوقف مؤقتاً',
        'completed' => 'مكتمل',
        'archived' => 'مؤرشف',
    ],

    // Form Fields
    'campaign_name' => 'اسم الحملة',
    'campaign_name_placeholder' => 'أدخل اسم الحملة',
    'campaign_name_hint' => 'استخدم اسماً وصفياً لحملتك',

    'budget_label' => 'الميزانية',
    'budget_placeholder' => 'أدخل مبلغ الميزانية',

    // Actions
    'save' => 'حفظ',
    'cancel' => 'إلغاء',
    'delete' => 'حذف',
    'edit' => 'تعديل',
    'create' => 'إنشاء',

    // Messages
    'campaign_created' => 'تم إنشاء الحملة بنجاح',
    'campaign_updated' => 'تم تحديث الحملة بنجاح',
    'campaign_deleted' => 'تم حذف الحملة بنجاح',

    // Confirmations
    'delete_campaign_confirm' => 'هل أنت متأكد من حذف هذه الحملة؟',
];
```

### Translation Key Naming Conventions

1. **Use snake_case:** `campaign_name`, `budget_label`
2. **Add suffixes for clarity:**
   - `_label` for form labels
   - `_placeholder` for input placeholders
   - `_hint` for help text
   - `_confirm` for confirmation messages
   - `_description` for longer explanatory text
3. **Use nested arrays for related values:** `status.draft`, `status.active`
4. **Keep keys descriptive:** `delete_campaign_confirm` not `del_confirm`

---

## Testing Guidelines

### Manual Testing Checklist

#### English (LTR) Testing
1. Navigate to the page
2. Verify all text is in English
3. Verify layout flows left-to-right
4. Verify icons are on the correct side
5. Verify breadcrumb navigation works
6. Submit forms and verify validation errors
7. Verify success messages display correctly

#### Arabic (RTL) Testing
1. Change locale to Arabic (add `?locale=ar` or use locale switcher)
2. Verify all text is in Arabic
3. Verify layout flows right-to-left
4. Verify icons are on the correct side (mirrored)
5. Verify breadcrumb navigation is reversed
6. Submit forms and verify validation errors in Arabic
7. Verify success messages display in Arabic
8. **Critical:** Verify URLs still display left-to-right
9. **Critical:** Verify numbers display correctly

#### Responsive Testing
- Test on desktop (1920x1080, 1366x768)
- Test on tablet (iPad, 768x1024)
- Test on mobile (iPhone, 375x667)
- Verify grid layouts adapt correctly
- Verify navigation collapses properly

### Automated Testing

**Feature Test Example:**

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campaign;

class CampaignRTLTest extends TestCase
{
    public function test_campaign_index_displays_in_arabic()
    {
        $this->app->setLocale('ar');

        $user = User::factory()->create();
        Campaign::factory()->count(3)->create(['org_id' => $user->org_id]);

        $response = $this->actingAs($user)
            ->get(route('orgs.campaigns.index', $user->org_id));

        $response->assertStatus(200);
        $response->assertSee(__('campaigns.campaigns'));
        $response->assertSee('dir="rtl"', false);
    }

    public function test_campaign_form_displays_in_english()
    {
        $this->app->setLocale('en');

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('orgs.campaigns.create', $user->org_id));

        $response->assertStatus(200);
        $response->assertSee(__('campaigns.create_campaign'));
        $response->assertSee('dir="ltr"', false);
    }
}
```

---

## Common Pitfalls

### 1. Forgetting LTR Override on URLs

**❌ Wrong:**
```blade
<input type="url" name="url" class="..." dir="{{ $dir }}">
```

**✅ Correct:**
```blade
<input type="url" name="url" class="..." dir="ltr">
```

### 2. Not Reversing Flex Containers

**❌ Wrong:**
```blade
<div class="flex items-center">
    <i class="fas fa-home {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
    <span>{{ __('campaigns.home') }}</span>
</div>
```

**✅ Correct:**
```blade
<div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
    <i class="fas fa-home {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
    <span>{{ __('campaigns.home') }}</span>
</div>
```

### 3. Missing Text Alignment on Labels

**❌ Wrong:**
```blade
<label for="name" class="block text-sm font-medium text-gray-700">
    {{ __('campaigns.name') }}
</label>
```

**✅ Correct:**
```blade
<label for="name" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">
    {{ __('campaigns.name') }}
</label>
```

### 4. Hardcoded English Text

**❌ Wrong:**
```blade
<button>Save</button>
```

**✅ Correct:**
```blade
<button>{{ __('campaigns.save') }}</button>
```

### 5. Not Swapping Position Classes

**❌ Wrong:**
```blade
<span class="absolute top-2 right-2">New</span>
```

**✅ Correct:**
```blade
<span class="absolute top-2 {{ $isRtl ? 'left-2' : 'right-2' }}">{{ __('campaigns.new') }}</span>
```

### 6. Forgetting Direction on Text Inputs

**❌ Wrong:**
```blade
<input type="text" name="name" class="...">
```

**✅ Correct:**
```blade
<input type="text" name="name" class="..." dir="{{ $dir }}">
```

### 7. Missing Translation Keys in Arabic File

**❌ Wrong:**
English file has 100 keys, Arabic file has 95 keys

**✅ Correct:**
Both files have exactly the same keys (structure must match)

---

## Troubleshooting

### Issue: Text is not right-aligned in RTL mode

**Solution:** Add `{{ $isRtl ? 'text-right' : '' }}` to the element.

```blade
<div class="{{ $isRtl ? 'text-right' : '' }}">
    {{ __('campaigns.content') }}
</div>
```

### Issue: Icons are on the wrong side

**Solution:**
1. Add `flex-row-reverse` to parent container
2. Swap margin classes (`ml-*` ↔ `mr-*`)

```blade
<div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
    <i class="fas fa-icon {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
    <span>{{ __('campaigns.text') }}</span>
</div>
```

### Issue: URLs are displaying right-to-left

**Solution:** Add `dir="ltr"` to URL inputs and display elements.

```blade
<input type="url" name="url" dir="ltr">
<a href="{{ $url }}" dir="ltr">{{ $url }}</a>
```

### Issue: Buttons are in wrong order

**Solution:** Add `flex-row-reverse` to button container.

```blade
<div class="flex {{ $isRtl ? 'flex-row-reverse space-x-reverse space-x-3' : 'space-x-3' }}">
    <button>{{ __('campaigns.cancel') }}</button>
    <button>{{ __('campaigns.save') }}</button>
</div>
```

### Issue: Translation not showing

**Solution:**
1. Check translation key exists in both `en` and `ar` files
2. Clear Laravel cache: `php artisan cache:clear`
3. Clear config cache: `php artisan config:clear`
4. Clear view cache: `php artisan view:clear`

### Issue: Spacing between elements is incorrect in RTL

**Solution:** Add `space-x-reverse` when using `space-x-*` utilities.

```blade
<div class="flex {{ $isRtl ? 'flex-row-reverse space-x-reverse space-x-4' : 'space-x-4' }}">
    <div>Item 1</div>
    <div>Item 2</div>
</div>
```

### Issue: Form validation errors not showing in Arabic

**Solution:** Ensure validation error messages are translated in `resources/lang/ar/validation.php` and controller uses `__()` for flash messages.

```php
// In controller
return redirect()->back()->with('error', __('campaigns.validation_error'));
```

---

## Additional Resources

### Laravel Localization
- [Laravel Localization Docs](https://laravel.com/docs/localization)
- [Laravel Translation Files](https://laravel.com/docs/localization#retrieving-translation-strings)

### Tailwind CSS RTL
- [Tailwind CSS Direction Utilities](https://tailwindcss.com/docs/direction)
- [Tailwind CSS Text Alignment](https://tailwindcss.com/docs/text-align)

### RTL Best Practices
- [W3C RTL Guidelines](https://www.w3.org/International/questions/qa-html-dir)
- [MDN BiDi Text](https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/dir)

---

## Version History

| Version | Date       | Changes                                      |
|---------|------------|----------------------------------------------|
| 1.0     | 2025-11-27 | Initial RTL implementation guide created     |

---

**Maintained by:** CMIS Development Team
**Questions?** Refer to this guide or consult the Laravel documentation.
