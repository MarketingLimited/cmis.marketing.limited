<?php

namespace App\Console\Commands\Maintenance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseCleanupCommand extends Command
{
    protected $signature = 'database:cleanup
                            {--days=90 : Days to keep soft deleted records}
                            {--dry-run : Show what would be deleted}';

    protected $description = 'Clean up old soft-deleted records';

    public function handle()
    {
        $this->info('🧹 Starting Database Cleanup');
        $this->newLine();

        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoffDate = Carbon::now()->subDays($days);

        $tables = [
            'cmis.campaigns',
            'cmis.creative_assets',
            'cmis.channels',
            'cmis.user_orgs',
        ];

        foreach ($tables as $table) {
            $this->info("📋 Processing table: {$table}");

            $count = DB::table($table)
                ->whereNotNull('deleted_at')
                ->where('deleted_at', '<', $cutoffDate)
                ->count();

            if ($count > 0) {
                $this->warn("   Found {$count} records older than {$days} days");

                if (!$dryRun) {
                    DB::table($table)
                        ->whereNotNull('deleted_at')
                        ->where('deleted_at', '<', $cutoffDate)
                        ->delete();

                    $this->info("   ✓ Permanently deleted {$count} records");
                } else {
                    $this->line("   → Would delete {$count} records (dry-run mode)");
                }
            } else {
                $this->line("   → No records to clean");
            }
        }

        $this->newLine();
        $this->info('✅ Cleanup Completed');

        return Command::SUCCESS;
    }
}
