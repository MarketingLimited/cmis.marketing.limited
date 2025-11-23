<?php

namespace App\Models\Other;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class OfferingsOld extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.offerings_old';

    protected $primaryKey = 'offering_id';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'offering_id',
        'org_id',
        'kind',
        'name',
        'description',
        'provider',
    ];

    protected $casts = [
        'offering_id' => 'string',
        'org_id' => 'string',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    

    /**
     * Scope to filter by kind
     */
    public function scopeOfKind($query, string $kind)
    {
        return $query->where('kind', $kind);

    /**
     * Scope to get offerings for a specific org
     */
    public function scopeForOrg(Builder $query, string $orgId): Builder
    {
        return $query->where('org_id', $orgId);
}
