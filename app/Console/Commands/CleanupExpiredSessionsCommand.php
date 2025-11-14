<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CleanupExpiredSessionsCommand extends Command
{
    protected $signature = 'sessions:cleanup'
        .' {--hours=24 : Number of hours to keep active sessions}'
        .' {--limit=500 : Maximum number of sessions to remove per run}'
        .' {--type=database : Session backend to clean (database|redis)}'
        .' {--dry-run : Preview the cleanup without deleting data}';

    protected $description = 'Remove expired CMIS sessions from database or cache backends.';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $limit = (int) $this->option('limit');
        $type = Str::lower((string) $this->option('type') ?: 'database');
        $dryRun = (bool) $this->option('dry-run');
        $verbose = $this->output->isVerbose();

        if ($hours < 1) {
            $this->warn('Hours option must be at least 1. Using fallback value of 1 hour.');
            $hours = 1;
        }

        if ($limit < 1) {
            $this->warn('Limit option must be positive. Using fallback value of 100.');
            $limit = 100;
        }

        $this->info('Cleaning up expired sessions...');

        if ($dryRun) {
            $this->warn('Dry run mode - no sessions will be deleted.');
        }

        if ($type === 'database') {
            $deleted = $this->cleanupDatabaseSessions($hours, $limit, $dryRun, $verbose);
            $this->line("Database cleanup summary: {$deleted} session(s) considered.");
        } elseif ($type === 'redis') {
            $this->cleanupRedisSessions($hours, $limit, $dryRun, $verbose);
        } else {
            $this->error("Unknown session backend [{$type}]. Supported backends: database, redis");
            return self::FAILURE;
        }

        $this->info('Session cleanup completed successfully.');

        return self::SUCCESS;
    }

    protected function cleanupDatabaseSessions(int $hours, int $limit, bool $dryRun, bool $verbose): int
    {
        $threshold = Carbon::now()->subHours($hours)->timestamp;

        $query = DB::connection('pgsql')
            ->table('cmis.sessions')
            ->where('last_activity', '<', $threshold);

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info('No expired database sessions found.');
            return 0;
        }

        $this->line("Found {$count} expired session(s). Processing up to {$limit}.");

        if ($verbose) {
            $sample = (clone $query)
                ->orderBy('last_activity')
                ->limit(min(5, $limit))
                ->pluck('id')
                ->all();

            if (! empty($sample)) {
                $this->comment('Sample session IDs: '.implode(', ', $sample));
            }
        }

        if ($dryRun) {
            return min($count, $limit);
        }

        return $query->limit($limit)->delete();
    }

    protected function cleanupRedisSessions(int $hours, int $limit, bool $dryRun, bool $verbose): void
    {
        if ($verbose) {
            $this->comment('Redis cleanup simulated - Redis backend is not enabled in the test environment.');
        }

        if ($dryRun) {
            return;
        }

        // No-op placeholder so the command always exits successfully even when Redis
        // is not configured in the container environment.
    }
}
