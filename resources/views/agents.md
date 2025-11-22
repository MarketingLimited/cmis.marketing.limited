# دليل الوكلاء - Views Layer (resources/views/)

## 1. Purpose (الغرض)

طبقة Views توفر **Blade Templates** للواجهة الأمامية:
- **100+ Blade Templates**: قوالب للحملات، التحليلات، المحتوى، إدارة المستخدمين
- **Component-Based Architecture**: مكونات قابلة لإعادة الاستخدام
- **Alpine.js Integration**: دمج مع مكونات Alpine.js التفاعلية
- **Tailwind CSS Styling**: تنسيق باستخدام Tailwind utility classes
- **RTL Support**: دعم اللغة العربية (Right-to-Left)

## 2. Owned Scope (النطاق المملوك)

### View Organization

```
resources/views/
├── layouts/
│   ├── app.blade.php              # Main application layout
│   └── guest.blade.php            # Guest/unauthenticated layout
│
├── components/                     # Reusable Blade components
│   ├── forms/                     # Form components
│   │   ├── input.blade.php
│   │   ├── select.blade.php
│   │   ├── textarea.blade.php
│   │   └── checkbox.blade.php
│   │
│   └── ui/                        # UI components
│       ├── button.blade.php
│       ├── card.blade.php
│       ├── modal.blade.php
│       ├── alert.blade.php
│       └── badge.blade.php
│
├── campaigns/                      # Campaign views
│   ├── index.blade.php            # Campaign list
│   ├── show.blade.php             # Campaign details
│   ├── create.blade.php           # Create campaign
│   ├── edit.blade.php             # Edit campaign
│   └── wizard/                    # Campaign creation wizard
│       ├── index.blade.php
│       └── steps/
│           ├── basic-info.blade.php
│           ├── targeting.blade.php
│           └── budget.blade.php
│
├── dashboard/                      # Dashboard views
│   └── analytics.blade.php        # Analytics dashboard
│
├── analytics/                      # Analytics views
│   ├── index.blade.php
│   └── reports.blade.php
│
├── content/                        # Content management
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
│
├── creative/                       # Creative assets
│   ├── index.blade.php
│   ├── templates.blade.php
│   └── ads.blade.php
│
├── social/                         # Social media
│   ├── posts.blade.php
│   └── scheduler.blade.php
│
├── integrations/                   # Platform integrations
│   ├── index.blade.php
│   └── show.blade.php
│
├── admin/                          # Admin views
│   ├── dashboard.blade.php
│   └── features/
│       └── index.blade.php
│
├── auth/                           # Authentication views
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── forgot-password.blade.php
│   └── reset-password.blade.php
│
├── automation/                     # Automation views
│   ├── rules.blade.php
│   └── optimization.blade.php
│
├── core/                           # Core system views
│   ├── index.blade.php
│   └── orgs/
│       └── index.blade.php
│
├── onboarding/                     # User onboarding
│   ├── index.blade.php
│   └── step.blade.php
│
├── assets/                         # Media assets
│   ├── index.blade.php
│   ├── upload.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
│
├── channels/                       # Marketing channels
│   └── index.blade.php
│
├── offerings/                      # Product offerings
│   ├── index.blade.php
│   └── list.blade.php
│
├── bundles/                        # Service bundles
│   └── index.blade.php
│
├── briefs/                         # Campaign briefs
│   └── index.blade.php
│
├── ai/                             # AI features
│   └── semantic-search.blade.php
│
├── exports/                        # Export templates
│   └── compare_pdf.blade.php      # PDF comparison export
│
├── vendor/                         # Third-party views
│   └── l5-swagger/
│       └── index.blade.php        # Swagger UI
│
└── welcome.blade.php              # Landing page
```

## 3. Key Files & Entry Points (الملفات الأساسية ونقاط الدخول)

### Main Layouts

#### App Layout (`layouts/app.blade.php`)
**Purpose**: Main authenticated user layout

**Key Features**:
- Navigation header
- Sidebar (if applicable)
- Flash messages
- Footer
- Alpine.js initialization
- Tailwind CSS

**Structure**:
```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('partials.header')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')
</body>
</html>
```

