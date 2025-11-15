<?php

namespace App\Events\Integration;

use App\Models\Core\Integration;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an integration is disconnected
 */
class IntegrationDisconnected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Integration $integration,
        public ?string $reason = null
    ) {}
}
