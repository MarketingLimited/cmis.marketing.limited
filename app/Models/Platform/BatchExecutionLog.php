<?php

namespace App\Models\Platform;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * BatchExecutionLog Model
 *
 * Tracks batch API execution for monitoring and analytics.
 * Uses PUBLIC RLS - monitoring data accessible system-wide for dashboards.
 *
 * @property string $id UUID primary key
 * @property string $batch_id UUID grouping requests executed together
 * @property string $platform Platform identifier (meta, google, tiktok, etc.)
 * @property string $batch_type Type of batch (field_expansion, search_stream, standard)
 * @property string|null $connection_id Connection UUID
 * @property string|null $org_id Organization UUID
 * @property int $request_count Total requests in batch
 * @property int $success_count Successfully completed requests
 * @property int $failure_count Failed requests
 * @property int $skipped_count Skipped requests
 * @property int|null $duration_ms Execution duration in milliseconds
 * @property int $api_calls_made Number of actual API calls made
 * @property int|null $bytes_received Response size in bytes
 * @property int|null $rate_limit_remaining Rate limit remaining after execution
 * @property \Carbon\Carbon|null $rate_limit_reset_at Rate limit reset time
 * @property bool $rate_limit_hit Whether rate limit was hit
 * @property array|null $response_summary Summary of responses (JSONB)
 * @property array|null $errors Error details (JSONB)
 * @property \Carbon\Carbon $started_at When batch execution started
 * @property \Carbon\Carbon|null $completed_at When batch execution completed
 */
class BatchExecutionLog extends BaseModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'cmis.batch_execution_log';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'batch_id',
        'platform',
        'batch_type',
        'connection_id',
        'org_id',
        'request_count',
        'success_count',
        'failure_count',
        'skipped_count',
        'duration_ms',
        'api_calls_made',
        'bytes_received',
        'rate_limit_remaining',
        'rate_limit_reset_at',
        'rate_limit_hit',
        'response_summary',
        'errors',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'request_count' => 'integer',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'skipped_count' => 'integer',
        'duration_ms' => 'integer',
        'api_calls_made' => 'integer',
        'bytes_received' => 'integer',
        'rate_limit_remaining' => 'integer',
        'rate_limit_hit' => 'boolean',
        'response_summary' => 'array',
        'errors' => 'array',
        'rate_limit_reset_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Batch type constants
     */
    public const BATCH_TYPE_STANDARD = 'standard';
    public const BATCH_TYPE_FIELD_EXPANSION = 'field_expansion';
    public const BATCH_TYPE_SEARCH_STREAM = 'search_stream';
    public const BATCH_TYPE_BULK = 'bulk';

    // ===== Relationships =====

    /**
     * Get the platform connection for this batch
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(PlatformConnection::class, 'connection_id', 'connection_id');
    }

    // ===== Scopes =====

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
     * Filter by organization
     */
    public function scopeForOrganization(Builder $query, string $orgId): Builder
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Get recent logs within time window
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }

    /**
     * Get logs where rate limit was hit
     */
    public function scopeRateLimited(Builder $query): Builder
    {
        return $query->where('rate_limit_hit', true);
    }

    /**
     * Get logs with failures
     */
    public function scopeWithFailures(Builder $query): Builder
    {
        return $query->where('failure_count', '>', 0);
    }

    /**
     * Get logs by batch type
     */
    public function scopeOfType(Builder $query, string $batchType): Builder
    {
        return $query->where('batch_type', $batchType);
    }

    // ===== Factory Methods =====

    /**
     * Start a new batch execution log
     */
    public static function startBatch(
        string $platform,
        int $requestCount,
        string $batchType = self::BATCH_TYPE_STANDARD,
        ?string $connectionId = null,
        ?string $orgId = null
    ): self {
        return self::create([
            'batch_id' => Str::uuid()->toString(),
            'platform' => $platform,
            'batch_type' => $batchType,
            'connection_id' => $connectionId,
            'org_id' => $orgId,
            'request_count' => $requestCount,
            'started_at' => now(),
        ]);
    }

    // ===== Completion Methods =====

    /**
     * Complete the batch execution log
     */
    public function complete(
        int $successCount,
        int $failureCount = 0,
        int $skippedCount = 0,
        ?array $errors = null,
        ?array $responseSummary = null
    ): bool {
        $startedAt = $this->started_at;
        $completedAt = now();
        $durationMs = $startedAt ? $startedAt->diffInMilliseconds($completedAt) : null;

        return $this->update([
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'skipped_count' => $skippedCount,
            'errors' => $errors,
            'response_summary' => $responseSummary,
            'duration_ms' => $durationMs,
            'completed_at' => $completedAt,
        ]);
    }

    /**
     * Update rate limit info
     */
    public function updateRateLimitInfo(
        ?int $remaining = null,
        ?\Carbon\Carbon $resetAt = null,
        bool $hit = false
    ): bool {
        return $this->update([
            'rate_limit_remaining' => $remaining,
            'rate_limit_reset_at' => $resetAt,
            'rate_limit_hit' => $hit,
        ]);
    }

    /**
     * Update API calls made
     */
    public function updateApiCallsMade(int $calls, ?int $bytesReceived = null): bool
    {
        return $this->update([
            'api_calls_made' => $calls,
            'bytes_received' => $bytesReceived,
        ]);
    }

    // ===== Analytics Helpers =====

    /**
     * Get success rate as percentage
     */
    public function getSuccessRate(): float
    {
        if ($this->request_count === 0) {
            return 0.0;
        }

        return round(($this->success_count / $this->request_count) * 100, 2);
    }

    /**
     * Get API efficiency (requests batched per API call)
     */
    public function getApiEfficiency(): float
    {
        if ($this->api_calls_made === 0) {
            return 0.0;
        }

        return round($this->request_count / $this->api_calls_made, 2);
    }

    /**
     * Check if batch was successful (no failures)
     */
    public function wasSuccessful(): bool
    {
        return $this->failure_count === 0 && $this->success_count > 0;
    }

    /**
     * Get average response time per request in ms
     */
    public function getAverageResponseTime(): ?float
    {
        if (!$this->duration_ms || $this->request_count === 0) {
            return null;
        }

        return round($this->duration_ms / $this->request_count, 2);
    }

    /**
     * Get platform statistics for a time period
     */
    public static function getPlatformStats(string $platform, int $hours = 24): array
    {
        $logs = self::forPlatform($platform)
            ->recent($hours)
            ->get();

        return [
            'total_batches' => $logs->count(),
            'total_requests' => $logs->sum('request_count'),
            'total_successes' => $logs->sum('success_count'),
            'total_failures' => $logs->sum('failure_count'),
            'total_api_calls' => $logs->sum('api_calls_made'),
            'rate_limit_hits' => $logs->where('rate_limit_hit', true)->count(),
            'avg_duration_ms' => round($logs->avg('duration_ms') ?? 0, 2),
            'avg_efficiency' => $logs->avg(fn($log) => $log->getApiEfficiency()) ?? 0,
        ];
    }
}
