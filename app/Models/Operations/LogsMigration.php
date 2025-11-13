<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogsMigration extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.logs_migration';

    protected $primaryKey = 'log_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'phase',
        'status',
        'executed_at',
        'details',
    ];

    protected $casts = [
        'log_id' => 'string',
        'executed_at' => 'datetime',
        'details' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope to filter by phase
     */
    public function scopeByPhase($query, string $phase)
    {
        return $query->where('phase', $phase);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get recent logs
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('executed_at', '>=', now()->subHours($hours));
    }
}
