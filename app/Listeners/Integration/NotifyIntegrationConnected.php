<?php

namespace App\Listeners\Integration;

use App\Events\Integration\IntegrationConnected;
use App\Jobs\Sync\SyncIntegrationDataJob;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\{Cache, DB, Log};

/**
 * Handles actions when integration is connected
 */
class NotifyIntegrationConnected implements ShouldQueue
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle integration connected event
     */
    public function handle(IntegrationConnected $event): void
    {
        $integration = $event->integration;

        Log::info('NotifyIntegrationConnected::handle - Integration connected', [
            'integration_id' => $integration->integration_id,
            'provider' => $integration->provider,
            'org_id' => $integration->org_id,
        ]);

        // Clear relevant caches
        Cache::forget("dashboard:org:{$integration->org_id}");
        Cache::forget("integrations:org:{$integration->org_id}");

        // Get users to notify (integration creator and org admins)
        $userIds = $this->getNotifyUserIds($integration);

        $platformName = ucfirst($integration->provider);

        foreach ($userIds as $userId) {
            $this->notificationService->notify(
                $userId,
                NotificationService::TYPE_SYSTEM_ALERT,
                __('notifications.integration_connected_title', ['platform' => $platformName]),
                __('notifications.integration_connected_message', [
                    'platform' => $platformName,
                    'account' => $integration->account_name ?? $integration->external_account_id ?? 'Unknown',
                ]),
                [
                    'org_id' => $integration->org_id,
                    'priority' => NotificationService::PRIORITY_LOW,
                    'category' => 'integrations',
                    'related_entity_type' => 'integration',
                    'related_entity_id' => $integration->integration_id,
                    'data' => [
                        'integration_id' => $integration->integration_id,
                        'provider' => $integration->provider,
                        'account_name' => $integration->account_name,
                    ],
                    'action_url' => route('integrations.show', ['id' => $integration->integration_id], false),
                    'channels' => ['in_app'],
                ]
            );
        }

        // Update analytics - increment integration count
        DB::table('cmis.integration_statistics')
            ->where('org_id', $integration->org_id)
            ->increment('total_integrations');

        // Record connection event
        DB::table('cmis.integration_events')->insert([
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'event_type' => 'connected',
            'provider' => $integration->provider,
            'metadata' => json_encode([
                'account_name' => $integration->account_name,
                'external_account_id' => $integration->external_account_id,
                'connected_at' => now()->toIso8601String(),
            ]),
            'created_at' => now(),
        ]);

        // Trigger initial sync job
        if (class_exists(SyncIntegrationDataJob::class)) {
            SyncIntegrationDataJob::dispatch($integration->integration_id)
                ->delay(now()->addSeconds(5)); // Small delay to ensure connection is fully established

            Log::info('Initial sync job dispatched for integration', [
                'integration_id' => $integration->integration_id,
            ]);
        }
    }

    /**
     * Get user IDs to notify
     */
    protected function getNotifyUserIds($integration): array
    {
        $userIds = [];

        // Integration creator
        if (!empty($integration->created_by)) {
            $userIds[] = $integration->created_by;
        }

        // Org admins (limit to avoid spam)
        $admins = DB::table('cmis.users')
            ->where('org_id', $integration->org_id)
            ->where('is_super_admin', true)
            ->limit(3)
            ->pluck('id')
            ->toArray();

        return array_unique(array_merge($userIds, $admins));
    }
}
