<?php

namespace App\Events\Campaign;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignStatusChangedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $campaign;
    public $oldStatus;
    public $newStatus;
    public $timestamp;

    public function __construct($campaign, $oldStatus, $newStatus)
    {
        $this->campaign = $campaign;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->timestamp = now();
    }
}
