<?php

namespace App\Models\Other;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExportBundle extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.export_bundles';

    protected $primaryKey = 'bundle_id';

    public $incrementing = false;

    protected $keyType = 'string';

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
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the bundle items
     */
    public function items()
    {
        return $this->hasMany(ExportBundleItem::class, 'bundle_id', 'bundle_id');
    }

    /**
     * Scope to get bundles for a specific org
     */
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }
}
