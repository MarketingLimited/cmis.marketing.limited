<?php

namespace App\Listeners\Integration;

use App\Events\Integration\IntegrationSyncFailed;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\{Cache, DB, Log};

/**
 * Handles actions when integration sync fails
 */
class HandleSyncFailure implements ShouldQueue
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle integration sync failed event
     */
    public function handle(IntegrationSyncFailed $event): void
    {
        $integration = $event->integration;
        $dataType = $event->dataType ?? 'all';
        $error = $event->error ?? 'Unknown error';

        Log::error('HandleSyncFailure::handle - Integration sync failed', [
            'integration_id' => $integration->integration_id,
            'provider' => $integration->provider,
            'data_type' => $dataType,
            'error' => $error,
        ]);

        // Clear caches
        Cache::forget("sync:org:{$integration->org_id}");
        Cache::forget("sync:integration:{$integration->integration_id}");

        // Determine severity and priority
        $severity = $this->determineSeverity($error);
        $priority = $severity === 'critical'
            ? NotificationService::PRIORITY_CRITICAL
            : ($severity === 'high' ? NotificationService::PRIORITY_HIGH : NotificationService::PRIORITY_MEDIUM);

        $platformName = ucfirst($integration->provider);

        // Get users to notify
        $userIds = $this->getNotifyUserIds($integration);

        foreach ($userIds as $userId) {
            $this->notificationService->notify(
                $userId,
                NotificationService::TYPE_JOB_FAILED,
                __('notifications.sync_failed_title', ['platform' => $platformName]),
                __('notifications.sync_failed_message', [
                    'platform' => $platformName,
                    'data_type' => $dataType,
                    'error' => $this->sanitizeError($error),
                ]),
                [
                    'org_id' => $integration->org_id,
                    'priority' => $priority,
                    'category' => 'sync',
                    'related_entity_type' => 'integration',
                    'related_entity_id' => $integration->integration_id,
                    'data' => [
                        'integration_id' => $integration->integration_id,
                        'provider' => $integration->provider,
                        'data_type' => $dataType,
                        'error' => $error,
                        'severity' => $severity,
                    ],
                    'action_url' => route('integrations.show', ['id' => $integration->integration_id], false),
                    'channels' => $severity === 'critical' ? ['in_app', 'email', 'slack'] : ['in_app', 'email'],
                ]
            );
        }

        // Create alert record
        DB::table('cmis.alerts')->insert([
            'org_id' => $integration->org_id,
            'type' => 'sync_failed',
            'severity' => $severity,
            'title' => __('notifications.sync_failed_title', ['platform' => $platformName]),
            'message' => __('notifications.sync_failed_message', [
                'platform' => $platformName,
                'data_type' => $dataType,
                'error' => $this->sanitizeError($error),
            ]),
            'related_entity_type' => 'integration',
            'related_entity_id' => $integration->integration_id,
            'is_read' => false,
            'created_at' => now(),
        ]);

        // Create incident record for tracking
        $this->createIncidentRecord($integration, $dataType, $error, $severity);

        // Update integration status
        DB::table('cmis.integrations')
            ->where('integration_id', $integration->integration_id)
            ->update([
                'sync_status' => 'failed',
                'sync_error' => $this->sanitizeError($error),
                'last_sync_attempt_at' => now(),
                'updated_at' => now(),
            ]);

        // Update failure statistics
        DB::table('cmis.integration_statistics')
            ->updateOrInsert(
                ['org_id' => $integration->org_id],
                [
                    'failed_syncs' => DB::raw('COALESCE(failed_syncs, 0) + 1'),
                    'last_failure_at' => now(),
                    'updated_at' => now(),
                ]
            );

        // Record sync event
        DB::table('cmis.integration_events')->insert([
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'event_type' => 'sync_failed',
            'provider' => $integration->provider,
            'metadata' => json_encode([
                'data_type' => $dataType,
                'error' => $error,
                'severity' => $severity,
                'failed_at' => now()->toIso8601String(),
            ]),
            'created_at' => now(),
        ]);

        // Check if auto-retry should be triggered
        $this->checkAutoRetry($integration, $dataType, $error);
    }

    /**
     * Determine severity based on error type
     */
    protected function determineSeverity(string $error): string
    {
        $errorLower = strtolower($error);

        // Critical: Authentication/access issues
        if (
            str_contains($errorLower, 'unauthorized') ||
            str_contains($errorLower, 'token') ||
            str_contains($errorLower, 'authentication') ||
            str_contains($errorLower, 'access denied') ||
            str_contains($errorLower, 'forbidden')
        ) {
            return 'critical';
        }

        // High: Rate limiting or API errors
        if (
            str_contains($errorLower, 'rate limit') ||
            str_contains($errorLower, '429') ||
            str_contains($errorLower, 'too many requests') ||
            str_contains($errorLower, 'api error')
        ) {
            return 'high';
        }

        // Medium: Connection or timeout issues
        if (
            str_contains($errorLower, 'timeout') ||
            str_contains($errorLower, 'connection') ||
            str_contains($errorLower, 'network')
        ) {
            return 'medium';
        }

        // Default to warning
        return 'warning';
    }

    /**
     * Sanitize error message for display
     */
    protected function sanitizeError(string $error): string
    {
        // Truncate long errors
        if (strlen($error) > 500) {
            $error = substr($error, 0, 500) . '...';
        }

        // Remove sensitive data patterns
        $error = preg_replace('/access_token["\']?\s*[:=]\s*["\']?[^"\'&\s]+/i', 'access_token=***', $error);
        $error = preg_replace('/api_key["\']?\s*[:=]\s*["\']?[^"\'&\s]+/i', 'api_key=***', $error);
        $error = preg_replace('/secret["\']?\s*[:=]\s*["\']?[^"\'&\s]+/i', 'secret=***', $error);

        return $error;
    }

    /**
     * Create incident record for tracking
     */
    protected function createIncidentRecord($integration, string $dataType, string $error, string $severity): void
    {
        DB::table('cmis.incidents')->insert([
            'org_id' => $integration->org_id,
            'incident_type' => 'sync_failure',
            'severity' => $severity,
            'status' => 'open',
            'title' => "Sync failure for {$integration->provider}",
            'description' => "Data type: {$dataType}. Error: {$this->sanitizeError($error)}",
            'related_entity_type' => 'integration',
            'related_entity_id' => $integration->integration_id,
            'metadata' => json_encode([
                'provider' => $integration->provider,
                'data_type' => $dataType,
                'error' => $error,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Check if auto-retry should be triggered
     */
    protected function checkAutoRetry($integration, string $dataType, string $error): void
    {
        $severity = $this->determineSeverity($error);

        // Don't auto-retry critical errors (auth issues need manual intervention)
        if ($severity === 'critical') {
            return;
        }

        // Check recent failure count
        $recentFailures = DB::table('cmis.integration_events')
            ->where('integration_id', $integration->integration_id)
            ->where('event_type', 'sync_failed')
            ->where('created_at', '>=', now()->subHours(1))
            ->count();

        // Only retry if less than 3 failures in the last hour
        if ($recentFailures < 3) {
            // Queue retry job with exponential backoff
            $delay = pow(2, $recentFailures) * 60; // 1min, 2min, 4min

            Log::info('Scheduling sync retry', [
                'integration_id' => $integration->integration_id,
                'retry_number' => $recentFailures + 1,
                'delay_seconds' => $delay,
            ]);

            // Note: Actual job dispatch would go here if SyncIntegrationDataJob exists
            // SyncIntegrationDataJob::dispatch($integration->integration_id, $dataType)
            //     ->delay(now()->addSeconds($delay));
        } else {
            Log::warning('Max retries reached, not scheduling automatic retry', [
                'integration_id' => $integration->integration_id,
                'recent_failures' => $recentFailures,
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
