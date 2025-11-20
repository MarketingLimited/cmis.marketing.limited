# Feature Toggle Route Integration Examples
**Date:** 2025-11-20
**Purpose:** Examples of how to integrate feature toggle middleware into existing routes

---

## Pattern 1: Platform-Specific Routes

For routes that include `{platform}` parameter:

```php
// BEFORE (no feature check)
Route::post('campaigns/{platform}/create', [CampaignController::class, 'create'])
    ->middleware(['auth', 'validate.org.access']);

// AFTER (with feature toggle)
Route::post('campaigns/{platform}/create', [CampaignController::class, 'create'])
    ->middleware(['auth', 'validate.org.access', 'feature.platform:paid_campaigns']);
```

### Complete Example:

```php
Route::prefix('campaigns/{platform}')->group(function () {
    // All routes in this group require paid_campaigns feature for the platform
    Route::middleware(['auth', 'validate.org.access', 'feature.platform:paid_campaigns'])
        ->group(function () {
            Route::post('/create', [CampaignController::class, 'create'])->name('campaigns.create');
            Route::put('/{id}', [CampaignController::class, 'update'])->name('campaigns.update');
            Route::delete('/{id}', [CampaignController::class, 'destroy'])->name('campaigns.delete');
            Route::post('/{id}/activate', [CampaignController::class, 'activate'])->name('campaigns.activate');
        });

    // Read-only routes (might not need feature toggle)
    Route::get('/', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/{id}', [CampaignController::class, 'show'])->name('campaigns.show');
});
```

---

## Pattern 2: Post Scheduling Routes

```php
Route::prefix('social/{platform}/schedule')->group(function () {
    Route::middleware(['auth', 'validate.org.access', 'feature.platform:scheduling'])
        ->group(function () {
            Route::post('/', [SchedulingController::class, 'schedule'])->name('social.schedule');
            Route::put('/{id}', [SchedulingController::class, 'update'])->name('social.schedule.update');
            Route::delete('/{id}', [SchedulingController::class, 'cancel'])->name('social.schedule.cancel');
        });
});
```

---

## Pattern 3: Controller-Based Feature Checks

If you prefer checking in the controller instead of middleware:

```php
// In Controller
use App\Services\FeatureToggle\FeatureFlagService;

class CampaignController extends Controller
{
    public function __construct(
        private FeatureFlagService $featureFlags
    ) {}

    public function create(Request $request, string $platform)
    {
        // Check feature at controller level
        $this->featureFlags->requireEnabled("paid_campaigns.{$platform}.enabled");

        // Or with custom message
        if (!$this->featureFlags->isEnabled("paid_campaigns.{$platform}.enabled")) {
            return response()->json([
                'error' => 'Paid campaigns not available for this platform',
                'available_platforms' => $this->featureFlags->getEnabledPlatforms('paid_campaigns')
            ], 403);
        }

        // Proceed with creation...
    }
}
```

---

## Pattern 4: Conditional Route Registration

For completely hiding routes when features are disabled:

```php
// In routes/api.php or routes/web.php

use App\Services\FeatureToggle\FeatureFlagService;

$featureFlags = app(FeatureFlagService::class);

// Only register Meta campaign routes if enabled
if ($featureFlags->isEnabled('paid_campaigns.meta.enabled')) {
    Route::prefix('campaigns/meta')->group(function () {
        // Meta-specific routes
    });
}

// Loop through enabled platforms
foreach ($featureFlags->getEnabledPlatforms('paid_campaigns') as $platform) {
    Route::prefix("campaigns/{$platform}")->group(function () use ($platform) {
        // Platform-specific routes
    });
}
```

---

## Pattern 5: API Resource Routes with Feature Toggle

```php
// Platform Integration Routes
Route::prefix('platforms')->name('platforms.')->group(function () {
    // List all enabled platforms (no auth needed)
    Route::get('/', [PlatformController::class, 'index'])->name('index');

    // Platform-specific operations (auth + feature check)
    Route::prefix('{platform}')->middleware(['auth', 'validate.org.access'])->group(function () {
        // OAuth routes (need feature check for paid_campaigns)
        Route::get('/auth', [PlatformController::class, 'auth'])
            ->middleware('feature.platform:paid_campaigns')
            ->name('auth');

        Route::get('/callback', [PlatformController::class, 'callback'])
            ->middleware('feature.platform:paid_campaigns')
            ->name('callback');

        // Account management
        Route::get('/accounts', [PlatformController::class, 'accounts'])
            ->middleware('feature.platform:paid_campaigns')
            ->name('accounts');

        // Analytics (different feature)
        Route::get('/analytics', [PlatformController::class, 'analytics'])
            ->middleware('feature.platform:analytics')
            ->name('analytics');
    });
});
```

---

## Pattern 6: Webhook Routes (Platform-Specific)

Webhooks might need different handling since they're called by external platforms:

```php
Route::prefix('webhooks')->group(function () {
    Route::post('{platform}/campaign', [WebhookController::class, 'handleCampaign'])
        ->middleware('verify.webhook')
        ->name('webhooks.campaign');

    // Inside WebhookController:
    public function handleCampaign(Request $request, string $platform)
    {
        // Check if platform is enabled before processing
        if (!$this->featureFlags->isEnabled("paid_campaigns.{$platform}.enabled")) {
            Log::warning("Webhook received for disabled platform", [
                'platform' => $platform,
                'event' => $request->input('event')
            ]);

            // Return 200 to prevent retries
            return response()->json([
                'status' => 'disabled',
                'message' => 'Feature disabled'
            ]);
        }

        // Process webhook...
    }
}
```

---

## Pattern 7: Bulk Operations with Mixed Platforms

