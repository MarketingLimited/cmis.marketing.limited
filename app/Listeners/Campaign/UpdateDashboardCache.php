<?php

namespace App\Listeners\Campaign;

use App\Events\Campaign\{CampaignCreated, CampaignMetricsUpdated};
use App\Services\Dashboard\UnifiedDashboardService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateDashboardCache implements ShouldQueue
{
    public function __construct(
        private UnifiedDashboardService $dashboardService
    ) {}

    public function handle(CampaignCreated|CampaignMetricsUpdated $event): void
    {
        // Clear dashboard cache when campaign changes
        $this->dashboardService->clearCache($event->campaign->org);
    }
}
