<?php

namespace App\Jobs\Export;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportCampaignDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaignId;
    protected $format;

    public function __construct($campaignId, $format = 'csv')
    {
        $this->campaignId = $campaignId;
        $this->format = $format;
    }

    public function handle()
    {
        // TODO: Implement campaign data export logic
    }
}
