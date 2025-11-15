<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManagePartitions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'partitions:manage
                            {--create : Create future partitions}
                            {--cleanup : Remove old partitions}
                            {--months-ahead=3 : Number of months ahead to create}
                            {--retention-months=12 : Keep partitions for this many months}';

    /**
     * The console command description.
     */
    protected $description = 'Manage database table partitions (create future partitions, cleanup old ones)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔧 Managing Database Partitions...');

        if ($this->option('create') || (!$this->option('create') && !$this->option('cleanup'))) {
            $this->createFuturePartitions();
        }

        if ($this->option('cleanup') || (!$this->option('create') && !$this->option('cleanup'))) {
            $this->cleanupOldPartitions();
        }

        $this->info('✅ Partition management completed!');

        return Command::SUCCESS;
    }

    /**
     * Create future partitions
     */
    private function createFuturePartitions(): void
    {
        $monthsAhead = (int)$this->option('months-ahead');
        $this->info("Creating partitions for next {$monthsAhead} months...");

        $tables = ['ad_metrics', 'social_posts'];

        foreach ($tables as $table) {
            $this->createPartitionsForTable($table, $monthsAhead);
        }
    }

    /**
     * Create partitions for a specific table
     */
    private function createPartitionsForTable(string $table, int $months): void
    {
        $startDate = now()->startOfMonth();

        for ($i = 1; $i <= $months; $i++) {
            $date = $startDate->copy()->addMonths($i);
            $partitionName = "{$table}_" . $date->format('Y_m');
            $rangeStart = $date->format('Y-m-01');
            $rangeEnd = $date->copy()->addMonth()->format('Y-m-01');

            // Check if partition already exists
            $exists = DB::selectOne("
                SELECT EXISTS (
                    SELECT FROM pg_tables
                    WHERE schemaname = 'cmis'
                    AND tablename = ?
                );
            ", [$partitionName]);

            if ($exists->exists) {
                $this->line("  ⏭️  Partition {$partitionName} already exists, skipping");
                continue;
            }

            try {
                DB::statement("
                    CREATE TABLE IF NOT EXISTS cmis.{$partitionName}
                    PARTITION OF cmis.{$table}
                    FOR VALUES FROM ('{$rangeStart}') TO ('{$rangeEnd}');
                ");

                $this->line("  ✅ Created partition: {$partitionName} ({$rangeStart} to {$rangeEnd})");
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to create {$partitionName}: " . $e->getMessage());
            }
        }
    }

    /**
     * Cleanup old partitions
     */
    private function cleanupOldPartitions(): void
    {
        $retentionMonths = (int)$this->option('retention-months');
        $this->info("Cleaning up partitions older than {$retentionMonths} months...");

        $cutoffDate = now()->subMonths($retentionMonths)->startOfMonth();
        $tables = ['ad_metrics', 'social_posts'];

        foreach ($tables as $table) {
            $this->cleanupPartitionsForTable($table, $cutoffDate);
        }
    }

    /**
     * Cleanup partitions for a specific table
     */
    private function cleanupPartitionsForTable(string $table, Carbon $cutoffDate): void
    {
        // Get all partitions for this table
        $partitions = DB::select("
            SELECT tablename
            FROM pg_tables
            WHERE schemaname = 'cmis'
            AND tablename LIKE ?
            ORDER BY tablename
        ", ["{$table}_%"]);

        foreach ($partitions as $partition) {
            $tableName = $partition->tablename;

            // Extract date from partition name (e.g., ad_metrics_2023_01)
            if (preg_match('/_(\d{4})_(\d{2})$/', $tableName, $matches)) {
                $year = $matches[1];
                $month = $matches[2];
                $partitionDate = Carbon::createFromFormat('Y-m', "{$year}-{$month}");

                if ($partitionDate->lt($cutoffDate)) {
                    if ($this->confirm("Delete partition {$tableName} ({$partitionDate->format('Y-m')})?")) {
                        try {
                            DB::statement("DROP TABLE IF EXISTS cmis.{$tableName};");
                            $this->line("  🗑️  Deleted partition: {$tableName}");
                        } catch (\Exception $e) {
                            $this->error("  ❌ Failed to delete {$tableName}: " . $e->getMessage());
                        }
                    } else {
                        $this->line("  ⏭️  Skipped: {$tableName}");
                    }
                }
            }
        }
    }
}
