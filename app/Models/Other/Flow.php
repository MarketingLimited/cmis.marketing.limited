<?php

namespace App\Models\Other;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Flow extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.flows';

    protected $primaryKey = 'flow_id';

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
     * Get the flow steps
     */
    public function steps(): HasMany
    {
        return $this->hasMany(FlowStep::class, 'flow_id', 'flow_id')->orderBy('ord');

    }
    /**
     * Scope to get enabled flows
     */
    public function scopeEnabled($query): Builder
    {
        return $query->where('enabled', true);
    }
}
