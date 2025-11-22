<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupSystemData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmis:cleanup
                            {--logs : Clean up old logs}
                            {--temp : Clean up temporary files}
                            {--cache : Clean up expired cache}
                            {--old-data : Archive old campaign data}
                            {--days=90 : Days to keep (older data will be archived)}
                            {--dry-run : Show what would be cleaned without actually doing it}
                            {--force : Required for production environment}
                            {--all : Run all cleanup tasks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old data, logs, and optimize database performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Starting system cleanup...');

        $dryRun = $this->option('dry-run');
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        $all = $this->option('all');
        $environment = app()->environment();

        // Production safety checks
        if ($environment === 'production' && !$dryRun) {
            if (!$this->option('force')) {
                $this->error('❌ Cannot run cleanup in production without --force flag');
                $this->warn('💡 Use --dry-run to preview what would be deleted, or add --force to proceed');
                return Command::FAILURE;
            }

            // Show preview of what will be affected
            $this->warn('⚠️  PRODUCTION ENVIRONMENT DETECTED');
            $this->warn('This operation will:');
            $this->showPreview($cutoffDate, $all);
            $this->newLine();

            // Require explicit confirmation phrase
            $confirmation = $this->ask('Type "DELETE PRODUCTION DATA" to confirm (or anything else to cancel)');

            if ($confirmation !== 'DELETE PRODUCTION DATA') {
                $this->info('❌ Cleanup cancelled - confirmation phrase did not match');
                return Command::FAILURE;
            }

            $this->warn('⚠️  Proceeding with production data cleanup...');
            $this->newLine();
        }

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->showPreview($cutoffDate, $all);
            $this->newLine();
        }

        try {
            $totalCleaned = 0;

            // Clean up logs
            if ($all || $this->option('logs')) {
                $cleaned = $this->cleanupLogs($cutoffDate, $dryRun);
                $totalCleaned += $cleaned;
            }

            // Clean up temporary files
            if ($all || $this->option('temp')) {
                $cleaned = $this->cleanupTempFiles($dryRun);
                $totalCleaned += $cleaned;
            }

            // Clean up expired cache
            if ($all || $this->option('cache')) {
                $this->cleanupCache($dryRun);
            }

            // Archive old campaign data
            if ($all || $this->option('old-data')) {
                $cleaned = $this->archiveOldData($cutoffDate, $dryRun);
                $totalCleaned += $cleaned;
            }

            // Optimize database
            if (!$dryRun && $totalCleaned > 0) {
                $this->optimizeDatabase();
            }

            $this->newLine();
            $this->info("✨ Cleanup completed! Total records processed: {$totalCleaned}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error during cleanup: ' . $e->getMessage());
            Log::error('Cleanup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Clean up old log entries
     */
    private function cleanupLogs($cutoffDate, $dryRun): int
    {
        $this->info('📝 Cleaning up old logs...');

        try {
            // Clean AI action logs
            $aiLogsQuery = DB::table('cmis_ai.ai_actions')
                ->where('created_at', '<', $cutoffDate)
                ->where('status', 'success');

            $count = $aiLogsQuery->count();

            if (!$dryRun && $count > 0) {
                $aiLogsQuery->delete();
            }

            $this->line("  • AI action logs: {$count} records" . ($dryRun ? ' (would be deleted)' : ' deleted'));

            // Clean operation audit logs
            $auditLogsQuery = DB::table('cmis_operations.ops_audit')
                ->where('created_at', '<', $cutoffDate);

            $auditCount = $auditLogsQuery->count();

            if (!$dryRun && $auditCount > 0) {
                $auditLogsQuery->delete();
            }

            $this->line("  • Audit logs: {$auditCount} records" . ($dryRun ? ' (would be deleted)' : ' deleted'));

            return $count + $auditCount;
        } catch (\Exception $e) {
            $this->error("  ❌ Failed to clean logs: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clean up temporary files
     */
    private function cleanupTempFiles($dryRun): int
    {
        $this->info('🗑️  Cleaning up temporary files...');

        try {
            $tempFiles = Storage::disk('local')->files('temp');
            $count = count($tempFiles);

            if (!$dryRun && $count > 0) {
                foreach ($tempFiles as $file) {
                    Storage::disk('local')->delete($file);
                }
            }

            $this->line("  • Temporary files: {$count} files" . ($dryRun ? ' (would be deleted)' : ' deleted'));

            return $count;
        } catch (\Exception $e) {
            $this->error("  ❌ Failed to clean temp files: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clean up expired cache
     */
    private function cleanupCache($dryRun): void
    {
        $this->info('💾 Cleaning up expired cache...');

        try {
            if (!$dryRun) {
                $this->call('cache:clear');
                $this->call('view:clear');
                $this->call('route:clear');
            }

            $this->line("  • Cache cleared" . ($dryRun ? ' (would be cleared)' : ''));
        } catch (\Exception $e) {
            $this->error("  ❌ Failed to clean cache: " . $e->getMessage());
        }
    }

    /**
     * Archive old campaign data
     */
    private function archiveOldData($cutoffDate, $dryRun): int
    {
        $this->info('📦 Archiving old campaign data...');

        try {
            // Find old completed campaigns
            $oldCampaignsQuery = DB::table('cmis.campaigns')
                ->where('end_date', '<', $cutoffDate)
                ->where('status', 'completed');

            $count = $oldCampaignsQuery->count();

            if (!$dryRun && $count > 0) {
                // Call database function to archive campaigns
                $campaigns = $oldCampaignsQuery->pluck('campaign_id');

                foreach ($campaigns as $campaignId) {
                    DB::select("
                        SELECT cmis.archive_campaign(?) as success
                    ", [$campaignId]);
                }
            }

            $this->line("  • Archived campaigns: {$count} campaigns" . ($dryRun ? ' (would be archived)' : ' archived'));

            return $count;
        } catch (\Exception $e) {
            $this->error("  ❌ Failed to archive data: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Optimize database tables
     */
    private function optimizeDatabase(): void
    {
        $this->info('⚡ Optimizing database...');

        try {
            // Vacuum and analyze tables
            DB::statement('VACUUM ANALYZE');

            $this->line("  • Database optimized");
        } catch (\Exception $e) {
            $this->error("  ❌ Failed to optimize database: " . $e->getMessage());
        }
    }

    /**
     * Show preview of what will be affected
     */
    private function showPreview($cutoffDate, $all): void
    {
        try {
            if ($all || $this->option('logs')) {
                $aiLogsCount = DB::table('cmis_ai.ai_actions')
                    ->where('created_at', '<', $cutoffDate)
                    ->where('status', 'success')
                    ->count();

                $auditLogsCount = DB::table('cmis_operations.ops_audit')
                    ->where('created_at', '<', $cutoffDate)
                    ->count();

                $this->line("  - Delete {$aiLogsCount} AI action logs older than {$cutoffDate->format('Y-m-d')}");
                $this->line("  - Delete {$auditLogsCount} audit logs older than {$cutoffDate->format('Y-m-d')}");
            }

            if ($all || $this->option('temp')) {
                $tempFilesCount = count(Storage::disk('local')->files('temp'));
                $this->line("  - Delete {$tempFilesCount} temporary files");
            }

            if ($all || $this->option('cache')) {
                $this->line("  - Clear all application caches (view, route, config)");
            }

            if ($all || $this->option('old-data')) {
                $campaignsCount = DB::table('cmis.campaigns')
                    ->where('end_date', '<', $cutoffDate)
                    ->where('status', 'completed')
                    ->count();

                $this->line("  - Archive {$campaignsCount} completed campaigns older than {$cutoffDate->format('Y-m-d')}");
            }

            if (!$this->option('dry-run') && ($all || $this->option('logs') || $this->option('old-data'))) {
                $this->line("  - Run VACUUM ANALYZE to optimize database");
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Failed to generate preview: " . $e->getMessage());
        }
    }
}
