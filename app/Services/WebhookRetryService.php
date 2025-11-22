<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

/**
 * Webhook Retry Service
 *
 * Implements webhook retry with exponential backoff for failed webhook deliveries.
 * Fixes Critical Issue #35: No webhook retry mechanism
 */
class WebhookRetryService
{
    protected int $maxRetries = 5;
    protected array $backoffSchedule = [60, 300, 900, 3600, 7200]; // 1min, 5min, 15min, 1hr, 2hr

    /**
     * Queue a webhook for retry
     */
    public function queueRetry(string $webhookId, array $payload, string $platform, int $attempt = 0): void
    {
        if ($attempt >= $this->maxRetries) {
            $this->moveToDeadLetterQueue($webhookId, $payload, $platform, 'Max retries exceeded');
            return;
        }

        $delay = $this->backoffSchedule[$attempt] ?? 7200;

        Log::info('Queueing webhook for retry', [
            'webhook_id' => $webhookId,
            'platform' => $platform,
            'attempt' => $attempt + 1,
            'delay_seconds' => $delay
        ]);

        // Store retry attempt in database
        DB::table('cmis_platform.webhook_retry_queue')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'webhook_id' => $webhookId,
            'platform' => $platform,
            'payload' => json_encode($payload),
            'attempt_number' => $attempt + 1,
            'scheduled_at' => now()->addSeconds($delay),
            'status' => 'pending',
            'created_at' => now(),
        ]);

        // Queue job to retry
        \App\Jobs\RetryWebhookJob::dispatch($webhookId, $payload, $platform, $attempt + 1)
            ->delay(now()->addSeconds($delay));
    }

    /**
     * Move failed webhook to dead letter queue for manual review
     */
    public function moveToDeadLetterQueue(string $webhookId, array $payload, string $platform, string $reason): void
    {
        Log::error('Moving webhook to dead letter queue', [
            'webhook_id' => $webhookId,
            'platform' => $platform,
            'reason' => $reason
        ]);

        DB::table('cmis_platform.webhook_dead_letter_queue')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'webhook_id' => $webhookId,
            'platform' => $platform,
            'payload' => json_encode($payload),
            'failure_reason' => $reason,
            'attempts_made' => $this->maxRetries,
            'created_at' => now(),
            'requires_manual_review' => true,
        ]);

        // Notify admins
        $this->notifyAdmins($webhookId, $platform, $reason);
    }

    /**
     * Get retry statistics
     */
    public function getRetryStats(): array
    {
        $pending = DB::table('cmis_platform.webhook_retry_queue')
            ->where('status', 'pending')
            ->count();

        $deadLetters = DB::table('cmis_platform.webhook_dead_letter_queue')
            ->where('requires_manual_review', true)
            ->count();

        return [
            'pending_retries' => $pending,
            'dead_letter_queue_size' => $deadLetters,
            'retry_success_rate' => $this->calculateSuccessRate(),
        ];
    }

    /**
     * Calculate retry success rate
     */
    protected function calculateSuccessRate(): float
    {
        $total = DB::table('cmis_platform.webhook_retry_queue')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($total === 0) {
            return 100.0;
        }

        $successful = DB::table('cmis_platform.webhook_retry_queue')
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Notify admins of critical webhook failures
     */
    protected function notifyAdmins(string $webhookId, string $platform, string $reason): void
    {
        // TODO: Implement notification (email, Slack, etc.)
        Log::critical('Webhook moved to dead letter queue - admin notification needed', [
            'webhook_id' => $webhookId,
            'platform' => $platform,
            'reason' => $reason
        ]);
    }
}
