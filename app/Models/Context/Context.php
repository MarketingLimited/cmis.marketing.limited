<?php

namespace App\Models\Context;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Context extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.contexts';

    protected $primaryKey = 'context_id';

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
     * Get the campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get contexts for a specific campaign
     */
    public function scopeForCampaign($query, string $campaignId): Builder
    {
        return $query->where('campaign_id', $campaignId);
    }
}
