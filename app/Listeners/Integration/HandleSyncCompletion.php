<?php

namespace App\Listeners\Integration;

use App\Events\Integration\IntegrationSyncCompleted;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\{Cache, DB, Log};

/**
 * Handles actions when integration sync completes
 */
class HandleSyncCompletion implements ShouldQueue
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle integration sync completed event
     */
    public function handle(IntegrationSyncCompleted $event): void
    {
        $integration = $event->integration;
        $dataType = $event->dataType ?? 'all';
        $stats = $event->stats ?? [];

        Log::info('HandleSyncCompletion::handle - Integration sync completed', [
            'integration_id' => $integration->integration_id,
            'provider' => $integration->provider,
            'data_type' => $dataType,
            'stats' => $stats,
        ]);

        // Clear caches
        Cache::forget("dashboard:org:{$integration->org_id}");
        Cache::forget("sync:org:{$integration->org_id}");
        Cache::forget("sync:integration:{$integration->integration_id}");
        Cache::forget("analytics:org:{$integration->org_id}");

        // Update sync statistics
        $this->updateSyncStatistics($integration, $dataType, $stats);

        // Check for significant changes and notify if needed
        $significantChanges = $this->detectSignificantChanges($integration, $stats);

        if (!empty($significantChanges)) {
            $this->notifySignificantChanges($integration, $significantChanges);
        }

        // Update last sync timestamp
        DB::table('cmis.integrations')
            ->where('integration_id', $integration->integration_id)
            ->update([
                'last_sync_at' => now(),
                'sync_status' => 'success',
                'sync_error' => null,
                'updated_at' => now(),
            ]);

        // Record sync event
        DB::table('cmis.integration_events')->insert([
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'event_type' => 'sync_completed',
            'provider' => $integration->provider,
            'metadata' => json_encode([
                'data_type' => $dataType,
                'stats' => $stats,
                'completed_at' => now()->toIso8601String(),
            ]),
            'created_at' => now(),
        ]);
    }

    /**
     * Update sync statistics
     */
    protected function updateSyncStatistics($integration, string $dataType, array $stats): void
    {
        $statRecord = [
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'provider' => $integration->provider,
            'data_type' => $dataType,
            'date' => now()->toDateString(),
        ];

        $updateData = [
            'records_synced' => $stats['records_synced'] ?? $stats['count'] ?? 0,
            'records_created' => $stats['created'] ?? 0,
            'records_updated' => $stats['updated'] ?? 0,
            'records_deleted' => $stats['deleted'] ?? 0,
            'sync_duration_ms' => $stats['duration_ms'] ?? null,
            'completed_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('cmis.sync_statistics')->updateOrInsert($statRecord, $updateData);

        // Update cumulative statistics
        DB::table('cmis.integration_statistics')
            ->updateOrInsert(
                ['org_id' => $integration->org_id],
                [
                    'total_syncs' => DB::raw('COALESCE(total_syncs, 0) + 1'),
                    'successful_syncs' => DB::raw('COALESCE(successful_syncs, 0) + 1'),
                    'last_sync_at' => now(),
                    'updated_at' => now(),
                ]
            );
    }

    /**
     * Detect significant changes that warrant notification
     */
    protected function detectSignificantChanges($integration, array $stats): array
    {
        $changes = [];

        // Large number of new records
        $created = $stats['created'] ?? 0;
        if ($created > 100) {
            $changes[] = [
                'type' => 'bulk_import',
                'message' => __('notifications.sync_bulk_import', ['count' => $created]),
                'count' => $created,
            ];
        }

        // Large number of deleted records
        $deleted = $stats['deleted'] ?? 0;
        if ($deleted > 50) {
            $changes[] = [
                'type' => 'bulk_deletion',
                'message' => __('notifications.sync_bulk_deletion', ['count' => $deleted]),
                'count' => $deleted,
            ];
        }

        // Check for performance anomalies
        $impressionsChange = $stats['impressions_change'] ?? 0;
        if (abs($impressionsChange) > 50) { // 50% change
            $direction = $impressionsChange > 0 ? 'increase' : 'decrease';
            $changes[] = [
                'type' => 'performance_change',
                'message' => __("notifications.sync_impressions_{$direction}", [
                    'percentage' => abs($impressionsChange),
                ]),
                'change' => $impressionsChange,
            ];
        }

        // Check for spend anomalies
        $spendChange = $stats['spend_change'] ?? 0;
        if (abs($spendChange) > 30) { // 30% change in spend
            $direction = $spendChange > 0 ? 'increase' : 'decrease';
            $changes[] = [
                'type' => 'spend_change',
                'message' => __("notifications.sync_spend_{$direction}", [
                    'percentage' => abs($spendChange),
                ]),
                'change' => $spendChange,
            ];
        }

        return $changes;
    }

    /**
     * Notify users about significant changes
     */
    protected function notifySignificantChanges($integration, array $changes): void
    {
        $platformName = ucfirst($integration->provider);

        // Get users to notify (just admins for sync changes)
        $userIds = DB::table('cmis.users')
            ->where('org_id', $integration->org_id)
            ->where('is_super_admin', true)
            ->limit(3)
            ->pluck('id')
            ->toArray();

        if (empty($userIds)) {
            return;
        }

        // Build message from changes
        $changeMessages = array_column($changes, 'message');
        $message = implode('. ', $changeMessages);

        foreach ($userIds as $userId) {
            $this->notificationService->notify(
                $userId,
                NotificationService::TYPE_SYSTEM_ALERT,
                __('notifications.sync_significant_changes_title', ['platform' => $platformName]),
                $message,
                [
                    'org_id' => $integration->org_id,
                    'priority' => NotificationService::PRIORITY_MEDIUM,
                    'category' => 'sync',
                    'related_entity_type' => 'integration',
                    'related_entity_id' => $integration->integration_id,
                    'data' => [
                        'integration_id' => $integration->integration_id,
                        'provider' => $integration->provider,
                        'changes' => $changes,
                    ],
                    'action_url' => route('integrations.show', ['id' => $integration->integration_id], false),
                    'channels' => ['in_app'],
                ]
            );
        }

        Log::info('Significant sync changes notification sent', [
            'integration_id' => $integration->integration_id,
            'changes_count' => count($changes),
        ]);
    }
}
