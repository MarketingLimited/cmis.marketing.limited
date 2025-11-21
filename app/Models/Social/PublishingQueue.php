<?php

namespace App\Models\Social;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PublishingQueue extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'cmis.publishing_queue';
    protected $primaryKey = 'queue_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'queue_id',
        'org_id',
        'scheduled_post_id',
        'platform',
        'status',
        'attempts',
        'max_attempts',
        'scheduled_for',
        'processed_at',
        'error_message',
        'execution_data',
    ];

    protected $casts = [
        'execution_data' => 'array',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'scheduled_for' => 'datetime',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    public function scheduledPost(): BelongsTo
    {
        return $this->belongsTo(ScheduledPost::class, 'scheduled_post_id', 'post_id');
    }

    // ===== Queue Management =====

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
        ]);
        $this->increment('attempts');
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }

    public function canRetry(): bool
    {
        return $this->attempts < $this->max_attempts;
    }

    public function isDue(): bool
    {
        return now()->isAfter($this->scheduled_for);
    }

    // ===== Scopes =====

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDue($query)
    {
        return $query->where('status', 'pending')
                     ->where('scheduled_for', '<=', now());
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
