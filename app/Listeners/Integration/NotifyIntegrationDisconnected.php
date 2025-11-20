<?php

namespace App\Listeners\Integration;

use App\Events\Integration\IntegrationDisconnected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Handles actions when integration is disconnected
 * Note: Stub implementation
 */
class NotifyIntegrationDisconnected implements ShouldQueue
{
    /**
     * Handle integration disconnected event
     *
     * @param IntegrationDisconnected $event Integration disconnected event
     * @return void
     */
    public function handle(IntegrationDisconnected $event): void
    {
        $integration = $event->integration;

        Log::warning('NotifyIntegrationDisconnected::handle called (stub) - Integration disconnected', [
            'integration_id' => $integration->integration_id,
            'provider' => $integration->provider,
            'org_id' => $integration->org_id,
            'reason' => $event->reason,
        ]);

        // Stub implementation - Send alert to organization users
        // Stub implementation - Pause related campaigns
        // Stub implementation - Update dashboard alerts
    }
}
