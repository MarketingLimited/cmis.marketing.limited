<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Models\Core\Integration;
use App\Observers\IntegrationObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Campaign::class => \App\Policies\CampaignPolicy::class,
        \App\Models\CreativeAsset::class => \App\Policies\CreativeAssetPolicy::class,
        \App\Models\Core\Integration::class => \App\Policies\IntegrationPolicy::class,
        \App\Models\Core\Org::class => \App\Policies\OrganizationPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository Bindings
        $this->registerRepositories();

        // Embedding Services
        $this->registerEmbeddingServices();

        // Marketplace Services
        $this->registerMarketplaceServices();
    }

    /**
     * Register Repository interfaces to concrete implementations
     */
    protected function registerRepositories(): void
    {
        // Campaign Repository
        $this->app->bind(
            \App\Repositories\Contracts\CampaignRepositoryInterface::class,
            \App\Repositories\CMIS\CampaignRepository::class
        );

        // Context Repository
        $this->app->bind(
            \App\Repositories\Contracts\ContextRepositoryInterface::class,
            \App\Repositories\CMIS\ContextRepository::class
        );

        // Creative Repository
        $this->app->bind(
            \App\Repositories\Contracts\CreativeRepositoryInterface::class,
            \App\Repositories\CMIS\CreativeRepository::class
        );

        // Permission Repository
        $this->app->bind(
            \App\Repositories\Contracts\PermissionRepositoryInterface::class,
            \App\Repositories\CMIS\PermissionRepository::class
        );

        // Analytics Repository
        $this->app->bind(
            \App\Repositories\Contracts\AnalyticsRepositoryInterface::class,
            \App\Repositories\Analytics\AnalyticsRepository::class
        );

        // Knowledge Repository
        $this->app->bind(
            \App\Repositories\Contracts\KnowledgeRepositoryInterface::class,
            \App\Repositories\Knowledge\KnowledgeRepository::class
        );

        // Embedding Repository
        $this->app->bind(
            \App\Repositories\Contracts\EmbeddingRepositoryInterface::class,
            \App\Repositories\Knowledge\EmbeddingRepository::class
        );

        // Operations Repository
        $this->app->bind(
            \App\Repositories\Contracts\OperationsRepositoryInterface::class,
            \App\Repositories\Operations\OperationsRepository::class
        );

        // Audit Repository
        $this->app->bind(
            \App\Repositories\Contracts\AuditRepositoryInterface::class,
            \App\Repositories\Operations\AuditRepository::class
        );

        // Cache Repository
        $this->app->bind(
            \App\Repositories\Contracts\CacheRepositoryInterface::class,
            \App\Repositories\CMIS\CacheRepository::class
        );

        // Marketing Repository
        $this->app->bind(
            \App\Repositories\Contracts\MarketingRepositoryInterface::class,
            \App\Repositories\Marketing\MarketingRepository::class
        );

        // Social Media Repository
        $this->app->bind(
            \App\Repositories\Contracts\SocialMediaRepositoryInterface::class,
            \App\Repositories\CMIS\SocialMediaRepository::class
        );

        // Notification Repository
        $this->app->bind(
            \App\Repositories\Contracts\NotificationRepositoryInterface::class,
            \App\Repositories\CMIS\NotificationRepository::class
        );

        // Verification Repository
        $this->app->bind(
            \App\Repositories\Contracts\VerificationRepositoryInterface::class,
            \App\Repositories\CMIS\VerificationRepository::class
        );

        // Trigger Repository
        $this->app->bind(
            \App\Repositories\Contracts\TriggerRepositoryInterface::class,
            \App\Repositories\CMIS\TriggerRepository::class
        );

        // Platform Asset Repository (Three-tier caching for platform assets)
        $this->app->bind(
            \App\Repositories\Contracts\PlatformAssetRepositoryInterface::class,
            \App\Repositories\Platform\PlatformAssetRepository::class
        );
    }

    /**
     * Register Embedding services
     */
    protected function registerEmbeddingServices(): void
    {
        // Embedding Provider (Gemini as default)
        $this->app->bind(
            \App\Services\Embedding\EmbeddingProviderInterface::class,
            \App\Services\Embedding\Providers\GeminiProvider::class
        );

        // Embedding Orchestrator (main service)
        $this->app->singleton(
            \App\Services\Embedding\EmbeddingOrchestrator::class,
            function ($app) {
                return new \App\Services\Embedding\EmbeddingOrchestrator(
                    $app->make(\App\Services\Embedding\EmbeddingProviderInterface::class),
                    $app->make(\App\Repositories\Contracts\EmbeddingRepositoryInterface::class)
                );
            }
        );

        // Gemini Embedding Service
        $this->app->singleton(\App\Services\CMIS\GeminiEmbeddingService::class);

        // Semantic Search Service
        $this->app->singleton(\App\Services\CMIS\SemanticSearchService::class);

        // Vector Integration Service
        $this->app->singleton(
            \App\Services\CMIS\VectorIntegrationService::class,
            function ($app) {
                return new \App\Services\CMIS\VectorIntegrationService(
                    $app->make(\App\Services\CMIS\SemanticSearchService::class),
                    $app->make(\App\Services\CMIS\GeminiEmbeddingService::class)
                );
            }
        );
    }

    /**
     * Register Marketplace services
     */
    protected function registerMarketplaceServices(): void
    {
        // Marketplace Service (singleton for caching)
        $this->app->singleton(
            \App\Services\Marketplace\MarketplaceService::class
        );

        // Navigation Service (depends on MarketplaceService)
        $this->app->singleton(
            \App\Services\Navigation\NavigationService::class,
            function ($app) {
                return new \App\Services\Navigation\NavigationService(
                    $app->make(\App\Services\Marketplace\MarketplaceService::class)
                );
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Register model observers
        $this->registerObservers();

        // Load migrations from phases directory
        $this->loadMigrationsFrom(database_path('migrations/phases'));

        // Configure Rate Limiters
        $this->configureRateLimiters();

        // Register Blade Components (Phase 1B - 2025-11-21)
        $this->registerBladeComponents();
    }

    /**
     * Register model observers
     */
    protected function registerObservers(): void
    {
        // Integration observer for cascade soft delete/restore
        Integration::observe(IntegrationObserver::class);
    }

    /**
     * Register custom Blade components
     */
    protected function registerBladeComponents(): void
    {
        \Illuminate\Support\Facades\Blade::component(
            'ai-quota-widget',
            \App\View\Components\AiQuotaWidget::class
        );
    }

    /**
     * Configure rate limiters for different API endpoint types
     */
    protected function configureRateLimiters(): void
    {
        // Authentication endpoints - strict limit
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => 'Too many authentication attempts',
                        'message' => 'Please try again in 1 minute'
                    ], 429);
                });
        });

        // General API endpoints - moderate limit per user+org
        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()?->user_id . '|' . ($request->route('org') ?? 'no-org');

            return Limit::perMinute(100)
                ->by($key)
                ->response(function () {
                    return response()->json([
                        'error' => 'Rate limit exceeded',
                        'message' => 'Too many requests. Please slow down.'
                    ], 429);
                });
        });

        // Webhook endpoints - high limit (platforms send many requests)
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(1000)
                ->by($request->ip());
        });

        // Heavy operations (sync, analytics) - lower limit
        RateLimiter::for('heavy', function (Request $request) {
            $key = $request->user()?->user_id . '|' . ($request->route('org') ?? 'no-org');

            return Limit::perMinute(20)
                ->by($key)
                ->response(function () {
                    return response()->json([
                        'error' => 'Rate limit exceeded',
                        'message' => 'This operation is rate limited. Please wait before retrying.'
                    ], 429);
                });
        });

        // AI operations - strict limit (expensive)
        RateLimiter::for('ai', function (Request $request) {
            $key = $request->user()?->user_id . '|' . ($request->route('org') ?? 'no-org');

            return [
                Limit::perMinute(30)->by($key),
                Limit::perHour(500)->by($key),
            ];
        });
    }
}
