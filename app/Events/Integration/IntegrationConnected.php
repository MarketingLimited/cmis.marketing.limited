<?php

namespace App\Events\Integration;

use App\Models\Core\Integration;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a new integration is connected
 */
class IntegrationConnected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Integration $integration
    ) {}
}
