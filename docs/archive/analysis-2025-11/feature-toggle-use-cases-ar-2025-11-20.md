# أمثلة عملية لنظام Feature Toggle في CMIS
**التاريخ:** 2025-11-20
**الهدف:** أمثلة محددة لحالات الاستخدام المطلوبة

---

## 🎯 الحالات المطلوبة

### حالة 1: تشغيل الجدولة لمنصة Meta و TikTok فقط
### حالة 2: تشغيل إدارة الحملات الممولة لـ Meta فقط
### حالة 3: إخفاء الميزات من الـ UI بناءً على الـ Feature Flags

---

## 📋 تصميم Feature Flags المطلوبة

### هيكل الـ Feature Flags

نحتاج إلى **Composite Feature Keys** لتوفير تحكم دقيق:

```
Format: {feature_category}.{platform}.{sub_feature}

Examples:
- scheduling.meta.enabled
- scheduling.tiktok.enabled
- scheduling.google.enabled
- paid_campaigns.meta.enabled
- paid_campaigns.google.enabled
- organic_posts.facebook.enabled
```

### جدول Feature Flags المقترحة

| Feature Key | Description (AR) | Description (EN) | Default |
|-------------|------------------|------------------|---------|
| `scheduling.meta.enabled` | تفعيل جدولة المنشورات لـ Meta | Enable post scheduling for Meta | `false` |
| `scheduling.tiktok.enabled` | تفعيل جدولة المنشورات لـ TikTok | Enable post scheduling for TikTok | `false` |
| `scheduling.google.enabled` | تفعيل جدولة المنشورات لـ Google | Enable post scheduling for Google | `false` |
| `scheduling.linkedin.enabled` | تفعيل جدولة المنشورات لـ LinkedIn | Enable post scheduling for LinkedIn | `false` |
| `scheduling.twitter.enabled` | تفعيل جدولة المنشورات لـ Twitter | Enable post scheduling for Twitter | `false` |
| `scheduling.snapchat.enabled` | تفعيل جدولة المنشورات لـ Snapchat | Enable post scheduling for Snapchat | `false` |
| `paid_campaigns.meta.enabled` | تفعيل إدارة الحملات الممولة لـ Meta | Enable paid campaign management for Meta | `false` |
| `paid_campaigns.google.enabled` | تفعيل إدارة الحملات الممولة لـ Google | Enable paid campaign management for Google | `false` |
| `paid_campaigns.tiktok.enabled` | تفعيل إدارة الحملات الممولة لـ TikTok | Enable paid campaign management for TikTok | `false` |
| `paid_campaigns.linkedin.enabled` | تفعيل إدارة الحملات الممولة لـ LinkedIn | Enable paid campaign management for LinkedIn | `false` |
| `paid_campaigns.twitter.enabled` | تفعيل إدارة الحملات الممولة لـ Twitter | Enable paid campaign management for Twitter | `false` |
| `paid_campaigns.snapchat.enabled` | تفعيل إدارة الحملات الممولة لـ Snapchat | Enable paid campaign management for Snapchat | `false` |

---

## 💻 أمثلة الكود

### 1️⃣ مثال: تشغيل الجدولة لـ Meta و TikTok فقط

#### أ) تفعيل الـ Flags في الـ Database

