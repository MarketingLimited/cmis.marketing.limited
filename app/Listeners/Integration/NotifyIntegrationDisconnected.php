<?php

namespace App\Listeners\Integration;

use App\Events\Integration\IntegrationDisconnected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Handles actions when integration is disconnected
 */
class NotifyIntegrationDisconnected implements ShouldQueue
{
    public function handle(IntegrationDisconnected $event): void
    {
        $integration = $event->integration;

        Log::warning('Integration disconnected', [
            'integration_id' => $integration->integration_id,
            'provider' => $integration->provider,
            'org_id' => $integration->org_id,
            'reason' => $event->reason,
        ]);

        // TODO: Send alert to organization users
        // TODO: Pause related campaigns
        // TODO: Update dashboard alerts
    }
}