#### Guest Layout (`layouts/guest.blade.php`)
**Purpose**: Layout for unauthenticated pages (login, register)

### Welcome Page (`welcome.blade.php`)
**Purpose**: Landing page with authentication links

**Features**:
- Responsive design
- Dark mode support
- Tailwind CSS styling
- Laravel Vite integration

### Campaign Views

#### Campaign List (`campaigns/index.blade.php`)
**Purpose**: Display all campaigns with filters and search

**Features**:
- Data table with campaigns
- Status filters
- Search functionality
- Pagination
- Alpine.js for interactive UI

#### Campaign Wizard (`campaigns/wizard/`)
**Purpose**: Multi-step campaign creation wizard

**Steps**:
1. `basic-info.blade.php`: Campaign name, description, type
2. `targeting.blade.php`: Audience targeting
3. `budget.blade.php`: Budget allocation

### Dashboard (`dashboard/analytics.blade.php`)
**Purpose**: Main analytics dashboard

**Alpine.js Components Used**:
```blade
<div x-data="campaignDashboard()" x-init="init()">
    <div x-html="renderDashboard()"></div>
</div>
```

### Reusable Components (`components/`)

#### Button Component (`components/ui/button.blade.php`)
```blade
@props([
    'type' => 'button',
    'variant' => 'primary',
])

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'px-4 py-2 rounded-md font-medium transition-colors ' . match($variant) {
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700',
        'secondary' => 'bg-gray-200 text-gray-900 hover:bg-gray-300',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        default => 'bg-blue-600 text-white hover:bg-blue-700'
    }]) }}
>
    {{ $slot }}
</button>
```

**Usage**:
```blade
<x-ui.button variant="primary">Save Campaign</x-ui.button>
<x-ui.button variant="danger" @click="deleteCampaign()">Delete</x-ui.button>
```

#### Form Input (`components/forms/input.blade.php`)
```blade
@props([
    'label' => '',
    'name' => '',
    'type' => 'text',
    'required' => false,
])

<div class="mb-4">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $attributes->merge(['class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500']) }}
    />

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
```

**Usage**:
```blade
<x-forms.input
    label="Campaign Name"
    name="name"
    type="text"
    required
    value="{{ old('name') }}"
/>
```

## 4. Dependencies & Interfaces (التبعيات والواجهات)

### Template Engine
- **Blade**: Laravel's templating engine
- **Directives**: `@if`, `@foreach`, `@yield`, `@section`, `@include`, `@extends`
- **Components**: `<x-component-name />`

### Frontend Dependencies
- **Tailwind CSS**: Utility-first CSS framework
- **Alpine.js**: Reactive JavaScript framework
- **Chart.js**: Charts via Alpine.js components
- **Vite**: Asset bundler

### Backend Integration
```
Routes → Controllers → Views
   ↓
Blade compiles to PHP
   ↓
Returns HTML to browser
```

## 5. Local Rules / Patterns (القواعد المحلية والأنماط)

### Blade Template Pattern

#### ✅ Standard View Structure
```blade
@extends('layouts.app')

@section('title', 'Page Title')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">{{ $title }}</h1>

        <!-- Content -->
        @if($items->count() > 0)
            @foreach($items as $item)
                <div class="mb-4">
                    {{ $item->name }}
                </div>
            @endforeach
        @else
            <p class="text-gray-600">No items found.</p>
        @endif
    </div>
@endsection
```

### Component Pattern

#### ✅ Creating Blade Components
```blade
<!-- resources/views/components/alert.blade.php -->
@props(['type' => 'info'])

<div {{ $attributes->merge(['class' => 'p-4 rounded-md ' . match($type) {
    'success' => 'bg-green-50 text-green-800 border border-green-200',
    'error' => 'bg-red-50 text-red-800 border border-red-200',
    'warning' => 'bg-yellow-50 text-yellow-800 border border-yellow-200',
    'info' => 'bg-blue-50 text-blue-800 border border-blue-200',
    default => 'bg-gray-50 text-gray-800 border border-gray-200'
}]) }}>
    {{ $slot }}
</div>
```

