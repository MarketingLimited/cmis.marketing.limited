<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasOrganization;
use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExecutionLog extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis_analytics.report_execution_logs';

    protected $primaryKey = 'log_id';

    protected $fillable = [
        'log_id',
        'org_id',
        'schedule_id',
        'report_type',
        'status',
        'executed_at',
        'recipients_count',
        'emails_sent',
        'error_message',
        'execution_time_ms',
    ];

    protected $casts = [
        'log_id' => 'string',
        'org_id' => 'string',
        'schedule_id' => 'string',
        'executed_at' => 'datetime',
        'recipients_count' => 'integer',
        'emails_sent' => 'integer',
        'execution_time_ms' => 'integer',
    ];

    /**
     * Get the organization this log belongs to
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the schedule this execution belongs to
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ScheduledReport::class, 'schedule_id', 'schedule_id');
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
