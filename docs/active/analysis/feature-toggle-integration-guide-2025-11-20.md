# CMIS Feature Toggle - Integration Guide

**Date:** 2025-11-20
**Purpose:** Practical integration examples for CMIS-specific use cases
**Audience:** Developers implementing feature toggles

---

## Table of Contents

1. Platform Toggle Integration (Highest Priority)
2. Campaign Feature Integration
3. AI Feature Integration
4. Service Layer Integration
5. Controller Integration
6. Middleware Integration
7. Queue Job Integration
8. Webhook Handler Integration
9. Testing Examples

---

## 1. Platform Toggle Integration (HIGHEST PRIORITY)

### Current Problem
Currently, any user can connect any platform (Meta, Google, TikTok, etc.). With feature toggles, we control:
- Which platforms are available system-wide
- Which platforms each organization has access to
- Which platforms individual users can access

### Implementation in AdPlatformFactory

**File:** `app/Services/AdPlatforms/AdPlatformFactory.php`

```php
<?php

namespace App\Services\AdPlatforms;

use App\Models\Core\Integration;
use App\Services\AdPlatforms\Contracts\AdPlatformInterface;
use App\Services\AdPlatforms\Meta\MetaAdsPlatform;
use App\Services\AdPlatforms\Google\GoogleAdsPlatform;
use App\Services\AdPlatforms\TikTok\TikTokAdsPlatform;
use App\Services\AdPlatforms\LinkedIn\LinkedInAdsPlatform;
use App\Services\AdPlatforms\Twitter\TwitterAdsPlatform;
use App\Services\AdPlatforms\Snapchat\SnapchatAdsPlatform;
use App\Services\FeatureToggle\FeatureToggleService;
use App\Exceptions\PlatformNotEnabledException;
use Auth;

/**
 * Factory for creating Ad Platform Service instances
 *
 * INTEGRATED WITH FEATURE TOGGLES:
 * - Checks platform feature flags before creating instances
 * - Respects org-level and platform-level toggles
 * - Provides helpful error messages
 */
class AdPlatformFactory
{
    public function __construct(
        private FeatureToggleService $featureToggleService
    ) {}

    /**
     * Create a platform service instance
     *
     * @param Integration $integration The platform integration
     * @return AdPlatformInterface
     * @throws PlatformNotEnabledException If platform is disabled
     * @throws \InvalidArgumentException If platform is not supported
     */
    public static function make(Integration $integration): AdPlatformInterface
    {
        $featureToggleService = app(FeatureToggleService::class);
        $currentUser = Auth::user();

        // 1. Check platform is supported
        if (!self::isSupported($integration->platform)) {
            throw new \InvalidArgumentException(
                "Unsupported ad platform: {$integration->platform}"
            );
        }

        // 2. Check platform feature toggle (CRITICAL)
        $featureName = "platform.{$integration->platform}.enabled";
        if (!$featureToggleService->isActiveForPlatform($featureName, $integration->platform)) {
            throw new PlatformNotEnabledException(
                "Platform '{$integration->platform}' is not currently available. " .
                "Please contact support to enable this platform."
            );
        }

        // 3. Check user-level override (optional, for beta access)
        if ($currentUser && !$featureToggleService->isActiveForUser($featureName, $currentUser)) {
            throw new PlatformNotEnabledException(
                "You don't have access to the '{$integration->platform}' platform yet. " .
                "Contact your account manager for access."
            );
        }

        // 4. Create platform instance
        return match ($integration->platform) {
            'meta', 'facebook', 'instagram' => new MetaAdsPlatform($integration),
            'google', 'google_ads' => new GoogleAdsPlatform($integration),
            'tiktok' => new TikTokAdsPlatform($integration),
            'linkedin' => new LinkedInAdsPlatform($integration),
            'twitter', 'x' => new TwitterAdsPlatform($integration),
            'snapchat' => new SnapchatAdsPlatform($integration),
            default => throw new \InvalidArgumentException(
                "Unsupported ad platform: {$integration->platform}"
            ),
        };
    }

    /**
     * Get list of supported AND ENABLED platforms for current org
     *
     * KEY CHANGE: Now filters by feature toggles
     */
    public static function getEnabledPlatforms(): array
    {
        $featureToggleService = app(FeatureToggleService::class);
        $supported = self::getSupportedPlatforms();
        $organization = Auth::user()->organization;

        return array_filter($supported, function ($platformData, $platform) use ($featureToggleService) {
            $featureName = "platform.{$platform}.enabled";
            return $featureToggleService->isActiveForPlatform($featureName, $platform);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Get list of ALL supported platforms (regardless of toggles)
     * Used for: admin views, help text, future roadmap
     */
    public static function getSupportedPlatforms(): array
    {
        return [
            'meta' => [
                'name' => 'Meta Ads (Facebook & Instagram)',
                'aliases' => ['facebook', 'instagram'],
                'features' => ['campaigns', 'ad_sets', 'ads', 'audiences', 'insights'],
            ],
            'google' => [
                'name' => 'Google Ads',
                'aliases' => ['google_ads'],
                'features' => ['campaigns', 'ad_groups', 'ads', 'keywords', 'reports'],
            ],
            'tiktok' => [
                'name' => 'TikTok Ads',
                'aliases' => [],
                'features' => ['campaigns', 'ad_groups', 'ads', 'audiences'],
            ],
            'linkedin' => [
                'name' => 'LinkedIn Ads',
                'aliases' => [],
                'features' => ['campaigns', 'creatives', 'ads', 'targeting'],
            ],
            'twitter' => [
                'name' => 'X Ads (Twitter)',
                'aliases' => ['x'],
                'features' => ['campaigns', 'line_items', 'ads', 'targeting'],
            ],
            'snapchat' => [
                'name' => 'Snapchat Ads',
                'aliases' => [],
                'features' => ['campaigns', 'ad_squads', 'ads', 'audiences'],
            ],
        ];
    }

    /**
     * Check if a platform is supported (not checking toggles)
     */
    public static function isSupported(string $platform): bool
    {
        $supported = self::getSupportedPlatforms();

        if (isset($supported[$platform])) {
            return true;
        }

        foreach ($supported as $platformData) {
            if (in_array($platform, $platformData['aliases'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get platform canonical name from alias
     */
    public static function getCanonicalName(string $platform): string
    {
        $supported = self::getSupportedPlatforms();

        if (isset($supported[$platform])) {
            return $platform;
        }

        foreach ($supported as $canonicalName => $platformData) {
            if (in_array($platform, $platformData['aliases'])) {
                return $canonicalName;
            }
        }

        return $platform;
    }

    /**
     * Get platform metadata
     */
    public static function getPlatformMetadata(string $platform): array
    {
        $platforms = self::getSupportedPlatforms();
        return $platforms[$platform] ?? [];
    }
}
```

