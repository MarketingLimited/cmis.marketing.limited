<?php

namespace App\Models\Webhook;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WebhookDeliveryLog Model
 *
 * Records delivery attempts for webhook configurations.
 * Tracks request/response details, status, and retry information.
 *
 * @property string $id UUID primary key
 * @property string $webhook_config_id Configuration UUID
 * @property string|null $webhook_event_id Original event UUID
 * @property string $org_id Organization UUID
 * @property string $callback_url Target URL
 * @property string $event_type Event type
 * @property array $payload Sent payload
 * @property array|null $request_headers Request headers
 * @property int|null $response_status HTTP response status
 * @property string|null $response_body Response body
 * @property array|null $response_headers Response headers
 * @property int|null $response_time_ms Response time in ms
 * @property string $status Delivery status
 * @property int $attempt_number Attempt number
 * @property string|null $error_message Error if failed
 * @property \Carbon\Carbon|null $next_retry_at Next retry time
 */
class WebhookDeliveryLog extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.webhook_delivery_logs';

    protected $fillable = [
        'webhook_config_id',
        'webhook_event_id',
        'org_id',
        'callback_url',
        'event_type',
        'payload',
        'request_headers',
        'response_status',
        'response_body',
        'response_headers',
        'response_time_ms',
        'status',
        'attempt_number',
        'error_message',
        'next_retry_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'request_headers' => 'array',
        'response_headers' => 'array',
        'response_status' => 'integer',
        'response_time_ms' => 'integer',
        'attempt_number' => 'integer',
        'next_retry_at' => 'datetime',
    ];

    /**
     * Delivery status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETRYING = 'retrying';

    // ===== Relationships =====

    /**
     * Get the webhook configuration
     */
    public function configuration(): BelongsTo
    {
        return $this->belongsTo(WebhookConfiguration::class, 'webhook_config_id');
    }

    // ===== Scopes =====

    /**
     * Get pending deliveries
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Get successful deliveries
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Get failed deliveries
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Get deliveries ready for retry
     */
    public function scopeReadyForRetry(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RETRYING)
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            });
    }

    /**
     * Get recent deliveries
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Filter by configuration
     */
    public function scopeForConfiguration(Builder $query, string $configId): Builder
    {
        return $query->where('webhook_config_id', $configId);
    }

    // ===== Status Management =====

    /**
     * Mark as successful
     */
    public function markSuccess(
        int $responseStatus,
        ?string $responseBody = null,
        ?array $responseHeaders = null,
        ?int $responseTimeMs = null
    ): bool {
        return $this->update([
            'status' => self::STATUS_SUCCESS,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'response_headers' => $responseHeaders,
            'response_time_ms' => $responseTimeMs,
            'error_message' => null,
            'next_retry_at' => null,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markFailed(
        string $errorMessage,
        ?int $responseStatus = null,
        ?string $responseBody = null
    ): bool {
        $config = $this->configuration;
        $shouldRetry = $config && $this->attempt_number < $config->max_retries;

        return $this->update([
            'status' => $shouldRetry ? self::STATUS_RETRYING : self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'next_retry_at' => $shouldRetry ? $this->calculateNextRetry() : null,
        ]);
    }

    /**
     * Calculate next retry time using exponential backoff
     */
    protected function calculateNextRetry(): \Carbon\Carbon
    {
        // Exponential backoff: 1min, 5min, 15min, 30min, 1hr
        $delays = [60, 300, 900, 1800, 3600];
        $delay = $delays[$this->attempt_number - 1] ?? 3600;

        return now()->addSeconds($delay);
    }

    /**
     * Increment attempt and prepare for retry
     */
    public function prepareRetry(): bool
    {
        return $this->update([
            'attempt_number' => $this->attempt_number + 1,
            'status' => self::STATUS_PENDING,
        ]);
    }

    /**
     * Check if delivery was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if can be retried
     */
    public function canRetry(): bool
    {
        $config = $this->configuration;
        return $config && $this->attempt_number < $config->max_retries;
    }
}
