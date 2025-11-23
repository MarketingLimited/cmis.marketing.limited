<?php

namespace App\Models\Other;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class ExportBundle extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.export_bundles';

    protected $primaryKey = 'bundle_id';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'bundle_id',
        'org_id',
        'name',
        'provider',
    ];

    protected $casts = [
        'bundle_id' => 'string',
        'org_id' => 'string',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    

    /**
     * Get the bundle items
     */
    public function items(): HasMany
    {
        return $this->hasMany(ExportBundleItem::class, 'bundle_id', 'bundle_id');
    }
}
