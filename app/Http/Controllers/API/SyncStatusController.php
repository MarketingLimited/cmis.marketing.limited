<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Concerns\ApiResponse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Core\{Org, Integration};
use App\Jobs\Sync\{SyncPlatformData, DispatchPlatformSyncs};
use Carbon\Carbon;

/**
 * @group Sync Management
 *
 * APIs for managing and monitoring platform data synchronization.
 * The system automatically syncs metrics every hour and campaigns every 4 hours.
 */
class SyncStatusController extends Controller
{
    use ApiResponse;

    /**
     * Get organization sync status
     *
     * Returns sync status for all integrations in an organization.
     * Includes last sync time, current status, errors, and next scheduled sync.
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 200 {
     *   "org_id": "550e8400-e29b-41d4-a716-446655440000",
     *   "total_integrations": 3,
     *   "active_syncs": 0,
     *   "failed_syncs": 1,
     *   "integrations": [
     *     {
     *       "integration_id": "uuid",
     *       "provider": "google",
     *       "platform": "advertising",
     *       "last_synced_at": "2024-01-15T14:30:00Z",
     *       "sync_status": "success",
     *       "sync_errors": null,
     *       "next_sync_at": "2024-01-15T18:30:00Z",
     *       "is_syncing": false,
     *       "token_status": {
     *         "status": "valid",
     *         "message": "Token is valid",
     *         "expires_at": "2024-02-15T14:30:00Z"
     *       }
     *     }
     *   ]
     * }
     *
     * @authenticated
     */
    public function orgStatus(Org $org): JsonResponse
    {
        $integrations = Integration::where('org_id', $org->org_id)
            ->where('is_active', true)
            ->get();

        $status = $integrations->map(function ($integration) {
            return [
                'integration_id' => $integration->integration_id,
                'provider' => $integration->provider,
                'platform' => $integration->platform,
                'last_synced_at' => $integration->last_synced_at?->toIso8601String(),
                'sync_status' => $integration->sync_status,
                'sync_errors' => $integration->sync_errors,
                'next_sync_at' => $this->calculateNextSync($integration),
                'is_syncing' => $integration->sync_status === 'syncing',
                'token_status' => $this->getTokenStatus($integration),
            ];
        });

        return $this->success(['org_id' => $org->org_id,
            'total_integrations' => $integrations->count(),
            'active_syncs' => $integrations->where('sync_status', 'syncing')->count(),
            'failed_syncs' => $integrations->where('sync_status', 'failed')->count(),
            'integrations' => $status,
        ], 'Operation completed successfully');
    }

    /**
     * Get integration sync status
     *
     * Returns detailed sync status for a specific integration including retry count.
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     * @urlParam integration string required Integration UUID. Example: 660e8400-e29b-41d4-a716-446655440001
     *
     * @response 200 {
     *   "integration_id": "660e8400-e29b-41d4-a716-446655440001",
     *   "provider": "meta",
     *   "platform": "advertising",
     *   "last_synced_at": "2024-01-15T14:30:00Z",
     *   "sync_status": "success",
     *   "sync_errors": null,
     *   "sync_retry_count": 0,
     *   "next_sync_at": "2024-01-15T18:30:00Z",
     *   "is_syncing": false,
     *   "token_status": {
     *     "status": "valid",
     *     "message": "Token is valid",
     *     "expires_at": "2024-02-15T14:30:00Z"
     *   },
     *   "is_active": true
     * }
     *
     * @response 404 {
     *   "error": "Integration not found"
     * }
     *
     * @authenticated
     */
    public function integrationStatus(Org $org, Integration $integration): JsonResponse
    {
        // Verify integration belongs to org
        if ($integration->org_id !== $org->org_id) {
            return $this->notFound('Integration not found');
        }

        return $this->success(['integration_id' => $integration->integration_id,
            'provider' => $integration->provider,
            'platform' => $integration->platform,
            'last_synced_at' => $integration->last_synced_at?->toIso8601String(),
            'sync_status' => $integration->sync_status,
            'sync_errors' => $integration->sync_errors,
            'sync_retry_count' => $integration->sync_retry_count,
            'next_sync_at' => $this->calculateNextSync($integration),
            'is_syncing' => $integration->sync_status === 'syncing',
            'token_status' => $this->getTokenStatus($integration),
            'is_active' => $integration->is_active,
        ], 'Operation completed successfully');
    }

    /**
     * Trigger organization sync
     *
     * Manually triggers synchronization for all active integrations in the organization.
     * Sync jobs are queued with priority and execute asynchronously.
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @bodyParam data_type string Data type to sync. Must be one of: all, campaigns, metrics, posts. Defaults to "all". Example: metrics
     *
     * @response 200 {
     *   "message": "Sync triggered successfully",
     *   "integrations_count": 3,
     *   "data_type": "metrics",
     *   "status": "queued"
     * }
     *
     * @response 400 {
     *   "error": "Invalid data type"
     * }
     *
     * @response 404 {
     *   "error": "No active integrations found"
     * }
     *
     * @authenticated
     */
    public function triggerOrgSync(Request $request, Org $org): JsonResponse
    {
        $dataType = $request->input('data_type', 'all');

        // Validate data type
        if (!in_array($dataType, ['all', 'campaigns', 'metrics', 'posts'])) {
            return $this->error('Invalid data type', 400);
        }

        // Get active integrations
        $integrations = Integration::where('org_id', $org->org_id)
            ->where('is_active', true)
            ->get();

        if ($integrations->isEmpty()) {
            return $this->notFound('No active integrations found');
        }

        // Dispatch sync jobs
        foreach ($integrations as $integration) {
            SyncPlatformData::dispatch($integration, $dataType)
                ->onQueue('priority'); // Use priority queue for manual syncs
        }

        return $this->success(['message' => 'Sync triggered successfully',
            'integrations_count' => $integrations->count(),
            'data_type' => $dataType,
            'status' => 'queued',
        ], 'Operation completed successfully');
    }

