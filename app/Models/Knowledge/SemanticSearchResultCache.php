<?php

namespace App\Models\Knowledge;

use App\Models\BaseModel;

class SemanticSearchResultCache extends BaseModel
{
    
    protected $table = 'cmis.semantic_search_result_cache';
    protected $primaryKey = 'cache_id';
    public $timestamps = false;

    protected $fillable = [
        'query_hash',
        'query_text',
        'filters_hash',
        'result_ids',
        'result_distances',
        'cached_at',
        'expires_at',
        'hit_count',
        'last_hit',
        'metadata',
        'provider',
    ];

    protected $casts = [
        'cache_id' => 'string',
        'result_ids' => 'array',
        'result_distances' => 'array',
        'cached_at' => 'datetime',
        'expires_at' => 'datetime',
        'hit_count' => 'integer',
        'last_hit' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Find by query hash
     */
    public static function findByHash(string $queryHash, ?string $filtersHash = null)
    {
        $query = self::where('query_hash', $queryHash);

        if ($filtersHash) {
            $query->where('filters_hash', $filtersHash);

        return $query->where('expires_at', '>', now())->first();

    }
    /**
     * Check if cache is valid
     */
    public function isValid(): bool
    {
        return $this->expires_at && $this->expires_at->isFuture();

    }
    /**
     * Record cache hit
     */
    public function recordHit(): void
    {
        $this->increment('hit_count');
        $this->update(['last_hit' => now()]);

    }
    /**
     * Scope valid caches
     */
    public function scopeValid($query): Builder
    {
        return $query->where('expires_at', '>', now());

    }
    /**
     * Scope expired caches
     */
    public function scopeExpired($query): Builder
    {
        return $query->where('expires_at', '<=', now());

    }
    /**
     * Scope by hit count
     */
    public function scopePopular($query, int $threshold = 10): Builder
    {
        return $query->where('hit_count', '>=', $threshold)
            ->orderBy('hit_count', 'desc');
}
}
}
