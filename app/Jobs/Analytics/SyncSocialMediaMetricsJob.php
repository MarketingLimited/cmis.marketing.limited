<?php

namespace App\Jobs\Analytics;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncSocialMediaMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $platform;
    protected $accountId;

    public function __construct($platform, $accountId)
    {
        $this->platform = $platform;
        $this->accountId = $accountId;
    }

    public function handle()
    {
        // TODO: Implement social media metrics sync logic
    }
}