```php
// database/seeders/InitialFeatureFlagsSeeder.php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InitialFeatureFlagsSeeder extends Seeder
{
    public function run()
    {
        $systemLevelFlags = [
            // تفعيل الجدولة لـ Meta و TikTok فقط
            [
                'feature_key' => 'scheduling.meta.enabled',
                'scope_type' => 'system',
                'scope_id' => null,
                'value' => true,
                'description' => 'Enable post scheduling for Meta platforms',
            ],
            [
                'feature_key' => 'scheduling.tiktok.enabled',
                'scope_type' => 'system',
                'scope_id' => null,
                'value' => true,
                'description' => 'Enable post scheduling for TikTok',
            ],
            // باقي المنصات مغلقة
            [
                'feature_key' => 'scheduling.google.enabled',
                'scope_type' => 'system',
                'scope_id' => null,
                'value' => false,
                'description' => 'Enable post scheduling for Google',
            ],
            [
                'feature_key' => 'scheduling.linkedin.enabled',
                'scope_type' => 'system',
                'scope_id' => null,
                'value' => false,
                'description' => 'Enable post scheduling for LinkedIn',
            ],
            // إدارة الحملات الممولة: Meta فقط
            [
                'feature_key' => 'paid_campaigns.meta.enabled',
                'scope_type' => 'system',
                'scope_id' => null,
                'value' => true,
                'description' => 'Enable paid campaign management for Meta',
            ],
            [
                'feature_key' => 'paid_campaigns.google.enabled',
                'scope_type' => 'system',
                'scope_id' => null,
                'value' => false,
                'description' => 'Enable paid campaign management for Google',
            ],
            [
                'feature_key' => 'paid_campaigns.tiktok.enabled',
                'scope_type' => 'system',
                'scope_id' => null,
                'value' => false,
                'description' => 'Enable paid campaign management for TikTok',
            ],
        ];

        foreach ($systemLevelFlags as $flag) {
            DB::table('cmis.feature_flags')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'feature_key' => $flag['feature_key'],
                'scope_type' => $flag['scope_type'],
                'scope_id' => $flag['scope_id'],
                'value' => $flag['value'],
                'description' => $flag['description'],
                'metadata' => json_encode(['configured_at' => now()]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
```

#### ب) استخدام الـ Flags في الكود

```php
// app/Services/Social/PostSchedulingService.php

namespace App\Services\Social;

use App\Services\FeatureToggle\FeatureFlagService;
use App\Exceptions\FeatureDisabledException;

class PostSchedulingService
{
    public function __construct(
        private FeatureFlagService $featureFlags
    ) {}

    public function schedulePost(
        string $platform,
        array $postData,
        \DateTimeInterface $scheduledAt
    ) {
        // التحقق من تفعيل الجدولة للمنصة المطلوبة
        $featureKey = "scheduling.{$platform}.enabled";

        if (!$this->featureFlags->isEnabled($featureKey)) {
            throw new FeatureDisabledException(
                "Post scheduling is not available for {$platform} platform"
            );
        }

        // تنفيذ الجدولة
        return $this->performScheduling($platform, $postData, $scheduledAt);
    }

    public function getAvailablePlatformsForScheduling(): array
    {
        $platforms = ['meta', 'tiktok', 'google', 'linkedin', 'twitter', 'snapchat'];
        $available = [];

        foreach ($platforms as $platform) {
            if ($this->featureFlags->isEnabled("scheduling.{$platform}.enabled")) {
                $available[] = $platform;
            }
        }

        return $available; // سيرجع: ['meta', 'tiktok']
    }
}
```

---

### 2️⃣ مثال: إدارة الحملات الممولة لـ Meta فقط

#### أ) في الـ Controller

```php
// app/Http/Controllers/Campaign/PaidCampaignController.php

namespace App\Http\Controllers\Campaign;

use App\Http\Controllers\Controller;
use App\Services\FeatureToggle\FeatureFlagService;
use Illuminate\Http\Request;

class PaidCampaignController extends Controller
{
    public function __construct(
        private FeatureFlagService $featureFlags
    ) {}

    public function create(Request $request)
    {
        $platform = $request->input('platform'); // 'meta', 'google', etc.

        // التحقق من تفعيل الحملات الممولة للمنصة
        if (!$this->featureFlags->isEnabled("paid_campaigns.{$platform}.enabled")) {
            return response()->json([
                'error' => 'Paid campaigns are not available for this platform',
                'platform' => $platform,
                'available_platforms' => $this->getAvailablePlatforms()
            ], 403);
        }

        // إنشاء الحملة
        return $this->processCampaignCreation($request);
    }

    private function getAvailablePlatforms(): array
    {
        $platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
        return array_filter($platforms, function($platform) {
            return $this->featureFlags->isEnabled("paid_campaigns.{$platform}.enabled");
        });
    }
}
```

