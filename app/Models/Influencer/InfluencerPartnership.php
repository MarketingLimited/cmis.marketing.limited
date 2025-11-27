<?php

namespace App\Models\Influencer;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InfluencerPartnership extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_influencer.influencer_partnerships';
    protected $primaryKey = 'partnership_id';

    protected $fillable = [
        'partnership_id',
        'influencer_id',
        'org_id',
        'partnership_type',
        'status',
        'start_date',
        'end_date',
        'contract_url',
        'terms',
        'exclusivity',
        'exclusivity_categories',
        'content_rights',
        'compensation_structure',
        'base_rate',
        'performance_bonus',
        'affiliate_commission_rate',
        'minimum_posts_per_month',
        'total_campaigns_completed',
        'total_revenue_generated',
        'renewal_terms',
        'auto_renew',
        'renewal_date',
        'renewal_count',
        'termination_notice_days',
        'nda_signed',
        'nda_signed_date',
        'notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'terms' => 'array',
        'exclusivity' => 'boolean',
        'exclusivity_categories' => 'array',
        'content_rights' => 'array',
        'compensation_structure' => 'array',
        'base_rate' => 'decimal:2',
        'performance_bonus' => 'decimal:2',
        'affiliate_commission_rate' => 'decimal:4',
        'minimum_posts_per_month' => 'integer',
        'total_campaigns_completed' => 'integer',
        'total_revenue_generated' => 'decimal:2',
        'renewal_terms' => 'array',
        'auto_renew' => 'boolean',
        'renewal_date' => 'date',
        'renewal_count' => 'integer',
        'termination_notice_days' => 'integer',
        'nda_signed' => 'boolean',
        'nda_signed_date' => 'date',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Partnership type constants
    public const TYPE_ONE_TIME = 'one_time';
    public const TYPE_SHORT_TERM = 'short_term';
    public const TYPE_LONG_TERM = 'long_term';
    public const TYPE_BRAND_AMBASSADOR = 'brand_ambassador';
    public const TYPE_EXCLUSIVE = 'exclusive';
    public const TYPE_AFFILIATE = 'affiliate';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_TERMINATED = 'terminated';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_RENEWAL_PENDING = 'renewal_pending';

    // Relationships
    public function influencer(): BelongsTo
    {
        return $this->belongsTo(Influencer::class, 'influencer_id', 'influencer_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(InfluencerCampaign::class, 'influencer_id', 'influencer_id')
                    ->where('start_date', '>=', $this->start_date)
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '<=', $this->end_date);
                    });
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InfluencerPayment::class, 'partnership_id', 'partnership_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('partnership_type', $type);
    }

    public function scopeExclusive($query)
    {
        return $query->where('exclusivity', true);
    }

    public function scopeUpForRenewal($query, int $days = 30)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                     ->whereNotNull('renewal_date')
                     ->where('renewal_date', '<=', now()->addDays($days));
    }

    public function scopeAutoRenew($query)
    {
        return $query->where('auto_renew', true);
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->end_date !== null && $this->end_date->isPast();
    }

    public function hasExclusivity(): bool
    {
        return $this->exclusivity === true;
    }

    public function hasNDA(): bool
    {
        return $this->nda_signed === true;
    }

    public function activate(): bool
    {
        return $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function suspend(): bool
    {
        return $this->update(['status' => self::STATUS_SUSPENDED]);
    }

    public function terminate(): bool
    {
        return $this->update([
            'status' => self::STATUS_TERMINATED,
            'end_date' => now(),
        ]);
    }

    public function renew(?int $months = null): bool
    {
        $duration = $months ?? $this->getDurationMonths();

        return $this->update([
            'status' => self::STATUS_ACTIVE,
            'start_date' => $this->end_date ?? now(),
            'end_date' => ($this->end_date ?? now())->addMonths($duration),
            'renewal_date' => ($this->end_date ?? now())->addMonths($duration)->subDays($this->termination_notice_days ?? 30),
            'renewal_count' => $this->renewal_count + 1,
        ]);
    }

    public function updateCompensation(array $compensation): bool
    {
        $updates = ['compensation_structure' => $compensation];

        if (isset($compensation['base_rate'])) {
            $updates['base_rate'] = $compensation['base_rate'];
        }

        if (isset($compensation['performance_bonus'])) {
            $updates['performance_bonus'] = $compensation['performance_bonus'];
        }

        if (isset($compensation['affiliate_commission_rate'])) {
            $updates['affiliate_commission_rate'] = $compensation['affiliate_commission_rate'];
        }

        return $this->update($updates);
    }

    public function incrementCampaigns(): bool
    {
        return $this->increment('total_campaigns_completed');
    }

    public function addRevenue(float $amount): bool
    {
        return $this->increment('total_revenue_generated', $amount);
    }

    public function signNDA(): bool
    {
        return $this->update([
            'nda_signed' => true,
            'nda_signed_date' => now(),
        ]);
    }

    public function getDurationMonths(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        return $this->start_date->diffInMonths($this->end_date);
    }

    public function getDaysRemaining(): int
    {
        if (!$this->end_date) {
            return 0;
        }

        return max(0, now()->diffInDays($this->end_date, false));
    }

    public function getDaysUntilRenewal(): ?int
    {
        if (!$this->renewal_date) {
            return null;
        }

        return max(0, now()->diffInDays($this->renewal_date, false));
    }

    public function getAverageRevenuePerCampaign(): float
    {
        if ($this->total_campaigns_completed === 0) {
            return 0;
        }

        return round($this->total_revenue_generated / $this->total_campaigns_completed, 2);
    }

    public function needsRenewal(int $daysThreshold = 30): bool
    {
        if (!$this->renewal_date) {
            return false;
        }

        return $this->renewal_date->diffInDays(now()) <= $daysThreshold;
    }

    public function canTerminate(): bool
    {
        if (!$this->termination_notice_days) {
            return true;
        }

        if (!$this->end_date) {
            return true;
        }

        return now()->diffInDays($this->end_date) >= $this->termination_notice_days;
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_ACTIVE => 'green',
            self::STATUS_EXPIRED => 'gray',
            self::STATUS_TERMINATED => 'red',
            self::STATUS_SUSPENDED => 'orange',
            self::STATUS_RENEWAL_PENDING => 'blue',
            default => 'gray',
        };
    }

    public function getTypeColor(): string
    {
        return match($this->partnership_type) {
            self::TYPE_ONE_TIME => 'blue',
            self::TYPE_SHORT_TERM => 'green',
            self::TYPE_LONG_TERM => 'purple',
            self::TYPE_BRAND_AMBASSADOR => 'orange',
            self::TYPE_EXCLUSIVE => 'red',
            self::TYPE_AFFILIATE => 'teal',
            default => 'gray',
        };
    }

    // Static Methods
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_ONE_TIME => 'One-Time',
            self::TYPE_SHORT_TERM => 'Short-Term',
            self::TYPE_LONG_TERM => 'Long-Term',
            self::TYPE_BRAND_AMBASSADOR => 'Brand Ambassador',
            self::TYPE_EXCLUSIVE => 'Exclusive',
            self::TYPE_AFFILIATE => 'Affiliate',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_TERMINATED => 'Terminated',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_RENEWAL_PENDING => 'Renewal Pending',
        ];
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'influencer_id' => 'required|uuid|exists:cmis_influencer.influencers,influencer_id',
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'partnership_type' => 'required|in:' . implode(',', array_keys(self::getTypeOptions())),
            'status' => 'nullable|in:' . implode(',', array_keys(self::getStatusOptions())),
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'contract_url' => 'nullable|url',
            'terms' => 'nullable|array',
            'exclusivity' => 'nullable|boolean',
            'exclusivity_categories' => 'nullable|array',
            'content_rights' => 'nullable|array',
            'compensation_structure' => 'nullable|array',
            'base_rate' => 'nullable|numeric|min:0',
            'performance_bonus' => 'nullable|numeric|min:0',
            'affiliate_commission_rate' => 'nullable|numeric|min:0|max:1',
            'minimum_posts_per_month' => 'nullable|integer|min:0',
            'renewal_terms' => 'nullable|array',
            'auto_renew' => 'nullable|boolean',
            'termination_notice_days' => 'nullable|integer|min:0',
            'nda_signed' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'status' => 'sometimes|in:' . implode(',', array_keys(self::getStatusOptions())),
            'compensation_structure' => 'sometimes|array',
            'base_rate' => 'sometimes|numeric|min:0',
            'auto_renew' => 'sometimes|boolean',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'influencer_id.required' => 'Influencer is required',
            'org_id.required' => 'Organization is required',
            'partnership_type.required' => 'Partnership type is required',
            'start_date.required' => 'Start date is required',
            'end_date.after' => 'End date must be after start date',
            'base_rate.min' => 'Base rate must be a positive number',
            'affiliate_commission_rate.max' => 'Commission rate must be between 0 and 1',
        ];
    }
}
