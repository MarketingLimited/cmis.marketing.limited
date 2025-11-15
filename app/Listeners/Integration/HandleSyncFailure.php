<?php

namespace App\Listeners\Integration;

use App\Events\Integration\IntegrationSyncFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\{Cache, Log};

/**
 * Handles actions when integration sync fails
 */
class HandleSyncFailure implements ShouldQueue
{
    public function handle(IntegrationSyncFailed $event): void
    {
        $integration = $event->integration;

        Log::error('Integration sync failed', [
            'integration_id' => $integration->integration_id,
            'provider' => $integration->provider,
            'data_type' => $event->dataType,
            'error' => $event->error,
        ]);

        // Clear caches to ensure fresh data on next request
        Cache::forget("sync:org:{$integration->org_id}");
        Cache::forget("sync:integration:{$integration->integration_id}");

        // TODO: Send alert to organization admins
        // TODO: Create incident record
        // TODO: Auto-retry logic (if not already handled)
    }
}
