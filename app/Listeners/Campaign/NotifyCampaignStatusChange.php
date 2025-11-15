<?php

namespace App\Listeners\Campaign;

use App\Events\Campaign\CampaignCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\{Cache, Log};

/**
 * Notifies when campaign status changes
 */
class NotifyCampaignStatusChange implements ShouldQueue
{
    public function handle(CampaignCreated $event): void
    {
        $campaign = $event->campaign;

        Log::info('Campaign created', [
            'campaign_id' => $campaign->campaign_id ?? $campaign->id,
            'name' => $campaign->name,
            'status' => $campaign->status,
            'platform' => $campaign->platform ?? 'N/A',
        ]);

        // TODO: Notify campaign managers
        // TODO: Update analytics
        // TODO: Trigger automated optimization checks
    }
}
