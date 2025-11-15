<?php

namespace App\Events\Campaign;

use App\Models\AdPlatform\AdCampaign;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AdCampaign $campaign
    ) {}
}
