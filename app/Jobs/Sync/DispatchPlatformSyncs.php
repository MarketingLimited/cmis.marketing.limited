<?php

namespace App\Jobs\Sync;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Core\Integration;

class DispatchPlatformSyncs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $dataType = 'all' // 'campaigns', 'metrics', 'posts', 'all'
    ) {
        $this->onQueue('sync');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Dispatching platform syncs", ['data_type' => $this->dataType]);

        // Get all active integrations
        $integrations = Integration::where('is_active', true)->get();

        Log::info("Found {$integrations->count()} active integrations");

        foreach ($integrations as $integration) {
            // Skip if token is expired and can't be refreshed
            if ($integration->isTokenExpired(0) && !$integration->refresh_token) {
                Log::warning("Skipping sync - token expired", [
                    'integration_id' => $integration->integration_id,
                    'provider' => $integration->provider,
                ]);
                continue;
            }

            // Dispatch sync job with random delay to stagger requests
            SyncPlatformData::dispatch($integration, $this->dataType)
                ->onQueue('sync')
                ->delay(now()->addSeconds(rand(0, 300))); // Stagger over 5 minutes

            Log::debug("Dispatched sync job", [
                'integration_id' => $integration->integration_id,
                'provider' => $integration->provider,
                'data_type' => $this->dataType,
            ]);
        }

        Log::info("Dispatched {$integrations->count()} sync jobs");
    }
}
