<?php

namespace App\Models\Campaign;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignOffering extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.campaign_offerings';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'campaign_id',
        'offering_id',
    ];

    protected $casts = [
        'campaign_id' => 'string',
        'offering_id' => 'string',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the campaign
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Scope to get offerings for a specific campaign
     */
    public function scopeForCampaign($query, string $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope to get campaigns for a specific offering
     */
    public function scopeForOffering($query, string $offeringId)
    {
        return $query->where('offering_id', $offeringId);
    }
}
