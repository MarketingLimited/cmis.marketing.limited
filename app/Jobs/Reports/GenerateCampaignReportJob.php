<?php

namespace App\Jobs\Reports;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCampaignReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaignId;
    protected $reportType;

    public function __construct($campaignId, $reportType = 'summary')
    {
        $this->campaignId = $campaignId;
        $this->reportType = $reportType;
    }

    public function handle()
    {
        // TODO: Implement campaign report generation logic
    }
}
