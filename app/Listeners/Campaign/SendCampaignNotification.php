<?php

namespace App\Listeners\Campaign;

use Illuminate\Support\Facades\Log;

/**
 * Send campaign notifications
 * Note: Stub implementation
 */
class SendCampaignNotification
{
    /**
     * Constructor
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle event
     *
     * @param mixed $event Campaign event
     * @return void
     */
    public function handle($event): void
    {
        Log::info('SendCampaignNotification::handle called (stub)');
        // Stub implementation - Campaign notification logic not yet implemented
    }
}
