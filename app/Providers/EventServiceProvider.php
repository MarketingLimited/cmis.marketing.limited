<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

// Campaign Events
use App\Events\Campaign\{CampaignCreated, CampaignMetricsUpdated};
use App\Listeners\Campaign\{UpdateDashboardCache, NotifyCampaignStatusChange};

// Content Events
use App\Events\Content\{PostPublished, PostScheduled, PostFailed};
use App\Listeners\Content\{NotifyPostScheduled, HandlePostFailure};

// Integration Events
use App\Events\Integration\{
    IntegrationConnected,
    IntegrationDisconnected,
    IntegrationSyncCompleted,
    IntegrationSyncFailed
};
use App\Listeners\Integration\{
    NotifyIntegrationConnected,
    NotifyIntegrationDisconnected,
    HandleSyncCompletion,
    HandleSyncFailure
};

// Token Expiry Events (NEW: Week 2)
use App\Events\IntegrationTokenExpiring;
use App\Listeners\SendTokenExpiringNotification;

// Budget Events
use App\Events\Budget\BudgetThresholdReached;
use App\Listeners\Budget\NotifyBudgetThreshold;

// Analytics
use App\Listeners\Analytics\UpdatePerformanceMetrics;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     */
    protected $listen = [
        // Campaign Events
        CampaignCreated::class => [
            UpdateDashboardCache::class,
            NotifyCampaignStatusChange::class,
        ],

        CampaignMetricsUpdated::class => [
            UpdateDashboardCache::class,
            UpdatePerformanceMetrics::class,
        ],

        // Content Events
        PostPublished::class => [
            UpdateDashboardCache::class,
        ],

        PostScheduled::class => [
            NotifyPostScheduled::class,
        ],

        PostFailed::class => [
            HandlePostFailure::class,
        ],

        // Integration Events
        IntegrationConnected::class => [
            NotifyIntegrationConnected::class,
        ],

        IntegrationDisconnected::class => [
            NotifyIntegrationDisconnected::class,
        ],

        IntegrationSyncCompleted::class => [
            HandleSyncCompletion::class,
        ],

        IntegrationSyncFailed::class => [
            HandleSyncFailure::class,
        ],

        // Token Expiry Events (NEW: Week 2)
        IntegrationTokenExpiring::class => [
            SendTokenExpiringNotification::class,
        ],

        // Budget Events
        BudgetThresholdReached::class => [
            NotifyBudgetThreshold::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
