<?php

namespace App\Jobs;

use App\Models\Core\Integration;
use App\Events\IntegrationTokenExpiring;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Job to check for expiring integration tokens and notify users
 *
 * This job runs daily to identify integrations with tokens expiring within:
 * - 7 days (warning)
 * - 3 days (urgent)
 * - 1 day (critical)
 *
 * Meta tokens expire after 60 days, so monitoring is critical.
 */
class CheckExpiringTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes

    protected int $warningDays;
    protected bool $autoRefresh;

    /**
     * Create a new job instance.
     *
     * @param int $warningDays Days before expiry to send warning (default: 7)
     * @param bool $autoRefresh Attempt to auto-refresh tokens (default: true)
     */
    public function __construct(int $warningDays = 7, bool $autoRefresh = true)
    {
        $this->warningDays = $warningDays;
        $this->autoRefresh = $autoRefresh;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🔍 Starting token expiry check', [
            'warning_days' => $this->warningDays,
            'auto_refresh' => $this->autoRefresh,
        ]);

        $now = now();
        $criticalThreshold = $now->copy()->addDay(); // 1 day
        $urgentThreshold = $now->copy()->addDays(3); // 3 days
        $warningThreshold = $now->copy()->addDays($this->warningDays); // 7 days

        // Get all active integrations with expiring tokens
        $expiringIntegrations = Integration::query()
            ->where('is_active', true)
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', $warningThreshold)
            ->where('token_expires_at', '>', $now) // Not yet expired
            ->with(['org:org_id,name,owner_id', 'creator:user_id,name,email'])
            ->get();

        if ($expiringIntegrations->isEmpty()) {
            Log::info('✅ No expiring tokens found');
            return;
        }

        Log::info("⚠️ Found {$expiringIntegrations->count()} expiring tokens");

        $stats = [
            'critical' => 0,
            'urgent' => 0,
            'warning' => 0,
            'auto_refreshed' => 0,
            'refresh_failed' => 0,
        ];

        foreach ($expiringIntegrations as $integration) {
            $expiresAt = $integration->token_expires_at;
            $daysUntilExpiry = $now->diffInDays($expiresAt, false);

            // Determine severity level
            $severity = $this->determineSeverity($expiresAt, $criticalThreshold, $urgentThreshold);
            $stats[$severity]++;

            Log::info("Token expiring for integration", [
                'integration_id' => $integration->integration_id,
                'platform' => $integration->platform,
                'org_id' => $integration->org_id,
                'expires_at' => $expiresAt->toDateTimeString(),
                'days_until_expiry' => round($daysUntilExpiry, 1),
                'severity' => $severity,
            ]);

            // Attempt auto-refresh if enabled and token has refresh capability
            if ($this->autoRefresh && $integration->refresh_token && $severity !== 'warning') {
                Log::info("🔄 Attempting auto-refresh for integration {$integration->integration_id}");

                try {
                    $refreshed = $integration->refreshAccessToken();

                    if ($refreshed) {
                        $stats['auto_refreshed']++;
                        Log::info("✅ Auto-refresh successful for integration {$integration->integration_id}");

                        // Still fire event to notify user of successful refresh
                        event(new IntegrationTokenExpiring($integration, $severity, true));
                        continue; // Skip notification for expired token
                    } else {
                        $stats['refresh_failed']++;
                        Log::warning("❌ Auto-refresh failed for integration {$integration->integration_id}");
                    }
                } catch (\Exception $e) {
                    $stats['refresh_failed']++;
                    Log::error("❌ Auto-refresh exception for integration {$integration->integration_id}: {$e->getMessage()}");
                }
            }

            // Fire event to notify users about expiring token
            event(new IntegrationTokenExpiring($integration, $severity, false));
        }

        Log::info('✅ Token expiry check completed', $stats);

        // Log to database for audit trail
        $this->logToAudit($stats);
    }

    /**
     * Determine severity level based on days until expiry
     */
    protected function determineSeverity(Carbon $expiresAt, Carbon $critical, Carbon $urgent): string
    {
        if ($expiresAt <= $critical) {
            return 'critical';
        } elseif ($expiresAt <= $urgent) {
            return 'urgent';
        } else {
            return 'warning';
        }
    }

    /**
     * Log results to audit table
     */
    protected function logToAudit(array $stats): void
    {
        try {
            \Illuminate\Support\Facades\DB::table('cmis_audit.logs')->insert([
                'event_type' => 'token_expiry_check',
                'event_source' => 'CheckExpiringTokensJob',
                'description' => 'Checked for expiring integration tokens',
                'metadata' => json_encode($stats),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to log to audit table: {$e->getMessage()}");
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('❌ CheckExpiringTokensJob failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
