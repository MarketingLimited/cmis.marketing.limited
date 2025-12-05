<?php

namespace App\Jobs\Backup;

use App\Models\Backup\BackupSchedule;
use App\Models\Backup\OrganizationBackup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled Backup Job
 *
 * Triggered by the Laravel scheduler to process due backup schedules.
 * Creates backup records and dispatches ProcessBackupJob for each.
 */
class ScheduledBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of retry attempts
     */
    public int $tries = 3;

    /**
     * Job timeout in seconds
     */
    public int $timeout = 300;

    /**
     * Schedule ID to process (optional)
     */
    protected ?string $scheduleId;

    /**
     * Create a new job instance
     */
    public function __construct(?string $scheduleId = null)
    {
        $this->scheduleId = $scheduleId;
        $this->onQueue('backups');
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        if ($this->scheduleId) {
            $this->processSchedule(BackupSchedule::find($this->scheduleId));
            return;
        }

        // Process all due schedules
        $dueSchedules = BackupSchedule::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', now());
            })
            ->get();

        Log::info("Processing scheduled backups", [
            'count' => $dueSchedules->count(),
        ]);

        foreach ($dueSchedules as $schedule) {
            $this->processSchedule($schedule);
        }
    }

    /**
     * Process a single schedule
     */
    protected function processSchedule(?BackupSchedule $schedule): void
    {
        if (!$schedule || !$schedule->is_active) {
            return;
        }

        try {
            Log::info("Processing backup schedule", [
                'schedule_id' => $schedule->id,
                'org_id' => $schedule->org_id,
                'frequency' => $schedule->frequency,
            ]);

            // Create backup record
            $backup = OrganizationBackup::create([
                'org_id' => $schedule->org_id,
                'backup_code' => OrganizationBackup::generateBackupCode(),
                'name' => $schedule->name . ' - ' . now()->format('Y-m-d H:i'),
                'description' => "Automated backup from schedule: {$schedule->name}",
                'type' => 'scheduled',
                'status' => 'pending',
                'storage_disk' => $schedule->storage_disk,
                'created_by' => $schedule->created_by,
            ]);

            // Dispatch backup job
            ProcessBackupJob::dispatch(
                $backup,
                $schedule->categories,
                config('backup.encryption.encrypt_by_default', false)
            );

            // Update schedule
            $schedule->update([
                'last_run_at' => now(),
                'next_run_at' => $this->calculateNextRun($schedule),
            ]);

            Log::info("Scheduled backup dispatched", [
                'backup_id' => $backup->id,
                'next_run' => $schedule->next_run_at,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to process backup schedule", [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate next run time for a schedule
     */
    protected function calculateNextRun(BackupSchedule $schedule): \DateTime
    {
        $timezone = $schedule->timezone ?? 'UTC';
        $now = now($timezone);

        // Parse scheduled time
        [$hours, $minutes] = explode(':', $schedule->time);

        $next = $now->copy()->setTime((int) $hours, (int) $minutes, 0);

        // If time has passed today, start from tomorrow
        if ($next <= $now) {
            $next->addDay();
        }

        return match ($schedule->frequency) {
            'hourly' => $now->addHour()->startOfHour(),
            'daily' => $next,
            'weekly' => $this->calculateWeeklyRun($next, $schedule->day_of_week),
            'monthly' => $this->calculateMonthlyRun($next, $schedule->day_of_month),
            default => $next,
        };
    }

    /**
     * Calculate next weekly run
     */
    protected function calculateWeeklyRun(\DateTime $base, ?int $dayOfWeek): \DateTime
    {
        $dayOfWeek = $dayOfWeek ?? 0; // Default to Sunday

        $current = (int) $base->format('w');
        $daysUntil = ($dayOfWeek - $current + 7) % 7;

        if ($daysUntil === 0 && $base <= now()) {
            $daysUntil = 7;
        }

        return (clone $base)->addDays($daysUntil);
    }

    /**
     * Calculate next monthly run
     */
    protected function calculateMonthlyRun(\DateTime $base, ?int $dayOfMonth): \DateTime
    {
        $dayOfMonth = $dayOfMonth ?? 1; // Default to 1st

        $next = (clone $base)->setDate(
            (int) $base->format('Y'),
            (int) $base->format('m'),
            min($dayOfMonth, (int) $base->format('t')) // Handle months with fewer days
        );

        if ($next <= now()) {
            $next->addMonth();
            // Adjust for month length
            $next->setDate(
                (int) $next->format('Y'),
                (int) $next->format('m'),
                min($dayOfMonth, (int) $next->format('t'))
            );
        }

        return $next;
    }
}