### Exception Class

**File:** `app/Exceptions/PlatformNotEnabledException.php`

```php
<?php

namespace App\Exceptions;

use Exception;

class PlatformNotEnabledException extends Exception
{
    public function __construct(string $message = "Platform not enabled")
    {
        parent::__construct($message);
    }

    public function render()
    {
        return response()->json([
            'message' => $this->message,
            'error' => 'PLATFORM_NOT_ENABLED',
        ], 403);
    }
}
```

### Usage in Controllers

**File:** `app/Http/Controllers/PlatformIntegrationController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Services\AdPlatforms\AdPlatformFactory;
use App\Models\Core\Integration;
use Illuminate\Http\Request;

class PlatformIntegrationController extends Controller
{
    /**
     * List available platforms for this organization
     *
     * Returns only ENABLED platforms (respects feature toggles)
     */
    public function listAvailablePlatforms()
    {
        $enabledPlatforms = AdPlatformFactory::getEnabledPlatforms();

        return response()->json([
            'platforms' => array_values($enabledPlatforms),
            'count' => count($enabledPlatforms),
        ]);
    }

    /**
     * List all platforms (including disabled ones)
     *
     * Used for: admin views, future roadmap, help text
     */
    public function listAllPlatforms()
    {
        $allPlatforms = AdPlatformFactory::getSupportedPlatforms();

        return response()->json([
            'platforms' => array_values($allPlatforms),
            'total' => count($allPlatforms),
        ]);
    }

    /**
     * Initiate OAuth flow for platform
     *
     * Will throw PlatformNotEnabledException if platform is disabled
     */
    public function initiateOAuth(Request $request, string $platform)
    {
        $request->validate([
            'platform' => 'required|string',
        ]);

        try {
            // This will throw PlatformNotEnabledException if disabled
            $platformService = AdPlatformFactory::make(
                Integration::factory()->forPlatform($platform)->make()
            );

            // Proceed with OAuth...
            return redirect()->to($platformService->getOAuthUrl());
        } catch (\App\Exceptions\PlatformNotEnabledException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'platform' => $platform,
            ], 403);
        }
    }

    /**
     * Handle OAuth callback
     */
    public function handleOAuthCallback(Request $request, string $platform)
    {
        // ... existing callback logic
        // Platform is already validated by initiateOAuth
    }
}
```

