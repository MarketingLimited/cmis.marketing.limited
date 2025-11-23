<?php

namespace App\Models\Optimization;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\Campaign\Campaign;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
class BudgetAllocation extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.budget_allocations';
    protected $primaryKey = 'allocation_id';

    protected $fillable = [
        'allocation_id',
        'org_id',
        'optimization_run_id',
        'campaign_id',
        'entity_type',
        'entity_id',
        'current_budget',
        'recommended_budget',
        'budget_change',
        'budget_change_percentage',
        'allocation_score',
        'expected_conversions',
        'expected_revenue',
        'expected_roas',
        'expected_cpa',
        'confidence_level',
        'justification',
        'constraints_applied',
        'performance_data',
        'status',
        'applied_at',
    ];

    protected $casts = [
        'current_budget' => 'decimal:2',
        'recommended_budget' => 'decimal:2',
        'budget_change' => 'decimal:2',
        'budget_change_percentage' => 'float',
        'allocation_score' => 'float',
        'expected_conversions' => 'float',
        'expected_revenue' => 'decimal:2',
        'expected_roas' => 'float',
        'expected_cpa' => 'decimal:2',
        'confidence_level' => 'float',
        'constraints_applied' => 'array',
        'performance_data' => 'array',
        'applied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    

    public function optimizationRun(): BelongsTo
    {
        return $this->belongsTo(OptimizationRun::class, 'optimization_run_id', 'run_id');

        }
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');


        }
    public function isIncrease(): bool
    {
        return $this->budget_change > 0;

        }
    public function isDecrease(): bool
    {
        return $this->budget_change < 0;

        }
    public function getChangeDirection(): string
    {
        if ($this->budget_change > 0) {
            return 'increase';
        }
        if ($this->budget_change < 0) {
            return 'decrease';
        }
        return 'no_change';
    }
    public function getBudgetChangeLabel(): string
    {
        $sign = $this->budget_change > 0 ? '+' : '';
        return $sign . '$' . number_format(abs($this->budget_change), 2);

        }
    public function getChangePercentageLabel(): string
    {
        $sign = $this->budget_change_percentage > 0 ? '+' : '';
        return $sign . number_format($this->budget_change_percentage, 2) . '%';

        }
    public function getExpectedROIIncrease(): ?float
    {
        if (!$this->expected_revenue || !$this->recommended_budget) {
            return null;
        }

        $currentROI = $this->current_budget > 0 ? $this->expected_revenue / $this->current_budget : 0;
        $expectedROI = $this->recommended_budget > 0 ? $this->expected_revenue / $this->recommended_budget : 0;

        return $expectedROI - $currentROI;
    }
    public function markAsApplied(): void
    {
        $this->update([
            'status' => 'applied',
            'applied_at' => now(),
        ]);
    }

    public function markAsRejected(): void
    {
        $this->update(['status' => 'rejected']);
    }

    // ===== Scopes =====

    public function scopePending($query): Builder
    {
        return $query->where('status', 'pending');

        }
    public function scopeApplied($query): Builder
    {
        return $query->where('status', 'applied');

        }
    public function scopeIncreases($query): Builder
    {
        return $query->where('budget_change', '>', 0);

        }
    public function scopeDecreases($query): Builder
    {
        return $query->where('budget_change', '<', 0);

        }
    public function scopeHighConfidence($query): Builder
    {
        return $query->where('confidence_level', '>=', 0.8);
    }
}
