<?php

namespace App\Listeners\Integration;

use App\Events\Integration\IntegrationSyncCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\{Cache, Log};

/**
 * Handles actions when integration sync completes
 */
class HandleSyncCompletion implements ShouldQueue
{
    public function handle(IntegrationSyncCompleted $event): void
    {
        $integration = $event->integration;

        Log::info('Integration sync completed', [
            'integration_id' => $integration->integration_id,
            'provider' => $integration->provider,
            'data_type' => $event->dataType,
            'stats' => $event->stats,
        ]);

        // Clear organization dashboard cache
        Cache::forget("dashboard:org:{$integration->org_id}");

        // Clear sync status cache
        Cache::forget("sync:org:{$integration->org_id}");
        Cache::forget("sync:integration:{$integration->integration_id}");

        // TODO: Update sync statistics
        // TODO: Notify if significant changes detected
    }
}
