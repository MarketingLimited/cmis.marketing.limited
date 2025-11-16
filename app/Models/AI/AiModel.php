<?php

namespace App\Models\AI;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class AiModel extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $connection = 'pgsql';

    protected $table = 'cmis.ai_models';

    protected $primaryKey = 'model_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'model_id',
        'org_id',
        'name',
        'engine',
        'version',
        'description',
        'model_name',
        'model_family',
        'status',
        'trained_at',
        'provider',
    ];

    protected $casts = [
        'model_id' => 'string',
        'org_id' => 'string',
        'created_at' => 'datetime',
        'trained_at' => 'datetime',
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
     * Scope to get models for a specific org
     */
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Scope to filter by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by provider
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}
