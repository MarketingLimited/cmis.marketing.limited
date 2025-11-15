<?php

namespace App\Events\Budget;

use App\Models\AdCampaign;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when campaign budget reaches a threshold (e.g., 80%, 90%, 100%)
 */
class BudgetThresholdReached
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AdCampaign $campaign,
        public float $threshold, // e.g., 0.8 for 80%
        public float $currentSpend,
        public float $budget
    ) {}

    public function getPercentageUsed(): float
    {
        return ($this->currentSpend / $this->budget) * 100;
    }
}
