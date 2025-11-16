<?php

namespace App\Models\Knowledge;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class EmbeddingUpdateQueue extends Model
{
    use HasUuids;
    protected $table = 'cmis.embedding_update_queue';
    protected $primaryKey = 'queue_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'source_type',
        'source_id',
        'content',
        'priority',
        'status',
        'retry_count',
        'last_error',
        'queued_at',
        'processing_started_at',
        'completed_at',
        'metadata',
        'provider',
    ];

    protected $casts = [
        'queue_id' => 'string',
        'source_id' => 'string',
        'priority' => 'integer',
        'retry_count' => 'integer',
        'queued_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Scope pending items
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->orderBy('priority', 'desc')
            ->orderBy('queued_at', 'asc');
    }

    /**
     * Scope processing items
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope failed items
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope by priority
     */
    public function scopeHighPriority($query, int $threshold = 5)
    {
        return $query->where('priority', '>=', $threshold);
    }

    /**
     * Mark as processing
     */
    public function markProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'processing_started_at' => now(),
        ]);
    }

    /**
     * Mark as completed
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'last_error' => $error,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Reset for retry
     */
    public function resetForRetry(): void
    {
        $this->update([
            'status' => 'pending',
            'processing_started_at' => null,
        ]);
    }
}
