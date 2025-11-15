<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Core\{Org, Integration};
use App\Jobs\Sync\{SyncPlatformData, DispatchPlatformSyncs};
use Carbon\Carbon;

class SyncStatusController extends Controller
{
    /**
     * Get sync status for an organization
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

        return response()->json([
            'org_id' => $org->org_id,
            'total_integrations' => $integrations->count(),
            'active_syncs' => $integrations->where('sync_status', 'syncing')->count(),
            'failed_syncs' => $integrations->where('sync_status', 'failed')->count(),
            'integrations' => $status,
        ]);
    }

    /**
     * Get sync status for specific integration
     */
    public function integrationStatus(Org $org, Integration $integration): JsonResponse
    {
        // Verify integration belongs to org
        if ($integration->org_id !== $org->org_id) {
            return response()->json(['error' => 'Integration not found'], 404);
        }

        return response()->json([
            'integration_id' => $integration->integration_id,
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
        ]);
    }

    /**
     * Trigger manual sync for organization (all integrations)
     */
    public function triggerOrgSync(Request $request, Org $org): JsonResponse
    {
        $dataType = $request->input('data_type', 'all');

        // Validate data type
        if (!in_array($dataType, ['all', 'campaigns', 'metrics', 'posts'])) {
            return response()->json(['error' => 'Invalid data type'], 400);
        }

        // Get active integrations
        $integrations = Integration::where('org_id', $org->org_id)
            ->where('is_active', true)
            ->get();

        if ($integrations->isEmpty()) {
            return response()->json(['error' => 'No active integrations found'], 404);
        }

        // Dispatch sync jobs
        foreach ($integrations as $integration) {
            SyncPlatformData::dispatch($integration, $dataType)
                ->onQueue('priority'); // Use priority queue for manual syncs
        }

        return response()->json([
            'message' => 'Sync triggered successfully',
            'integrations_count' => $integrations->count(),
            'data_type' => $dataType,
            'status' => 'queued',
        ]);
    }

    /**
     * Trigger manual sync for specific integration
     */
    public function triggerIntegrationSync(Request $request, Org $org, Integration $integration): JsonResponse
    {
        // Verify integration belongs to org
        if ($integration->org_id !== $org->org_id) {
            return response()->json(['error' => 'Integration not found'], 404);
        }

        if (!$integration->is_active) {
            return response()->json(['error' => 'Integration is not active'], 400);
        }

        $dataType = $request->input('data_type', 'all');

        // Validate data type
        if (!in_array($dataType, ['all', 'campaigns', 'metrics', 'posts'])) {
            return response()->json(['error' => 'Invalid data type'], 400);
        }

        // Dispatch sync job
        SyncPlatformData::dispatch($integration, $dataType)
            ->onQueue('priority');

        return response()->json([
            'message' => 'Sync triggered successfully',
            'integration_id' => $integration->integration_id,
            'provider' => $integration->provider,
            'data_type' => $dataType,
            'status' => 'queued',
        ]);
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

        return response()->json($stats);
    }
}