    /**
     * Trigger integration sync
     *
     * Manually triggers synchronization for a specific integration.
     * The sync job is queued with priority and executes asynchronously.
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     * @urlParam integration string required Integration UUID. Example: 660e8400-e29b-41d4-a716-446655440001
     *
     * @bodyParam data_type string Data type to sync. Must be one of: all, campaigns, metrics, posts. Defaults to "all". Example: campaigns
     *
     * @response 200 {
     *   "message": "Sync triggered successfully",
     *   "integration_id": "660e8400-e29b-41d4-a716-446655440001",
     *   "provider": "meta",
     *   "data_type": "campaigns",
     *   "status": "queued"
     * }
     *
     * @response 400 {
     *   "error": "Invalid data type"
     * }
     *
     * @response 400 {
     *   "error": "Integration is not active"
     * }
     *
     * @response 404 {
     *   "error": "Integration not found"
     * }
     *
     * @authenticated
     */
    public function triggerIntegrationSync(Request $request, Org $org, Integration $integration): JsonResponse
    {
        // Verify integration belongs to org
        if ($integration->org_id !== $org->org_id) {
            return $this->notFound('Integration not found');
        }

        if (!$integration->is_active) {
            return $this->error('Integration is not active', 400);
        }

        $dataType = $request->input('data_type', 'all');

        // Validate data type
        if (!in_array($dataType, ['all', 'campaigns', 'metrics', 'posts'])) {
            return $this->error('Invalid data type', 400);
        }

        // Dispatch sync job
        SyncPlatformData::dispatch($integration, $dataType)
            ->onQueue('priority');

        return $this->success(['message' => 'Sync triggered successfully',
            'integration_id' => $integration->integration_id,
            'provider' => $integration->provider,
            'data_type' => $dataType,
            'status' => 'queued',
        ], 'Operation completed successfully');
    }

    /**
     * Calculate next scheduled sync time
     */
    private function calculateNextSync(Integration $integration): ?string
    {
        if (!$integration->last_synced_at) {
            return 'Pending first sync';
        }

        // Default sync interval: 4 hours
        $nextSync = $integration->last_synced_at->addHours(4);

        if ($nextSync->isPast()) {
            return 'Overdue';
        }

        return $nextSync->toIso8601String();
    }

    /**
     * Get token status
     */
    private function getTokenStatus(Integration $integration): array
    {
        if (!$integration->token_expires_at) {
            return [
                'status' => 'unknown',
                'message' => 'No expiration set',
            ];
        }

        if ($integration->isTokenExpired(0)) {
            return [
                'status' => 'expired',
                'message' => 'Token expired',
                'expired_at' => $integration->token_expires_at->toIso8601String(),
            ];
        }

        if ($integration->needsTokenRefresh()) {
            return [
                'status' => 'expiring_soon',
                'message' => 'Token will expire soon',
                'expires_at' => $integration->token_expires_at->toIso8601String(),
            ];
        }

        return [
            'status' => 'valid',
            'message' => 'Token is valid',
            'expires_at' => $integration->token_expires_at->toIso8601String(),
        ];
    }

    /**
     * Get sync statistics
     *
     * Returns aggregated sync statistics for the organization including
     * totals by provider, status breakdown, and last 24h activity.
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 200 {
     *   "total_integrations": 8,
     *   "by_provider": {
     *     "google": 2,
     *     "meta": 3,
     *     "tiktok": 1,
     *     "linkedin": 1,
     *     "twitter": 1
     *   },
     *   "sync_status": {
     *     "success": 7,
     *     "pending": 0,
     *     "syncing": 0,
     *     "failed": 1
     *   },
     *   "last_24h": {
     *     "synced": 8,
     *     "failed": 1
     *   },
     *   "oldest_sync": "2024-01-14T10:00:00Z",
     *   "newest_sync": "2024-01-15T14:30:00Z"
     * }
     *
     * @authenticated
     */
    public function statistics(Org $org): JsonResponse
    {
        $integrations = Integration::where('org_id', $org->org_id)
            ->where('is_active', true)
            ->get();

        $stats = [
            'total_integrations' => $integrations->count(),
            'by_provider' => $integrations->groupBy('provider')->map->count(),
            'sync_status' => [
                'success' => $integrations->where('sync_status', 'success')->count(),
                'pending' => $integrations->where('sync_status', 'pending')->count(),
                'syncing' => $integrations->where('sync_status', 'syncing')->count(),
                'failed' => $integrations->where('sync_status', 'failed')->count(),
            ],
            'last_24h' => [
                'synced' => $integrations->where('last_synced_at', '>=', now()->subDay())->count(),
                'failed' => $integrations
                    ->where('sync_status', 'failed')
                    ->where('updated_at', '>=', now()->subDay())
                    ->count(),
            ],
            'oldest_sync' => $integrations->min('last_synced_at'),
            'newest_sync' => $integrations->max('last_synced_at'),
        ];

        return $this->success($stats, 'Retrieved successfully');
    }
}
