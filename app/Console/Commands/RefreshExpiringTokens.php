<?php

namespace App\Console\Commands;

use App\Http\Controllers\Settings\PlatformConnectionsController;
use App\Models\Platform\PlatformConnection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshExpiringTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:refresh
                            {--platform= : Specific platform to refresh (e.g., tiktok)}
                            {--force : Force refresh even if not expiring soon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh platform access tokens that are expiring soon';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $platform = $this->option('platform');
        $force = $this->option('force');

        $this->info('Starting token refresh check...');

        // Build query for connections that need refresh
        $query = PlatformConnection::withoutGlobalScopes()
            ->where('status', 'active')
            ->whereNotNull('refresh_token');

        if ($platform) {
            $query->where('platform', $platform);
        }

        // Get connections expiring in the next 2 hours (or all if force)
        if (!$force) {
            $query->where(function ($q) {
                $q->where('token_expires_at', '<=', now()->addHours(2))
                  ->orWhereNull('token_expires_at');
            });
        }

        $connections = $query->get();

        if ($connections->isEmpty()) {
            $this->info('No tokens need refreshing at this time.');
            return Command::SUCCESS;
        }

        $this->info("Found {$connections->count()} connection(s) to refresh.");

        $refreshed = 0;
        $failed = 0;

        foreach ($connections as $connection) {
            $this->line("Processing: {$connection->platform} - {$connection->account_name}");

            $success = $this->refreshConnection($connection);

            if ($success) {
                $refreshed++;
                $this->info("  ✓ Refreshed successfully");
            } else {
                $failed++;
                $this->error("  ✗ Refresh failed");
            }
        }

        $this->newLine();
        $this->info("Refresh complete: {$refreshed} succeeded, {$failed} failed");

        Log::info('Token refresh command completed', [
            'total' => $connections->count(),
            'refreshed' => $refreshed,
            'failed' => $failed,
        ]);

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Refresh a single connection based on its platform.
     */
    protected function refreshConnection(PlatformConnection $connection): bool
    {
        return match ($connection->platform) {
            'tiktok' => PlatformConnectionsController::refreshTikTokTokenSilently($connection),
            // Add other platforms here as needed
            // 'google' => $this->refreshGoogleToken($connection),
            // 'meta' => $this->refreshMetaToken($connection),
            default => $this->logUnsupportedPlatform($connection),
        };
    }

    /**
     * Log unsupported platform and return false.
     */
    protected function logUnsupportedPlatform(PlatformConnection $connection): bool
    {
        Log::warning('Token refresh not implemented for platform', [
            'platform' => $connection->platform,
            'connection_id' => $connection->connection_id,
        ]);
        return false;
    }
}
