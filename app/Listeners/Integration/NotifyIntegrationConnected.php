<?php

namespace App\Listeners\Integration;

use App\Events\Integration\IntegrationConnected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Handles actions when integration is connected
 */
class NotifyIntegrationConnected implements ShouldQueue
{
    public function handle(IntegrationConnected $event): void
    {
        $integration = $event->integration;

        Log::info('Integration connected', [
            'integration_id' => $integration->integration_id,
            'provider' => $integration->provider,
            'org_id' => $integration->org_id,
        ]);

        // TODO: Send notification to organization users
        // TODO: Trigger initial sync
        // TODO: Update analytics
    }
}