---

## 2. Campaign Feature Integration

### Campaign Creation Route Protection

**File:** `routes/api.php`

```php
<?php

use App\Http\Controllers\CampaignController;
use App\Http\Middleware\CheckFeatureAccess;

Route::middleware(['auth:sanctum', CheckFeatureAccess::class . ':campaign.creation.enabled'])
    ->post('/campaigns', [CampaignController::class, 'store']);

Route::middleware(['auth:sanctum', CheckFeatureAccess::class . ':campaign.editing.enabled'])
    ->put('/campaigns/{id}', [CampaignController::class, 'update']);

Route::middleware(['auth:sanctum', CheckFeatureAccess::class . ':campaign.publishing.enabled'])
    ->post('/campaigns/{id}/publish', [CampaignController::class, 'publish']);

Route::middleware(['auth:sanctum', CheckFeatureAccess::class . ':campaign.scheduling.enabled'])
    ->post('/campaigns/{id}/schedule', [CampaignController::class, 'schedule']);
```

### Campaign Controller Implementation

**File:** `app/Http/Controllers/CampaignController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\FeatureToggle\FeatureToggleService;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct(
        private FeatureToggleService $featureToggleService
    ) {}

    /**
     * Store a new campaign
     * Route already checks feature toggle via middleware
     */
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'name' => 'required|string',
            'platforms' => 'required|array',
            'platforms.*' => 'string',
        ]);

        // Double-check platforms are enabled (extra safety)
        foreach ($validated['platforms'] as $platform) {
            if (!$this->featureToggleService->isActiveForPlatform(
                "platform.{$platform}.enabled",
                $platform
            )) {
                return response()->json([
                    'message' => "Platform '$platform' is not available",
                    'error' => 'PLATFORM_NOT_ENABLED',
                ], 403);
            }
        }

        // Create campaign
        $campaign = Campaign::create($validated);

        return response()->json($campaign, 201);
    }

    /**
     * Update campaign
     */
    public function update(Request $request, Campaign $campaign)
    {
        // Feature toggle already checked by middleware
        $campaign->update($request->validated());
        return response()->json($campaign);
    }

    /**
     * Publish campaign to platforms
     */
    public function publish(Request $request, Campaign $campaign)
    {
        // Check platforms are still enabled
        $platforms = $campaign->platforms;
        foreach ($platforms as $platform) {
            if (!$this->featureToggleService->isActiveForPlatform(
                "platform.{$platform}.enabled",
                $platform
            )) {
                return response()->json([
                    'message' => "Cannot publish: platform '$platform' is no longer available",
                    'error' => 'PLATFORM_DISABLED',
                ], 403);
            }
        }

        // Publish to each platform
        $campaign->publish();

        return response()->json([
            'message' => 'Campaign published successfully',
            'campaign' => $campaign,
        ]);
    }

    /**
     * Schedule campaign for future publishing
     */
    public function schedule(Request $request, Campaign $campaign)
    {
        // Feature already checked by middleware
        $validated = $request->validate([
            'scheduled_for' => 'required|date|after:now',
        ]);

        $campaign->scheduleFor($validated['scheduled_for']);

        return response()->json([
            'message' => 'Campaign scheduled',
            'campaign' => $campaign,
        ]);
    }

    /**
     * Delete campaign
     */
    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return response()->json(null, 204);
    }
}
```

---

## 3. AI Feature Integration

### Semantic Search Feature Check

**File:** `app/Services/CMIS/SemanticSearchService.php`

