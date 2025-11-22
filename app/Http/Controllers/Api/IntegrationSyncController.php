<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Core\Integration;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * IntegrationSyncController
 *
 * Handles manual sync operations for platform integrations.
 * Allows users to manually retry failed syncs.
 *
 * Issue #80 - Add manual retry for failed syncs
 */
class IntegrationSyncController extends Controller
{
    use ApiResponse;

    /**
     * Get sync status for an integration.
     *
     * GET /api/orgs/{org}/integrations/{integration}/sync-status
     */
    public function getSyncStatus(Request $request, string $org, string $integrationId): JsonResponse
    {
        $integration = Integration::findOrFail($integrationId);
        $this->authorize('view', $integration);

        $lastSync = $integration->last_sync_at;
        $lastError = $integration->last_sync_error;
        $syncStatus = $integration->sync_status ?? 'never_synced';

        $statusInfo = [
            'integration_id' => $integration->id,
            'platform' => $integration->platform,
            'sync_status' => $syncStatus,
            'last_sync_at' => $lastSync?->toIso8601String(),
            'last_successful_sync_at' => $integration->last_successful_sync_at?->toIso8601String(),
            'last_sync_error' => $lastError,
            'consecutive_failures' => $integration->consecutive_sync_failures ?? 0,
            'can_retry' => $this->canRetrySync($integration),
            'next_scheduled_sync' => $this->getNextScheduledSync($integration),
        ];

        // Add failure details if exists
        if ($syncStatus === 'failed' && $lastError) {
            $statusInfo['failure_details'] = [
                'error_message' => $lastError,
                'failed_at' => $lastSync?->toIso8601String(),
                'retry_available' => true,
                'troubleshooting_suggestions' => $this->getTroubleshootingSuggestions($integration),
            ];
        }

        return $this->success($statusInfo);
    }

