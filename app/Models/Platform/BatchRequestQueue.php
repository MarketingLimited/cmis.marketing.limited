<?php

namespace App\Models\Platform;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * BatchRequestQueue Model
 *
 * Stores pending platform API requests to be batched and executed together.
 * Implements deduplication via request_key to prevent duplicate requests.
 *
 * Uses org_id RLS isolation - each organization can only see their own queued requests.
 *
 * @property string $id UUID primary key
 * @property string $org_id Organization UUID (for RLS)
 * @property string $platform Platform identifier (meta, google, tiktok, etc.)
 * @property string $connection_id FK to platform_connections
 * @property string $request_type Type of request (get_pages, get_metrics, etc.)
 * @property string $request_key Deduplication key (hash of request params)
 * @property array $request_params Request parameters (JSONB)
 * @property string|null $batch_group Group for batching similar requests
 * @property string|null $batch_id UUID of batch when grouped
 * @property int $priority Priority 1-10 (1=highest)
 * @property string $status Status (pending, queued, processing, completed, failed, cancelled)
 * @property int $attempts Number of execution attempts
 * @property int $max_attempts Maximum retry attempts
 * @property array|null $response_data API response data (JSONB)
 * @property string|null $error_message Error description if failed
 * @property string|null $error_code Error code if failed
 * @property \Carbon\Carbon $scheduled_at When request should be processed
 * @property \Carbon\Carbon|null $started_at When processing started
 * @property \Carbon\Carbon|null $completed_at When processing completed
 * @property \Carbon\Carbon|null $next_retry_at When to retry if failed
 */
class BatchRequestQueue extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    /**
     * The table associated with the model.
     */
    protected $table = 'cmis.batch_request_queue';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'org_id',
        'platform',
        'connection_id',
        'request_type',
        'request_key',
        'request_params',
        'batch_group',
        'batch_id',
        'priority',
        'status',
        'attempts',
        'max_attempts',
        'response_data',
        'error_message',
        'error_code',
        'scheduled_at',
        'started_at',
        'completed_at',
        'next_retry_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'request_params' => 'array',
        'response_data' => 'array',
        'priority' => 'integer',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

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
        'pinterest',
    ];

    /**
     * Batch groups for organizing similar requests
     */
    public const BATCH_GROUPS = [
        'assets' => ['get_pages', 'get_ad_accounts', 'get_pixels', 'get_catalogs', 'get_instagram_accounts'],
        'metrics' => ['get_metrics', 'get_insights', 'get_analytics'],
        'campaigns' => ['get_campaigns', 'get_ad_sets', 'get_ads'],
        'content' => ['get_posts', 'get_media', 'get_creatives'],
    ];

    // ===== Relationships =====

    /**
     * Get the platform connection for this request
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(PlatformConnection::class, 'connection_id', 'connection_id');
    }

    // ===== Scopes =====

    /**
     * Get pending requests ready for processing
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Filter by platform
     */
    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /**
     * Filter by connection
     */
    public function scopeForConnection(Builder $query, string $connectionId): Builder
    {
        return $query->where('connection_id', $connectionId);
    }

    /**
     * Order by priority (1=highest first) then scheduled time
     */
    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority', 'asc')
            ->orderBy('scheduled_at', 'asc');
    }

    /**
     * Get failed requests that can be retried
     */
    public function scopeRetryable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED)
            ->whereRaw('attempts < max_attempts')
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            });
    }

    /**
     * Get requests by batch group
     */
    public function scopeInBatchGroup(Builder $query, string $batchGroup): Builder
    {
        return $query->where('batch_group', $batchGroup);
    }

    /**
     * Get requests in a specific batch
     */
    public function scopeInBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('batch_id', $batchId);
    }

    // ===== Status Management =====

    /**
     * Mark request as processing
     */
    public function markProcessing(?string $batchId = null): bool
    {
        return $this->update([
            'status' => self::STATUS_PROCESSING,
            'batch_id' => $batchId ?? $this->batch_id,
            'started_at' => now(),
            'attempts' => $this->attempts + 1,
        ]);
    }

    /**
     * Mark request as completed with response
     */
    public function markCompleted(array $response = []): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'response_data' => $response,
            'completed_at' => now(),
            'error_message' => null,
            'error_code' => null,
        ]);
    }

    /**
     * Mark request as failed with error
     */
    public function markFailed(string $errorMessage, ?string $errorCode = null): bool
    {
        $nextRetry = $this->calculateNextRetry();

        return $this->update([
            'status' => $this->attempts >= $this->max_attempts ? self::STATUS_FAILED : self::STATUS_PENDING,
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
            'next_retry_at' => $nextRetry,
        ]);
    }

    /**
     * Mark request as cancelled
     */
    public function markCancelled(?string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'error_message' => $reason ?? 'Cancelled by user',
            'completed_at' => now(),
        ]);
    }

    // ===== Helpers =====

    /**
     * Generate a deduplication key from request parameters
     */
    public static function generateRequestKey(string $platform, string $requestType, array $params): string
    {
        $normalized = $params;
        ksort($normalized);
        return md5("{$platform}:{$requestType}:" . json_encode($normalized));
    }

    /**
     * Infer batch group from request type
     */
    public static function inferBatchGroup(string $requestType): ?string
    {
        foreach (self::BATCH_GROUPS as $group => $types) {
            if (in_array($requestType, $types)) {
                return $group;
            }
        }
        return null;
    }

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
     * Check if request can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED
            && $this->attempts < $this->max_attempts;
    }

    /**
     * Check if request is in a terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ]) || ($this->status === self::STATUS_FAILED && !$this->canRetry());
    }

    /**
     * Get response data value using dot notation
     */
    public function getResponseValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->response_data, $key, $default);
    }

    /**
     * Queue a new request with deduplication
     */
    public static function queueRequest(
        string $orgId,
        string $platform,
        string $connectionId,
        string $requestType,
        array $params = [],
        int $priority = 5,
        ?string $batchGroup = null
    ): self {
        $requestKey = self::generateRequestKey($platform, $requestType, $params);
        $batchGroup = $batchGroup ?? self::inferBatchGroup($requestType);

        // Check for existing pending/processing request (deduplication)
        $existing = self::where('platform', $platform)
            ->where('request_key', $requestKey)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_QUEUED, self::STATUS_PROCESSING])
            ->first();

        if ($existing) {
            return $existing;
        }

        return self::create([
            'org_id' => $orgId,
            'platform' => $platform,
            'connection_id' => $connectionId,
            'request_type' => $requestType,
            'request_key' => $requestKey,
            'request_params' => $params,
            'batch_group' => $batchGroup,
            'priority' => $priority,
            'status' => self::STATUS_PENDING,
            'scheduled_at' => now(),
        ]);
    }
}