#### ب) في الـ Middleware

```php
// app/Http/Middleware/CheckPlatformFeatureEnabled.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\FeatureToggle\FeatureFlagService;

class CheckPlatformFeatureEnabled
{
    public function __construct(
        private FeatureFlagService $featureFlags
    ) {}

    public function handle(Request $request, Closure $next, string $featureCategory)
    {
        // استخراج المنصة من الـ Route
        $platform = $request->route('platform');

        if (!$platform) {
            return response()->json(['error' => 'Platform not specified'], 400);
        }

        // التحقق من تفعيل الميزة
        $featureKey = "{$featureCategory}.{$platform}.enabled";

        if (!$this->featureFlags->isEnabled($featureKey)) {
            return response()->json([
                'error' => "Feature '{$featureCategory}' is not enabled for platform '{$platform}'",
                'feature' => $featureCategory,
                'platform' => $platform,
            ], 403);
        }

        return $next($request);
    }
}
```

**استخدام الـ Middleware في Routes:**

```php
// routes/api.php

use App\Http\Middleware\CheckPlatformFeatureEnabled;

Route::prefix('campaigns/{platform}')->group(function () {
    // الحملات الممولة - يتطلب التحقق من التفعيل
    Route::post('paid-campaigns', [PaidCampaignController::class, 'create'])
        ->middleware(['auth', CheckPlatformFeatureEnabled::class . ':paid_campaigns']);

    Route::get('paid-campaigns', [PaidCampaignController::class, 'index'])
        ->middleware(['auth', CheckPlatformFeatureEnabled::class . ':paid_campaigns']);
});

Route::prefix('social/{platform}')->group(function () {
    // جدولة المنشورات - يتطلب التحقق من التفعيل
    Route::post('schedule', [PostSchedulingController::class, 'schedule'])
        ->middleware(['auth', CheckPlatformFeatureEnabled::class . ':scheduling']);
});
```

---

### 3️⃣ مثال: إخفاء الميزات من الـ UI

#### أ) في الـ Blade Templates

```blade
{{-- resources/views/campaigns/create.blade.php --}}

<div class="platform-selector">
    <h3>اختر المنصة</h3>

    {{-- Meta - متاح --}}
    @if(app('feature.flags')->isEnabled('paid_campaigns.meta.enabled'))
    <div class="platform-card available">
        <img src="/images/meta-logo.png" alt="Meta">
        <h4>Meta Ads</h4>
        <button onclick="selectPlatform('meta')">اختر Meta</button>
    </div>
    @else
    <div class="platform-card disabled">
        <img src="/images/meta-logo.png" alt="Meta" class="grayscale">
        <h4>Meta Ads</h4>
        <span class="coming-soon">قريباً</span>
    </div>
    @endif

    {{-- Google - غير متاح --}}
    @if(app('feature.flags')->isEnabled('paid_campaigns.google.enabled'))
    <div class="platform-card available">
        <img src="/images/google-logo.png" alt="Google">
        <h4>Google Ads</h4>
        <button onclick="selectPlatform('google')">اختر Google</button>
    </div>
    @else
    <div class="platform-card disabled">
        <img src="/images/google-logo.png" alt="Google" class="grayscale">
        <h4>Google Ads</h4>
        <span class="coming-soon">قريباً</span>
    </div>
    @endif

    {{-- TikTok - غير متاح --}}
    @if(app('feature.flags')->isEnabled('paid_campaigns.tiktok.enabled'))
    <div class="platform-card available">
        <img src="/images/tiktok-logo.png" alt="TikTok">
        <h4>TikTok Ads</h4>
        <button onclick="selectPlatform('tiktok')">اختر TikTok</button>
    </div>
    @else
    <div class="platform-card disabled">
        <img src="/images/tiktok-logo.png" alt="TikTok" class="grayscale">
        <h4>TikTok Ads</h4>
        <span class="coming-soon">قريباً</span>
    </div>
    @endif
</div>
```