    /**
     * Manually trigger sync for an integration.
     *
     * POST /api/orgs/{org}/integrations/{integration}/sync
     *
     * Request:
     * {
     *   "type": "all|campaigns|posts|metrics",
     *   "force": false
     * }
     */
    public function triggerSync(Request $request, string $org, string $integrationId): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'nullable|in:all,campaigns,posts,metrics',
            'force' => 'nullable|boolean',
        ]);

        $integration = Integration::findOrFail($integrationId);
        $this->authorize('update', $integration);

        $syncType = $validated['type'] ?? 'all';
        $force = $validated['force'] ?? false;

        // Check if sync is already in progress
        if (!$force && $integration->sync_status === 'in_progress') {
            return $this->error(
                'Sync is already in progress. Use force=true to override.',
                409,
                null,
                'SYNC_IN_PROGRESS'
            );
        }

        // Check rate limiting (max 1 manual sync per 5 minutes)
        $lastSync = $integration->last_sync_at;
        if (!$force && $lastSync && $lastSync->greaterThan(now()->subMinutes(5))) {
            $waitTime = 5 - $lastSync->diffInMinutes(now());
            return $this->error(
                "Please wait {$waitTime} more minute(s) before syncing again.",
                429,
                ['retry_after' => $waitTime * 60],
                'SYNC_RATE_LIMITED'
            );
        }

        // Update sync status
        $integration->update([
            'sync_status' => 'in_progress',
            'last_sync_at' => now(),
        ]);

        try {
            // Get connector for platform
            $connector = ConnectorFactory::make($integration->platform);

            $results = [];

            // Perform sync based on type
            if (in_array($syncType, ['all', 'campaigns'])) {
                $campaigns = $connector->syncCampaigns($integration);
                $results['campaigns'] = [
                    'synced' => $campaigns->count(),
                    'status' => 'success',
                ];
            }

            if (in_array($syncType, ['all', 'posts'])) {
                $posts = $connector->syncPosts($integration);
                $results['posts'] = [
                    'synced' => $posts->count(),
                    'status' => 'success',
                ];
            }

            if (in_array($syncType, ['all', 'metrics'])) {
                $metrics = $connector->syncMetrics($integration);
                $results['metrics'] = [
                    'synced' => count($metrics),
                    'status' => 'success',
                ];
            }

            // Update integration status
            $integration->update([
                'sync_status' => 'success',
                'last_successful_sync_at' => now(),
                'last_sync_error' => null,
                'consecutive_sync_failures' => 0,
            ]);

            Log::info('Manual sync completed successfully', [
                'integration_id' => $integration->id,
                'platform' => $integration->platform,
                'sync_type' => $syncType,
                'results' => $results,
            ]);

            return $this->success([
                'integration_id' => $integration->id,
                'platform' => $integration->platform,
                'sync_type' => $syncType,
                'status' => 'success',
                'results' => $results,
                'synced_at' => now()->toIso8601String(),
            ], 'Sync completed successfully');

        } catch (\Exception $e) {
            // Update failure status
            $integration->update([
                'sync_status' => 'failed',
                'last_sync_error' => $e->getMessage(),
                'consecutive_sync_failures' => ($integration->consecutive_sync_failures ?? 0) + 1,
            ]);

            Log::error('Manual sync failed', [
                'integration_id' => $integration->id,
                'platform' => $integration->platform,
                'sync_type' => $syncType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Sync failed: ' . $e->getMessage(),
                500,
                [
                    'error_code' => get_class($e),
                    'troubleshooting' => $this->getTroubleshootingSuggestions($integration),
                ],
                'SYNC_FAILED'
            );
        }
    }

    /**
     * Get sync history for an integration.
     *
     * GET /api/orgs/{org}/integrations/{integration}/sync-history
     */
    public function getSyncHistory(Request $request, string $org, string $integrationId): JsonResponse
    {
        $integration = Integration::findOrFail($integrationId);
        $this->authorize('view', $integration);

        // Get sync logs from database
        // Note: Assumes a sync_logs table exists. Adjust as needed.
        $history = \DB::table('cmis_platform.sync_logs')
            ->where('integration_id', $integrationId)
            ->orderBy('started_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'sync_type' => $log->sync_type,
                    'status' => $log->status,
                    'started_at' => $log->started_at,
                    'completed_at' => $log->completed_at,
                    'duration_seconds' => $log->duration_seconds,
                    'items_synced' => $log->items_synced,
                    'error_message' => $log->error_message,
                ];
            });

        return $this->success([
            'integration_id' => $integration->id,
            'platform' => $integration->platform,
            'history' => $history,
        ]);
    }

    /**
     * Check if sync can be retried.
     */
    protected function canRetrySync(Integration $integration): bool
    {
        // Cannot retry if already in progress
        if ($integration->sync_status === 'in_progress') {
            return false;
        }

        // Cannot retry if last sync was less than 5 minutes ago
        if ($integration->last_sync_at && $integration->last_sync_at->greaterThan(now()->subMinutes(5))) {
            return false;
        }

        return true;
    }

    /**
     * Get next scheduled sync time.
     */
    protected function getNextScheduledSync(Integration $integration): ?string
    {
        // Scheduled syncs typically run every 1-6 hours depending on plan
        $interval = match($integration->sync_frequency ?? 'standard') {
            'realtime' => 15, // Every 15 minutes
            'frequent' => 60, // Every hour
            'standard' => 360, // Every 6 hours
            default => 1440, // Daily
        };

        if ($integration->last_sync_at) {
            $nextSync = $integration->last_sync_at->addMinutes($interval);
            return $nextSync->toIso8601String();
        }

        return null;
    }

    /**
     * Get troubleshooting suggestions based on error.
     */
    protected function getTroubleshootingSuggestions(Integration $integration): array
    {
        $error = $integration->last_sync_error ?? '';

        $suggestions = [];

        // Check for common error patterns
        if (str_contains($error, 'authentication') || str_contains($error, 'token')) {
            $suggestions[] = 'Your access token may have expired. Please reconnect your ' . ucfirst($integration->platform) . ' account.';
        }

        if (str_contains($error, 'rate limit')) {
            $suggestions[] = 'Platform rate limit reached. Wait a few minutes and try again.';
        }

        if (str_contains($error, 'permission')) {
            $suggestions[] = 'Insufficient permissions. Please ensure your ' . ucfirst($integration->platform) . ' account has the required permissions.';
        }

        if (str_contains($error, 'network') || str_contains($error, 'timeout')) {
            $suggestions[] = 'Network error. Check your internet connection and try again.';
        }

        if (str_contains($error, 'not found')) {
            $suggestions[] = 'Resource not found on ' . ucfirst($integration->platform) . '. It may have been deleted.';
        }

        // Add generic suggestion if no specific match
        if (empty($suggestions)) {
            $suggestions[] = 'Try reconnecting your integration or contact support if the problem persists.';
        }

        return $suggestions;
    }
}
