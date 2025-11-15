<?php

namespace App\Events\Campaign;

use App\Models\AdPlatform\AdCampaign;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CampaignMetricsUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AdCampaign $campaign,
        public Collection $metrics
    ) {}
}
