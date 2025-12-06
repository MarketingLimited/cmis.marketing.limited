<?php

namespace App\Models\Platform;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WebhookEvent Model
 *
 * Stores incoming webhook events from all platforms for audit and reprocessing.
 * Uses PUBLIC RLS because events arrive from external platforms before org identification.
 *
 * @property string $id UUID primary key
 * @property string $platform Platform identifier (meta, google, tiktok, etc.)
 * @property string $event_type Type of event (leadgen, campaign_status, etc.)
 * @property string|null $external_event_id Platform's event ID
 * @property array $headers Request headers (JSONB)
 * @property array $payload Event payload (JSONB)
 * @property string|null $raw_payload Original payload for debugging
 * @property string|null $signature Webhook signature
 * @property bool|null $signature_valid Whether signature was verified
 * @property string|null $source_ip Request source IP
 * @property string|null $user_agent Request user agent
 * @property string $request_method HTTP method (usually POST)
 * @property string $status Status (received, processing, processed, failed, ignored, duplicate)
 * @property int $attempts Processing attempts
 * @property int $max_attempts Maximum retry attempts
 * @property string|null $error_message Error description if failed
 * @property string|null $error_code Error code if failed
 * @property string|null $org_id Organization UUID (set after processing)
 * @property string|null $connection_id Connection UUID (set after processing)
 * @property string|null $related_asset_id Related asset UUID
 * @property \Carbon\Carbon $received_at When event was received
 * @property \Carbon\Carbon|null $processed_at When processing completed
 * @property \Carbon\Carbon|null $next_retry_at When to retry if failed
 */
