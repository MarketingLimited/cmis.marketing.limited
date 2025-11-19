<?php

namespace App\Events\Campaign;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignCompletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $campaign;
    public $completedAt;

    public function __construct($campaign)
    {
        $this->campaign = $campaign;
        $this->completedAt = now();
    }
}
