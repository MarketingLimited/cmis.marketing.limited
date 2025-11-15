<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\Campaign\{CampaignCreated, CampaignMetricsUpdated};
use App\Events\Content\PostPublished;
use App\Listeners\Campaign\UpdateDashboardCache;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     */
    protected $listen = [
        CampaignCreated::class => [
            UpdateDashboardCache::class,
        ],

        CampaignMetricsUpdated::class => [
            UpdateDashboardCache::class,
        ],

        PostPublished::class => [
            // Add listeners here
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
