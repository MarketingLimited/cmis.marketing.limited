<?php

namespace App\Jobs\Campaign;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateCampaignStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaignId;

    public function __construct($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    public function handle()
    {
        // TODO: Implement campaign stats update logic
    }
}
