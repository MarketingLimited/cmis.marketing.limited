<?php

namespace App\Events;

use App\Models\Core\Integration;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an integration token is expiring soon
 *
 * Severity levels:
 * - critical: < 1 day until expiry
 * - urgent: < 3 days until expiry
 * - warning: < 7 days until expiry
 */
class IntegrationTokenExpiring
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Integration $integration;
    public string $severity;
    public bool $wasAutoRefreshed;
    public int $daysUntilExpiry;

    /**
     * Create a new event instance.
     *
     * @param Integration $integration The integration with expiring token
     * @param string $severity Severity level: critical, urgent, warning
     * @param bool $wasAutoRefreshed Whether token was auto-refreshed
     */
    public function __construct(Integration $integration, string $severity, bool $wasAutoRefreshed = false)
    {
        $this->integration = $integration;
        $this->severity = $severity;
        $this->wasAutoRefreshed = $wasAutoRefreshed;

        // Calculate days until expiry
        $this->daysUntilExpiry = $integration->token_expires_at
            ? now()->diffInDays($integration->token_expires_at, false)
            : 0;
    }

    /**
     * Get the event name for broadcasting
     */
    public function broadcastAs(): string
    {
        return 'integration.token.expiring';
    }
}
