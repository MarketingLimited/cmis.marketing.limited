<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDataExportJob;
use App\Models\Analytics\DataExportConfig;
use Illuminate\Console\Command;

/**
 * Process Scheduled Exports Command (Phase 14)
 *
 * Checks for due scheduled exports and dispatches jobs
 * Should be run every 5-15 minutes via Laravel scheduler
 */
class ProcessScheduledExports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exports:process-scheduled
                            {--org= : Process exports for specific organization}
                            {--config= : Process specific export configuration}
                            {--sync : Run synchronously instead of dispatching to queue}
                            {--dry-run : Show what would be processed without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled data exports that are due';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for scheduled exports...');

        if ($configId = $this->option('config')) {
            return $this->processSpecificConfig($configId);
        }

        $query = DataExportConfig::query()
            ->where('is_active', true)
            ->whereNotNull('schedule');

        if ($orgId = $this->option('org')) {
            $query->where('org_id', $orgId);
        }

        // Find due exports
        $dueExports = $query->get()->filter(function ($config) {
            return $config->isDue();
        });

        if ($dueExports->isEmpty()) {
            $this->info('No scheduled exports are due at this time.');
            return self::SUCCESS;
        }

        $this->info("Found {$dueExports->count()} due export(s)");

        if ($this->option('dry-run')) {
            $this->displayDueExports($dueExports);
            return self::SUCCESS;
        }

        $processed = 0;
        $failed = 0;

        foreach ($dueExports as $config) {
            try {
                $this->line("Processing: {$config->name} (ID: {$config->config_id})");

                if ($this->option('sync')) {
                    // Run synchronously
                    $exportService = app(\App\Services\Analytics\DataExportService::class);
                    $log = $exportService->executeExport($config);

                    $this->info("  ✓ Completed: {$log->records_count} records, " .
                               number_format($log->file_size / 1024, 2) . " KB");
                } else {
                    // Dispatch to queue
                    ProcessDataExportJob::dispatch($config->config_id, $config->org_id);
                    $this->info("  ✓ Job dispatched to queue");
                }

                $processed++;
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Summary: {$processed} processed, {$failed} failed");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Process specific export configuration
     */
    protected function processSpecificConfig(string $configId): int
    {
        $config = DataExportConfig::find($configId);

        if (!$config) {
            $this->error("Export configuration not found: {$configId}");
            return self::FAILURE;
        }

        if (!$config->is_active) {
            $this->warn("Export configuration is not active: {$config->name}");
            return self::FAILURE;
        }

        $this->info("Processing export: {$config->name}");

        if ($this->option('dry-run')) {
            $this->table(
                ['Property', 'Value'],
                [
                    ['Name', $config->name],
                    ['Type', $config->export_type],
                    ['Format', $config->format],
                    ['Delivery', $config->delivery_method],
                    ['Schedule', $config->schedule ? json_encode($config->schedule) : 'Manual'],
                    ['Last Run', $config->last_exported_at?->diffForHumans() ?? 'Never'],
                    ['Next Run', $config->next_export_at?->diffForHumans() ?? 'N/A']
                ]
            );
            return self::SUCCESS;
        }

        try {
            if ($this->option('sync')) {
                $exportService = app(\App\Services\Analytics\DataExportService::class);
                $log = $exportService->executeExport($config);

                $this->info("✓ Export completed successfully");
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Records', number_format($log->records_count)],
                        ['File Size', number_format($log->file_size / 1024, 2) . ' KB'],
                        ['Duration', $log->execution_time . 's'],
                        ['Status', $log->status]
                    ]
                );
            } else {
                ProcessDataExportJob::dispatch($config->config_id, $config->org_id);
                $this->info("✓ Export job dispatched to queue");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("✗ Export failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Display due exports
     */
    protected function displayDueExports($exports): void
    {
        $this->table(
            ['Name', 'Type', 'Format', 'Org ID', 'Last Run', 'Next Run'],
            $exports->map(fn($config) => [
                $config->name,
                $config->export_type,
                $config->format,
                substr($config->org_id, 0, 8) . '...',
                $config->last_exported_at?->diffForHumans() ?? 'Never',
                $config->next_export_at?->diffForHumans() ?? 'N/A'
            ])
        );
    }
}
