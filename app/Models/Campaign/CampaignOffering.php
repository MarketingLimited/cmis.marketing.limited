<?php

namespace App\Models\Campaign;

use App\Models\Campaign;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignOffering extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis.campaign_offerings';
    protected $primaryKey = 'offering_id';

    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'offering_id',
        'provider',
    ];

    protected $casts = [
        'campaign_id' => 'string',
        'offering_id' => 'string',
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
     * Scope to get offerings for a specific campaign
     */
    public function scopeForCampaign($query, string $campaignId): Builder
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope to get campaigns for a specific offering
     */
    public function scopeForOffering($query, string $offeringId): Builder
    {
        return $query->where('offering_id', $offeringId);
    }
}