**Usage**:
```blade
<x-alert type="success">Campaign created successfully!</x-alert>
<x-alert type="error">Failed to save campaign.</x-alert>
```

### Alpine.js Integration Pattern

```blade
<div x-data="{ open: false }">
    <button @click="open = !open" class="btn-primary">
        Toggle
    </button>

    <div x-show="open" x-transition class="mt-4">
        Content revealed!
    </div>
</div>

<!-- Using imported component -->
<div x-data="campaignDashboard()" x-init="init()">
    <template x-if="isLoading">
        <div class="animate-spin">Loading...</div>
    </template>

    <template x-if="!isLoading && data">
        <div x-html="renderDashboard()"></div>
    </template>
</div>
```

### Form Validation Pattern

```blade
<form method="POST" action="{{ route('campaigns.store') }}">
    @csrf

    <x-forms.input
        label="Campaign Name"
        name="name"
        type="text"
        required
        value="{{ old('name') }}"
    />

    <x-forms.textarea
        label="Description"
        name="description"
        rows="4"
        value="{{ old('description') }}"
    />

    <x-forms.select
        label="Status"
        name="status"
        :options="['draft' => 'Draft', 'active' => 'Active', 'paused' => 'Paused']"
        value="{{ old('status', 'draft') }}"
    />

    <div class="flex justify-end space-x-4 rtl:space-x-reverse">
        <x-ui.button variant="secondary" type="button" @click="window.history.back()">
            Cancel
        </x-ui.button>
        <x-ui.button variant="primary" type="submit">
            Create Campaign
        </x-ui.button>
    </div>
</form>

@if($errors->any())
    <x-alert type="error" class="mt-4">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
```

## 6. How to Run / Test (كيفية التشغيل والاختبار)

### Viewing Templates

```bash
# Start Laravel dev server
php artisan serve

# Visit in browser
http://localhost:8000

# Compile assets with Vite (hot reload)
npm run dev
```

### Testing Views

```bash
# Clear view cache
php artisan view:clear

# Compile views
php artisan view:cache

# Test specific route
curl http://localhost:8000/campaigns
```

### View Testing (PHPUnit)

```php
// tests/Feature/ViewTest.php
public function test_campaigns_index_view()
{
    $response = $this->get('/campaigns');

    $response->assertStatus(200);
    $response->assertViewIs('campaigns.index');
    $response->assertViewHas('campaigns');
}

public function test_campaign_show_view()
{
    $campaign = Campaign::factory()->create();

    $response = $this->get("/campaigns/{$campaign->id}");

    $response->assertStatus(200);
    $response->assertSee($campaign->name);
}
```

## 7. Common Tasks for Agents (المهام الشائعة للوكلاء)

### Create New View

```bash
# Create view file
touch resources/views/your-module/your-view.blade.php
```

```blade
@extends('layouts.app')

@section('title', 'Your Page Title')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Your Content</h1>

        <!-- Your content here -->
    </div>
@endsection
```

### Create Blade Component

```bash
# Create component
php artisan make:component YourComponent

# This creates:
# - app/View/Components/YourComponent.php
# - resources/views/components/your-component.blade.php
```

```php
// app/View/Components/YourComponent.php
namespace App\View\Components;

use Illuminate\View\Component;

class YourComponent extends Component
{
    public function __construct(
        public string $title,
        public string $type = 'default'
    ) {}

    public function render()
    {
        return view('components.your-component');
    }
}
```

```blade
<!-- resources/views/components/your-component.blade.php -->
<div class="component-wrapper">
    <h3>{{ $title }}</h3>
    <div class="content-{{ $type }}">
        {{ $slot }}
    </div>
</div>
```

**Usage**:
```blade
<x-your-component title="My Component" type="primary">
    Component content goes here
</x-your-component>
```

### Add Alpine.js Component to View

