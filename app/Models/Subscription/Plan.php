<?php

namespace App\Models\Subscription;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan Model - Subscription plans for CMIS platform.
 *
 * @property string $plan_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property float $price_monthly
 * @property float $price_yearly
 * @property string $currency
 * @property int|null $max_users
 * @property int|null $max_orgs
 * @property int|null $max_api_calls_per_month
 * @property int|null $max_storage_gb
 * @property array $features
 * @property bool $is_active
 * @property bool $is_default
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Plan extends BaseModel
{
    use HasFactory;

    protected $table = 'cmis.plans';
    protected $primaryKey = 'plan_id';

    protected $fillable = [
        'plan_id',
        'name',
        'code',
        'description',
        'price_monthly',
        'price_yearly',
        'currency',
        'max_users',
        'max_orgs',
        'max_api_calls_per_month',
        'max_storage_gb',
        'features',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'max_users' => 'integer',
        'max_orgs' => 'integer',
        'max_api_calls_per_month' => 'integer',
        'max_storage_gb' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    /**
     * Get all subscriptions for this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id', 'plan_id');
    }

    // ===== Scopes =====

    /**
     * Scope to get only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get the default plan.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // ===== Accessors =====

    /**
     * Get the yearly savings amount.
     */
    public function getYearlySavingsAttribute(): float
    {
        $monthlyTotal = $this->price_monthly * 12;
        return $monthlyTotal - $this->price_yearly;
    }

    /**
     * Get the yearly savings percentage.
     */
    public function getYearlySavingsPercentAttribute(): float
    {
        if ($this->price_monthly <= 0) {
            return 0;
        }
        $monthlyTotal = $this->price_monthly * 12;
        return round((($monthlyTotal - $this->price_yearly) / $monthlyTotal) * 100, 1);
    }

    // ===== Feature Checks =====

    /**
     * Check if plan has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return $this->features[$feature] ?? false;
    }

    /**
     * Check if plan has unlimited users.
     */
    public function hasUnlimitedUsers(): bool
    {
        return $this->max_users === null;
    }

    /**
     * Check if plan has unlimited organizations.
     */
    public function hasUnlimitedOrgs(): bool
    {
        return $this->max_orgs === null;
    }

    /**
     * Check if plan has unlimited API calls.
     */
    public function hasUnlimitedApiCalls(): bool
    {
        return $this->max_api_calls_per_month === null;
    }

    /**
     * Check if plan has unlimited storage.
     */
    public function hasUnlimitedStorage(): bool
    {
        return $this->max_storage_gb === null;
    }

    // ===== Static Methods =====

    /**
     * Get the default plan.
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Get plan by code.
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get all active plans ordered by sort_order.
     */
    public static function getAllActive()
    {
        return static::active()->ordered()->get();
    }
}
