<?php

namespace App\Listeners\Budget;

use App\Events\Budget\BudgetThresholdReached;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Notifies when campaign budget reaches threshold
 */
class NotifyBudgetThreshold implements ShouldQueue
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle budget threshold reached event
     */
    public function handle(BudgetThresholdReached $event): void
    {
        $campaign = $event->campaign;
        $percentage = $event->getPercentageUsed();

        Log::warning('NotifyBudgetThreshold::handle - Budget threshold reached', [
            'campaign_id' => $campaign->campaign_id,
            'campaign_name' => $campaign->name,
            'threshold' => $event->threshold * 100 . '%',
            'current_spend' => $event->currentSpend,
            'budget' => $event->budget,
            'percentage_used' => round($percentage, 2) . '%',
        ]);

        // Get campaign managers/owners to notify
        $userIds = $this->getCampaignManagerIds($campaign);

        // Determine priority based on percentage
        $priority = $percentage >= 100
            ? NotificationService::PRIORITY_CRITICAL
            : ($percentage >= 90 ? NotificationService::PRIORITY_HIGH : NotificationService::PRIORITY_MEDIUM);

        $title = $percentage >= 100
            ? __('notifications.budget_exceeded_title', ['name' => $campaign->name])
            : __('notifications.budget_threshold_title', ['percentage' => round($percentage), 'name' => $campaign->name]);

        $message = $percentage >= 100
            ? __('notifications.budget_exceeded_message', [
                'name' => $campaign->name,
                'overspend' => number_format($event->currentSpend - $event->budget, 2),
            ])
            : __('notifications.budget_threshold_message', [
                'name' => $campaign->name,
                'percentage' => round($percentage),
                'spent' => number_format($event->currentSpend, 2),
                'budget' => number_format($event->budget, 2),
            ]);

        foreach ($userIds as $userId) {
            $this->notificationService->notify(
                $userId,
                NotificationService::TYPE_CAMPAIGN_ALERT,
                $title,
                $message,
                [
                    'org_id' => $campaign->org_id,
                    'priority' => $priority,
                    'category' => 'budget',
                    'related_entity_type' => 'campaign',
                    'related_entity_id' => $campaign->campaign_id,
                    'data' => [
                        'campaign_id' => $campaign->campaign_id,
                        'campaign_name' => $campaign->name,
                        'threshold' => $event->threshold,
                        'current_spend' => $event->currentSpend,
                        'budget' => $event->budget,
                        'percentage_used' => $percentage,
                    ],
                    'action_url' => route('campaigns.show', ['id' => $campaign->campaign_id], false),
                    'channels' => $percentage >= 100 ? ['in_app', 'email', 'slack'] : ['in_app', 'email'],
                ]
            );
        }

        // Create alert record
        DB::table('cmis.alerts')->insert([
            'org_id' => $campaign->org_id,
            'type' => $percentage >= 100 ? 'budget_exceeded' : 'budget_threshold',
            'severity' => $percentage >= 100 ? 'critical' : 'warning',
            'title' => $title,
            'message' => $message,
            'related_entity_type' => 'campaign',
            'related_entity_id' => $campaign->campaign_id,
            'is_read' => false,
            'created_at' => now(),
        ]);

        // If 100% budget used, optionally pause campaign
        if ($percentage >= 100 && config('campaigns.auto_pause_on_budget_exceeded', false)) {
            Log::critical('Auto-pausing campaign due to budget exceeded', [
                'campaign_id' => $campaign->campaign_id,
            ]);

            DB::table('cmis_ads.ad_campaigns')
                ->where('campaign_id', $campaign->campaign_id)
                ->update([
                    'status' => 'paused',
                    'paused_reason' => 'budget_exceeded',
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Get user IDs of campaign managers
     */
    protected function getCampaignManagerIds($campaign): array
    {
        // Get campaign owner/creator
        $userIds = [];

        if (!empty($campaign->created_by)) {
            $userIds[] = $campaign->created_by;
        }

        // Get org admins
        $admins = DB::table('cmis.users')
            ->where('org_id', $campaign->org_id)
            ->where(function ($q) {
                $q->where('is_super_admin', true)
                    ->orWhereExists(function ($subq) {
                        $subq->select(DB::raw(1))
                            ->from('cmis.user_roles')
                            ->whereColumn('user_id', 'cmis.users.id')
                            ->where('role', 'admin');
                    });
            })
            ->pluck('id')
            ->toArray();

        return array_unique(array_merge($userIds, $admins));
    }
}