class WebhookEvent extends BaseModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'cmis.webhook_events';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'platform',
        'event_type',
        'external_event_id',
        'headers',
        'payload',
        'raw_payload',
        'signature',
        'signature_valid',
        'source_ip',
        'user_agent',
        'request_method',
        'status',
        'attempts',
        'max_attempts',
        'error_message',
        'error_code',
        'org_id',
        'connection_id',
        'related_asset_id',
        'received_at',
        'processed_at',
        'next_retry_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'signature_valid' => 'boolean',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_RECEIVED = 'received';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_IGNORED = 'ignored';
    public const STATUS_DUPLICATE = 'duplicate';

    /**
     * Supported platforms
     */
    public const PLATFORMS = [
        'meta',
        'google',
        'tiktok',
        'linkedin',
        'twitter',
        'snapchat',
        'whatsapp',
    ];

    // ===== Relationships =====

    /**
     * Get the platform connection for this event
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(PlatformConnection::class, 'connection_id', 'connection_id');
    }

    /**
     * Get the related asset for this event
     */
    public function relatedAsset(): BelongsTo
    {
        return $this->belongsTo(PlatformAsset::class, 'related_asset_id', 'asset_id');
    }

    // ===== Scopes =====

    /**
     * Get unprocessed events (received or failed with retries remaining)
     */
    public function scopeUnprocessed(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_RECEIVED)
                ->orWhere(function ($q2) {
                    $q2->where('status', self::STATUS_FAILED)
                        ->whereRaw('attempts < max_attempts')
                        ->where(function ($q3) {
                            $q3->whereNull('next_retry_at')
                                ->orWhere('next_retry_at', '<=', now());
                        });
                });
        });
    }

    /**
     * Filter by platform
     */
    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /**
     * Get recent events within time window
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('received_at', '>=', now()->subHours($hours));
    }

    /**
     * Filter by event type
     */
    public function scopeOfType(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Filter by status
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Get events with invalid signatures
     */
    public function scopeInvalidSignature(Builder $query): Builder
    {
        return $query->where('signature_valid', false);
    }

    /**
     * Filter by organization (after processing)
     */
    public function scopeForOrganization(Builder $query, string $orgId): Builder
    {
        return $query->where('org_id', $orgId);
    }

    // ===== Status Management =====

    /**
     * Mark event as processing
     */
    public function markProcessing(): bool
    {
        return $this->update([
            'status' => self::STATUS_PROCESSING,
            'attempts' => $this->attempts + 1,
        ]);
    }

    /**
     * Mark event as processed with org context
     */
    public function markProcessed(?string $orgId = null, ?string $connectionId = null): bool
    {
        $updates = [
            'status' => self::STATUS_PROCESSED,
            'processed_at' => now(),
            'error_message' => null,
            'error_code' => null,
        ];

        if ($orgId) {
            $updates['org_id'] = $orgId;
        }

        if ($connectionId) {
            $updates['connection_id'] = $connectionId;
        }

        return $this->update($updates);
    }

    /**
     * Mark event as failed with error
     */
    public function markFailed(string $errorMessage, ?string $errorCode = null): bool
    {
        $nextRetry = $this->calculateNextRetry();

        return $this->update([
            'status' => $this->attempts >= $this->max_attempts ? self::STATUS_FAILED : self::STATUS_RECEIVED,
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
            'next_retry_at' => $nextRetry,
        ]);
    }

    /**
     * Mark event as ignored (not relevant)
     */
    public function markIgnored(?string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_IGNORED,
            'error_message' => $reason,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark event as duplicate
     */
    public function markDuplicate(string $originalEventId): bool
    {
        return $this->update([
            'status' => self::STATUS_DUPLICATE,
            'error_message' => "Duplicate of event: {$originalEventId}",
            'processed_at' => now(),
        ]);
    }

    // ===== Helpers =====

    /**
     * Calculate next retry time using exponential backoff
     */
    protected function calculateNextRetry(): ?\Carbon\Carbon
    {
        if ($this->attempts >= $this->max_attempts) {
            return null;
        }

        // Exponential backoff: 60s, 300s (5m), 900s (15m)
        $delays = [60, 300, 900];
        $delay = $delays[$this->attempts] ?? 900;

        return now()->addSeconds($delay);
    }

    /**
     * Get payload value using dot notation
     */
    public function getPayloadValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->payload, $key, $default);
    }

    /**
     * Get header value (case-insensitive)
     */
    public function getHeader(string $name, mixed $default = null): mixed
    {
        $headers = $this->headers ?? [];
        $name = strtolower($name);

        foreach ($headers as $key => $value) {
            if (strtolower($key) === $name) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Check if event can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED
            && $this->attempts < $this->max_attempts;
    }

    /**
     * Check if event has valid signature
     */
    public function hasValidSignature(): bool
    {
        return $this->signature_valid === true;
    }

    /**
     * Extract event type from platform-specific payload
     */
    public static function extractEventType(string $platform, array $payload): string
    {
        return match($platform) {
            'meta' => $payload['entry'][0]['changes'][0]['field'] ?? $payload['object'] ?? 'unknown',
            'google' => $payload['message']['attributes']['event_type'] ?? 'unknown',
            'tiktok' => $payload['event'] ?? 'unknown',
            'linkedin' => $payload['eventType'] ?? 'unknown',
            'twitter' => $payload['for_user_id'] ? 'account_activity' : 'unknown',
            'snapchat' => $payload['type'] ?? 'unknown',
            'whatsapp' => $payload['entry'][0]['changes'][0]['value']['messaging_product'] ?? 'message',
            default => 'unknown',
        };
    }

    /**
     * Create webhook event from request
     */
    public static function createFromRequest(
        string $platform,
        array $payload,
        array $headers = [],
        ?string $rawPayload = null,
        ?string $signature = null,
        ?bool $signatureValid = null,
        ?string $sourceIp = null,
        ?string $userAgent = null
    ): self {
        $eventType = self::extractEventType($platform, $payload);

        return self::create([
            'platform' => $platform,
            'event_type' => $eventType,
            'headers' => $headers,
            'payload' => $payload,
            'raw_payload' => $rawPayload,
            'signature' => $signature,
            'signature_valid' => $signatureValid,
            'source_ip' => $sourceIp,
            'user_agent' => $userAgent,
            'status' => self::STATUS_RECEIVED,
            'received_at' => now(),
        ]);
    }
}