```php
<?php

namespace App\Services\CMIS;

use App\Services\FeatureToggle\FeatureToggleService;
use App\Services\CMIS\Traits\HasVectorValidation;
use Illuminate\Support\Collection;

class SemanticSearchService
{
    use HasVectorValidation;

    public function __construct(
        private FeatureToggleService $featureToggleService
    ) {}

    /**
     * Search with semantic understanding
     *
     * @throws \Exception if feature is not enabled
     */
    public function search(string $query, array $filters = []): Collection
    {
        // Check feature is enabled
        if (!$this->featureToggleService->isActive('ai.semantic-search.enabled')) {
            throw new \Exception(
                'Semantic search is not available in your current plan. ' .
                'Please upgrade to access AI-powered search.'
            );
        }

        // Check user has access
        if (!$this->featureToggleService->isActiveForUser('ai.semantic-search.enabled', auth()->user())) {
            throw new \Exception(
                'You don\'t have access to semantic search yet. Contact support.'
            );
        }

        // Perform search
        $embedding = $this->generateEmbedding($query);
        $results = $this->vectorSearch($embedding, $filters);

        return $results;
    }

    /**
     * Batch semantic search (if feature enabled)
     */
    public function batchSearch(array $queries, array $filters = []): array
    {
        if (!$this->featureToggleService->isActive('ai.semantic-search.enabled')) {
            return [];
        }

        return array_map(fn($query) => $this->search($query, $filters), $queries);
    }
}
```

### Campaign Optimization Service

**File:** `app/Services/AI/CampaignOptimizationService.php`

```php
<?php

namespace App\Services\AI;

use App\Services\FeatureToggle\FeatureToggleService;

class CampaignOptimizationService
{
    public function __construct(
        private FeatureToggleService $featureToggleService
    ) {}

    /**
     * Get AI optimization suggestions
     * Returns empty array if feature disabled (graceful degradation)
     */
    public function getOptimizations($campaign): array
    {
        if (!$this->featureToggleService->isActive('ai.auto-optimization.enabled')) {
            return []; // No optimizations available
        }

        // Generate optimizations
        $suggestions = $this->generateSuggestions($campaign);

        return $suggestions;
    }

    /**
     * Apply AI-recommended optimizations
     */
    public function applyOptimizations($campaign, array $optimizations): void
    {
        if (!$this->featureToggleService->isActive('ai.auto-optimization.enabled')) {
            throw new \Exception('Optimization feature not available');
        }

        foreach ($optimizations as $optimization) {
            $this->applyOptimization($campaign, $optimization);
        }
    }

    private function generateSuggestions($campaign): array
    {
        // AI logic here
        return [];
    }

    private function applyOptimization($campaign, array $optimization): void
    {
        // Apply logic here
    }
}
```

---

## 4. Service Layer Integration

### Creating a Feature-Aware Service

**Template:** Create any new service with feature toggle support

```php
<?php

namespace App\Services\MyDomain;

use App\Services\FeatureToggle\FeatureToggleService;

class MyFeatureService
{
    public function __construct(
        private FeatureToggleService $featureToggleService
    ) {}

    /**
     * Main feature method
     * Throws exception if feature disabled
     */
    public function execute($data)
    {
        // Check feature is enabled
        if (!$this->isFeatureEnabled()) {
            throw new \Exception('This feature is not available');
        }

        // Execute logic
        return $this->doWork($data);
    }

    /**
     * Helper method to check if feature enabled
     */
    private function isFeatureEnabled(): bool
    {
        return $this->featureToggleService->isActive('my-feature.enabled');
    }

    /**
     * Optional: With user-level check
     */
    private function isFeatureEnabledForUser($user): bool
    {
        return $this->featureToggleService->isActiveForUser('my-feature.enabled', $user);
    }

    private function doWork($data)
    {
        // Implementation
    }
}
```

---

## 5. Controller Integration

### Feature Check in Controllers

```php
<?php

namespace App\Http\Controllers;

use App\Services\FeatureToggle\FeatureToggleService;
use Illuminate\Http\Request;

class MyController extends Controller
{
    public function __construct(
        private FeatureToggleService $featureToggleService
    ) {}

    /**
     * Show feature-gated page
     */
    public function show()
    {
        if (!$this->featureToggleService->isActive('my-feature.enabled')) {
            return response()->view('errors.feature-unavailable', [], 403);
        }

        return view('my-feature.show');
    }

    /**
     * In API response, include feature availability
     */
    public function index()
    {
        $features = [
            'semantic_search' => $this->featureToggleService->isActive('ai.semantic-search.enabled'),
            'auto_optimization' => $this->featureToggleService->isActive('ai.auto-optimization.enabled'),
            'scheduling' => $this->featureToggleService->isActive('campaign.scheduling.enabled'),
        ];

        $data = [
            'items' => $this->getItems(),
            'available_features' => $features,
        ];

        return response()->json($data);
    }

    private function getItems()
    {
        // ... fetch items
    }
}
```

