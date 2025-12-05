<?php

namespace App\Models\Subscription;

use App\Models\Concerns\HasOrganization;
use App\Models\BaseModel;
use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subscription Model - Organization subscriptions to plans.
 *
 * @property string $subscription_id
 * @property string $org_id
 * @property string|null $plan_id
 * @property string $status
 * @property \Carbon\Carbon|null $starts_at
 * @property \Carbon\Carbon|null $ends_at
 * @property \Carbon\Carbon|null $trial_ends_at
 * @property \Carbon\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property array|null $metadata
 */
class Subscription extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.subscriptions';
    protected $primaryKey = 'subscription_id';

    /**
     * Subscription status constants.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_TRIAL = 'trial';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PAST_DUE = 'past_due';

    protected $fillable = [
        'subscription_id',
        'org_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'cancellation_reason',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    /**
     * Get the plan for this subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id');
    }

    /**
     * Get the organization for this subscription.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    // ===== Status Methods =====

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if subscription is on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if subscription is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED || $this->cancelled_at !== null;
    }

    /**
     * Check if subscription is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->ends_at && $this->ends_at->isPast());
    }

    /**
     * Check if subscription is valid (active or on trial).
     */
    public function isValid(): bool
    {
        return $this->isActive() || $this->isOnTrial();
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Reactivate a cancelled subscription.
     */
    public function reactivate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);
    }

    /**
     * Change the plan for this subscription.
     */
    public function changePlan(string $planId): void
    {
        $this->update([
            'plan_id' => $planId,
        ]);
    }

    /**
     * Extend the trial period.
     */
    public function extendTrial(int $days): void
    {
        $currentEnd = $this->trial_ends_at ?? now();
        $this->update([
            'trial_ends_at' => $currentEnd->addDays($days),
        ]);
    }

    // ===== Scopes =====

    /**
     * Scope to get active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get trial subscriptions.
     */
    public function scopeOnTrial($query)
    {
        return $query->where('status', self::STATUS_TRIAL)
            ->where('trial_ends_at', '>', now());
    }

    /**
     * Scope to get cancelled subscriptions.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope to get expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_EXPIRED)
                ->orWhere('ends_at', '<', now());
        });
    }

    /**
     * Scope to filter by plan.
     */
    public function scopeForPlan($query, string $planId)
    {
        return $query->where('plan_id', $planId);
    }

    /**
     * Scope to get valid subscriptions (active or on trial).
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_ACTIVE)
                ->orWhere(function ($q2) {
                    $q2->where('status', self::STATUS_TRIAL)
                        ->where('trial_ends_at', '>', now());
                });
        });
    }
}
