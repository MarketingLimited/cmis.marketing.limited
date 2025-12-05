<?php

namespace App\Jobs\Platform;

use App\Models\Platform\AssetRelationship;
use App\Models\Platform\OrgAssetAccess;
use App\Models\Platform\PlatformAsset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Weekly job to cleanup stale platform assets.
 *
 * Runs every Sunday at 5 AM to:
 * - Mark assets as inactive if not synced in 30+ days
 * - Hard-delete soft-deleted assets older than 90 days
 * - Clean up orphaned relationships
 * - Generate cleanup report
 *
 * This maintains database hygiene and prevents accumulation
 * of stale data from deactivated connections.
 */
class CleanupStaleAssetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times to attempt the job.
     */
    public int $tries = 2;

    /**
     * Backoff times in seconds.
     */
    public array $backoff = [300, 900];

    /**
     * Job timeout in seconds (30 minutes).
     */
    public int $timeout = 1800;

    /**
     * Days without sync before marking asset as stale.
     */
    protected int $staleDays;

    /**
     * Days after soft-delete before hard-delete.
     */
    protected int $hardDeleteDays;

    /**
     * Create a new job instance.
     */
    public function __construct(int $staleDays = 30, int $hardDeleteDays = 90)
    {
        $this->staleDays = $staleDays;
        $this->hardDeleteDays = $hardDeleteDays;
        $this->onQueue('maintenance');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('CleanupStaleAssetsJob started', [
            'stale_days' => $this->staleDays,
            'hard_delete_days' => $this->hardDeleteDays,
        ]);

        $stats = [
            'marked_inactive' => 0,
            'hard_deleted_assets' => 0,
            'hard_deleted_access' => 0,
            'orphaned_relationships' => 0,
            'errors' => 0,
        ];

        try {
            // Step 1: Mark stale assets as inactive
            $stats['marked_inactive'] = $this->markStaleAssetsInactive();

            // Step 2: Hard delete old soft-deleted assets
            $stats['hard_deleted_assets'] = $this->hardDeleteOldAssets();

            // Step 3: Hard delete old soft-deleted access records
            $stats['hard_deleted_access'] = $this->hardDeleteOldAccessRecords();

            // Step 4: Clean up orphaned relationships
            $stats['orphaned_relationships'] = $this->cleanupOrphanedRelationships();

            // Step 5: Vacuum/optimize tables if needed
            $this->optimizeTables();

        } catch (\Exception $e) {
            Log::error('CleanupStaleAssetsJob error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $stats['errors']++;
            throw $e;
        }

        Log::info('CleanupStaleAssetsJob completed', $stats);
    }

    /**
     * Mark assets as inactive if not synced in X days.
     */
    protected function markStaleAssetsInactive(): int
    {
        $staleDate = now()->subDays($this->staleDays);

        $count = PlatformAsset::query()
            ->where('is_active', true)
            ->where('last_synced_at', '<', $staleDate)
            ->count();

        if ($count > 0) {
            PlatformAsset::query()
                ->where('is_active', true)
                ->where('last_synced_at', '<', $staleDate)
                ->update([
                    'is_active' => false,
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);

            Log::info('Marked stale assets as inactive', [
                'count' => $count,
                'not_synced_since' => $staleDate->toDateTimeString(),
            ]);
        }

        return $count;
    }

    /**
     * Hard delete assets that have been soft-deleted for X days.
     */
    protected function hardDeleteOldAssets(): int
    {
        $deleteDate = now()->subDays($this->hardDeleteDays);

        // First, get the asset IDs to delete (for relationship cleanup)
        $assetIds = PlatformAsset::query()
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<', $deleteDate)
            ->pluck('asset_id')
            ->toArray();

        if (empty($assetIds)) {
            return 0;
        }

        // Delete related access records first
        OrgAssetAccess::whereIn('asset_id', $assetIds)->forceDelete();

        // Delete relationships
        AssetRelationship::whereIn('parent_asset_id', $assetIds)
            ->orWhereIn('child_asset_id', $assetIds)
            ->forceDelete();

        // Hard delete the assets
        $count = PlatformAsset::query()
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<', $deleteDate)
            ->forceDelete();

        Log::info('Hard deleted old assets', [
            'count' => $count,
            'deleted_before' => $deleteDate->toDateTimeString(),
        ]);

        return $count;
    }

    /**
     * Hard delete access records that have been soft-deleted for X days.
     */
    protected function hardDeleteOldAccessRecords(): int
    {
        $deleteDate = now()->subDays($this->hardDeleteDays);

        $count = OrgAssetAccess::query()
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<', $deleteDate)
            ->forceDelete();

        if ($count > 0) {
            Log::info('Hard deleted old access records', [
                'count' => $count,
                'deleted_before' => $deleteDate->toDateTimeString(),
            ]);
        }

        return $count;
    }

    /**
     * Clean up orphaned asset relationships.
     */
    protected function cleanupOrphanedRelationships(): int
    {
        // Find relationships where parent or child no longer exists
        $orphanedParent = AssetRelationship::query()
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('cmis.platform_assets')
                    ->whereRaw('cmis.platform_assets.asset_id = cmis.asset_relationships.parent_asset_id');
            })
            ->pluck('relationship_id')
            ->toArray();

        $orphanedChild = AssetRelationship::query()
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('cmis.platform_assets')
                    ->whereRaw('cmis.platform_assets.asset_id = cmis.asset_relationships.child_asset_id');
            })
            ->pluck('relationship_id')
            ->toArray();

        $orphanedIds = array_unique(array_merge($orphanedParent, $orphanedChild));

        if (!empty($orphanedIds)) {
            $count = AssetRelationship::whereIn('relationship_id', $orphanedIds)->forceDelete();

            Log::info('Cleaned up orphaned relationships', [
                'count' => $count,
            ]);

            return $count;
        }

        return 0;
    }

    /**
     * Optimize tables after cleanup (PostgreSQL specific).
     */
    protected function optimizeTables(): void
    {
        try {
            // ANALYZE updates statistics for query planning
            DB::statement('ANALYZE cmis.platform_assets');
            DB::statement('ANALYZE cmis.org_asset_access');
            DB::statement('ANALYZE cmis.asset_relationships');

            Log::debug('Tables analyzed after cleanup');
        } catch (\Exception $e) {
            // Non-critical, just log warning
            Log::warning('Failed to analyze tables', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CleanupStaleAssetsJob failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
