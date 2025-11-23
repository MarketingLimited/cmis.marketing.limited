<?php

namespace App\Console\Commands;

use App\Services\Automation\AutomationExecutionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessAutomationSchedulesCommand extends Command
{
    protected $signature = 'automation:process-schedules
                          {--limit=50 : Maximum number of schedules to process}
                          {--dry-run : Run without executing actions}';

    protected $description = 'Process due automation schedules and execute automation rules';

    private AutomationExecutionService $executionService;

    public function __construct(AutomationExecutionService $executionService)
    {
        parent::__construct();
        $this->executionService = $executionService;
    }

    public function handle(): int
    {
        $this->info('🤖 Processing automation schedules...');

        try {
            $dryRun = $this->option('dry-run');

            if ($dryRun) {
                $this->warn('⚠️  DRY RUN MODE - No actions will be executed');
            }

            $results = $this->executionService->processDueSchedules();

            $this->info("✅ Processed: {$results['processed']}");
            $this->info("✅ Successful: {$results['successful']}");
            $this->warn("⚠️  Failed: {$results['failed']}");
            $this->comment("ℹ️  Skipped: {$results['skipped']}");

            if ($results['failed'] > 0 && !empty($results['details'])) {
                $this->error("\nFailed schedules:");
                foreach ($results['details'] as $detail) {
                    if ($detail['status'] === 'failed') {
                        $this->error("  - Schedule {$detail['schedule_id']}: {$detail['error']}");
                    }
                }
            }

            Log::info('Automation schedules processed', $results);

            return $results['failed'] > 0 ? self::FAILURE : self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Failed to process automation schedules: {$e->getMessage()}");
            Log::error('Automation schedule processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }
}
