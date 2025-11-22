<?php

namespace App\Models\Campaign;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class CampaignBudget extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.campaign_budgets';
    protected $primaryKey = 'budget_id';

    protected $fillable = [
        'budget_id', 'campaign_id', 'org_id', 'amount', 'currency', 'period'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
