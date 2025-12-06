<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use App\Models\Marketplace\MarketplaceApp;
use App\Models\Subscription\Plan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PlanApp Model
 *
 * Maps which marketplace apps are available for each subscription plan.
 * This enables plan-based access control for features and apps.
 *
 * @property string $plan_app_id
 * @property string $plan_id
 * @property string $app_id
 * @property bool $is_enabled
 * @property int|null $usage_limit
 * @property array $settings_override
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Plan $plan
 * @property-read MarketplaceApp $app
 */
class PlanApp extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected $table = 'cmis.plan_apps';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'plan_app_id';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'plan_id',
        'app_id',
        'is_enabled',
        'usage_limit',
        'settings_override',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'usage_limit' => 'integer',
        'settings_override' => 'array',
    ];

    /**
     * Get the plan this association belongs to.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id');
    }

    /**
     * Get the app this association belongs to.
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(MarketplaceApp::class, 'app_id', 'app_id');
    }

    /**
     * Scope to only enabled apps.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope for a specific plan.
     */
    public function scopeForPlan($query, string $planId)
    {
        return $query->where('plan_id', $planId);
    }

    /**
     * Scope for a specific app.
     */
    public function scopeForApp($query, string $appId)
    {
        return $query->where('app_id', $appId);
    }

    /**
     * Check if this plan-app association has a usage limit.
     */
    public function hasUsageLimit(): bool
    {
        return $this->usage_limit !== null && $this->usage_limit > 0;
    }

    /**
     * Get a specific setting override value.
     */
    public function getSettingOverride(string $key, $default = null)
    {
        return $this->settings_override[$key] ?? $default;
    }
}
