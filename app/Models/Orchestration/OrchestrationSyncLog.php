<?php

namespace App\Models\Orchestration;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrchestrationSyncLog extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'cmis.orchestration_sync_logs';
    protected $primaryKey = 'sync_id';
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
        'sync_id',
        'org_id',
        'orchestration_id',
        'platform_mapping_id',
        'sync_type',
        'direction',
        'status',
        'started_at',
        'completed_at',
        'duration_ms',
        'changes_detected',
        'changes_applied',
        'entities_synced',
        'entities_failed',
        'error_message',
        'error_details',
    ];

    protected $casts = [
        'changes_detected' => 'array',
        'changes_applied' => 'array',
        'error_details' => 'array',
        'duration_ms' => 'integer',
        'entities_synced' => 'integer',
        'entities_failed' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    public function orchestration(): BelongsTo
    {
        return $this->belongsTo(CampaignOrchestration::class, 'orchestration_id', 'orchestration_id');
    }

    public function platformMapping(): BelongsTo
    {
        return $this->belongsTo(OrchestrationPlatform::class, 'platform_mapping_id', 'platform_mapping_id');
    }

    // ===== Sync Tracking =====

    public function markAsRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(array $results): void
    {
        $duration = $this->started_at ? now()->diffInMilliseconds($this->started_at) : 0;

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'duration_ms' => $duration,
            'changes_detected' => $results['changes_detected'] ?? [],
            'changes_applied' => $results['changes_applied'] ?? [],
            'entities_synced' => $results['entities_synced'] ?? 0,
            'entities_failed' => $results['entities_failed'] ?? 0,
        ]);
    }

    public function markAsFailed(string $errorMessage, ?array $errorDetails = null): void
    {
        $duration = $this->started_at ? now()->diffInMilliseconds($this->started_at) : 0;

        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'duration_ms' => $duration,
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
        ]);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'completed' && $this->entities_failed === 0;
    }

    public function hasPartialFailure(): bool
    {
        return $this->status === 'completed' && $this->entities_failed > 0;
    }

    public function getSyncTypeLabel(): string
    {
        return match($this->sync_type) {
            'full' => 'Full Sync',
            'incremental' => 'Incremental Sync',
            'settings' => 'Settings Sync',
            'performance' => 'Performance Sync',
            'creative' => 'Creative Sync',
            default => ucfirst($this->sync_type)
        };
    }

    public function getDirectionLabel(): string
    {
        return match($this->direction) {
            'push' => 'CMIS → Platform',
            'pull' => 'Platform → CMIS',
            'bidirectional' => 'Two-Way Sync',
            default => ucfirst($this->direction)
        };
    }

    public function getSuccessRate(): float
    {
        $total = $this->entities_synced + $this->entities_failed;
        if ($total === 0) {
            return 0;
        }

        return ($this->entities_synced / $total) * 100;
    }

    // ===== Scopes =====

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForSyncType($query, string $type)
    {
        return $query->where('sync_type', $type);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }
}
