<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpsEtlLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.ops_etl_log';
    protected $primaryKey = 'log_id';
    public $incrementing = false;
    protected $keyType = 'string';

    const UPDATED_AT = null; // No updated_at column

    protected $fillable = [
        'org_id',
        'job_name',
        'job_type',
        'source',
        'destination',
        'records_processed',
        'records_succeeded',
        'records_failed',
        'duration_seconds',
        'started_at',
        'completed_at',
        'status',
        'error_details',
        'metadata',
    ];

    protected $casts = [
        'records_processed' => 'integer',
        'records_succeeded' => 'integer',
        'records_failed' => 'integer',
        'duration_seconds' => 'integer',
        'error_details' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // Relationships
    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id', 'org_id');
    }

    // Scopes
    public function scopeByOrg($query, $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('job_type', $type);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helpers
    public static function start($orgId, $jobName, $jobType, $source, $destination)
    {
        return static::create([
            'org_id' => $orgId,
            'job_name' => $jobName,
            'job_type' => $jobType,
            'source' => $source,
            'destination' => $destination,
            'started_at' => now(),
            'status' => 'running',
            'records_processed' => 0,
            'records_succeeded' => 0,
            'records_failed' => 0,
        ]);
    }

    public function complete($recordsProcessed, $recordsSucceeded, $recordsFailed = 0)
    {
        $this->update([
            'status' => 'completed',
            'records_processed' => $recordsProcessed,
            'records_succeeded' => $recordsSucceeded,
            'records_failed' => $recordsFailed,
            'completed_at' => now(),
            'duration_seconds' => now()->diffInSeconds($this->started_at),
        ]);
    }

    public function fail($errorDetails = null)
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'duration_seconds' => now()->diffInSeconds($this->started_at),
            'error_details' => $errorDetails,
        ]);
    }

    public function getSuccessRate()
    {
        if ($this->records_processed == 0) {
            return 0;
        }

        return round(($this->records_succeeded / $this->records_processed) * 100, 2);
    }
}
