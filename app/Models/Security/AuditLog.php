<?php

namespace App\Models\Security;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class AuditLog extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.audit_log';

    protected $primaryKey = 'log_id';

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
