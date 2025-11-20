<?php

namespace App\Listeners\Integration;

use App\Events\Integration\IntegrationConnected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Handles actions when integration is connected
 * Note: Stub implementation
 */
class NotifyIntegrationConnected implements ShouldQueue
{
    /**
     * Handle integration connected event
     *
     * @param IntegrationConnected $event Integration connected event
     * @return void
     */
    public function handle(IntegrationConnected $event): void
    {
        $integration = $event->integration;

        Log::info('NotifyIntegrationConnected::handle called (stub) - Integration connected', [
            'integration_id' => $integration->integration_id,
            'provider' => $integration->provider,
            'org_id' => $integration->org_id,
        ]);

        // Stub implementation - Send notification to organization users
        // Stub implementation - Trigger initial sync
        // Stub implementation - Update analytics
    }
}
