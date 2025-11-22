<?php

namespace App\Models\Optimization;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\Campaign\Campaign;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AudienceOverlap extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.audience_overlaps';
    protected $primaryKey = 'overlap_id';

    protected $fillable = [
        'overlap_id',
        'org_id',
        'campaign_a_id',
        'campaign_b_id',
        'entity_a_type',
        'entity_a_id',
        'entity_b_type',
        'entity_b_id',
        'overlap_percentage',
        'overlap_size',
        'audience_a_size',
        'audience_b_size',
        'severity',
        'impact_score',
        'wasted_spend_estimate',
        'frequency_inflation',
        'recommendations',
        'status',
        'detected_at',
        'resolved_at',
        'resolution_action',
    ];

    protected $casts = [
        'overlap_percentage' => 'float',
        'overlap_size' => 'integer',
        'audience_a_size' => 'integer',
        'audience_b_size' => 'integer',
        'impact_score' => 'float',
        'wasted_spend_estimate' => 'decimal:2',
        'frequency_inflation' => 'float',
        'recommendations' => 'array',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    

    public function campaignA(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_a_id', 'campaign_id');

    public function campaignB(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_b_id', 'campaign_id');

    // ===== Overlap Analysis =====

    public function getSeverityLevel(): string
    {
        if ($this->overlap_percentage >= 75) {
            return 'critical';
        } elseif ($this->overlap_percentage >= 50) {
            return 'high';
        } elseif ($this->overlap_percentage >= 25) {
            return 'medium';
        return 'low';

    public function getSeverityColor(): string
    {
        return match($this->getSeverityLevel()) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'blue',
            default => 'gray'
        };

    public function getOverlapPercentageLabel(): string
    {
        return number_format($this->overlap_percentage, 2) . '%';

    public function getImpactDescription(): string
    {
        $wastedSpend = $this->wasted_spend_estimate ? '$' . number_format($this->wasted_spend_estimate, 2) : 'N/A';
        $frequency = $this->frequency_inflation ? number_format($this->frequency_inflation, 2) . 'x' : 'N/A';

        return "Wasted Spend: {$wastedSpend}, Frequency Inflation: {$frequency}";
    }

    public function calculateJaccardIndex(): float
    {
        // Jaccard Index = Intersection / Union
        if ($this->audience_a_size === 0 && $this->audience_b_size === 0) {
            return 0.0;

        $union = $this->audience_a_size + $this->audience_b_size - $this->overlap_size;

        if ($union === 0) {
            return 0.0;

        return round($this->overlap_size / $union, 4);

    public function isCritical(): bool
    {
        return $this->getSeverityLevel() === 'critical';

    public function isResolved(): bool
    {
        return $this->status === 'resolved';

    public function resolve(string $action): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_action' => $action,
        ]);

    public function markAsIgnored(): void
    {
        $this->update(['status' => 'ignored']);

    // ===== Scopes =====

    public function scopeActive($query)
    {
        return $query->where('status', 'active');

    public function scopeCritical($query)
    {
        return $query->where('overlap_percentage', '>=', 75);

    public function scopeHighImpact($query)
    {
        return $query->where('impact_score', '>=', 0.7);

    public function scopeForCampaign($query, string $campaignId)
    {
        return $query->where(function ($q) use ($campaignId) {
            $q->where('campaign_a_id', $campaignId)
              ->orWhere('campaign_b_id', $campaignId);
}
