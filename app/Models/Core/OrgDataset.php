<?php

namespace App\Models\Core;

use App\Models\AI\DatasetPackage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgDataset extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.org_datasets';
    protected $primaryKey = 'pkg_id';

    public $timestamps = false;

    public $incrementing = false;

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
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the dataset package
     */
    public function datasetPackage()
    {
        return $this->belongsTo(DatasetPackage::class, 'pkg_id', 'pkg_id');
    }

    /**
     * Scope to get enabled datasets
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to get datasets for a specific org
     */
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }
}
