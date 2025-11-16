<?php

namespace App\Models\Context;

use App\Models\Core\Org;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Context extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $connection = 'pgsql';

    protected $table = 'cmis.contexts';

    protected $primaryKey = 'context_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'context_id',
        'org_id',
        'campaign_id',
        'type',
        'metadata',
        'provider',
    ];

    protected $casts = [
        'context_id' => 'string',
        'org_id' => 'string',
        'campaign_id' => 'string',
        'metadata' => 'array',
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
     * Get the campaign
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get contexts for a specific org
     */
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Scope to get contexts for a specific campaign
     */
    public function scopeForCampaign($query, string $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }
}