```blade
@extends('layouts.app')

@section('content')
    <!-- Include Alpine.js component -->
    <div x-data="campaignAnalytics()" x-init="init()">
        <!-- Loading state -->
        <template x-if="isLoading">
            <div class="flex justify-center p-8">
                <svg class="animate-spin h-8 w-8 text-blue-500" ...></svg>
            </div>
        </template>

        <!-- Content -->
        <template x-if="!isLoading">
            <div x-html="renderContent()"></div>
        </template>
    </div>
@endsection

@push('scripts')
    <script type="module">
        import { campaignAnalytics } from '/resources/js/components/index.js';
        window.campaignAnalytics = campaignAnalytics;
    </script>
@endpush
```

## 8. Notes / Gotchas (ملاحظات ومحاذير)

### ⚠️ Common Mistakes

1. **Forgetting CSRF Token**
   ```blade
   ❌ <form method="POST">

   ✅ <form method="POST">
       @csrf
   </form>
   ```

2. **Not Escaping Output**
   ```blade
   ❌ {!! $userInput !!}  <!-- XSS vulnerability -->

   ✅ {{ $userInput }}     <!-- Auto-escaped -->
   ```

3. **Missing Old Values on Validation Errors**
   ```blade
   ❌ <input name="name" value="{{ $campaign->name }}">

   ✅ <input name="name" value="{{ old('name', $campaign->name) }}">
   ```

4. **Hardcoded RTL/LTR**
   ```blade
   ❌ <div class="ml-4">  <!-- Always left margin -->

   ✅ <div class="ms-4 rtl:mr-4">  <!-- Margin start (respects direction) -->
   ```

### 🎯 Best Practices

1. **Use Blade Components**
   - Create reusable components for repeated UI patterns
   - Use `@props` for component properties
   - Leverage slots for flexible content

2. **Flash Messages Pattern**
   ```blade
   <!-- In layout -->
   @if(session('success'))
       <x-alert type="success">{{ session('success') }}</x-alert>
   @endif

   @if(session('error'))
       <x-alert type="error">{{ session('error') }}</x-alert>
   @endif
   ```

   ```php
   // In controller
   return redirect()->route('campaigns.index')
       ->with('success', 'Campaign created successfully!');
   ```

3. **Responsive Design with Tailwind**
   ```blade
   <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
       <!-- Responsive grid -->
   </div>
   ```

4. **Dark Mode Support**
   ```blade
   <div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
       Content with dark mode support
   </div>
   ```

5. **RTL Support**
   ```blade
   <div class="flex space-x-4 rtl:space-x-reverse">
       <button>First</button>
       <button>Second</button>
   </div>
   ```

### 📊 Statistics

- **Total Views**: 100+ Blade templates
- **Components**: 20+ reusable components
- **Layouts**: 2 (app, guest)
- **Modules**: 20+ feature modules
- **Alpine.js Integration**: 10+ interactive components

### 🔗 Related Files

- **JavaScript Components**: `resources/js/components/` - Alpine.js components
- **Stylesheets**: `resources/css/app.css` - Tailwind CSS
- **Controllers**: `app/Http/Controllers/` - View data providers
- **Routes**: `routes/web.php` - Route definitions

### 🚀 Performance Tips

1. **Cache Views in Production**
   ```bash
   php artisan view:cache
   ```

2. **Minimize Inline Styles**
   - Use Tailwind classes instead of inline styles
   - Extract repeated classes to components

3. **Lazy Load Heavy Content**
   ```blade
   <template x-if="showHeavyContent">
       <!-- Only rendered when needed -->
   </template>
   ```

4. **Optimize Images**
   ```blade
   <img src="{{ asset('images/logo.png') }}"
        alt="Logo"
        loading="lazy"
        class="w-32 h-32 object-contain">
   ```

### 🎨 Tailwind CSS Utilities

Common patterns used in views:
- **Layout**: `container`, `mx-auto`, `px-4`, `py-8`
- **Grid**: `grid`, `grid-cols-3`, `gap-6`
- **Flex**: `flex`, `items-center`, `justify-between`, `space-x-4`
- **Typography**: `text-3xl`, `font-bold`, `text-gray-600`
- **Colors**: `bg-blue-600`, `text-white`, `border-gray-300`
- **Responsive**: `md:grid-cols-2`, `lg:flex-row`
- **States**: `hover:bg-blue-700`, `focus:ring-blue-500`
- **RTL**: `rtl:space-x-reverse`, `rtl:text-right`
