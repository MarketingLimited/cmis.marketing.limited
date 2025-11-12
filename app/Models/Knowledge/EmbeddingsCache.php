<?php

namespace App\Models\Knowledge;

use App\Casts\VectorCast;
use Illuminate\Database\Eloquent\Model;

class EmbeddingsCache extends Model
{
    protected $table = 'cmis.embeddings_cache';
    protected $primaryKey = 'cache_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'content_hash',
        'content_type',
        'embedding',
        'model_name',
        'embedding_dim',
        'cached_at',
        'last_accessed',
        'access_count',
        'metadata',
        'provider',
    ];

    protected $casts = [
        'cache_id' => 'string',
        'embedding' => VectorCast::class,
        'embedding_dim' => 'integer',
        'cached_at' => 'datetime',
        'last_accessed' => 'datetime',
        'access_count' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Find cached embedding by content hash
     */
    public static function findByHash(string $contentHash, ?string $modelName = null)
    {
        $query = self::where('content_hash', $contentHash);

        if ($modelName) {
            $query->where('model_name', $modelName);
        }

        return $query->first();
    }

    /**
     * Get or create cache entry
     */
    public static function getOrCreate(string $content, string $contentType, ?string $modelName = null)
    {
        $hash = md5($content);
        $cached = self::findByHash($hash, $modelName);

        if ($cached) {
            $cached->recordAccess();
            return $cached;
        }

        return null; // Caller should generate embedding
    }

    /**
     * Record access
     */
    public function recordAccess(): void
    {
        $this->increment('access_count');
        $this->update(['last_accessed' => now()]);
    }

    /**
     * Scope by content type
     */
    public function scopeByContentType($query, string $contentType)
    {
        return $query->where('content_type', $contentType);
    }

    /**
     * Scope by model name
     */
    public function scopeByModel($query, string $modelName)
    {
        return $query->where('model_name', $modelName);
    }

    /**
     * Scope stale entries (not accessed in X days)
     */
    public function scopeStale($query, int $days = 30)
    {
        return $query->where('last_accessed', '<', now()->subDays($days));
    }
}