When operations span multiple platforms:

```php
public function bulkCreateCampaigns(Request $request)
{
    $campaigns = $request->input('campaigns'); // Array of campaigns with platforms

    $enabledPlatforms = $this->featureFlags->getEnabledPlatforms('paid_campaigns');

    $results = [];

    foreach ($campaigns as $campaign) {
        $platform = $campaign['platform'];

        if (!in_array($platform, $enabledPlatforms)) {
            $results[] = [
                'platform' => $platform,
                'status' => 'skipped',
                'reason' => 'Feature not enabled'
            ];
            continue;
        }

        // Create campaign...
        $results[] = [
            'platform' => $platform,
            'status' => 'created',
            'id' => $createdId
        ];
    }

    return response()->json([
        'results' => $results,
        'enabled_platforms' => $enabledPlatforms
    ]);
}
```

---

## Integration Checklist for Existing Routes

### Routes to Update:

1. **Ad Campaign Routes** (`/campaigns/*`)
   - Add `feature.platform:paid_campaigns` middleware
   - File: `routes/api.php` lines ~456-473, ~807-824

2. **Ad Creative Routes** (`/ad-creatives/*`)
   - Add `feature.platform:paid_campaigns` middleware
   - File: `routes/api.php` (find ad creative routes)

3. **Scheduling Routes** (`/schedule/*`, `/social/*/schedule`)
   - Add `feature.platform:scheduling` middleware

4. **Analytics Routes** (`/analytics/*`)
   - Add `feature.platform:analytics` middleware for platform-specific analytics

5. **Platform Integration Routes** (`/integrations/*/connect`)
   - Add feature checks before OAuth flows

### Controllers to Update:

1. **AdCampaignController**
   - Add feature checks in create/update/delete methods
   - Show only enabled platforms in listings

2. **AdPlatformFactory** (✅ Already done!)
   - Throws `FeatureDisabledException` when creating platform instances

3. **SchedulingController**
   - Add feature checks for post scheduling

4. **IntegrationController**
   - Check feature flags before OAuth flow

5. **AnalyticsController**
   - Filter analytics by enabled platforms

---

## Frontend Integration Examples

### JavaScript/Alpine.js

```javascript
// Fetch enabled platforms on page load
async function loadEnabledPlatforms() {
    const response = await fetch('/api/features/available-platforms');
    const data = await response.json();

    // Filter to only show enabled platforms
    this.platforms = data.platforms.filter(p => p.enabled);

    // Or get specific feature
    this.metaPaidCampaignsEnabled = data.platforms
        .find(p => p.key === 'meta')
        ?.features?.paid_campaigns?.enabled || false;
}

// Check before making API call
async function createCampaign(platform, campaignData) {
    // Check if feature is enabled
    const checkResponse = await fetch(`/api/features/check/paid_campaigns.${platform}.enabled`);
    const { enabled } = await checkResponse.json();

    if (!enabled) {
        alert(`Paid campaigns are not available for ${platform}`);
        return;
    }

    // Proceed with creation
    const response = await fetch(`/api/campaigns/${platform}`, {
        method: 'POST',
        body: JSON.stringify(campaignData),
        headers: { 'Content-Type': 'application/json' }
    });

    // Handle response...
}
```

### Blade Templates

```blade
{{-- Show only enabled platforms --}}
<div class="platform-selector">
    @featureEnabled('paid_campaigns.meta.enabled')
        <button onclick="selectPlatform('meta')">Meta Ads</button>
    @endFeatureEnabled

    @featureDisabled('paid_campaigns.google.enabled')
        <button disabled class="opacity-50">Google Ads (Coming Soon)</button>
    @endFeatureDisabled
</div>

{{-- Or use the service directly --}}
@if($featureFlags->isEnabled('scheduling.tiktok.enabled'))
    <a href="/schedule/tiktok">Schedule TikTok Post</a>
@endif

{{-- List enabled platforms --}}
<div>
    <h3>Available Platforms:</h3>
    @php
        $enabledPlatforms = $featureFlags->getEnabledPlatforms('paid_campaigns');
    @endphp

    @foreach($enabledPlatforms as $platform)
        <span class="badge">{{ ucfirst($platform) }}</span>
    @endforeach
</div>
```

---

## Testing Strategy

### Test Each Pattern:

1. **Middleware Protection**
   ```php
   // Test that disabled platforms return 403
   $this->postJson('/campaigns/google/create', $data)
       ->assertStatus(403)
       ->assertJsonStructure(['error', 'available_platforms']);
   ```

2. **Controller Checks**
   ```php
   // Test controller-level feature checks
   $this->featureFlags->set('paid_campaigns.meta.enabled', false);

   $this->postJson('/campaigns/meta/create', $data)
       ->assertStatus(403);
   ```

3. **Factory Integration**
   ```php
   // Test AdPlatformFactory throws exception
   $this->expectException(FeatureDisabledException::class);

   AdPlatformFactory::make($disabledIntegration);
   ```

---

## Migration Plan

### Phase 1: Critical Routes (Week 1)
- ✅ Ad campaign creation/update/delete
- ✅ Platform OAuth flows
- ✅ Post scheduling

### Phase 2: Secondary Routes (Week 2)
- ✅ Analytics endpoints
- ✅ Bulk operations
- ✅ Webhook handlers

### Phase 3: Frontend (Week 2-3)
- ✅ Platform selector components
- ✅ Feature availability indicators
- ✅ Conditional UI rendering

### Phase 4: Testing & Validation (Week 3-4)
- ✅ Integration tests
- ✅ User acceptance testing
- ✅ Performance validation

---

**Status:** Ready for implementation across existing routes
