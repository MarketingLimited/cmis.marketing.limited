<?php

namespace App\Events\Integration;

use App\Models\Core\Integration;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when integration sync completes successfully
 */
class IntegrationSyncCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Integration $integration,
        public string $dataType,
        public array $stats
    ) {}
}
