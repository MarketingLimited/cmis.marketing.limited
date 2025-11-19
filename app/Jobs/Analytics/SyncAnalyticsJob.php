<?php

namespace App\Jobs\Analytics;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orgId;
    protected $dateRange;

    public function __construct($orgId, $dateRange = null)
    {
        $this->orgId = $orgId;
        $this->dateRange = $dateRange;
    }

    public function handle()
    {
        // TODO: Implement analytics sync logic
    }
}
