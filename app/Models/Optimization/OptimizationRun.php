<?php

namespace App\Models\Optimization;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\Core\User;
use App\Models\Campaign\Campaign;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class OptimizationRun extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.optimization_runs';
    protected $primaryKey = 'run_id';

    protected $fillable = [
        'run_id',
        'org_id',
        'model_id',
        'optimization_type',
        'target_entity_type',
        'target_entity_id',
        'objective',
        'constraints',
        'input_data',
        'recommendations',
        'status',
        'started_at',
        'completed_at',
        'duration_seconds',
        'iterations',
        'convergence_achieved',
        'convergence_value',
        'improvement_percentage',
        'baseline_value',
        'optimized_value',
        'confidence_score',
        'error_message',
        'execution_metadata',
        'executed_by',
        'applied_at',
        'applied_by',
    ];

    protected $casts = [
        'constraints' => 'array',
        'input_data' => 'array',
        'recommendations' => 'array',
        'execution_metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'applied_at' => 'datetime',
        'duration_seconds' => 'integer',
        'iterations' => 'integer',
        'convergence_achieved' => 'boolean',
        'convergence_value' => 'float',
        'improvement_percentage' => 'float',
        'baseline_value' => 'float',
        'optimized_value' => 'float',
        'confidence_score' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    

    public function model(): BelongsTo
    {
        return $this->belongsTo(OptimizationModel::class, 'model_id', 'model_id');

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by', 'user_id');

    public function applier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by', 'user_id');

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'target_entity_id', 'campaign_id');

    public function budgetAllocations(): HasMany
    {
        return $this->hasMany(BudgetAllocation::class, 'optimization_run_id', 'run_id');

    public function insights(): HasMany
    {
        return $this->hasMany(OptimizationInsight::class, 'optimization_run_id', 'run_id');

    // ===== Execution Status Management =====

    public function markAsRunning(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

    public function markAsCompleted(array $results): void
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'duration_seconds' => $duration,
            'recommendations' => $results['recommendations'] ?? [],
            'convergence_achieved' => $results['convergence_achieved'] ?? false,
            'convergence_value' => $results['convergence_value'] ?? null,
            'improvement_percentage' => $results['improvement_percentage'] ?? null,
            'baseline_value' => $results['baseline_value'] ?? null,
            'optimized_value' => $results['optimized_value'] ?? null,
            'confidence_score' => $results['confidence_score'] ?? null,
            'iterations' => $results['iterations'] ?? null,
        ]);

    public function markAsFailed(string $errorMessage): void
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;

        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'duration_seconds' => $duration,
            'error_message' => $errorMessage,
        ]);

    public function markAsApplied(string $userId): void
    {
        $this->update([
            'status' => 'applied',
            'applied_at' => now(),
            'applied_by' => $userId,
        ]);

    // ===== Performance Helpers =====

    public function isSuccessful(): bool
    {
        return in_array($this->status, ['completed', 'applied']);

    public function hasImprovement(): bool
    {
        return $this->improvement_percentage > 0;

    public function getImprovementLabel(): string
    {
        if (!$this->improvement_percentage) {
            return 'N/A';

        $sign = $this->improvement_percentage > 0 ? '+' : '';
        return $sign . number_format($this->improvement_percentage, 2) . '%';

    public function getOptimizationTypeLabel(): string
    {
        return match($this->optimization_type) {
            'budget_allocation' => 'Budget Allocation',
            'bid_optimization' => 'Bid Optimization',
            'audience_targeting' => 'Audience Targeting',
            'creative_optimization' => 'Creative Optimization',
            default => ucfirst(str_replace('_', ' ', $this->optimization_type))
        };

    public function getObjectiveLabel(): string
    {
        return match($this->objective) {
            'maximize_conversions' => 'Maximize Conversions',
            'maximize_roas' => 'Maximize ROAS',
            'minimize_cpa' => 'Minimize CPA',
            'maximize_revenue' => 'Maximize Revenue',
            'maximize_reach' => 'Maximize Reach',
            default => ucfirst(str_replace('_', ' ', $this->objective))
        };

    // ===== Scopes =====

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');

    public function scopeApplied($query)
    {
        return $query->where('status', 'applied');

    public function scopeForOptimizationType($query, string $type)
    {
        return $query->where('optimization_type', $type);

    public function scopeWithImprovement($query)
    {
        return $query->where('improvement_percentage', '>', 0);
}
