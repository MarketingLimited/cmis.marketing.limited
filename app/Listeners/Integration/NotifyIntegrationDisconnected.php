<?php

namespace App\Listeners\Integration;

use App\Events\Integration\IntegrationDisconnected;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\{Cache, DB, Log};

/**
 * Handles actions when integration is disconnected
 */
class NotifyIntegrationDisconnected implements ShouldQueue
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle integration disconnected event
     */
    public function handle(IntegrationDisconnected $event): void
    {
        $integration = $event->integration;
        $reason = $event->reason ?? 'User initiated disconnect';

        Log::warning('NotifyIntegrationDisconnected::handle - Integration disconnected', [
            'integration_id' => $integration->integration_id,
            'provider' => $integration->provider,
            'org_id' => $integration->org_id,
            'reason' => $reason,
        ]);

        // Clear relevant caches
        Cache::forget("dashboard:org:{$integration->org_id}");
        Cache::forget("integrations:org:{$integration->org_id}");
        Cache::forget("sync:integration:{$integration->integration_id}");

        // Determine if this is a critical disconnect (token expiry, revoked access)
        $isCritical = $this->isCriticalDisconnect($reason);
        $priority = $isCritical
            ? NotificationService::PRIORITY_HIGH
            : NotificationService::PRIORITY_MEDIUM;

        $platformName = ucfirst($integration->provider);

        // Get users to notify
        $userIds = $this->getNotifyUserIds($integration);

        foreach ($userIds as $userId) {
            $this->notificationService->notify(
                $userId,
                NotificationService::TYPE_SYSTEM_ALERT,
                __('notifications.integration_disconnected_title', ['platform' => $platformName]),
                __('notifications.integration_disconnected_message', [
                    'platform' => $platformName,
                    'account' => $integration->account_name ?? $integration->external_account_id ?? 'Unknown',
                    'reason' => $reason,
                ]),
                [
                    'org_id' => $integration->org_id,
                    'priority' => $priority,
                    'category' => 'integrations',
                    'related_entity_type' => 'integration',
                    'related_entity_id' => $integration->integration_id,
                    'data' => [
                        'integration_id' => $integration->integration_id,
                        'provider' => $integration->provider,
                        'reason' => $reason,
                        'is_critical' => $isCritical,
                    ],
                    'action_url' => route('integrations.index', [], false),
                    'channels' => $isCritical ? ['in_app', 'email'] : ['in_app'],
                ]
            );
        }

        // Create alert record for critical disconnects
        if ($isCritical) {
            DB::table('cmis.alerts')->insert([
                'org_id' => $integration->org_id,
                'type' => 'integration_disconnected',
                'severity' => 'warning',
                'title' => __('notifications.integration_disconnected_title', ['platform' => $platformName]),
                'message' => __('notifications.integration_disconnected_message', [
                    'platform' => $platformName,
                    'account' => $integration->account_name ?? 'Unknown',
                    'reason' => $reason,
                ]),
                'related_entity_type' => 'integration',
                'related_entity_id' => $integration->integration_id,
                'is_read' => false,
                'created_at' => now(),
            ]);
        }

        // Pause related campaigns if disconnect is critical
        if ($isCritical) {
            $this->pauseRelatedCampaigns($integration, $reason);
        }

        // Record disconnection event
        DB::table('cmis.integration_events')->insert([
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'event_type' => 'disconnected',
            'provider' => $integration->provider,
            'metadata' => json_encode([
                'reason' => $reason,
                'is_critical' => $isCritical,
                'disconnected_at' => now()->toIso8601String(),
            ]),
            'created_at' => now(),
        ]);

        // Update statistics
        DB::table('cmis.integration_statistics')
            ->where('org_id', $integration->org_id)
            ->decrement('total_integrations');
    }

    /**
     * Check if this is a critical disconnect
     */
    protected function isCriticalDisconnect(string $reason): bool
    {
        $criticalReasons = [
            'token_expired',
            'token_revoked',
            'access_revoked',
            'account_suspended',
            'api_error',
            'rate_limited',
        ];

        $reasonLower = strtolower($reason);
        foreach ($criticalReasons as $critical) {
            if (str_contains($reasonLower, $critical)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pause campaigns that use this integration
     */
    protected function pauseRelatedCampaigns($integration, string $reason): void
    {
        $affectedCount = DB::table('cmis_ads.ad_campaigns')
            ->where('org_id', $integration->org_id)
            ->where('platform', $integration->provider)
            ->where('status', 'active')
            ->update([
                'status' => 'paused',
                'paused_reason' => 'integration_disconnected: ' . $reason,
                'updated_at' => now(),
            ]);

        if ($affectedCount > 0) {
            Log::warning('Paused campaigns due to integration disconnect', [
                'integration_id' => $integration->integration_id,
                'affected_campaigns' => $affectedCount,
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

        // Org admins
        $admins = DB::table('cmis.users')
            ->where('org_id', $integration->org_id)
            ->where('is_super_admin', true)
            ->limit(5)
            ->pluck('id')
            ->toArray();

        return array_unique(array_merge($userIds, $admins));
    }
}
