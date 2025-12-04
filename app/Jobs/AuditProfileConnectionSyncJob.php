<?php

namespace App\Jobs;

use App\Services\Profile\ProfileConnectionAuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queued job for running profile-connection sync audits.
 *
 * Can audit:
 * - Single organization (when orgId is provided)
 * - All organizations (when orgId is null)
 *
 * Scheduled to run every 6 hours to detect and fix:
 * - Orphaned profiles (connection deleted/inactive)
 * - Missing profiles (selected assets without profiles)
 * - Profiles to soft-delete (deselected assets)
 * - Profiles to restore (re-selected assets)
 * - Stale queue settings and boost rule references
 */
class AuditProfileConnectionSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 120;

    /**
     * Create a new job instance.
     *
     * @param string|null $orgId Organization ID to audit (null = all orgs)
     * @param bool $fix Whether to actually fix issues (true) or just audit (false)
     */
    public function __construct(
        public ?string $orgId = null,
        public bool $fix = true
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ProfileConnectionAuditService $service): void
    {
        Log::channel('profile-audit')->info('Starting profile-connection sync audit job', [
            'org_id' => $this->orgId ?? 'all',
            'fix_mode' => $this->fix,
        ]);

        try {
            if ($this->orgId) {
                $results = $service->runFullAudit($this->orgId, $this->fix);
                $this->logResults($results);
            } else {
                $summary = $service->runAuditForAllOrgs($this->fix);
                $this->logSummary($summary);
            }
        } catch (\Throwable $e) {
            Log::channel('profile-audit')->error('Profile-connection sync audit job failed', [
                'org_id' => $this->orgId ?? 'all',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Log results for single org audit.
     */
    protected function logResults(array $results): void
    {
        $totalFixed = ($results['orphaned_profiles']['fixed'] ?? 0)
            + ($results['missing_profiles']['jobs_dispatched'] ?? 0)
            + ($results['deselected_profiles']['fixed'] ?? 0)
            + ($results['profiles_to_restore']['fixed'] ?? 0)
            + ($results['stale_data']['queue_settings_fixed'] ?? 0)
            + ($results['stale_data']['boost_rules_fixed'] ?? 0);

        $totalFound = ($results['orphaned_profiles']['found'] ?? 0)
            + ($results['missing_profiles']['found'] ?? 0)
            + ($results['deselected_profiles']['found'] ?? 0)
            + ($results['profiles_to_restore']['found'] ?? 0)
            + ($results['stale_data']['queue_settings_found'] ?? 0)
            + ($results['stale_data']['boost_rules_found'] ?? 0);

        Log::channel('profile-audit')->info('Profile-connection sync audit completed', [
            'org_id' => $results['org_id'],
            'fix_mode' => $results['fix_mode'],
            'total_issues_found' => $totalFound,
            'total_issues_fixed' => $totalFixed,
            'duration_seconds' => $results['duration_seconds'] ?? 0,
        ]);
    }

    /**
     * Log summary for all-org audit.
     */
    protected function logSummary(array $summary): void
    {
        $totalFixed = ($summary['total_orphaned_fixed'] ?? 0)
            + ($summary['total_missing_jobs'] ?? 0)
            + ($summary['total_deselected_fixed'] ?? 0)
            + ($summary['total_restored'] ?? 0)
            + ($summary['total_stale_cleaned'] ?? 0);

        Log::channel('profile-audit')->info('All-org profile-connection sync audit completed', [
            'total_orgs_processed' => $summary['total_orgs'],
            'total_issues_fixed' => $totalFixed,
            'duration_seconds' => $summary['duration_seconds'] ?? 0,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('profile-audit')->error('Profile-connection sync audit job failed permanently', [
            'org_id' => $this->orgId ?? 'all',
            'fix_mode' => $this->fix,
            'error' => $exception->getMessage(),
        ]);
    }
}
