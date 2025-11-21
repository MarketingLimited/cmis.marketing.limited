<?php

namespace App\Console\Commands;

use App\Jobs\ProcessScheduledReportJob;
use App\Models\Analytics\ScheduledReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Process Scheduled Reports Command (Phase 12)
 *
 * Checks for due scheduled reports and dispatches jobs to process them
 * Should be run every minute via Laravel scheduler
 */
class ProcessScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:process-scheduled
                            {--force : Force process all active schedules regardless of due date}
                            {--schedule= : Process specific schedule by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due scheduled reports and dispatch email delivery jobs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for due scheduled reports...');

        if ($scheduleId = $this->option('schedule')) {
            return $this->processSingleSchedule($scheduleId);
        }

        $query = ScheduledReport::active();

        if (!$this->option('force')) {
            $query->due();
        }

        $dueReports = $query->get();

        if ($dueReports->isEmpty()) {
            $this->info('No due reports found.');
            return self::SUCCESS;
        }

        $this->info("Found {$dueReports->count()} due report(s).");

        $processed = 0;
        $failed = 0;

        foreach ($dueReports as $schedule) {
            try {
                $this->line("Dispatching: {$schedule->name} (ID: {$schedule->schedule_id})");

                // Dispatch job to queue
                ProcessScheduledReportJob::dispatch($schedule->schedule_id);

                $processed++;
            } catch (\Exception $e) {
                $this->error("Failed to dispatch {$schedule->name}: {$e->getMessage()}");
                Log::error("Failed to dispatch scheduled report job", [
                    'schedule_id' => $schedule->schedule_id,
                    'error' => $e->getMessage()
                ]);
                $failed++;
            }
        }

        $this->info("\nProcessing complete:");
        $this->table(
            ['Status', 'Count'],
            [
                ['Dispatched', $processed],
                ['Failed', $failed],
                ['Total', $dueReports->count()]
            ]
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Process a single schedule by ID
     *
     * @param string $scheduleId
     * @return int
     */
    protected function processSingleSchedule(string $scheduleId): int
    {
        $schedule = ScheduledReport::find($scheduleId);

        if (!$schedule) {
            $this->error("Schedule not found: {$scheduleId}");
            return self::FAILURE;
        }

        if (!$schedule->is_active && !$this->option('force')) {
            $this->error("Schedule is not active: {$schedule->name}");
            return self::FAILURE;
        }

        try {
            $this->info("Dispatching: {$schedule->name}");
            ProcessScheduledReportJob::dispatch($schedule->schedule_id);
            $this->info('Job dispatched successfully.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to dispatch job: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
