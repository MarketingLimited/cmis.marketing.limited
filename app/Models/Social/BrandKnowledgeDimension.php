<?php

namespace App\Models\Social;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BrandKnowledgeDimension Model
 *
 * Stores extracted marketing DNA and brand knowledge from historical social content.
 * Represents a single dimension (marketing objective, tone, hook, visual style, etc.)
 * detected in a post or media asset.
 *
 * @property string $dimension_id
 * @property string $org_id
 * @property string $profile_group_id
 * @property string|null $post_id
 * @property string|null $media_asset_id
 * @property string $dimension_category
 * @property string $dimension_type
 * @property string $dimension_value
 * @property float $confidence_score
 * @property boolean $is_core_dna
 * @property integer $frequency_count
 */
class BrandKnowledgeDimension extends BaseModel
{
    use HasOrganization, SoftDeletes;

    protected $table = 'cmis.brand_knowledge_dimensions';
    protected $primaryKey = 'dimension_id';

    protected $fillable = [
        'org_id', 'profile_group_id', 'post_id', 'media_asset_id',
        'dimension_category', 'dimension_type', 'dimension_value', 'dimension_details',
        'confidence_score', 'is_core_dna', 'frequency_count',
        'first_seen_at', 'last_seen_at',
        'avg_success_score', 'success_post_count', 'total_post_count',
        'co_occurring_dimensions', 'performance_context',
        'platform', 'season', 'year', 'month',
        'status', 'is_validated', 'validated_by', 'validated_at',
        'metadata', 'notes',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:4',
        'is_core_dna' => 'boolean',
        'frequency_count' => 'integer',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'avg_success_score' => 'decimal:4',
        'success_post_count' => 'integer',
        'total_post_count' => 'integer',
        'co_occurring_dimensions' => 'array',
        'performance_context' => 'array',
        'year' => 'integer',
        'month' => 'integer',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Dimension Categories
    const CATEGORY_STRATEGY = 'strategy';
    const CATEGORY_MESSAGING = 'messaging';
    const CATEGORY_CREATIVE = 'creative';
    const CATEGORY_VISUAL = 'visual';
    const CATEGORY_PERFORMANCE = 'performance';
    const CATEGORY_AUDIENCE = 'audience';
    const CATEGORY_FUNNEL = 'funnel';
    const CATEGORY_CONTENT = 'content';
    const CATEGORY_FORMAT = 'format';

    // Common Dimension Types (from spec)
    const TYPE_MARKETING_OBJECTIVE = 'marketing_objectives';
    const TYPE_EMOTIONAL_TRIGGER = 'emotional_triggers';
    const TYPE_HOOK = 'hooks';
    const TYPE_TONE = 'tones';
    const TYPE_CTA = 'cta';
    const TYPE_COLOR_PALETTE = 'color_palette';
    const TYPE_TYPOGRAPHY = 'typography';
    const TYPE_ART_DIRECTION = 'art_direction';
    const TYPE_MOOD = 'mood';

    // ===== Relationships =====

    public function profileGroup(): BelongsTo
    {
        return $this->belongsTo(ProfileGroup::class, 'profile_group_id', 'group_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'post_id', 'id');
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id', 'asset_id');
    }

    // ===== Scopes =====

    public function scopeForProfileGroup($query, string $profileGroupId)
    {
        return $query->where('profile_group_id', $profileGroupId);
    }

    public function scopeCoreDNA($query)
    {
        return $query->where('is_core_dna', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('dimension_category', $category);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('dimension_type', $type);
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeHighConfidence($query, float $minConfidence = 0.7)
    {
        return $query->where('confidence_score', '>=', $minConfidence);
    }

    public function scopeValidated($query)
    {
        return $query->where('is_validated', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFrequent($query, int $minFrequency = 5)
    {
        return $query->where('frequency_count', '>=', $minFrequency);
    }

    public function scopeSuccessful($query, float $minSuccessScore = 0.7)
    {
        return $query->where('avg_success_score', '>=', $minSuccessScore);
    }

    // ===== Helper Methods =====

    /**
     * Check if this dimension is part of core brand DNA
     */
    public function isCoreBrandDNA(): bool
    {
        return $this->is_core_dna;
    }

    /**
     * Check if this dimension has high confidence
     */
    public function hasHighConfidence(float $threshold = 0.7): bool
    {
        return $this->confidence_score >= $threshold;
    }

    /**
     * Check if this dimension correlates with success
     */
    public function correlatesWithSuccess(float $threshold = 0.6): bool
    {
        return $this->avg_success_score && $this->avg_success_score >= $threshold;
    }

    /**
     * Increment frequency count
     */
    public function incrementFrequency(): void
    {
        $this->increment('frequency_count');
        $this->update([
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Update success correlation
     */
    public function updateSuccessCorrelation(float $successScore): void
    {
        $this->increment('total_post_count');

        if ($successScore >= 0.7) { // Threshold for "success post"
            $this->increment('success_post_count');
        }

        // Recalculate average
        $avgSuccess = ($this->avg_success_score * ($this->total_post_count - 1) + $successScore) / $this->total_post_count;

        $this->update([
            'avg_success_score' => $avgSuccess,
        ]);
    }

    /**
     * Mark as core DNA
     */
    public function markAsCoreDNA(): void
    {
        $this->update(['is_core_dna' => true]);
    }

    /**
     * Validate this dimension
     */
    public function validate(string $userId, ?string $notes = null): void
    {
        $this->update([
            'is_validated' => true,
            'validated_by' => $userId,
            'validated_at' => now(),
            'notes' => $notes ?? $this->notes,
        ]);
    }

    /**
     * Get co-occurring dimensions
     */
    public function getCoOccurringDimensions(): array
    {
        return $this->co_occurring_dimensions ?? [];
    }

    /**
     * Add co-occurring dimension
     */
    public function addCoOccurringDimension(string $dimensionType, string $dimensionValue, float $rate): void
    {
        $coOccurring = $this->getCoOccurringDimensions();

        $coOccurring[] = [
            'dimension_type' => $dimensionType,
            'dimension_value' => $dimensionValue,
            'co_occurrence_rate' => $rate,
        ];

        $this->update(['co_occurring_dimensions' => $coOccurring]);
    }

    /**
     * Get performance context
     */
    public function getPerformanceContext(): array
    {
        return $this->performance_context ?? [];
    }

    /**
     * Set performance context
     */
    public function setPerformanceContext(array $context): void
    {
        $this->update(['performance_context' => $context]);
    }
}