#### ب) في Alpine.js Components

```html
<!-- resources/views/dashboard/platforms.blade.php -->

<div x-data="platformDashboard()">
    <!-- عرض المنصات المتاحة فقط -->
    <div class="platforms-grid">
        <template x-for="platform in availablePlatforms" :key="platform.key">
            <div class="platform-card"
                 :class="{ 'active': platform.enabled, 'disabled': !platform.enabled }"
                 @click="platform.enabled && selectPlatform(platform.key)">

                <img :src="`/images/${platform.key}-logo.png`" :alt="platform.name">
                <h3 x-text="platform.name"></h3>

                <!-- عرض الميزات المتاحة -->
                <div class="features" x-show="platform.enabled">
                    <span x-show="platform.features.scheduling" class="badge badge-success">
                        جدولة المنشورات
                    </span>
                    <span x-show="platform.features.paidCampaigns" class="badge badge-primary">
                        الحملات الممولة
                    </span>
                    <span x-show="platform.features.analytics" class="badge badge-info">
                        التحليلات
                    </span>
                </div>

                <!-- عرض "قريباً" للميزات المغلقة -->
                <div x-show="!platform.enabled" class="coming-soon-overlay">
                    <span>قريباً</span>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function platformDashboard() {
    return {
        platforms: @json($platforms), // Data from backend
        availablePlatforms: [],

        init() {
            // فلترة المنصات بناءً على Feature Flags
            this.loadAvailablePlatforms();
        },

        async loadAvailablePlatforms() {
            try {
                const response = await fetch('/api/features/available-platforms');
                const data = await response.json();
                this.availablePlatforms = data.platforms;
            } catch (error) {
                console.error('Failed to load available platforms:', error);
            }
        },

        selectPlatform(platformKey) {
            window.location.href = `/campaigns/create?platform=${platformKey}`;
        }
    }
}
</script>
```

#### ج) API Endpoint للـ Frontend

```php
// app/Http/Controllers/Api/FeatureController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeatureToggle\FeatureFlagService;

class FeatureController extends Controller
{
    public function __construct(
        private FeatureFlagService $featureFlags
    ) {}

    public function getAvailablePlatforms()
    {
        $platforms = [
            'meta' => ['name' => 'Meta', 'logo' => 'meta-logo.png'],
            'google' => ['name' => 'Google', 'logo' => 'google-logo.png'],
            'tiktok' => ['name' => 'TikTok', 'logo' => 'tiktok-logo.png'],
            'linkedin' => ['name' => 'LinkedIn', 'logo' => 'linkedin-logo.png'],
            'twitter' => ['name' => 'Twitter', 'logo' => 'twitter-logo.png'],
            'snapchat' => ['name' => 'Snapchat', 'logo' => 'snapchat-logo.png'],
        ];

        $availablePlatforms = [];

        foreach ($platforms as $key => $info) {
            $availablePlatforms[] = [
                'key' => $key,
                'name' => $info['name'],
                'logo' => $info['logo'],
                'enabled' => $this->isPlatformEnabled($key),
                'features' => [
                    'scheduling' => $this->featureFlags->isEnabled("scheduling.{$key}.enabled"),
                    'paidCampaigns' => $this->featureFlags->isEnabled("paid_campaigns.{$key}.enabled"),
                    'analytics' => $this->featureFlags->isEnabled("analytics.{$key}.enabled"),
                ],
            ];
        }

        return response()->json([
            'platforms' => $availablePlatforms,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function isPlatformEnabled(string $platform): bool
    {
        // المنصة متاحة إذا كانت أي ميزة مفعلة
        return $this->featureFlags->isEnabled("scheduling.{$platform}.enabled")
            || $this->featureFlags->isEnabled("paid_campaigns.{$platform}.enabled")
            || $this->featureFlags->isEnabled("analytics.{$platform}.enabled");
    }

    public function getFeatureMatrix()
    {
        // جدول كامل للميزات والمنصات
        $platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
        $features = ['scheduling', 'paid_campaigns', 'analytics', 'organic_posts'];

        $matrix = [];

        foreach ($features as $feature) {
            $matrix[$feature] = [];
            foreach ($platforms as $platform) {
                $matrix[$feature][$platform] = $this->featureFlags->isEnabled(
                    "{$feature}.{$platform}.enabled"
                );
            }
        }

        return response()->json([
            'matrix' => $matrix,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

#### د) Route للـ API

```php
// routes/api.php

