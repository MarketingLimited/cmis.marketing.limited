<?php

namespace App\Models\Intelligence;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recommendation extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_intelligence.recommendations';
    protected $primaryKey = 'recommendation_id';

    protected $fillable = [
        'recommendation_id',
        'org_id',
        'type',
        'priority',
        'title',
        'description',
        'reasoning',
        'impact_estimate',
        'confidence',
        'status',
        'category',
        'target_entity_type',
        'target_entity_id',
        'suggested_actions',
        'applied_at',
        'applied_by',
        'result',
        'actual_impact',
        'feedback_rating',
        'feedback_notes',
        'expires_at',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'impact_estimate' => 'decimal:2',
        'confidence' => 'decimal:2',
        'actual_impact' => 'decimal:2',
        'suggested_actions' => 'array',
        'result' => 'array',
        'applied_at' => 'datetime',
        'expires_at' => 'datetime',
        'feedback_rating' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Type constants
    public const TYPE_BUDGET_OPTIMIZATION = 'budget_optimization';
    public const TYPE_BID_ADJUSTMENT = 'bid_adjustment';
    public const TYPE_TARGETING_REFINEMENT = 'targeting_refinement';
    public const TYPE_CREATIVE_REFRESH = 'creative_refresh';
    public const TYPE_SCHEDULE_OPTIMIZATION = 'schedule_optimization';
    public const TYPE_CAMPAIGN_PAUSE = 'campaign_pause';
    public const TYPE_CAMPAIGN_EXPANSION = 'campaign_expansion';
    public const TYPE_AUDIENCE_EXPANSION = 'audience_expansion';
    public const TYPE_PERFORMANCE_ALERT = 'performance_alert';

    // Priority constants
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_IN_REVIEW = 'in_review';

    // Category constants
    public const CATEGORY_PERFORMANCE = 'performance';
    public const CATEGORY_BUDGET = 'budget';
    public const CATEGORY_TARGETING = 'targeting';
    public const CATEGORY_CREATIVE = 'creative';
    public const CATEGORY_AUTOMATION = 'automation';

    // Relationships
    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by', 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApplied($query)
    {
        return $query->where('status', self::STATUS_APPLIED);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', self::PRIORITY_URGENT);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeHighConfidence($query, float $minConfidence = 0.8)
    {
        return $query->where('confidence', '>=', $minConfidence);
    }

    // Helper Methods
    public function isApplied(): bool
    {
        return $this->status === self::STATUS_APPLIED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isHighPriority(): bool
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    public function isHighConfidence(float $threshold = 0.8): bool
    {
        return $this->confidence >= $threshold;
    }

    public function apply(?string $userId = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APPLIED,
            'applied_at' => now(),
            'applied_by' => $userId ?? auth()->id(),
        ]);
    }

    public function reject(): bool
    {
        return $this->update(['status' => self::STATUS_REJECTED]);
    }

    public function recordResult(array $result, ?float $actualImpact = null): bool
    {
        return $this->update([
            'result' => $result,
            'actual_impact' => $actualImpact,
        ]);
    }

    public function provideFeedback(int $rating, ?string $notes = null): bool
    {
        return $this->update([
            'feedback_rating' => $rating,
            'feedback_notes' => $notes,
        ]);
    }

    public function getPriorityColor(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'blue',
            self::PRIORITY_MEDIUM => 'yellow',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_URGENT => 'red',
            default => 'gray',
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPLIED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_EXPIRED => 'gray',
            self::STATUS_IN_REVIEW => 'blue',
            default => 'gray',
        };
    }

    public function getAccuracyScore(): ?float
    {
        if ($this->actual_impact === null || $this->impact_estimate === null || $this->impact_estimate == 0) {
            return null;
        }

        $error = abs($this->impact_estimate - $this->actual_impact);
        $mape = ($error / abs($this->impact_estimate)) * 100;

        return max(0, 100 - $mape) / 100;
    }

    // Static Methods
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_BUDGET_OPTIMIZATION => 'Budget Optimization',
            self::TYPE_BID_ADJUSTMENT => 'Bid Adjustment',
            self::TYPE_TARGETING_REFINEMENT => 'Targeting Refinement',
            self::TYPE_CREATIVE_REFRESH => 'Creative Refresh',
            self::TYPE_SCHEDULE_OPTIMIZATION => 'Schedule Optimization',
            self::TYPE_CAMPAIGN_PAUSE => 'Campaign Pause',
            self::TYPE_CAMPAIGN_EXPANSION => 'Campaign Expansion',
            self::TYPE_AUDIENCE_EXPANSION => 'Audience Expansion',
            self::TYPE_PERFORMANCE_ALERT => 'Performance Alert',
        ];
    }

    public static function getPriorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPLIED => 'Applied',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_IN_REVIEW => 'In Review',
        ];
    }

    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_PERFORMANCE => 'Performance',
            self::CATEGORY_BUDGET => 'Budget',
            self::CATEGORY_TARGETING => 'Targeting',
            self::CATEGORY_CREATIVE => 'Creative',
            self::CATEGORY_AUTOMATION => 'Automation',
        ];
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'type' => 'required|in:' . implode(',', array_keys(self::getTypeOptions())),
            'priority' => 'required|in:' . implode(',', array_keys(self::getPriorityOptions())),
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'reasoning' => 'nullable|string',
            'impact_estimate' => 'nullable|numeric',
            'confidence' => 'required|numeric|min:0|max:1',
            'category' => 'required|in:' . implode(',', array_keys(self::getCategoryOptions())),
            'target_entity_type' => 'nullable|string|max:255',
            'target_entity_id' => 'nullable|uuid',
            'suggested_actions' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'status' => 'sometimes|in:' . implode(',', array_keys(self::getStatusOptions())),
            'result' => 'sometimes|array',
            'actual_impact' => 'sometimes|numeric',
            'feedback_rating' => 'sometimes|integer|min:1|max:5',
            'feedback_notes' => 'sometimes|string|max:1000',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'org_id.required' => 'Organization is required',
            'type.required' => 'Recommendation type is required',
            'priority.required' => 'Priority is required',
            'title.required' => 'Title is required',
            'description.required' => 'Description is required',
            'confidence.required' => 'Confidence score is required',
            'confidence.min' => 'Confidence must be between 0 and 1',
            'confidence.max' => 'Confidence must be between 0 and 1',
        ];
    }
}
