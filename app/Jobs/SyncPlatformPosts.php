<?php

namespace App\Jobs;

use App\Models\Core\Integration;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncPlatformPosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected Integration $integration;

    /**
     * Create a new job instance.
     */
    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting posts sync for integration {$this->integration->integration_id} ({$this->integration->platform})");

            $connector = ConnectorFactory::make($this->integration->platform);
            $posts = $connector->syncPosts($this->integration);

            // Update last_sync_at
            $this->integration->update(['last_sync_at' => now()]);

            Log::info("Synced {$posts->count()} posts for integration {$this->integration->integration_id}");
        } catch (\Exception $e) {
            Log::error("Failed to sync posts for integration {$this->integration->integration_id}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job failed after {$this->tries} attempts for integration {$this->integration->integration_id}: {$exception->getMessage()}");
    }
}
