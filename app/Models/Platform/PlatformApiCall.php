<?php

namespace App\Models\Platform;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for tracking all API calls to external platforms.
 *
 * Used for:
 * - API usage analytics and insights
 * - Rate limit monitoring
 * - Error tracking and debugging
 * - Platform owner dashboard metrics
 *
 * @property string $call_id
 * @property string $org_id
 * @property string|null $connection_id
 * @property string $platform
 * @property string $endpoint
 * @property string $method
 * @property string|null $action_type
 * @property int|null $http_status
 * @property int|null $duration_ms
 * @property bool $success
 * @property string|null $error_message
 * @property array|null $request_payload
 * @property array|null $response_data
 * @property int|null $rate_limit_remaining
 * @property \Carbon\Carbon|null $rate_limit_reset_at
 * @property \Carbon\Carbon $called_at
 */
class PlatformApiCall extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.platform_api_calls';
    protected $primaryKey = 'call_id';

    protected $fillable = [
        'call_id',
        'org_id',
        'connection_id',
        'platform',
        'endpoint',
        'method',
        'action_type',
        'http_status',
        'duration_ms',
        'success',
        'error_message',
        'request_payload',
        'response_data',
        'rate_limit_remaining',
        'rate_limit_reset_at',
        'called_at',
        'user_id',
        'app_id',
        'request_id',
    ];

    protected $casts = [
        'success' => 'boolean',
        'request_payload' => 'array',
        'response_data' => 'array',
        'rate_limit_reset_at' => 'datetime',
        'called_at' => 'datetime',
        'http_status' => 'integer',
        'duration_ms' => 'integer',
        'rate_limit_remaining' => 'integer',
    ];

    // ===== Relationships =====

    public function connection(): BelongsTo
    {
        return $this->belongsTo(PlatformConnection::class, 'connection_id', 'connection_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'user_id');
    }

    // ===== Scopes =====

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    public function scopeSlowRequests($query, int $thresholdMs = 1000)
    {
        return $query->where('duration_ms', '>', $thresholdMs);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('called_at', [$startDate, $endDate]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('called_at', today());
    }

    public function scopeThisHour($query)
    {
        return $query->where('called_at', '>=', now()->startOfHour());
    }

    public function scopeByEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', 'like', "%{$endpoint}%");
    }

    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByApp($query, string $appId)
    {
        return $query->where('app_id', $appId);
    }

    // ===== Aggregation Methods =====

    public static function getRequestCountByPlatform($orgId = null, $startDate = null, $endDate = null): array
    {
        $query = self::query()
            ->selectRaw('platform, COUNT(*) as total, SUM(CASE WHEN success THEN 1 ELSE 0 END) as successful')
            ->groupBy('platform');

        if ($orgId) {
            $query->where('org_id', $orgId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('called_at', [$startDate, $endDate]);
        }

        return $query->get()->keyBy('platform')->toArray();
    }

    public static function getAverageResponseTime($orgId = null, $platform = null): float
    {
        $query = self::query()->whereNotNull('duration_ms');

        if ($orgId) {
            $query->where('org_id', $orgId);
        }

        if ($platform) {
            $query->where('platform', $platform);
        }

        return (float) $query->avg('duration_ms') ?? 0;
    }

    public static function getErrorRate($orgId = null, $platform = null, $hours = 24): float
    {
        $query = self::query()
            ->where('called_at', '>=', now()->subHours($hours));

        if ($orgId) {
            $query->where('org_id', $orgId);
        }

        if ($platform) {
            $query->where('platform', $platform);
        }

        $total = $query->count();
        if ($total === 0) {
            return 0;
        }

        $failed = (clone $query)->where('success', false)->count();
        return round(($failed / $total) * 100, 2);
    }

    public static function getHourlyStats($orgId = null, $platform = null, $hours = 24): array
    {
        $query = self::query()
            ->selectRaw("DATE_TRUNC('hour', called_at) as hour, COUNT(*) as total, SUM(CASE WHEN success THEN 1 ELSE 0 END) as successful, AVG(duration_ms) as avg_duration")
            ->where('called_at', '>=', now()->subHours($hours))
            ->groupByRaw("DATE_TRUNC('hour', called_at)")
            ->orderByRaw("DATE_TRUNC('hour', called_at)");

        if ($orgId) {
            $query->where('org_id', $orgId);
        }

        if ($platform) {
            $query->where('platform', $platform);
        }

        return $query->get()->toArray();
    }
}
