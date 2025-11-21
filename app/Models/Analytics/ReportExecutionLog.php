<?php

namespace App\Models\Analytics;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Report Execution Log Model (Phase 12)
 *
 * Tracks execution history of scheduled reports
 *
 * @property string $log_id
 * @property string $schedule_id
 * @property string $org_id
 * @property \Carbon\Carbon $executed_at
 * @property string $status
 * @property string|null $file_path
 * @property string|null $file_url
 * @property int|null $file_size
 * @property int $recipients_count
 * @property int $emails_sent
 * @property int $emails_failed
 * @property string|null $error_message
 * @property array|null $metadata
 * @property int|null $execution_time_ms
 */
class ReportExecutionLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.report_execution_logs';
    protected $primaryKey = 'log_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'schedule_id',
        'org_id',
        'executed_at',
        'status',
        'file_path',
        'file_url',
        'file_size',
        'recipients_count',
        'emails_sent',
        'emails_failed',
        'error_message',
        'metadata',
        'execution_time_ms'
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'file_size' => 'integer',
        'recipients_count' => 'integer',
        'emails_sent' => 'integer',
        'emails_failed' => 'integer',
        'metadata' => 'array',
        'execution_time_ms' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the scheduled report
     */
    public function scheduledReport(): BelongsTo
    {
        return $this->belongsTo(ScheduledReport::class, 'schedule_id', 'schedule_id');
    }

    /**
     * Get the organization
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Scope: Successful executions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope: Failed executions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Recent executions
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('executed_at', '>=', now()->subDays($days));
    }

    /**
     * Check if execution was successful
     */
    public function wasSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Get success rate as percentage
     */
    public function getSuccessRate(): float
    {
        if ($this->recipients_count === 0) {
            return 0.0;
        }

        return ($this->emails_sent / $this->recipients_count) * 100;
    }
}
