<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class AnalyticsIntegration extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.analytics_integrations';

    protected $primaryKey = 'integration_id';

    public $timestamps = true;

    protected $fillable = [
        'integration_id',
        'org_id',
        'campaign_id',
        'platform',
        'source_endpoint',
        'mapping',
        'refresh_frequency',
        'last_synced_at',
        'provider',
    ];

    protected $casts = [
        'integration_id' => 'string',
        'org_id' => 'string',
        'campaign_id' => 'string',
        'mapping' => 'array',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    

    /**
     * Get the campaign
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');

    /**
     * Scope to get integrations for a specific org
     */
    public function scopeForOrg(Builder $query, string $orgId): Builder
    {
        return $query->where('org_id', $orgId);

    /**
     * Scope to get integrations for a specific campaign
     */
    public function scopeForCampaign($query, string $campaignId)
    {
        return $query->where('campaign_id', $campaignId);

    /**
     * Scope to filter by platform
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
}