Route::prefix('features')->group(function () {
    Route::get('available-platforms', [FeatureController::class, 'getAvailablePlatforms']);
    Route::get('matrix', [FeatureController::class, 'getFeatureMatrix']);
});
```

---

## 🎨 أمثلة CSS للـ UI

```css
/* public/css/feature-toggle.css */

.platform-card {
    position: relative;
    padding: 20px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.platform-card.available {
    cursor: pointer;
    border-color: #10b981;
}

.platform-card.available:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
}

.platform-card.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background-color: #f9fafb;
}

.platform-card img.grayscale {
    filter: grayscale(100%);
}

.coming-soon {
    display: inline-block;
    padding: 4px 12px;
    background-color: #fbbf24;
    color: #78350f;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 8px;
}

.coming-soon-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(0, 0, 0, 0.5);
    border-radius: 8px;
}

.coming-soon-overlay span {
    background-color: #fbbf24;
    color: #78350f;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 14px;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    margin: 2px;
}

.badge-success {
    background-color: #d1fae5;
    color: #065f46;
}

.badge-primary {
    background-color: #dbeafe;
    color: #1e40af;
}

.badge-info {
    background-color: #e0e7ff;
    color: #3730a3;
}
```

---

## 🔧 Admin Panel للإدارة

```php
// app/Http/Controllers/Admin/FeatureFlagController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FeatureToggle\FeatureFlagService;
use Illuminate\Http\Request;

class FeatureFlagController extends Controller
{
    public function __construct(
        private FeatureFlagService $featureFlags
    ) {
        $this->middleware(['auth', 'admin']); // فقط للمدراء
    }

    public function index()
    {
        $platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
        $features = ['scheduling', 'paid_campaigns', 'analytics', 'organic_posts'];

        $matrix = [];

        foreach ($features as $feature) {
            foreach ($platforms as $platform) {
                $key = "{$feature}.{$platform}.enabled";
                $matrix[$feature][$platform] = [
                    'key' => $key,
                    'enabled' => $this->featureFlags->isEnabled($key),
                ];
            }
        }

        return view('admin.features.index', compact('matrix', 'platforms', 'features'));
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'feature_key' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        $this->featureFlags->set(
            $request->input('feature_key'),
            $request->boolean('enabled')
        );

        return response()->json([
            'success' => true,
            'message' => 'Feature flag updated successfully',
            'feature_key' => $request->input('feature_key'),
            'enabled' => $request->boolean('enabled'),
        ]);
    }

    public function bulkToggle(Request $request)
    {
        // تفعيل/إلغاء تفعيل مجموعة من الـ flags دفعة واحدة
        $request->validate([
            'features' => 'required|array',
            'features.*.key' => 'required|string',
            'features.*.enabled' => 'required|boolean',
        ]);

        foreach ($request->input('features') as $feature) {
            $this->featureFlags->set($feature['key'], $feature['enabled']);
        }

        return response()->json([
            'success' => true,
            'message' => count($request->input('features')) . ' features updated',
        ]);
    }
}
```

**Blade View للـ Admin Panel:**

```blade
{{-- resources/views/admin/features/index.blade.php --}}

