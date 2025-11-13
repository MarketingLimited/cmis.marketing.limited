<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CacheMetadata extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $table = 'cmis.cache_metadata';

    protected $primaryKey = 'cache_name';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'cache_name',
        'last_refreshed',
        'refresh_count',
        'avg_refresh_time_ms',
        'last_refresh_duration_ms',
        'auto_refresh',
        'metadata',
        'hit_count',
    ];

    protected $casts = [
        'last_refreshed' => 'datetime',
        'refresh_count' => 'integer',
        'avg_refresh_time_ms' => 'decimal:2',
        'last_refresh_duration_ms' => 'decimal:2',
        'auto_refresh' => 'boolean',
        'metadata' => 'array',
        'hit_count' => 'integer',
    ];

    /**
     * Scope to get auto-refresh caches
     */
    public function scopeAutoRefresh($query)
    {
        return $query->where('auto_refresh', true);
    }
}
