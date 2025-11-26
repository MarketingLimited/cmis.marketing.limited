<?php

namespace App\Jobs\Social;

use App\Models\Platform\PlatformConnection;
use App\Services\Social\PlatformServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Refresh expired or expiring OAuth tokens for platform connections
 *
 * This job runs periodically (typically hourly) to ensure all platform
 * connections have fresh access tokens and won't fail during publishing
 */
class RefreshExpiredTokensJob implements ShouldQueue
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
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Refreshing expired platform tokens');

            // Get all connections with expired or soon-to-expire tokens
            $connections = PlatformConnection::where('status', 'active')
                ->where(function ($query) {
                    // Tokens that are expired
                    $query->where('token_expires_at', '<', now())
                          // OR tokens expiring within 30 minutes
                          ->orWhere('token_expires_at', '<', now()->addMinutes(30));
                })
                ->whereNotNull('refresh_token')
                ->get();

            $stats = [
                'total' => $connections->count(),
                'refreshed' => 0,
                'failed' => 0,
                'skipped' => 0,
            ];

            foreach ($connections as $connection) {
                try {
                    // Check if platform supports token refresh
                    if (!PlatformServiceFactory::isSupported($connection->platform)) {
                        Log::warning('Platform not supported for token refresh', [
                            'platform' => $connection->platform,
                            'connection_id' => $connection->connection_id,
                        ]);
                        $stats['skipped']++;
                        continue;
                    }

                    // Refresh the token
                    $refreshedConnection = PlatformServiceFactory::ensureFreshToken($connection);

                    if ($refreshedConnection->token_expires_at > now()->addMinutes(30)) {
                        $stats['refreshed']++;

                        Log::info('Token refreshed successfully', [
                            'platform' => $connection->platform,
                            'connection_id' => $connection->connection_id,
                            'org_id' => $connection->org_id,
                            'expires_at' => $refreshedConnection->token_expires_at->toISOString(),
                        ]);
                    }

                } catch (\Exception $e) {
                    $stats['failed']++;

                    Log::error('Failed to refresh token', [
                        'platform' => $connection->platform,
                        'connection_id' => $connection->connection_id,
                        'org_id' => $connection->org_id,
                        'error' => $e->getMessage(),
                    ]);

                    // Mark connection as having token issues
                    $connection->update([
                        'status' => 'token_expired',
                        'last_error_at' => now(),
                        'last_error_message' => 'Token refresh failed: ' . $e->getMessage(),
                    ]);
                }
            }

            Log::info('Token refresh job completed', $stats);

        } catch (\Exception $e) {
            Log::error('RefreshExpiredTokensJob failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RefreshExpiredTokensJob failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