@extends('layouts.admin')

@section('content')
<div class="container" x-data="featureManager()">
    <h1>إدارة الميزات والمنصات</h1>

    <!-- Feature Matrix Table -->
    <div class="feature-matrix">
        <table class="table">
            <thead>
                <tr>
                    <th>الميزة / Feature</th>
                    @foreach($platforms as $platform)
                    <th class="text-center">{{ ucfirst($platform) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($features as $feature)
                <tr>
                    <td><strong>{{ ucfirst(str_replace('_', ' ', $feature)) }}</strong></td>
                    @foreach($platforms as $platform)
                    <td class="text-center">
                        <label class="toggle-switch">
                            <input type="checkbox"
                                   :checked="isEnabled('{{ $feature }}', '{{ $platform }}')"
                                   @change="toggleFeature('{{ $feature }}.{{ $platform }}.enabled', $event.target.checked)">
                            <span class="slider"></span>
                        </label>
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Quick Presets -->
    <div class="presets mt-4">
        <h3>إعدادات سريعة (Presets)</h3>
        <div class="preset-buttons">
            <button @click="applyPreset('launch')" class="btn btn-primary">
                إعداد الإطلاق الأولي (Meta + TikTok Scheduling, Meta Paid Campaigns)
            </button>
            <button @click="applyPreset('all-scheduling')" class="btn btn-secondary">
                تفعيل الجدولة لكل المنصات
            </button>
            <button @click="applyPreset('all-paid')" class="btn btn-secondary">
                تفعيل الحملات الممولة لكل المنصات
            </button>
            <button @click="applyPreset('disable-all')" class="btn btn-danger">
                إيقاف كل الميزات
            </button>
        </div>
    </div>

    <!-- Save Changes Button -->
    <div class="actions mt-4">
        <button @click="saveChanges" class="btn btn-success btn-lg" x-show="hasChanges">
            حفظ التغييرات
        </button>
        <span x-show="!hasChanges" class="text-muted">لا توجد تغييرات</span>
    </div>
</div>

<script>
function featureManager() {
    return {
        matrix: @json($matrix),
        changes: [],
        hasChanges: false,

        init() {
            console.log('Feature Manager initialized');
        },

        isEnabled(feature, platform) {
            return this.matrix[feature]?.[platform]?.enabled || false;
        },

        toggleFeature(key, enabled) {
            // تسجيل التغيير
            const existingIndex = this.changes.findIndex(c => c.key === key);
            if (existingIndex >= 0) {
                this.changes[existingIndex].enabled = enabled;
            } else {
                this.changes.push({ key, enabled });
            }
            this.hasChanges = this.changes.length > 0;
        },

        async saveChanges() {
            if (this.changes.length === 0) return;

            try {
                const response = await fetch('/admin/features/bulk-toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        features: this.changes
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('تم حفظ التغييرات بنجاح');
                    this.changes = [];
                    this.hasChanges = false;
                    location.reload(); // إعادة تحميل لعرض القيم الجديدة
                }
            } catch (error) {
                alert('فشل حفظ التغييرات: ' + error.message);
            }
        },

        applyPreset(presetName) {
            this.changes = [];

            if (presetName === 'launch') {
                // الإعداد المطلوب: جدولة Meta و TikTok، حملات Meta الممولة فقط
                this.changes = [
                    { key: 'scheduling.meta.enabled', enabled: true },
                    { key: 'scheduling.tiktok.enabled', enabled: true },
                    { key: 'scheduling.google.enabled', enabled: false },
                    { key: 'scheduling.linkedin.enabled', enabled: false },
                    { key: 'scheduling.twitter.enabled', enabled: false },
                    { key: 'scheduling.snapchat.enabled', enabled: false },
                    { key: 'paid_campaigns.meta.enabled', enabled: true },
                    { key: 'paid_campaigns.google.enabled', enabled: false },
                    { key: 'paid_campaigns.tiktok.enabled', enabled: false },
                    { key: 'paid_campaigns.linkedin.enabled', enabled: false },
                    { key: 'paid_campaigns.twitter.enabled', enabled: false },
                    { key: 'paid_campaigns.snapchat.enabled', enabled: false },
                ];
            } else if (presetName === 'all-scheduling') {
                // تفعيل الجدولة لكل المنصات
                const platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
                platforms.forEach(platform => {
                    this.changes.push({ key: `scheduling.${platform}.enabled`, enabled: true });
                });
            } else if (presetName === 'all-paid') {
                // تفعيل الحملات الممولة لكل المنصات
                const platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
                platforms.forEach(platform => {
                    this.changes.push({ key: `paid_campaigns.${platform}.enabled`, enabled: true });
                });
            } else if (presetName === 'disable-all') {
                // إيقاف كل شيء
                const platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
                const features = ['scheduling', 'paid_campaigns', 'analytics', 'organic_posts'];
                features.forEach(feature => {
                    platforms.forEach(platform => {
                        this.changes.push({ key: `${feature}.${platform}.enabled`, enabled: false });
                    });
                });
            }

            this.hasChanges = true;
            alert(`تم تطبيق الإعداد: ${presetName}. اضغط "حفظ التغييرات" لتأكيد.`);
        }
    }
}
</script>
@endsection
```

---

## 📊 مثال على النتيجة النهائية

### بعد تطبيق الإعداد الأولي:

#### ✅ المنصات المتاحة (Enabled):
- **Meta**: جدولة المنشورات ✓ | الحملات الممولة ✓
- **TikTok**: جدولة المنشورات ✓

#### ❌ المنصات غير المتاحة (Disabled):
- **Google**: جدولة المنشورات ✗ | الحملات الممولة ✗
- **LinkedIn**: جدولة المنشورات ✗ | الحملات الممولة ✗
- **Twitter**: جدولة المنشورات ✗ | الحملات الممولة ✗
- **Snapchat**: جدولة المنشورات ✗ | الحملات الممولة ✗

### تجربة المستخدم في الـ UI:

1. **لوحة التحكم**: يرى المستخدم فقط Meta و TikTok كمنصات نشطة
2. **إنشاء حملة ممولة**: يظهر Meta فقط كخيار متاح، باقي المنصات تظهر "قريباً"
3. **جدولة المنشورات**: يظهر Meta و TikTok كخيارات متاحة
4. **API**: ترفض الطلبات للمنصات غير المفعلة بخطأ 403

---

## 🔄 السيناريو التدريجي (Gradual Rollout)

### المرحلة 1: الإطلاق الأولي (Launch - Week 1)
```
✅ scheduling.meta.enabled
✅ scheduling.tiktok.enabled
✅ paid_campaigns.meta.enabled
```

### المرحلة 2: توسع محدود (Week 2-3)
```
✅ scheduling.meta.enabled
✅ scheduling.tiktok.enabled
✅ scheduling.google.enabled ← جديد
✅ paid_campaigns.meta.enabled
✅ paid_campaigns.google.enabled ← جديد
```

### المرحلة 3: توسع كامل (Week 4+)
```
✅ All scheduling features enabled
✅ All paid_campaigns features enabled
✅ analytics.* features enabled
```

---

## 🎯 الخلاصة

هذا التصميم يوفر:

✅ **تحكم دقيق**: على مستوى الميزة + المنصة
✅ **إخفاء/إظهار UI**: ديناميكي بناءً على الـ flags
✅ **Admin Panel سهل**: لإدارة الميزات
✅ **Presets جاهزة**: للإعدادات الشائعة
✅ **API محمي**: يرفض الطلبات للميزات المغلقة
✅ **تدرج في التفعيل**: من منصة واحدة إلى كل المنصات

---

**التالي:** تطبيق الكود في المشروع!
