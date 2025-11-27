<?php

namespace App\Models\Platform;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use App\Models\Social\ProfileGroup;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BoostRule Model
 *
 * Represents an automated boost rule for promoting social media posts.
 * Defines triggers (manual, time-based, performance-based), target profiles,
 * and boost configuration (budget, audience, duration).
 *
 * @property string $boost_rule_id
 * @property string $org_id
 * @property string $profile_group_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property string $trigger_type
 * @property array|null $delay_after_publish
 * @property array|null $performance_threshold
 * @property array $apply_to_social_profiles
 * @property string $ad_account_id
 * @property array $boost_config
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class BoostRule extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis.boost_rules';
    protected $primaryKey = 'boost_rule_id';

    protected $fillable = [
        'org_id',
        'profile_group_id',
        'name',
        'description',
        'is_active',
        'trigger_type',
        'trigger_threshold',
        'trigger_metric',
        'trigger_time_window_hours',
        'budget_type',
        'budget_amount',
        'budget_currency',
        'duration_hours',
        'delay_after_publish',
        'performance_threshold',
        'apply_to_social_profiles',
        'ad_account_id',
        'boost_config',
        'targeting_options',
        'max_boosts_per_day',
        'max_budget_per_day',
        'platforms',
        'settings',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trigger_threshold' => 'decimal:2',
        'budget_amount' => 'decimal:2',
        'max_budget_per_day' => 'decimal:2',
        'delay_after_publish' => 'array',
        'performance_threshold' => 'array',
        'apply_to_social_profiles' => 'array',
        'boost_config' => 'array',
        'targeting_options' => 'array',
        'platforms' => 'array',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Trigger type constants
     */
    const TRIGGER_MANUAL = 'manual';
    const TRIGGER_AUTO_AFTER_PUBLISH = 'auto_after_publish';
    const TRIGGER_AUTO_PERFORMANCE = 'auto_performance';

    /**
     * Get the profile group this boost rule belongs to
     */
    public function profileGroup(): BelongsTo
    {
        return $this->belongsTo(ProfileGroup::class, 'profile_group_id', 'group_id');
    }

    /**
     * Get the ad account used for boosting
     */
    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class, 'ad_account_id', 'id');
    }

    /**
     * Get the user who created this boost rule
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Scope to get only active boost rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by trigger type
     */
    public function scopeByTriggerType($query, string $triggerType)
    {
        return $query->where('trigger_type', $triggerType);
    }

    /**
     * Scope to get manual trigger rules
     */
    public function scopeManual($query)
    {
        return $query->where('trigger_type', self::TRIGGER_MANUAL);
    }

    /**
     * Scope to get auto-publish trigger rules
     */
    public function scopeAutoAfterPublish($query)
    {
        return $query->where('trigger_type', self::TRIGGER_AUTO_AFTER_PUBLISH);
    }

    /**
     * Scope to get performance-based trigger rules
     */
    public function scopeAutoPerformance($query)
    {
        return $query->where('trigger_type', self::TRIGGER_AUTO_PERFORMANCE);
    }

    /**
     * Scope to get rules for a specific social profile
     */
    public function scopeForProfile($query, string $integrationId)
    {
        return $query->whereJsonContains('apply_to_social_profiles', $integrationId);
    }

    /**
     * Check if rule applies to a specific social profile
     */
    public function appliesToProfile(string $integrationId): bool
    {
        // Empty array means applies to all profiles in group
        if (empty($this->apply_to_social_profiles)) {
            return true;
        }

        return in_array($integrationId, $this->apply_to_social_profiles);
    }

    /**
     * Check if rule is manual trigger
     */
    public function isManualTrigger(): bool
    {
        return $this->trigger_type === self::TRIGGER_MANUAL;
    }

    /**
     * Check if rule is auto-publish trigger
     */
    public function isAutoAfterPublish(): bool
    {
        return $this->trigger_type === self::TRIGGER_AUTO_AFTER_PUBLISH;
    }

    /**
     * Check if rule is performance-based trigger
     */
    public function isAutoPerformance(): bool
    {
        return $this->trigger_type === self::TRIGGER_AUTO_PERFORMANCE;
    }

    /**
     * Get delay duration in hours
     */
    public function getDelayInHours(): ?int
    {
        if (!$this->delay_after_publish) {
            return null;
        }

        $value = $this->delay_after_publish['value'] ?? 0;
        $unit = $this->delay_after_publish['unit'] ?? 'hours';

        return match($unit) {
            'minutes' => (int)($value / 60),
            'hours' => (int)$value,
            'days' => (int)($value * 24),
            default => (int)$value,
        };
    }

    /**
     * Get performance threshold metric
     */
    public function getPerformanceMetric(): ?string
    {
        return $this->performance_threshold['metric'] ?? null;
    }

    /**
     * Get performance threshold operator
     */
    public function getPerformanceOperator(): ?string
    {
        return $this->performance_threshold['operator'] ?? null;
    }

    /**
     * Get performance threshold value
     */
    public function getPerformanceValue(): mixed
    {
        return $this->performance_threshold['value'] ?? null;
    }

    /**
     * Get performance time window in hours
     */
    public function getPerformanceTimeWindow(): ?int
    {
        return $this->performance_threshold['time_window_hours'] ?? null;
    }

    /**
     * Get boost budget amount
     */
    public function getBudgetAmount(): ?float
    {
        return isset($this->boost_config['budget_amount'])
            ? (float)$this->boost_config['budget_amount']
            : null;
    }

    /**
     * Get boost budget type
     */
    public function getBudgetType(): ?string
    {
        return $this->boost_config['budget_type'] ?? null;
    }

    /**
     * Get boost duration in days
     */
    public function getDurationDays(): ?int
    {
        return $this->boost_config['duration_days'] ?? null;
    }

    /**
     * Get boost objective
     */
    public function getObjective(): ?string
    {
        return $this->boost_config['objective'] ?? null;
    }

    /**
     * Get boost audience configuration
     */
    public function getAudience(): ?array
    {
        return $this->boost_config['audience'] ?? null;
    }

    /**
     * Check if performance threshold is met
     *
     * @param float $actualValue The actual metric value from the post
     * @return bool
     */
    public function isPerformanceThresholdMet(float $actualValue): bool
    {
        if (!$this->isAutoPerformance()) {
            return false;
        }

        $operator = $this->getPerformanceOperator();
        $thresholdValue = $this->getPerformanceValue();

        if (!$operator || $thresholdValue === null) {
            return false;
        }

        return match($operator) {
            '>' => $actualValue > $thresholdValue,
            '>=' => $actualValue >= $thresholdValue,
            '<' => $actualValue < $thresholdValue,
            '<=' => $actualValue <= $thresholdValue,
            '==' => $actualValue == $thresholdValue,
            default => false,
        };
    }

    /**
     * Get all available trigger types
     */
    public static function getAvailableTriggerTypes(): array
    {
        return [
            self::TRIGGER_MANUAL,
            self::TRIGGER_AUTO_AFTER_PUBLISH,
            self::TRIGGER_AUTO_PERFORMANCE,
        ];
    }

    /**
     * Get the count of social profiles this rule applies to
     */
    public function getAppliedProfilesCountAttribute(): int
    {
        if (empty($this->apply_to_social_profiles)) {
            // Applies to all profiles in group
            return $this->profileGroup->socialIntegrations()->count();
        }

        return count($this->apply_to_social_profiles);
    }
}
