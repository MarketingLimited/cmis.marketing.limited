<?php

namespace App\Models\Other;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Segment extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.segments';

    protected $primaryKey = 'segment_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'name',
        'persona',
        'notes',
    ];

    protected $casts = [
        'segment_id' => 'string',
        'org_id' => 'string',
        'persona' => 'array',
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
     * Scope to get segments for a specific org
     */
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }
}
