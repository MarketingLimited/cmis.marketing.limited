<?php

namespace App\Models\Knowledge;

use App\Models\BaseModel;

class EmbeddingApiLog extends BaseModel
{
    
    protected $table = 'cmis.embedding_api_log';
    protected $primaryKey = 'log_id';
    public $timestamps = false;

    protected $fillable = [
        'config_id',
        'request_type',
        'input_tokens',
        'response_time_ms',
        'status_code',
        'error_message',
        'cost_estimate',
        'logged_at',
        'metadata',
        'provider',
    ];

    protected $casts = [
        'log_id' => 'string',
        'config_id' => 'string',
        'input_tokens' => 'integer',
        'response_time_ms' => 'integer',
        'status_code' => 'integer',
        'cost_estimate' => 'decimal:6',
        'logged_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the API config
     */
    public function config(): BelongsTo
    {
        return $this->belongsTo(EmbeddingApiConfig::class, 'config_id', 'config_id');

    }
    /**
     * Scope successful requests
     */
    public function scopeSuccessful($query): Builder
    {
        return $query->whereBetween('status_code', [200, 299]);

    }
    /**
     * Scope failed requests
     */
    public function scopeFailed($query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status_code', '<', 200)
                ->orWhere('status_code', '>=', 300);

    }
    /**
     * Scope slow requests
     */
    public function scopeSlow($query, int $thresholdMs = 1000): Builder
    {
        return $query->where('response_time_ms', '>', $thresholdMs);

    }
    /**
     * Scope by request type
     */
    public function scopeByType($query, string $type): Builder
    {
        return $query->where('request_type', $type);

    }
    /**
     * Scope recent logs
     */
    public function scopeRecent($query, int $days = 7): Builder
    {
        return $query->where('logged_at', '>=', now()->subDays($days));

    }
    /**
     * Check if request was successful
     */
    public function wasSuccessful(): bool
    {
        return $this->status_code >= 200 && $this->status_code < 300;
}
}
}
