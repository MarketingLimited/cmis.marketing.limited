<?php

namespace App\Models\Campaign;

use App\Models\Concerns\HasOrganization;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignBudget extends BaseModel
{
    use HasFactory, HasOrganization;

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

    /**
     * Get the campaign this budget belongs to
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'campaign_id');
    }
}
