<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Services\Social\FacebookSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncFacebookDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;
    public $backoff = [60, 300, 900];

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

    public function handle(): void
    {
        Log::info('Starting Facebook sync job', [
            'integration_id' => $this->integration->integration_id,
            'org_id' => $this->integration->org_id,
        ]);

        try {
            DB::transaction(function () {
                // Set database context for RLS
                DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                    config('cmis.system_user_id'),
                    $this->integration->org_id
                ]);

                $service = new FacebookSyncService($this->integration);

                // Sync account
                $accountResult = $service->syncAccount();

                if (isset($accountResult['error'])) {
                    throw new \Exception($accountResult['error']);
                }

                // Sync posts
                $postsResult = $service->syncPosts($this->from, $this->to, $this->limit);

                Log::info('Facebook sync completed', [
                    'integration_id' => $this->integration->integration_id,
                    'posts_count' => count($postsResult['posts'] ?? []),
                ]);
            });

            // Log sync success
            DB::table('cmis.sync_logs')->insert([
                'org_id' => $this->integration->org_id,
                'source' => 'facebook',
                'status' => 'success',
                'message' => 'Facebook sync completed via job',
                'created_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Facebook sync job failed', [
                'integration_id' => $this->integration->integration_id,
                'org_id' => $this->integration->org_id,
                'error' => $e->getMessage(),
            ]);

            DB::table('cmis.sync_logs')->insert([
                'org_id' => $this->integration->org_id,
                'source' => 'facebook',
                'status' => 'failed',
                'message' => 'Facebook sync failed: ' . $e->getMessage(),
                'created_at' => now(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Facebook sync job failed permanently', [
            'integration_id' => $this->integration->integration_id,
            'org_id' => $this->integration->org_id,
            'error' => $exception->getMessage(),
        ]);

        $this->integration->update([
            'last_sync_at' => now(),
            'last_sync_status' => 'failed',
            'last_sync_error' => $exception->getMessage(),
        ]);
    }
}
