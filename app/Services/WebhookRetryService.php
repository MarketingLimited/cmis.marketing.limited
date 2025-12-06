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
    protected NotificationService $notificationService;

    public function __construct(?NotificationService $notificationService = null)
    {
        $this->notificationService = $notificationService ?? app(NotificationService::class);
    }

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
    public function moveToDeadLetterQueue(string $webhookId, array $payload, string $platform, string $reason, ?string $orgId = null): void
    {
        Log::error('Moving webhook to dead letter queue', [
            'webhook_id' => $webhookId,
            'platform' => $platform,
            'reason' => $reason,
            'org_id' => $orgId,
        ]);

        // Try to extract org_id from payload if not provided
        if (!$orgId) {
            $orgId = $this->extractOrgIdFromPayload($payload, $webhookId);
        }

        DB::table('cmis_platform.webhook_dead_letter_queue')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'webhook_id' => $webhookId,
            'platform' => $platform,
            'org_id' => $orgId,
            'payload' => json_encode($payload),
            'failure_reason' => $reason,
            'attempts_made' => $this->maxRetries,
            'created_at' => now(),
            'requires_manual_review' => true,
        ]);

        // Notify admins
        $this->notifyAdmins($webhookId, $platform, $reason, $orgId);

        // Create alert record
        if ($orgId) {
            DB::table('cmis.alerts')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'org_id' => $orgId,
                'type' => 'webhook_failure',
                'severity' => 'critical',
                'title' => __('notifications.webhook_failed_title', ['platform' => ucfirst($platform)]),
                'message' => __('notifications.webhook_failed_message', [
                    'platform' => ucfirst($platform),
                    'reason' => $reason,
                ]),
                'related_entity_type' => 'webhook',
                'related_entity_id' => $webhookId,
                'metadata' => json_encode([
                    'webhook_id' => $webhookId,
                    'platform' => $platform,
                    'reason' => $reason,
                    'attempts_made' => $this->maxRetries,
                ]),
                'is_read' => false,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Extract org_id from payload or webhook record
     */
    protected function extractOrgIdFromPayload(array $payload, string $webhookId): ?string
    {
        // Try to get from payload
        if (!empty($payload['org_id'])) {
            return $payload['org_id'];
        }

        // Try to get from original webhook event
        $event = DB::table('cmis_platform.webhook_events')
            ->where('id', $webhookId)
            ->first();

        return $event->org_id ?? null;
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
    protected function notifyAdmins(string $webhookId, string $platform, string $reason, ?string $orgId = null): void
    {
        Log::critical('Webhook moved to dead letter queue - notifying admins', [
            'webhook_id' => $webhookId,
            'platform' => $platform,
            'reason' => $reason,
            'org_id' => $orgId,
        ]);

        try {
            $this->notificationService->notifyWebhookFailure(
                $webhookId,
                $platform,
                $reason,
                [
                    'org_id' => $orgId,
                    'data' => [
                        'webhook_id' => $webhookId,
                        'platform' => $platform,
                        'reason' => $reason,
                        'max_retries' => $this->maxRetries,
                        'failed_at' => now()->toIso8601String(),
                    ],
                ]
            );

            Log::info('Webhook failure notification sent to admins', [
                'webhook_id' => $webhookId,
                'platform' => $platform,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send webhook failure notification to admins', [
                'webhook_id' => $webhookId,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark a retry as successful
     */
    public function markRetrySuccess(string $webhookId, int $attempt): void
    {
        DB::table('cmis_platform.webhook_retry_queue')
            ->where('webhook_id', $webhookId)
            ->where('attempt_number', $attempt)
            ->update([
                'status' => 'success',
                'processed_at' => now(),
                'updated_at' => now(),
            ]);

        Log::info('Webhook retry succeeded', [
            'webhook_id' => $webhookId,
            'attempt' => $attempt,
        ]);
    }

    /**
     * Mark a retry as failed (but not final - will be retried)
     */
    public function markRetryFailed(string $webhookId, int $attempt, string $errorMessage): void
    {
        DB::table('cmis_platform.webhook_retry_queue')
            ->where('webhook_id', $webhookId)
            ->where('attempt_number', $attempt)
            ->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
                'processed_at' => now(),
                'updated_at' => now(),
            ]);

        Log::warning('Webhook retry failed', [
            'webhook_id' => $webhookId,
            'attempt' => $attempt,
            'error' => $errorMessage,
        ]);
    }

    /**
     * Get webhooks in dead letter queue for manual review
     */
    public function getDeadLetterQueue(?string $orgId = null, int $limit = 50): array
    {
        $query = DB::table('cmis_platform.webhook_dead_letter_queue')
            ->where('requires_manual_review', true)
            ->orderBy('created_at', 'desc');

        if ($orgId) {
            $query->where('org_id', $orgId);
        }

        return $query->limit($limit)->get()->toArray();
    }

    /**
     * Manually retry a dead letter webhook
     */
    public function retryDeadLetter(string $deadLetterId): bool
    {
        $deadLetter = DB::table('cmis_platform.webhook_dead_letter_queue')
            ->where('id', $deadLetterId)
            ->first();

        if (!$deadLetter) {
            return false;
        }

        // Reset retry count and queue again
        $this->queueRetry(
            $deadLetter->webhook_id,
            json_decode($deadLetter->payload, true),
            $deadLetter->platform,
            0 // Start from first attempt
        );

        // Mark dead letter as retried
        DB::table('cmis_platform.webhook_dead_letter_queue')
            ->where('id', $deadLetterId)
            ->update([
                'requires_manual_review' => false,
                'retried_at' => now(),
            ]);

        Log::info('Dead letter webhook queued for retry', [
            'dead_letter_id' => $deadLetterId,
            'webhook_id' => $deadLetter->webhook_id,
        ]);

        return true;
    }

    /**
     * Dismiss a dead letter (mark as reviewed, no retry needed)
     */
    public function dismissDeadLetter(string $deadLetterId, string $reason): bool
    {
        $updated = DB::table('cmis_platform.webhook_dead_letter_queue')
            ->where('id', $deadLetterId)
            ->update([
                'requires_manual_review' => false,
                'dismissed_at' => now(),
                'dismissed_reason' => $reason,
            ]);

        if ($updated) {
            Log::info('Dead letter webhook dismissed', [
                'dead_letter_id' => $deadLetterId,
                'reason' => $reason,
            ]);
        }

        return $updated > 0;
    }
}
