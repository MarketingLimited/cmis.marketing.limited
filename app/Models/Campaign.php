<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Campaign extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.campaigns';

    protected $primaryKey = 'campaign_id';

    protected $fillable = [
        'campaign_id',
        'org_id',
        'name',
        'objective',
        'status',
        'start_date',
        'end_date',
        'budget',
        'currency',
        'context_id',
        'creative_id',
        'value_id',
        'created_by',
        'provider',
        'deleted_by',
        'description',
    ];

    protected $casts = [
        'campaign_id' => 'string',
        'org_id' => 'string',
        'context_id' => 'string',
        'creative_id' => 'string',
        'value_id' => 'string',
        'created_by' => 'string',
        'deleted_by' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get validation rules for campaign creation
     *
     * @return array
     */
    public static function createRules(): array
    {
        return [
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'name' => 'required|string|max:255',
            'objective' => 'required|in:awareness,traffic,engagement,leads,sales,app_installs,video_views',
            'status' => 'nullable|in:draft,active,paused,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'context_id' => 'nullable|uuid',
            'creative_id' => 'nullable|uuid',
            'value_id' => 'nullable|uuid',
            'created_by' => 'nullable|uuid|exists:cmis.users,user_id',
            'provider' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get validation rules for campaign updates
     *
     * @return array
     */
    public static function updateRules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'objective' => 'sometimes|required|in:awareness,traffic,engagement,leads,sales,app_installs,video_views',
            'status' => 'sometimes|required|in:draft,active,paused,completed',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'context_id' => 'nullable|uuid',
            'creative_id' => 'nullable|uuid',
            'value_id' => 'nullable|uuid',
            'provider' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get validation messages
     *
     * @return array
     */
    public static function validationMessages(): array
    {
        return [
            'org_id.required' => 'Organization ID is required',
            'org_id.uuid' => 'Organization ID must be a valid UUID',
            'org_id.exists' => 'Organization does not exist',
            'name.required' => 'Campaign name is required',
            'name.max' => 'Campaign name cannot exceed 255 characters',
            'objective.required' => 'Campaign objective is required',
            'objective.in' => 'Invalid campaign objective',
            'status.in' => 'Invalid campaign status',
            'end_date.after' => 'End date must be after start date',
            'budget.numeric' => 'Budget must be a number',
            'budget.min' => 'Budget cannot be negative',
            'currency.size' => 'Currency code must be exactly 3 characters',
            'created_by.exists' => 'Creator user does not exist',
        ];
    }



    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function offerings(): BelongsToMany
    {
        return $this->belongsToMany(
            Offering::class,
            'cmis.campaign_offerings',
            'campaign_id',
            'offering_id'
        );
    }

    public function performanceMetrics(): HasMany
    {
        return $this->hasMany(CampaignPerformanceMetric::class, 'campaign_id', 'campaign_id');
    }

    public function adCampaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class, 'campaign_id', 'campaign_id');
    }

    public function creativeAssets(): HasMany
    {
        return $this->hasMany(\App\Models\CreativeAsset::class, 'campaign_id', 'campaign_id');
    }
}
