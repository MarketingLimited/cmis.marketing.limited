<?php

namespace App\Listeners\Budget;

use App\Events\Budget\BudgetThresholdReached;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notifies when campaign budget reaches threshold
 */
class NotifyBudgetThreshold implements ShouldQueue
{
    public function handle(BudgetThresholdReached $event): void
    {
        $campaign = $event->campaign;
        $percentage = $event->getPercentageUsed();

        Log::warning('Budget threshold reached', [
            'campaign_id' => $campaign->campaign_id,
            'campaign_name' => $campaign->name,
            'threshold' => $event->threshold * 100 . '%',
            'current_spend' => $event->currentSpend,
            'budget' => $event->budget,
            'percentage_used' => round($percentage, 2) . '%',
        ]);

        // TODO: Send email/SMS notification to campaign managers
        // TODO: Create dashboard alert
        // TODO: Auto-pause campaign if 100% reached (optional)

        // If 100% budget used, consider pausing
        if ($percentage >= 100) {
            Log::critical('Campaign budget exceeded', [
                'campaign_id' => $campaign->campaign_id,
                'overspend' => $event->currentSpend - $event->budget,
            ]);

            // TODO: Pause campaign automatically
            // TODO: Send urgent notification
        }
    }
}
