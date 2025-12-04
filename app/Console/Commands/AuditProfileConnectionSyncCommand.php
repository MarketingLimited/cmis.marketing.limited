<?php

namespace App\Console\Commands;

use App\Services\Profile\ProfileConnectionAuditService;
use Illuminate\Console\Command;

/**
 * Artisan command for auditing and fixing profile-connection-asset sync issues.
 *
 * Usage:
 *   php artisan profiles:audit-sync              # Dry run - show issues
 *   php artisan profiles:audit-sync --fix        # Fix all issues
 *   php artisan profiles:audit-sync --org=UUID   # Audit specific org
 *   php artisan profiles:audit-sync --type=orphaned --fix  # Fix only orphaned profiles
 */
class AuditProfileConnectionSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profiles:audit-sync
        {--org= : Specific organization ID to audit (default: all orgs)}
        {--fix : Actually fix issues (default: dry-run mode)}
        {--type= : Specific check type: orphaned|missing|deselected|restore|cleanup|all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit and fix profile-connection-asset synchronization issues';

    /**
     * Execute the console command.
     */
    public function handle(ProfileConnectionAuditService $service): int
    {
        $orgId = $this->option('org');
        $fix = $this->option('fix');
        $type = $this->option('type') ?? 'all';

        $this->info('Profile-Connection Sync Audit');
        $this->info('=============================');
        $this->info('Mode: ' . ($fix ? 'FIX (changes will be made)' : 'DRY-RUN (preview only)'));
        $this->info('Scope: ' . ($orgId ? "Organization: {$orgId}" : 'All organizations'));
        $this->info('Type: ' . ucfirst($type));
        $this->newLine();

        if ($orgId) {
            $results = $this->auditSingleOrg($service, $orgId, $fix, $type);
            $this->displayResults($results);
        } else {
            $results = $this->auditAllOrgs($service, $fix, $type);
            $this->displaySummary($results);
        }

        return Command::SUCCESS;
    }

    /**
     * Audit a single organization.
     */
    protected function auditSingleOrg(
        ProfileConnectionAuditService $service,
        string $orgId,
        bool $fix,
        string $type
    ): array {
        if ($type === 'all') {
            return $service->runFullAudit($orgId, $fix);
        }

        $results = [
            'org_id' => $orgId,
            'fix_mode' => $fix,
            'type' => $type,
        ];

        switch ($type) {
            case 'orphaned':
                $found = $service->findOrphanedProfiles($orgId);
                $results['orphaned_profiles'] = [
                    'found' => $found->count(),
                    'fixed' => $fix ? $service->softDeleteOrphanedProfiles($orgId) : 0,
                ];
                break;

            case 'missing':
                $found = $service->findMissingProfiles($orgId);
                $results['missing_profiles'] = [
                    'found' => count($found),
                    'jobs_dispatched' => $fix ? $service->createMissingProfiles($orgId) : 0,
                ];
                break;

            case 'deselected':
                $found = $service->findProfilesToSoftDelete($orgId);
                $results['deselected_profiles'] = [
                    'found' => $found->count(),
                    'fixed' => $fix ? $service->softDeleteDeselectedProfiles($orgId) : 0,
                ];
                break;

            case 'restore':
                $found = $service->findProfilesToRestore($orgId);
                $results['profiles_to_restore'] = [
                    'found' => $found->count(),
                    'fixed' => $fix ? $service->restoreSelectedProfiles($orgId) : 0,
                ];
                break;

            case 'cleanup':
                $staleSettings = $service->findStaleQueueSettings($orgId);
                $invalidRules = $service->findInvalidBoostRuleRefs($orgId);
                $results['stale_data'] = [
                    'queue_settings_found' => $staleSettings->count(),
                    'boost_rules_found' => $invalidRules->count(),
                    'queue_settings_fixed' => 0,
                    'boost_rules_fixed' => 0,
                ];
                if ($fix) {
                    $cleaned = $service->cleanupStaleData($orgId);
                    $results['stale_data']['queue_settings_fixed'] = $cleaned['queue_settings'];
                    $results['stale_data']['boost_rules_fixed'] = $cleaned['boost_rules'];
                }
                break;

            default:
                $this->error("Unknown type: {$type}. Use: orphaned|missing|deselected|restore|cleanup|all");
                return [];
        }

        return $results;
    }

    /**
     * Audit all organizations.
     */
    protected function auditAllOrgs(
        ProfileConnectionAuditService $service,
        bool $fix,
        string $type
    ): array {
        if ($type === 'all') {
            $this->info('Running full audit for all organizations...');
            return $service->runAuditForAllOrgs($fix);
        }

        // For specific types, we need to iterate manually
        $this->error('Type-specific audits require --org parameter. Use --type=all for all-org audits.');
        return [];
    }

    /**
     * Display results for a single organization.
     */
    protected function displayResults(array $results): void
    {
        if (empty($results)) {
            return;
        }

        $this->newLine();
        $this->info('Results:');
        $this->table(
            ['Category', 'Found', 'Fixed'],
            $this->formatResultsTable($results)
        );

        if (isset($results['duration_seconds'])) {
            $this->newLine();
            $this->info("Duration: {$results['duration_seconds']} seconds");
        }

        if (!($results['fix_mode'] ?? false)) {
            $this->newLine();
            $this->warn('This was a DRY-RUN. Run with --fix to make changes.');
        }
    }

    /**
     * Display summary for all organizations.
     */
    protected function displaySummary(array $summary): void
    {
        if (empty($summary)) {
            return;
        }

        $this->newLine();
        $this->info('Summary for all organizations:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Organizations', $summary['total_orgs'] ?? 0],
                ['Orphaned Profiles Fixed', $summary['total_orphaned_fixed'] ?? 0],
                ['Missing Profile Jobs Dispatched', $summary['total_missing_jobs'] ?? 0],
                ['Deselected Profiles Fixed', $summary['total_deselected_fixed'] ?? 0],
                ['Profiles Restored', $summary['total_restored'] ?? 0],
                ['Stale Data Cleaned', $summary['total_stale_cleaned'] ?? 0],
                ['Duration (seconds)', $summary['duration_seconds'] ?? 0],
            ]
        );
    }

    /**
     * Format results into table rows.
     */
    protected function formatResultsTable(array $results): array
    {
        $rows = [];

        if (isset($results['orphaned_profiles'])) {
            $rows[] = [
                'Orphaned Profiles',
                $results['orphaned_profiles']['found'],
                $results['orphaned_profiles']['fixed'],
            ];
        }

        if (isset($results['missing_profiles'])) {
            $rows[] = [
                'Missing Profiles',
                $results['missing_profiles']['found'],
                $results['missing_profiles']['jobs_dispatched'] . ' jobs',
            ];
        }

        if (isset($results['deselected_profiles'])) {
            $rows[] = [
                'Deselected Profiles',
                $results['deselected_profiles']['found'],
                $results['deselected_profiles']['fixed'],
            ];
        }

        if (isset($results['profiles_to_restore'])) {
            $rows[] = [
                'Profiles to Restore',
                $results['profiles_to_restore']['found'],
                $results['profiles_to_restore']['fixed'],
            ];
        }

        if (isset($results['stale_data'])) {
            $rows[] = [
                'Stale Queue Settings',
                $results['stale_data']['queue_settings_found'],
                $results['stale_data']['queue_settings_fixed'],
            ];
            $rows[] = [
                'Invalid Boost Rule Refs',
                $results['stale_data']['boost_rules_found'],
                $results['stale_data']['boost_rules_fixed'],
            ];
        }

        return $rows;
    }
}
