<?php

namespace App\Listeners\Campaign;

use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Send campaign notifications for various events
 */
class SendCampaignNotification
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle event
     */
    public function handle($event): void
    {
        Log::info('SendCampaignNotification::handle - Processing campaign event', [
            'event_class' => get_class($event),
        ]);

        // Determine event type and process accordingly
        $campaign = $event->campaign ?? null;
        if (!$campaign) {
            Log::warning('SendCampaignNotification::handle - No campaign in event');
            return;
        }

        $eventClass = class_basename($event);
        $userId = $campaign->created_by ?? null;

        if (!$userId) {
            Log::warning('SendCampaignNotification::handle - No user to notify');
            return;
        }

        // Determine notification type based on event
        switch ($eventClass) {
            case 'CampaignPaused':
                $this->notifyPaused($campaign, $userId, $event);
                break;
            case 'CampaignResumed':
                $this->notifyResumed($campaign, $userId);
                break;
            case 'CampaignCompleted':
                $this->notifyCompleted($campaign, $userId);
                break;
            case 'CampaignApproved':
                $this->notifyApproved($campaign, $userId);
                break;
            case 'CampaignRejected':
                $this->notifyRejected($campaign, $userId, $event->reason ?? 'No reason provided');
                break;
            default:
                Log::info('SendCampaignNotification::handle - Unhandled event type', [
                    'event_class' => $eventClass,
                ]);
        }
    }

    protected function notifyPaused($campaign, string $userId, $event): void
    {
        $this->notificationService->notify(
            $userId,
            NotificationService::TYPE_CAMPAIGN_ALERT,
            __('notifications.campaign_paused_title', ['name' => $campaign->name]),
            __('notifications.campaign_paused_message', [
                'name' => $campaign->name,
                'reason' => $event->reason ?? 'Manual pause',
            ]),
            [
                'org_id' => $campaign->org_id,
                'priority' => NotificationService::PRIORITY_MEDIUM,
                'category' => 'campaigns',
                'related_entity_type' => 'campaign',
                'related_entity_id' => $campaign->campaign_id ?? $campaign->id,
                'channels' => ['in_app', 'email'],
            ]
        );
    }

    protected function notifyResumed($campaign, string $userId): void
    {
        $this->notificationService->notify(
            $userId,
            NotificationService::TYPE_CAMPAIGN_ALERT,
            __('notifications.campaign_resumed_title', ['name' => $campaign->name]),
            __('notifications.campaign_resumed_message', ['name' => $campaign->name]),
            [
                'org_id' => $campaign->org_id,
                'priority' => NotificationService::PRIORITY_LOW,
                'category' => 'campaigns',
                'related_entity_type' => 'campaign',
                'related_entity_id' => $campaign->campaign_id ?? $campaign->id,
                'channels' => ['in_app'],
            ]
        );
    }

    protected function notifyCompleted($campaign, string $userId): void
    {
        $this->notificationService->notify(
            $userId,
            NotificationService::TYPE_CAMPAIGN_ALERT,
            __('notifications.campaign_completed_title', ['name' => $campaign->name]),
            __('notifications.campaign_completed_message', ['name' => $campaign->name]),
            [
                'org_id' => $campaign->org_id,
                'priority' => NotificationService::PRIORITY_MEDIUM,
                'category' => 'campaigns',
                'related_entity_type' => 'campaign',
                'related_entity_id' => $campaign->campaign_id ?? $campaign->id,
                'channels' => ['in_app', 'email'],
            ]
        );
    }

    protected function notifyApproved($campaign, string $userId): void
    {
        $this->notificationService->notify(
            $userId,
            NotificationService::TYPE_CAMPAIGN_ALERT,
            __('notifications.campaign_approved_title', ['name' => $campaign->name]),
            __('notifications.campaign_approved_message', ['name' => $campaign->name]),
            [
                'org_id' => $campaign->org_id,
                'priority' => NotificationService::PRIORITY_MEDIUM,
                'category' => 'campaigns',
                'related_entity_type' => 'campaign',
                'related_entity_id' => $campaign->campaign_id ?? $campaign->id,
                'channels' => ['in_app', 'email'],
            ]
        );
    }

    protected function notifyRejected($campaign, string $userId, string $reason): void
    {
        $this->notificationService->notify(
            $userId,
            NotificationService::TYPE_CAMPAIGN_ALERT,
            __('notifications.campaign_rejected_title', ['name' => $campaign->name]),
            __('notifications.campaign_rejected_message', [
                'name' => $campaign->name,
                'reason' => $reason,
            ]),
            [
                'org_id' => $campaign->org_id,
                'priority' => NotificationService::PRIORITY_HIGH,
                'category' => 'campaigns',
                'related_entity_type' => 'campaign',
                'related_entity_id' => $campaign->campaign_id ?? $campaign->id,
                'channels' => ['in_app', 'email'],
            ]
        );
    }
}
