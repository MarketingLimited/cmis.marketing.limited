<?php

namespace App\Jobs;

use App\Models\Analytics\DataExportConfig;
use App\Services\Analytics\DataExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Process Data Export Job (Phase 14)
 *
 * Queue job to process scheduled or manual data exports
 */
class ProcessDataExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 300; // 5 minutes

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes for large exports

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $configId,
        public ?string $orgId = null
    ) {
        $this->onQueue('exports');
    }

    /**
     * Execute the job.
     */
    public function handle(DataExportService $exportService): void
    {
        try {
            $config = DataExportConfig::findOrFail($this->configId);

            // Initialize transaction context if org_id provided
            if ($this->orgId) {
                DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                    $config->created_by ?? 'system',
                    $this->orgId
                ]);
            }

            Log::info('Processing data export', [
                'config_id' => $this->configId,
                'org_id' => $config->org_id,
                'export_type' => $config->export_type,
                'format' => $config->format
            ]);

            // Execute the export
            $log = $exportService->executeExport($config);

            Log::info('Data export completed successfully', [
                'config_id' => $this->configId,
                'log_id' => $log->log_id,
                'records_count' => $log->records_count,
                'file_size' => $log->file_size
            ]);

        } catch (\Exception $e) {
            Log::error('Data export failed', [
                'config_id' => $this->configId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Data export job failed permanently', [
            'config_id' => $this->configId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Mark the config as failed if it exists
        try {
            $config = DataExportConfig::find($this->configId);
            if ($config) {
                $config->update([
                    'last_error' => $exception->getMessage(),
                    'last_exported_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update export config after failure', [
                'config_id' => $this->configId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
