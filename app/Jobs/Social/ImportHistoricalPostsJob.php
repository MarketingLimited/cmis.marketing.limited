<?php

namespace App\Jobs\Social;

use App\Models\Integration;
use App\Services\Social\HistoricalContentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Import Historical Posts Job
 *
 * Asynchronously imports historical posts from a platform integration.
 */
class ImportHistoricalPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;

    private string $integrationId;
    private array $options;
    private ?string $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $integrationId,
        array $options = [],
        ?string $userId = null
    ) {
        $this->integrationId = $integrationId;
        $this->options = $options;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(HistoricalContentService $service): void
    {
        try {
            $integration = Integration::findOrFail($this->integrationId);

            Log::info('Starting historical import', [
                'integration_id' => $this->integrationId,
                'platform' => $integration->platform,
                'options' => $this->options,
            ]);

            // Set org context for RLS
            \DB::statement("SET app.current_org_id = '{$integration->org_id}'");

            $result = $service->importFromPlatform($integration, $this->options);

            Log::info('Historical import completed', [
                'integration_id' => $this->integrationId,
                'imported_count' => $result['imported_count'],
                'success_posts' => $result['success_posts'],
            ]);

            // TODO: Send notification to user
            // if ($this->userId) {
            //     $user = User::find($this->userId);
            //     $user->notify(new HistoricalImportCompleted($result));
            // }

        } catch (\Exception $e) {
            Log::error('Historical import job failed', [
                'integration_id' => $this->integrationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Historical import job failed permanently', [
            'integration_id' => $this->integrationId,
            'error' => $exception->getMessage(),
        ]);

        // TODO: Notify user of failure
    }
}
