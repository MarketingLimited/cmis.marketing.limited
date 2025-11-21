<?php

namespace App\Models\Optimization;

use App\Models\Core\Org;
use App\Models\Core\User;
use App\Models\Campaign\Campaign;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OptimizationInsight extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'cmis.optimization_insights';
    protected $primaryKey = 'insight_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'insight_id',
        'org_id',
        'optimization_run_id',
        'campaign_id',
        'insight_type',
        'category',
        'priority',
        'title',
        'description',
        'impact_estimate',
        'confidence_score',
        'supporting_data',
        'recommendations',
        'automated_action',
        'action_taken',
        'status',
        'generated_at',
        'acknowledged_at',
        'acknowledged_by',
        'applied_at',
        'applied_by',
        'expires_at',
    ];

    protected $casts = [
        'impact_estimate' => 'decimal:2',
        'confidence_score' => 'float',
        'supporting_data' => 'array',
        'recommendations' => 'array',
        'automated_action' => 'array',
        'action_taken' => 'array',
        'generated_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'applied_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    public function optimizationRun(): BelongsTo
    {
        return $this->belongsTo(OptimizationRun::class, 'optimization_run_id', 'run_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by', 'user_id');
    }

    public function applier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by', 'user_id');
    }

    // ===== Insight Management =====

    public function acknowledge(string $userId): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId,
        ]);
    }

    public function apply(string $userId, array $actionTaken): void
    {
        $this->update([
            'status' => 'applied',
            'applied_at' => now(),
            'applied_by' => $userId,
            'action_taken' => $actionTaken,
        ]);
    }

    public function dismiss(): void
    {
        $this->update(['status' => 'dismissed']);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    // ===== Insight Analysis =====

    public function isActionable(): bool
    {
        return $this->status === 'pending' &&
               $this->confidence_score >= 0.7 &&
               (!$this->expires_at || now()->isBefore($this->expires_at));
    }

    public function isExpired(): bool
    {
        return $this->expires_at && now()->isAfter($this->expires_at);
    }

    public function isHighPriority(): bool
    {
        return $this->priority === 'critical' || $this->priority === 'high';
    }

    public function getPriorityLevel(): int
    {
        return match($this->priority) {
            'critical' => 1,
            'high' => 2,
            'medium' => 3,
            'low' => 4,
            default => 5
        };
    }

    public function getPriorityColor(): string
    {
        return match($this->priority) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'blue',
            default => 'gray'
        };
    }

    public function getCategoryLabel(): string
    {
        return match($this->category) {
            'budget' => 'Budget Optimization',
            'targeting' => 'Audience Targeting',
            'creative' => 'Creative Performance',
            'bidding' => 'Bid Strategy',
            'timing' => 'Schedule Optimization',
            'platform' => 'Platform Performance',
            default => ucfirst($this->category)
        };
    }

    public function getInsightTypeLabel(): string
    {
        return match($this->insight_type) {
            'opportunity' => 'Opportunity',
            'risk' => 'Risk',
            'anomaly' => 'Anomaly',
            'trend' => 'Trend',
            'recommendation' => 'Recommendation',
            default => ucfirst($this->insight_type)
        };
    }

    public function getImpactEstimateLabel(): string
    {
        if (!$this->impact_estimate) {
            return 'N/A';
        }

        $sign = $this->impact_estimate > 0 ? '+' : '';
        return $sign . '$' . number_format($this->impact_estimate, 2);
    }

    public function getConfidenceLabel(): string
    {
        if (!$this->confidence_score) {
            return 'N/A';
        }

        $percentage = $this->confidence_score * 100;

        if ($percentage >= 90) {
            return 'Very High (' . number_format($percentage, 0) . '%)';
        } elseif ($percentage >= 75) {
            return 'High (' . number_format($percentage, 0) . '%)';
        } elseif ($percentage >= 60) {
            return 'Medium (' . number_format($percentage, 0) . '%)';
        }

        return 'Low (' . number_format($percentage, 0) . '%)';
    }

    public function hasAutomatedAction(): bool
    {
        return !empty($this->automated_action) && is_array($this->automated_action);
    }

    public function canAutoExecute(): bool
    {
        return $this->hasAutomatedAction() &&
               $this->isActionable() &&
               $this->confidence_score >= 0.85;
    }

    // ===== Scopes =====

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActionable($query)
    {
        return $query->where('status', 'pending')
                     ->where('confidence_score', '>=', 0.7)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['critical', 'high']);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeForInsightType($query, string $type)
    {
        return $query->where('insight_type', $type);
    }

    public function scopeWithAutomation($query)
    {
        return $query->whereNotNull('automated_action');
    }
}
