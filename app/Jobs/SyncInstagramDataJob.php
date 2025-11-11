<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Services\Social\InstagramSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncInstagramDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    protected Integration $integration;
    protected ?Carbon $from;
    protected ?Carbon $to;
    protected int $limit;

    public function __construct(Integration $integration, ?Carbon $from = null, ?Carbon $to = null, int $limit = 25)
    {
        $this->integration = $integration;
        $this->from = $from;
        $this->to = $to;
        $this->limit = $limit;
        $this->onQueue('social-sync');
    }

    public function handle(InstagramSyncService $service): void
    {
        Log::info('Starting Instagram sync job', [
            'integration_id' => $this->integration->integration_id,
            'org_id' => $this->integration->org_id,
        ]);

        try {
            // Set database context for RLS
            DB::transaction(function () use ($service) {
                DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                    config('cmis.system_user_id'),
                    $this->integration->org_id
                ]);

                // Sync the integration
                $service->syncIntegrationByAccountId($this->integration);
            });

            Log::info('Instagram sync completed successfully', [
                'integration_id' => $this->integration->integration_id,
                'org_id' => $this->integration->org_id,
            ]);

            // Log sync success
            DB::table('cmis.sync_logs')->insert([
                'org_id' => $this->integration->org_id,
                'source' => 'instagram',
                'status' => 'success',
                'message' => 'Instagram sync completed via job',
                'created_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Instagram sync job failed', [
                'integration_id' => $this->integration->integration_id,
                'org_id' => $this->integration->org_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Log sync failure
            DB::table('cmis.sync_logs')->insert([
                'org_id' => $this->integration->org_id,
                'source' => 'instagram',
                'status' => 'failed',
                'message' => 'Instagram sync failed: ' . $e->getMessage(),
                'created_at' => now(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Instagram sync job failed permanently', [
            'integration_id' => $this->integration->integration_id,
            'org_id' => $this->integration->org_id,
            'error' => $exception->getMessage(),
        ]);

        // Update integration status or send notification
        $this->integration->update([
            'last_sync_at' => now(),
            'last_sync_status' => 'failed',
            'last_sync_error' => $exception->getMessage(),
        ]);
    }
}