---

## 6. Middleware Integration

### Feature Access Middleware

**File:** `app/Http/Middleware/CheckFeatureAccess.php`

```php
<?php

namespace App\Http\Middleware;

use App\Services\FeatureToggle\FeatureToggleService;
use Closure;
use Illuminate\Http\Request;

class CheckFeatureAccess
{
    public function __construct(
        private FeatureToggleService $featureToggleService
    ) {}

    /**
     * Handle incoming request
     *
     * Usage: middleware('feature:campaign.creation.enabled')
     */
    public function handle(Request $request, Closure $next, string $feature): mixed
    {
        if (!$this->featureToggleService->isActive($feature)) {
            return response()->json([
                'message' => "Feature '{$feature}' is not available",
                'error' => 'FEATURE_NOT_ENABLED',
            ], 403);
        }

        return $next($request);
    }
}
```

### Register in Kernel

**File:** `app/Http/Kernel.php`

```php
protected $routeMiddleware = [
    // ... other middleware
    'feature' => \App\Http\Middleware\CheckFeatureAccess::class,
];
```

---

## 7. Queue Job Integration

### Feature-Aware Jobs

**File:** `app/Jobs/PublishCampaignJob.php`

```php
<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\FeatureToggle\FeatureToggleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishCampaignJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Campaign $campaign
    ) {}

    public function handle(FeatureToggleService $featureToggleService): void
    {
        // Check feature is still enabled
        if (!$featureToggleService->isActive('campaign.publishing.enabled')) {
            $this->fail(new \Exception('Campaign publishing feature is no longer enabled'));
            return;
        }

        // Publish to each platform
        foreach ($this->campaign->platforms as $platform) {
            if (!$featureToggleService->isActiveForPlatform("platform.{$platform}.enabled", $platform)) {
                \Log::warning("Platform {$platform} disabled, skipping publish", [
                    'campaign_id' => $this->campaign->id,
                ]);
                continue;
            }

            // Publish to platform
            $this->publishToplatform($platform);
        }
    }

    private function publishToplatform(string $platform): void
    {
        // Platform-specific logic
    }
}
```

---

## 8. Webhook Handler Integration

### Handle Webhooks from Disabled Platforms

**File:** `app/Services/Integration/WebhookHandler.php`

```php
<?php

namespace App\Services\Integration;

use App\Services\FeatureToggle\FeatureToggleService;
use Illuminate\Http\Request;

class WebhookHandler
{
    public function __construct(
        private FeatureToggleService $featureToggleService
    ) {}

    /**
     * Handle incoming webhook from platform
     */
    public function handle(Request $request, string $platform)
    {
        // Check platform is still enabled
        if (!$this->featureToggleService->isActiveForPlatform("platform.{$platform}.enabled", $platform)) {
            \Log::info("Webhook received from disabled platform: {$platform}");
            // Return 200 OK to prevent retry, but don't process
            return response()->json(['ok' => true]);
        }

        // Process webhook
        $this->processWebhook($platform, $request);
    }

    private function processWebhook(string $platform, Request $request): void
    {
        // Implementation
    }
}
```

---

## 9. Testing Examples

### Unit Test Example

**File:** `tests/Unit/FeatureToggle/PlatformToggleTest.php`

