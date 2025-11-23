<?php

namespace App\Models\Core;

use App\Models\Concerns\HasOrganization;

use App\Models\AI\DatasetPackage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgDataset extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.org_datasets';
    protected $primaryKey = 'pkg_id';

    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'pkg_id',
        'enabled',
        'provider',
    ];

    protected $casts = [
        'org_id' => 'string',
        'pkg_id' => 'string',
        'enabled' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    

    /**
     * Get the dataset package
     */
    public function datasetPackage()
    {
        return $this->belongsTo(DatasetPackage::class, 'pkg_id', 'pkg_id');

    /**
     * Scope to get enabled datasets
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);

    /**
     * Scope to get datasets for a specific org
     */
    public function scopeForOrg(Builder $query, string $orgId): Builder
    {
        return $query->where('org_id', $orgId);
}
