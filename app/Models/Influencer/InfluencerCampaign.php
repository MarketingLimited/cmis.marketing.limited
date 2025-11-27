<?php

namespace App\Models\Influencer;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InfluencerCampaign extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_influencer.influencer_campaigns';
    protected $primaryKey = 'influencer_campaign_id';

    protected $fillable = [
        'influencer_campaign_id',
        'campaign_id',
        'influencer_id',
        'org_id',
        'name',
        'description',
        'campaign_type',
        'status',
        'start_date',
        'end_date',
        'budget',
        'agreed_rate',
        'payment_terms',
        'deliverables',
        'required_content_count',
        'completed_content_count',
        'hashtags',
        'mentions',
        'links',
        'approval_required',
        'approval_status',
        'approved_by',
        'approved_at',
        'performance_goals',
        'actual_performance',
        'total_impressions',
        'total_engagement',
        'total_clicks',
        'total_conversions',
        'roi',
        'notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'agreed_rate' => 'decimal:2',
        'payment_terms' => 'array',
        'deliverables' => 'array',
        'required_content_count' => 'integer',
        'completed_content_count' => 'integer',
        'hashtags' => 'array',
        'mentions' => 'array',
        'links' => 'array',
        'approval_required' => 'boolean',
        'approved_at' => 'datetime',
        'performance_goals' => 'array',
        'actual_performance' => 'array',
        'total_impressions' => 'integer',
        'total_engagement' => 'integer',
        'total_clicks' => 'integer',
        'total_conversions' => 'integer',
        'roi' => 'decimal:4',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Campaign type constants
    public const TYPE_SPONSORED_POST = 'sponsored_post';
    public const TYPE_PRODUCT_REVIEW = 'product_review';
    public const TYPE_BRAND_AMBASSADOR = 'brand_ambassador';
    public const TYPE_GIVEAWAY = 'giveaway';
    public const TYPE_EVENT_COVERAGE = 'event_coverage';
    public const TYPE_CONTENT_COLLABORATION = 'content_collaboration';
    public const TYPE_AFFILIATE = 'affiliate';
    public const TYPE_TAKEOVER = 'takeover';

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PROPOSED = 'proposed';
    public const STATUS_NEGOTIATING = 'negotiating';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ON_HOLD = 'on_hold';

    // Approval status constants
    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';
    public const APPROVAL_REVISION_REQUIRED = 'revision_required';

    // Relationships
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    public function influencer(): BelongsTo
    {
        return $this->belongsTo(Influencer::class, 'influencer_id', 'influencer_id');
    }

    public function content(): HasMany
    {
        return $this->hasMany(InfluencerContent::class, 'influencer_campaign_id', 'influencer_campaign_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InfluencerPayment::class, 'influencer_campaign_id', 'influencer_campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_IN_PROGRESS]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('campaign_type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('approval_required', true)
                     ->where('approval_status', self::APPROVAL_PENDING);
    }

    public function scopeCurrentlyRunning($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_IN_PROGRESS])
                     ->where('start_date', '<=', now())
                     ->where(function ($q) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', now());
                     });
    }

    // Helper Methods
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_IN_PROGRESS]);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function requiresApproval(): bool
    {
        return $this->approval_required === true;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function activate(): bool
    {
        return $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function complete(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_content_count' => $this->content()->count(),
        ]);
    }

    public function cancel(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }

    public function approve(?string $userId = null): bool
    {
        return $this->update([
            'approval_status' => self::APPROVAL_APPROVED,
            'approved_by' => $userId ?? auth()->id(),
            'approved_at' => now(),
        ]);
    }

    public function reject(?string $userId = null): bool
    {
        return $this->update([
            'approval_status' => self::APPROVAL_REJECTED,
            'approved_by' => $userId ?? auth()->id(),
            'approved_at' => now(),
        ]);
    }

    public function requestRevision(?string $userId = null): bool
    {
        return $this->update([
            'approval_status' => self::APPROVAL_REVISION_REQUIRED,
            'approved_by' => $userId ?? auth()->id(),
            'approved_at' => now(),
        ]);
    }

    public function updatePerformanceMetrics(array $metrics): bool
    {
        $updates = [
            'actual_performance' => $metrics,
        ];

        if (isset($metrics['impressions'])) {
            $updates['total_impressions'] = $metrics['impressions'];
        }

        if (isset($metrics['engagement'])) {
            $updates['total_engagement'] = $metrics['engagement'];
        }

        if (isset($metrics['clicks'])) {
            $updates['total_clicks'] = $metrics['clicks'];
        }

        if (isset($metrics['conversions'])) {
            $updates['total_conversions'] = $metrics['conversions'];
        }

        // Calculate ROI
        if ($this->budget > 0 && isset($metrics['revenue'])) {
            $updates['roi'] = ($metrics['revenue'] - $this->budget) / $this->budget;
        }

        return $this->update($updates);
    }

    public function getCompletionPercentage(): float
    {
        if ($this->required_content_count === 0) {
            return 0;
        }

        return round(($this->completed_content_count / $this->required_content_count) * 100, 2);
    }

    public function getDaysRemaining(): int
    {
        if (!$this->end_date) {
            return 0;
        }

        return max(0, now()->diffInDays($this->end_date, false));
    }

    public function getDuration(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        return $this->start_date->diffInDays($this->end_date);
    }

    public function getEngagementRate(): float
    {
        if ($this->total_impressions === 0) {
            return 0;
        }

        return round(($this->total_engagement / $this->total_impressions) * 100, 2);
    }

    public function getClickThroughRate(): float
    {
        if ($this->total_impressions === 0) {
            return 0;
        }

        return round(($this->total_clicks / $this->total_impressions) * 100, 2);
    }

    public function getConversionRate(): float
    {
        if ($this->total_clicks === 0) {
            return 0;
        }

        return round(($this->total_conversions / $this->total_clicks) * 100, 2);
    }

    public function hasMetGoals(): bool
    {
        if (empty($this->performance_goals) || empty($this->actual_performance)) {
            return false;
        }

        foreach ($this->performance_goals as $metric => $goal) {
            if (!isset($this->actual_performance[$metric])) {
                return false;
            }

            if ($this->actual_performance[$metric] < $goal) {
                return false;
            }
        }

        return true;
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_PROPOSED => 'blue',
            self::STATUS_NEGOTIATING => 'yellow',
            self::STATUS_CONFIRMED => 'green',
            self::STATUS_ACTIVE, self::STATUS_IN_PROGRESS => 'green',
            self::STATUS_COMPLETED => 'blue',
            self::STATUS_CANCELLED => 'red',
            self::STATUS_ON_HOLD => 'orange',
            default => 'gray',
        };
    }

    public function getApprovalColor(): string
    {
        return match($this->approval_status) {
            self::APPROVAL_PENDING => 'yellow',
            self::APPROVAL_APPROVED => 'green',
            self::APPROVAL_REJECTED => 'red',
            self::APPROVAL_REVISION_REQUIRED => 'orange',
            default => 'gray',
        };
    }

    // Static Methods
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_SPONSORED_POST => 'Sponsored Post',
            self::TYPE_PRODUCT_REVIEW => 'Product Review',
            self::TYPE_BRAND_AMBASSADOR => 'Brand Ambassador',
            self::TYPE_GIVEAWAY => 'Giveaway',
            self::TYPE_EVENT_COVERAGE => 'Event Coverage',
            self::TYPE_CONTENT_COLLABORATION => 'Content Collaboration',
            self::TYPE_AFFILIATE => 'Affiliate',
            self::TYPE_TAKEOVER => 'Takeover',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PROPOSED => 'Proposed',
            self::STATUS_NEGOTIATING => 'Negotiating',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_ON_HOLD => 'On Hold',
        ];
    }

    public static function getApprovalStatusOptions(): array
    {
        return [
            self::APPROVAL_PENDING => 'Pending',
            self::APPROVAL_APPROVED => 'Approved',
            self::APPROVAL_REJECTED => 'Rejected',
            self::APPROVAL_REVISION_REQUIRED => 'Revision Required',
        ];
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'campaign_id' => 'nullable|uuid|exists:cmis.campaigns,campaign_id',
            'influencer_id' => 'required|uuid|exists:cmis_influencer.influencers,influencer_id',
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'campaign_type' => 'required|in:' . implode(',', array_keys(self::getTypeOptions())),
            'status' => 'nullable|in:' . implode(',', array_keys(self::getStatusOptions())),
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'agreed_rate' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|array',
            'deliverables' => 'nullable|array',
            'required_content_count' => 'nullable|integer|min:1',
            'hashtags' => 'nullable|array',
            'mentions' => 'nullable|array',
            'links' => 'nullable|array',
            'approval_required' => 'nullable|boolean',
            'performance_goals' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:' . implode(',', array_keys(self::getStatusOptions())),
            'budget' => 'sometimes|numeric|min:0',
            'approval_status' => 'sometimes|in:' . implode(',', array_keys(self::getApprovalStatusOptions())),
            'actual_performance' => 'sometimes|array',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'influencer_id.required' => 'Influencer is required',
            'org_id.required' => 'Organization is required',
            'name.required' => 'Campaign name is required',
            'campaign_type.required' => 'Campaign type is required',
            'start_date.required' => 'Start date is required',
            'end_date.after' => 'End date must be after start date',
            'budget.min' => 'Budget must be a positive number',
            'agreed_rate.min' => 'Rate must be a positive number',
        ];
    }
}
