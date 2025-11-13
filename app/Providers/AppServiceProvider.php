<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
            \App\Repositories\SocialMedia\SocialMediaRepository::class
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
    }
}
