<?php

namespace App\Models\Other;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Flow extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.flows';

    protected $primaryKey = 'flow_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'flow_id',
        'org_id',
        'name',
        'description',
        'version',
        'tags',
        'enabled',
        'provider',
    ];

    protected $casts = [
        'flow_id' => 'string',
        'org_id' => 'string',
        'tags' => 'array',
        'enabled' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the flow steps
     */
    public function steps()
    {
        return $this->hasMany(FlowStep::class, 'flow_id', 'flow_id')->orderBy('ord');
    }

    /**
     * Scope to get enabled flows
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to get flows for a specific org
     */
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }
}
