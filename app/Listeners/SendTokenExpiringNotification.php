<?php

namespace App\Listeners;

use App\Events\IntegrationTokenExpiring;
use App\Models\Notification\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener to send notifications when integration tokens are expiring
 *
 * Creates in-app notifications for:
 * - Organization owner
 * - Integration creator
 * - Users with 'manage_integrations' permission
 */
class SendTokenExpiringNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;
    public $timeout = 60;

    /**
     * Handle the event.
     */
    public function handle(IntegrationTokenExpiring $event): void
    {
        $integration = $event->integration;
        $severity = $event->severity;
        $wasAutoRefreshed = $event->wasAutoRefreshed;
        $daysUntilExpiry = $event->daysUntilExpiry;

        Log::info('📬 Sending token expiry notification', [
            'integration_id' => $integration->integration_id,
            'platform' => $integration->platform,
            'severity' => $severity,
            'days_until_expiry' => $daysUntilExpiry,
            'auto_refreshed' => $wasAutoRefreshed,
        ]);

        // Prepare notification data
        $notificationData = $this->prepareNotificationData($integration, $severity, $daysUntilExpiry, $wasAutoRefreshed);

        // Get users to notify
        $usersToNotify = $this->getUsersToNotify($integration);

        // Create notifications for each user
        foreach ($usersToNotify as $userId) {
            try {
                Notification::create([
                    'user_id' => $userId,
                    'org_id' => $integration->org_id,
                    'type' => $notificationData['type'],
                    'title' => $notificationData['title'],
                    'message' => $notificationData['message'],
                    'data' => $notificationData['data'],
                    'severity' => $severity,
                    'is_read' => false,
                    'action_url' => $notificationData['action_url'],
                ]);

                Log::info("✅ Notification created for user {$userId}");

            } catch (\Exception $e) {
                Log::error("❌ Failed to create notification for user {$userId}: {$e->getMessage()}");
            }
        }

        Log::info("✅ Sent {count($usersToNotify)} notifications for integration {$integration->integration_id}");
    }

    /**
     * Prepare notification data based on severity and refresh status
     */
    protected function prepareNotificationData(
        $integration,
        string $severity,
        int $daysUntilExpiry,
        bool $wasAutoRefreshed
    ): array {
        $platform = ucfirst($integration->platform);
        $username = $integration->username ?? 'Unknown';

        // If auto-refreshed successfully
        if ($wasAutoRefreshed) {
            return [
                'type' => 'integration_token_refreshed',
                'title' => "✅ {$platform} Token Auto-Refreshed",
                'message' => "The access token for your {$platform} account ({$username}) was automatically refreshed successfully.",
                'data' => [
                    'integration_id' => $integration->integration_id,
                    'platform' => $integration->platform,
                    'username' => $username,
                    'new_expires_at' => $integration->token_expires_at?->toDateTimeString(),
                ],
                'action_url' => "/dashboard/integrations/{$integration->integration_id}",
            ];
        }

        // Expiring token notifications based on severity
        $messages = [
            'critical' => [
                'title' => "🚨 URGENT: {$platform} Token Expires in {$daysUntilExpiry} Day(s)!",
                'message' => "Your {$platform} account ({$username}) token will expire very soon! Please reconnect your account immediately to avoid service disruption.",
            ],
            'urgent' => [
                'title' => "⚠️ {$platform} Token Expires in {$daysUntilExpiry} Days",
                'message' => "Your {$platform} account ({$username}) token is expiring soon. Please reconnect your account to continue using this integration.",
            ],
            'warning' => [
                'title' => "📅 {$platform} Token Expires in {$daysUntilExpiry} Days",
                'message' => "Your {$platform} account ({$username}) token will expire in {$daysUntilExpiry} days. Consider reconnecting your account soon.",
            ],
        ];

        $selectedMessage = $messages[$severity] ?? $messages['warning'];

        return [
            'type' => 'integration_token_expiring',
            'title' => $selectedMessage['title'],
            'message' => $selectedMessage['message'],
            'data' => [
                'integration_id' => $integration->integration_id,
                'platform' => $integration->platform,
                'username' => $username,
                'expires_at' => $integration->token_expires_at?->toDateTimeString(),
                'days_until_expiry' => $daysUntilExpiry,
                'severity' => $severity,
            ],
            'action_url' => "/dashboard/integrations/{$integration->integration_id}/reconnect",
        ];
    }

    /**
     * Get list of user IDs who should receive notifications
     */
    protected function getUsersToNotify($integration): array
    {
        $users = [];

        // 1. Organization owner
        if ($integration->org && $integration->org->owner_id) {
            $users[] = $integration->org->owner_id;
        }

        // 2. Integration creator
        if ($integration->created_by) {
            $users[] = $integration->created_by;
        }

        // 3. Users with 'manage_integrations' permission for this org
        try {
            $permissionUsers = \Illuminate\Support\Facades\DB::table('cmis.user_permissions')
                ->where('org_id', $integration->org_id)
                ->where('permission', 'manage_integrations')
                ->pluck('user_id')
                ->toArray();

            $users = array_merge($users, $permissionUsers);
        } catch (\Exception $e) {
            Log::warning("Failed to fetch users with manage_integrations permission: {$e->getMessage()}");
        }

        // Remove duplicates and return
        return array_unique(array_filter($users));
    }

    /**
     * Handle job failure
     */
    public function failed(IntegrationTokenExpiring $event, \Throwable $exception): void
    {
        Log::error('❌ Failed to send token expiry notification', [
            'integration_id' => $event->integration->integration_id,
            'error' => $exception->getMessage(),
        ]);
    }
}
