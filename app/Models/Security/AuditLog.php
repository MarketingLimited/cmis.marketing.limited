<?php

namespace App\Models\Security;

use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.audit_log';

    protected $primaryKey = 'log_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'org_id',
        'actor',
        'action',
        'target',
        'meta',
        'ts',
        'deleted_by',
    ];

    protected $casts = [
        'log_id' => 'string',
        'org_id' => 'string',
        'meta' => 'array',
        'ts' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'deleted_by' => 'string',
    ];

    /**
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Scope to get logs for a specific organization
     */
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Scope to get logs for a specific actor
     */
    public function scopeByActor($query, string $actor)
    {
        return $query->where('actor', $actor);
    }

    /**
     * Scope to get logs for a specific action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get recent logs
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('ts', '>=', now()->subHours($hours));
    }
}
