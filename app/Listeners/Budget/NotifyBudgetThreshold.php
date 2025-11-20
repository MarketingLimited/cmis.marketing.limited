<?php

namespace App\Listeners\Budget;

use App\Events\Budget\BudgetThresholdReached;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notifies when campaign budget reaches threshold
 * Note: Stub implementation
 */
class NotifyBudgetThreshold implements ShouldQueue
{
    /**
     * Handle budget threshold reached event
     *
     * @param BudgetThresholdReached $event Budget threshold event
     * @return void
     */
    public function handle(BudgetThresholdReached $event): void
    {
        $campaign = $event->campaign;
        $percentage = $event->getPercentageUsed();

        Log::warning('NotifyBudgetThreshold::handle called (stub) - Budget threshold reached', [
            'campaign_id' => $campaign->campaign_id,
            'campaign_name' => $campaign->name,
            'threshold' => $event->threshold * 100 . '%',
            'current_spend' => $event->currentSpend,
            'budget' => $event->budget,
            'percentage_used' => round($percentage, 2) . '%',
        ]);

        // Stub implementation - Send email/SMS notification to campaign managers
        // Stub implementation - Create dashboard alert
        // Stub implementation - Auto-pause campaign if 100% reached (optional)

        // If 100% budget used, consider pausing
        if ($percentage >= 100) {
            Log::critical('NotifyBudgetThreshold::handle called (stub) - Campaign budget exceeded', [
                'campaign_id' => $campaign->campaign_id,
                'overspend' => $event->currentSpend - $event->budget,
            ]);

            // Stub implementation - Pause campaign automatically
            // Stub implementation - Send urgent notification
        }
    }
}
