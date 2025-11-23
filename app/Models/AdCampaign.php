<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class AdCampaign extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.ad_campaigns_v2';
    protected $primaryKey = 'ad_campaign_id';

    protected $fillable = [
        'ad_campaign_id', 'org_id', 'campaign_id', 'name', 'status'
    ];

    protected $casts = [
        'ad_campaign_id' => 'string',
        'org_id' => 'string',
        'campaign_id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the parent campaign this ad campaign belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }
}
