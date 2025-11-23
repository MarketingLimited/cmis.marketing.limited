<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class LogsMigration extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'cmis.logs_migration';

    protected $primaryKey = 'log_id';

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
