<?php

namespace App\Listeners\Campaign;

use App\Events\Campaign\CampaignCreated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\{Cache, DB, Log};

/**
 * Notifies when campaign status changes
 */
class NotifyCampaignStatusChange implements ShouldQueue
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle campaign created event
     */
    public function handle(CampaignCreated $event): void
    {
        $campaign = $event->campaign;

        Log::info('NotifyCampaignStatusChange::handle - Campaign created', [
            'campaign_id' => $campaign->campaign_id ?? $campaign->id,
            'name' => $campaign->name,
            'status' => $campaign->status,
            'platform' => $campaign->platform ?? 'N/A',
        ]);

        // Clear caches
        if (isset($campaign->org_id)) {
            Cache::forget("dashboard:org:{$campaign->org_id}");
            Cache::forget("campaigns:org:{$campaign->org_id}");
        }

        // Get users to notify (campaign creator and org admins)
        $userIds = $this->getNotifyUserIds($campaign);

        foreach ($userIds as $userId) {
            $this->notificationService->notify(
                $userId,
                NotificationService::TYPE_CAMPAIGN_ALERT,
                __('notifications.campaign_created_title', ['name' => $campaign->name]),
                __('notifications.campaign_created_message', [
                    'name' => $campaign->name,
                    'platform' => ucfirst($campaign->platform ?? 'Unknown'),
                    'status' => ucfirst($campaign->status ?? 'draft'),
                ]),
                [
                    'org_id' => $campaign->org_id ?? null,
                    'priority' => NotificationService::PRIORITY_LOW,
                    'category' => 'campaigns',
                    'related_entity_type' => 'campaign',
                    'related_entity_id' => $campaign->campaign_id ?? $campaign->id,
                    'data' => [
                        'campaign_id' => $campaign->campaign_id ?? $campaign->id,
                        'campaign_name' => $campaign->name,
                        'platform' => $campaign->platform,
                        'status' => $campaign->status,
                    ],
                    'action_url' => route('campaigns.show', ['id' => $campaign->campaign_id ?? $campaign->id], false),
                    'channels' => ['in_app'],
                ]
            );
        }

        // Update analytics aggregates
        if (isset($campaign->org_id)) {
            DB::table('cmis.campaign_statistics')
                ->where('org_id', $campaign->org_id)
                ->increment('total_campaigns');
        }
    }

    /**
     * Get user IDs to notify
     */
    protected function getNotifyUserIds($campaign): array
    {
        $userIds = [];

        // Campaign creator
        if (!empty($campaign->created_by)) {
            $userIds[] = $campaign->created_by;
        }

        // Org admins (limit notifications to avoid spam)
        if (isset($campaign->org_id)) {
            $admins = DB::table('cmis.users')
                ->where('org_id', $campaign->org_id)
                ->where('is_super_admin', true)
                ->limit(5)
                ->pluck('id')
                ->toArray();

            $userIds = array_merge($userIds, $admins);
        }

        return array_unique($userIds);
    }
}
