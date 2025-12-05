<?php

namespace App\Jobs\Platform;

use App\Models\Platform\OrgAssetAccess;
use App\Models\Platform\PlatformAsset;
use App\Models\Platform\PlatformConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Daily job to verify org asset access records.
 *
 * Runs daily at 4 AM to:
 * - Verify org access records still have valid connections
 * - Mark inactive access records when connections are removed
 * - Update verification timestamps
 * - Clean up orphaned access records
 *
 * This ensures the org_asset_access table remains accurate and
 * doesn't reference deleted or invalid connections/assets.
 */
class VerifyAssetAccessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times to attempt the job.
     */
    public int $tries = 3;

    /**
     * Backoff times in seconds.
     */
    public array $backoff = [60, 300, 900];

    /**
     * Job timeout in seconds (15 minutes).
     */
    public int $timeout = 900;

    /**
     * Optional: specific org ID to verify (null = all orgs).
     */
    protected ?string $orgId;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $orgId = null)
    {
        $this->orgId = $orgId;
        $this->onQueue('maintenance');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('VerifyAssetAccessJob started', [
            'org_id' => $this->orgId,
        ]);

        $stats = [
            'verified' => 0,
            'marked_inactive' => 0,
            'orphaned_removed' => 0,
            'errors' => 0,
        ];

        try {
            // Step 1: Verify connection validity
            $stats = array_merge($stats, $this->verifyConnectionValidity());

            // Step 2: Verify asset existence
            $stats = array_merge($stats, $this->verifyAssetExistence());

            // Step 3: Update verification timestamps for valid records
            $stats['verified'] = $this->updateVerificationTimestamps();

            // Step 4: Clean up orphaned access records
            $stats['orphaned_removed'] = $this->cleanupOrphanedRecords();

        } catch (\Exception $e) {
            Log::error('VerifyAssetAccessJob error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $stats['errors']++;
            throw $e;
        }

        Log::info('VerifyAssetAccessJob completed', $stats);
    }

    /**
     * Verify that access records reference valid, active connections.
     */
    protected function verifyConnectionValidity(): array
    {
        $stats = ['connection_invalid' => 0];

        $query = OrgAssetAccess::query()
            ->where('is_active', true);

        if ($this->orgId) {
            $query->where('org_id', $this->orgId);
        }

        // Get access records with invalid or inactive connections
        $invalidAccessRecords = $query->get()->filter(function ($access) {
            $connection = PlatformConnection::where('connection_id', $access->connection_id)->first();
            return !$connection || $connection->status !== 'active';
        });

        foreach ($invalidAccessRecords as $access) {
            try {
                $access->update([
                    'is_active' => false,
                    'deleted_at' => now(),
                ]);
                $stats['connection_invalid']++;

                Log::debug('Marked access inactive due to invalid connection', [
                    'access_id' => $access->access_id,
                    'connection_id' => $access->connection_id,
                    'org_id' => $access->org_id,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to mark access inactive', [
                    'access_id' => $access->access_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Verify that access records reference existing assets.
     */
    protected function verifyAssetExistence(): array
    {
        $stats = ['asset_missing' => 0];

        $query = OrgAssetAccess::query()
            ->where('is_active', true);

        if ($this->orgId) {
            $query->where('org_id', $this->orgId);
        }

        // Get access records with missing assets
        $missingAssetRecords = $query->get()->filter(function ($access) {
            $asset = PlatformAsset::where('asset_id', $access->asset_id)->first();
            return !$asset || !$asset->is_active;
        });

        foreach ($missingAssetRecords as $access) {
            try {
                $access->update([
                    'is_active' => false,
                    'deleted_at' => now(),
                ]);
                $stats['asset_missing']++;

                Log::debug('Marked access inactive due to missing asset', [
                    'access_id' => $access->access_id,
                    'asset_id' => $access->asset_id,
                    'org_id' => $access->org_id,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to mark access inactive', [
                    'access_id' => $access->access_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Update verification timestamps for all valid, active access records.
     */
    protected function updateVerificationTimestamps(): int
    {
        $query = OrgAssetAccess::query()
            ->where('is_active', true)
            ->whereNull('deleted_at');

        if ($this->orgId) {
            $query->where('org_id', $this->orgId);
        }

        // Update in batches to avoid memory issues
        $updated = 0;
        $query->chunkById(500, function ($records) use (&$updated) {
            foreach ($records as $record) {
                $record->update([
                    'last_verified_at' => now(),
                    'verification_count' => DB::raw('verification_count + 1'),
                ]);
                $updated++;
            }
        }, 'access_id');

        return $updated;
    }

    /**
     * Clean up orphaned access records (soft-deleted, old).
     */
    protected function cleanupOrphanedRecords(): int
    {
        // Remove records that have been soft-deleted for more than 30 days
        $thirtyDaysAgo = now()->subDays(30);

        $query = OrgAssetAccess::query()
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<', $thirtyDaysAgo);

        if ($this->orgId) {
            $query->where('org_id', $this->orgId);
        }

        // Hard delete old soft-deleted records
        $count = $query->count();
        $query->forceDelete();

        Log::info('Removed orphaned access records', [
            'count' => $count,
            'older_than' => $thirtyDaysAgo->toDateTimeString(),
        ]);

        return $count;
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('VerifyAssetAccessJob failed permanently', [
            'org_id' => $this->orgId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
