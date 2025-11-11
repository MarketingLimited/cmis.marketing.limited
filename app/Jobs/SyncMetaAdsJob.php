<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Services\Ads\MetaAdsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncMetaAdsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600; // 10 minutes - ads data can be large
    public $backoff = [120, 600, 1800];

    protected Integration $integration;
    protected ?Carbon $from;
    protected ?Carbon $to;
    protected int $limit;

    public function __construct(Integration $integration, ?Carbon $from = null, ?Carbon $to = null, int $limit = 50)
    {
        $this->integration = $integration;
        $this->from = $from;
        $this->to = $to;
        $this->limit = $limit;
        $this->onQueue('ads-sync');
    }

    public function handle(): void
    {
        Log::info('Starting Meta Ads sync job', [
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

                $service = new MetaAdsService($this->integration);

                // Sync ad account
                $accountResult = $service->syncAccount();

                if (isset($accountResult['error'])) {
                    throw new \Exception($accountResult['error']);
                }

                // Sync campaigns (which cascades to ad sets and ads)
                $campaignsResult = $service->syncCampaigns($this->from, $this->to, $this->limit);

                $campaignIds = collect($campaignsResult['campaigns'] ?? [])->pluck('id')->toArray();

                // Sync metrics for campaigns
                if (!empty($campaignIds)) {
                    $service->syncMetrics($campaignIds);
                }

                Log::info('Meta Ads sync completed', [
                    'integration_id' => $this->integration->integration_id,
                    'campaigns_count' => count($campaignsResult['campaigns'] ?? []),
                ]);
            });

            // Log sync success
            DB::table('cmis.sync_logs')->insert([
                'org_id' => $this->integration->org_id,
                'source' => 'meta_ads',
                'status' => 'success',
                'message' => 'Meta Ads sync completed via job',
                'created_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Meta Ads sync job failed', [
                'integration_id' => $this->integration->integration_id,
                'org_id' => $this->integration->org_id,
                'error' => $e->getMessage(),
            ]);

            DB::table('cmis.sync_logs')->insert([
                'org_id' => $this->integration->org_id,
                'source' => 'meta_ads',
                'status' => 'failed',
                'message' => 'Meta Ads sync failed: ' . $e->getMessage(),
                'created_at' => now(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Meta Ads sync job failed permanently', [
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
