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
        'ad_campaign_id', 'org_id', 'name', 'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
