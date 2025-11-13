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

class SyncPlatformComments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected Integration $integration;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
    }

    public function handle(): void
    {
        try {
            Log::info("Starting comments sync for integration {$this->integration->integration_id}");

            $connector = ConnectorFactory::make($this->integration->platform);
            $comments = $connector->syncComments($this->integration);

            $this->integration->update(['last_sync_at' => now()]);

            Log::info("Synced {$comments->count()} comments for integration {$this->integration->integration_id}");
        } catch (\Exception $e) {
            Log::error("Failed to sync comments: {$e->getMessage()}");
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Comments sync job failed for integration {$this->integration->integration_id}: {$exception->getMessage()}");
    }
}
