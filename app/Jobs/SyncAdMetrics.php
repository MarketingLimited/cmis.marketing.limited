<?php

namespace App\Jobs;

use App\Models\Channels\AdAccount;
use App\Services\AdPlatformService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncAdMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $adAccountId,
        public ?Carbon $startDate = null,
        public ?Carbon $endDate = null
    ) {
        $this->startDate = $startDate ?? now()->subDays(7);
        $this->endDate = $endDate ?? now();
    }

    /**
     * Execute the job.
     */
    public function handle(AdPlatformService $adPlatformService): void
    {
        Log::info('Syncing ad metrics', [
            'ad_account_id' => $this->adAccountId,
            'start_date' => $this->startDate->toDateString(),
            'end_date' => $this->endDate->toDateString(),
        ]);

        try {
            $adAccount = AdAccount::findOrFail($this->adAccountId);

            // Update sync status
            $adAccount->update([
                'sync_status' => 'syncing',
                'last_sync_started_at' => now(),
            ]);

            // Sync metrics from platform
            $result = $adPlatformService->syncMetrics(
                $adAccount,
                $this->startDate,
                $this->endDate
            );

            // Update sync status
            $adAccount->update([
                'sync_status' => 'completed',
                'last_sync_completed_at' => now(),
                'last_sync_metrics' => [
                    'campaigns_synced' => $result['campaigns_synced'] ?? 0,
                    'metrics_records' => $result['metrics_records'] ?? 0,
                    'errors' => $result['errors'] ?? [],
                ],
            ]);

            Log::info('Ad metrics sync completed', [
                'ad_account_id' => $this->adAccountId,
                'campaigns_synced' => $result['campaigns_synced'] ?? 0,
                'metrics_records' => $result['metrics_records'] ?? 0,
            ]);

        } catch (\Exception $e) {
            Log::error('Ad metrics sync failed', [
                'ad_account_id' => $this->adAccountId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Ad metrics sync job failed after all retries', [
            'ad_account_id' => $this->adAccountId,
            'error' => $exception->getMessage(),
        ]);

        try {
            $adAccount = AdAccount::find($this->adAccountId);
            if ($adAccount) {
                $adAccount->update([
                    'sync_status' => 'failed',
                    'last_sync_error' => $exception->getMessage(),
                    'last_sync_failed_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update ad account status after job failure', [
                'ad_account_id' => $this->adAccountId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