```php
<?php

namespace Tests\Unit\FeatureToggle;

use App\Models\Core\Organization;
use App\Models\Core\User;
use App\Services\FeatureToggle\FeatureToggleService;
use Tests\TestCase;

class PlatformToggleTest extends TestCase
{
    private FeatureToggleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FeatureToggleService::class);
    }

    /** @test */
    public function platform_disabled_by_default()
    {
        $org = Organization::factory()->create();
        $result = $this->service->isActiveForPlatform('platform.tiktok.enabled', 'tiktok', $org);

        $this->assertFalse($result);
    }

    /** @test */
    public function can_enable_platform_for_organization()
    {
        $org = Organization::factory()->create();
        $this->service->enableFeature('platform.meta.enabled', $org);

        $result = $this->service->isActiveForPlatform('platform.meta.enabled', 'meta', $org);
        $this->assertTrue($result);
    }

    /** @test */
    public function platform_enabled_globally_affects_all_orgs()
    {
        $this->service->enableFeature('platform.google.enabled'); // System-wide

        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $this->assertTrue($this->service->isActiveForPlatform('platform.google.enabled', 'google', $org1));
        $this->assertTrue($this->service->isActiveForPlatform('platform.google.enabled', 'google', $org2));
    }

    /** @test */
    public function org_override_takes_precedence_over_system_default()
    {
        // System: enabled
        $this->service->enableFeature('platform.tiktok.enabled');

        // Org: disabled
        $org = Organization::factory()->create();
        $this->service->disableFeature('platform.tiktok.enabled', $org);

        // Result: should be disabled for this org
        $result = $this->service->isActiveForPlatform('platform.tiktok.enabled', 'tiktok', $org);
        $this->assertFalse($result);
    }
}
```

### Feature Test Example

**File:** `tests/Feature/PlatformIntegrationTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Core\Integration;
use App\Models\Core\Organization;
use App\Models\Core\User;
use App\Services\AdPlatforms\AdPlatformFactory;
use App\Exceptions\PlatformNotEnabledException;
use Tests\TestCase;

class PlatformIntegrationTest extends TestCase
{
    protected Organization $organization;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
        $this->user = User::factory()->for($this->organization)->create();
    }

    /** @test */
    public function cannot_create_disabled_platform_integration()
    {
        $this->actingAs($this->user);

        $integration = Integration::factory()
            ->for($this->organization)
            ->forPlatform('meta')
            ->make();

        // Platform is disabled by default
        $this->expectException(PlatformNotEnabledException::class);
        AdPlatformFactory::make($integration);
    }

    /** @test */
    public function can_create_enabled_platform_integration()
    {
        $this->actingAs($this->user);

        // Enable platform for org
        $featureService = app(\App\Services\FeatureToggle\FeatureToggleService::class);
        $featureService->enableFeature('platform.google.enabled', $this->organization);

        $integration = Integration::factory()
            ->for($this->organization)
            ->forPlatform('google')
            ->make();

        // Should work now
        $platform = AdPlatformFactory::make($integration);
        $this->assertNotNull($platform);
    }

    /** @test */
    public function list_available_platforms_respects_toggles()
    {
        // Enable only Meta
        $featureService = app(\App\Services\FeatureToggle\FeatureToggleService::class);
        $featureService->enableFeature('platform.meta.enabled', $this->organization);

        $this->actingAs($this->user);
        $response = $this->getJson('/api/platforms/available');

        $platforms = $response->json('platforms');
        $platformNames = array_column($platforms, 'name');

        $this->assertContains('Meta Ads (Facebook & Instagram)', $platformNames);
        $this->assertNotContains('Google Ads', $platformNames);
    }

    /** @test */
    public function platform_disable_prevents_new_integrations()
    {
        $featureService = app(\App\Services\FeatureToggle\FeatureToggleService::class);
        $featureService->enableFeature('platform.tiktok.enabled', $this->organization);

        // Create integration while enabled
        $integration = Integration::factory()
            ->for($this->organization)
            ->forPlatform('tiktok')
            ->create();

        $this->assertTrue($integration->exists);

        // Disable platform
        $featureService->disableFeature('platform.tiktok.enabled', $this->organization);

        // Cannot sync or use existing integration
        $this->actingAs($this->user);
        $response = $this->postJson("/api/integrations/{$integration->id}/sync");

        $response->assertStatus(403);
    }
}
```

---

## Summary: Integration Checklist

### Must Update (Critical)
- [x] `AdPlatformFactory` - Check platform feature togles
- [x] Platform routes - Protect with middleware
- [x] Campaign routes - Protect with middleware
- [x] Controllers - Add feature checks

### Should Update (Important)
- [ ] AI services - Check AI feature toggles
- [ ] Webhook handlers - Skip disabled platforms
- [ ] Queue jobs - Verify features still enabled
- [ ] Scheduled tasks - Check features before executing

### Nice to Have
- [ ] API responses - Include feature availability
- [ ] Frontend - Conditionally show/hide features
- [ ] Notifications - Notify users of feature changes
- [ ] Analytics - Track feature usage

---

**Next Step:** Review the main design document and begin Phase 1 implementation
