<?php

namespace App\Jobs;

use App\Models\Analytics\ScheduledReport;
use App\Services\Analytics\EmailReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Process Scheduled Report Job (Phase 12)
 *
 * Processes a single scheduled report execution
 * - Generates report
 * - Sends via email
 * - Logs execution
 * - Updates next run time
 */
class ProcessScheduledReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $retryAfter = 300; // 5 minutes

    /**
     * The scheduled report to process
     */
    protected string $scheduleId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $scheduleId)
    {
        $this->scheduleId = $scheduleId;
        $this->onQueue('reports'); // Use dedicated reports queue
    }

    /**
     * Execute the job.
     */
    public function handle(EmailReportService $emailService): void
    {
        $schedule = ScheduledReport::find($this->scheduleId);

        if (!$schedule) {
            Log::warning("Scheduled report not found: {$this->scheduleId}");
            return;
        }

        if (!$schedule->is_active) {
            Log::info("Skipping inactive scheduled report: {$this->scheduleId}");
            return;
        }

        // Initialize RLS context for the organization
        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $schedule->user_id,
            $schedule->org_id
        ]);

        Log::info("Processing scheduled report", [
            'schedule_id' => $schedule->schedule_id,
            'name' => $schedule->name,
            'org_id' => $schedule->org_id,
            'frequency' => $schedule->frequency
        ]);

        try {
            // Process report and send emails
            $log = $emailService->sendScheduledReport($schedule);

            Log::info("Scheduled report processed successfully", [
                'schedule_id' => $schedule->schedule_id,
                'log_id' => $log->log_id,
                'status' => $log->status,
                'emails_sent' => $log->emails_sent,
                'emails_failed' => $log->emails_failed
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to process scheduled report", [
                'schedule_id' => $schedule->schedule_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Rethrow to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Scheduled report job failed after all retries", [
            'schedule_id' => $this->scheduleId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Optionally notify administrators
        // Mail::to(config('mail.admin'))->send(new ScheduledReportFailedNotification(...));
    }
}
