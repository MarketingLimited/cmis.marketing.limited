<?php

namespace App\Console\Commands;

use App\Jobs\CheckExpiringTokensJob;
use Illuminate\Console\Command;

/**
 * Command to check for expiring integration tokens
 *
 * Usage:
 *   php artisan integrations:check-expiring-tokens
 *   php artisan integrations:check-expiring-tokens --days=3
 *   php artisan integrations:check-expiring-tokens --no-auto-refresh
 */
class CheckExpiringTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'integrations:check-expiring-tokens
                            {--days=7 : Days before expiry to send warning}
                            {--no-auto-refresh : Disable automatic token refresh}
                            {--sync : Run synchronously instead of dispatching to queue}';

    /**
     * The console command description.
     */
    protected $description = 'Check for expiring integration tokens and notify users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $autoRefresh = !$this->option('no-auto-refresh');
        $sync = $this->option('sync');

        $this->info("🔍 Checking for tokens expiring within {$days} days...");
        $this->info("Auto-refresh: " . ($autoRefresh ? 'Enabled' : 'Disabled'));

        if ($sync) {
            // Run synchronously
            $this->info('Running synchronously...');
            $job = new CheckExpiringTokensJob($days, $autoRefresh);
            $job->handle();
            $this->info('✅ Check completed synchronously');
        } else {
            // Dispatch to queue
            CheckExpiringTokensJob::dispatch($days, $autoRefresh)
                ->onQueue('notifications');
            $this->info('✅ Job dispatched to queue');
        }

        return self::SUCCESS;
    }
}
