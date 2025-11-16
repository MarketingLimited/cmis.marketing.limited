<?php

namespace App\Models\Knowledge;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class SemanticSearchLog extends Model
{
    use HasUuids;
    protected $table = 'cmis.semantic_search_log';
    protected $primaryKey = 'search_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'user_id',
        'query_text',
        'search_type',
        'filters_applied',
        'results_count',
        'top_result_id',
        'top_result_distance',
        'search_duration_ms',
        'clicked_result_id',
        'was_helpful',
        'feedback_text',
        'searched_at',
        'metadata',
        'provider',
    ];

    protected $casts = [
        'search_id' => 'string',
        'org_id' => 'string',
        'user_id' => 'string',
        'top_result_id' => 'string',
        'clicked_result_id' => 'string',
        'filters_applied' => 'array',
        'results_count' => 'integer',
        'top_result_distance' => 'float',
        'search_duration_ms' => 'integer',
        'was_helpful' => 'boolean',
        'searched_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'user_id');
    }

    /**
     * Scope by search type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('search_type', $type);
    }

    /**
     * Scope with feedback
     */
    public function scopeWithFeedback($query)
    {
        return $query->whereNotNull('was_helpful');
    }

    /**
     * Scope helpful searches
     */
    public function scopeHelpful($query)
    {
        return $query->where('was_helpful', true);
    }

    /**
     * Scope with clicks
     */
    public function scopeWithClicks($query)
    {
        return $query->whereNotNull('clicked_result_id');
    }

    /**
     * Scope slow searches
     */
    public function scopeSlow($query, int $thresholdMs = 500)
    {
        return $query->where('search_duration_ms', '>', $thresholdMs);
    }

    /**
     * Scope recent searches
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('searched_at', '>=', now()->subDays($days));
    }
}
