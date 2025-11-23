<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataExportLog extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.data_export_logs';
    protected $primaryKey = 'log_id';
    protected $fillable = [
        'config_id', 'org_id', 'started_at', 'completed_at', 'status',
        'format', 'records_count', 'file_size', 'file_path', 'file_url',
        'delivery_url', 'error_message', 'execution_time_ms', 'metadata'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'records_count' => 'integer',
        'file_size' => 'integer',
        'execution_time_ms' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(DataExportConfig::class, 'config_id', 'config_id');

    

        }
    public function scopeCompleted($query): Builder
    {
        return $query->where('status', 'completed');

        }
    public function scopeFailed($query): Builder
    {
        return $query->where('status', 'failed');

        }
    public function scopeRecent($query, int $days = 30): Builder
    {
        return $query->where('started_at', '>=', now()->subDays($days));

        }
    public function markCompleted(int $recordsCount, int $fileSize, string $filePath): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'records_count' => $recordsCount,
            'file_size' => $fileSize,
            'file_path' => $filePath,
            'execution_time_ms' => (int) (now()->diffInMilliseconds($this->started_at))
        ]);

        }
    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $error,
            'execution_time_ms' => (int) (now()->diffInMilliseconds($this->started_at))
        ]);
}
}
}
