<?php

namespace App\Models\Social;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Integration;
use App\Models\Campaign;
use App\Models\Analytics\Metric;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SocialPost Model
 * 
 * Unified social post model consolidating 5 previous tables.
 * Handles draft → scheduled → published workflow.
 */
class SocialPost extends BaseModel
{
    use HasOrganization, SoftDeletes;

    protected $table = 'cmis.social_posts';

    protected $fillable = [
        'org_id', 'integration_id', 'platform', 'account_id', 'account_username',
        'post_external_id', 'permalink', 'content', 'media', 'post_type',
        'targeting', 'options', 'status', 'scheduled_at', 'published_at',
        'failed_at', 'error_message', 'requires_approval', 'created_by',
        'approved_by', 'approved_at', 'approval_notes', 'campaign_id',
        'tags', 'impressions_cache', 'engagement_cache', 'metrics_updated_at',
        'metadata', 'retry_count',
        // Historical Content & Brand Knowledge fields
        'profile_group_id', 'source', 'is_historical', 'is_schedulable', 'is_editable',
        'is_analyzed', 'is_in_knowledge_base', 'analysis_status', 'analyzed_at',
        'success_score', 'success_label', 'success_hypothesis',
        'platform_metrics', 'extracted_entities', 'extracted_tones',
        'extracted_hooks', 'extracted_ctas', 'extracted_objectives',
        'extracted_emotions',
    ];

    protected $casts = [
        'media' => 'array',
        'targeting' => 'array',
        'options' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'failed_at' => 'datetime',
        'approved_at' => 'datetime',
        'metrics_updated_at' => 'datetime',
        'requires_approval' => 'boolean',
        'impressions_cache' => 'integer',
        'engagement_cache' => 'integer',
        'retry_count' => 'integer',
        // Historical Content & Brand Knowledge casts
        'is_historical' => 'boolean',
        'is_schedulable' => 'boolean',
        'is_editable' => 'boolean',
        'is_analyzed' => 'boolean',
        'is_in_knowledge_base' => 'boolean',
        'analyzed_at' => 'datetime',
        'success_score' => 'decimal:4',
        'platform_metrics' => 'array',
        'extracted_entities' => 'array',
        'extracted_tones' => 'array',
        'extracted_hooks' => 'array',
        'extracted_ctas' => 'array',
        'extracted_objectives' => 'array',
        'extracted_emotions' => 'array',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PUBLISHING = 'publishing';
    const STATUS_PUBLISHED = 'published';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Source constants
    const SOURCE_USER_CREATED = 'user_created';
    const SOURCE_IMPORTED = 'imported';
    const SOURCE_AI_GENERATED = 'ai_generated';

    // Success label constants
    const SUCCESS_HIGH_PERFORMER = 'high_performer';
    const SUCCESS_AVERAGE = 'average';
    const SUCCESS_LOW_PERFORMER = 'low_performer';

    // Relationships
    public function integration() { return $this->belongsTo(Integration::class, 'integration_id'); }
    public function campaign() { return $this->belongsTo(Campaign::class); }
    public function metrics() { return $this->morphMany(Metric::class, 'entity'); }
    public function history() { return $this->hasMany(PostHistory::class, 'post_id'); }

    // Historical Content relationships
    public function profileGroup() { return $this->belongsTo(ProfileGroup::class, 'profile_group_id', 'group_id'); }
    public function mediaAssets() { return $this->hasMany(MediaAsset::class, 'post_id'); }
    public function brandKnowledgeDimensions() { return $this->hasMany(BrandKnowledgeDimension::class, 'post_id'); }

    // Scopes
    public function scopePublished($q) { return $q->where('status', self::STATUS_PUBLISHED); }
    public function scopeScheduled($q) { return $q->where('status', self::STATUS_SCHEDULED); }
    public function scopeDraft($q) { return $q->where('status', self::STATUS_DRAFT); }
    public function scopePlatform($q, $platform) { return $q->where('platform', $platform); }

    // Historical Content scopes
    public function scopeHistorical($q) { return $q->where('is_historical', true); }
    public function scopeUserCreated($q) { return $q->where('source', self::SOURCE_USER_CREATED); }
    public function scopeImported($q) { return $q->where('source', self::SOURCE_IMPORTED); }
    public function scopeAnalyzed($q) { return $q->where('is_analyzed', true); }
    public function scopePendingAnalysis($q) { return $q->where('is_analyzed', false)->where('is_historical', true); }
    public function scopeInKnowledgeBase($q) { return $q->where('is_in_knowledge_base', true); }
    public function scopeSuccessPosts($q, float $minScore = 0.7) { return $q->where('success_score', '>=', $minScore); }
    public function scopeHighPerformers($q) { return $q->where('success_label', self::SUCCESS_HIGH_PERFORMER); }
    public function scopeForProfileGroup($q, string $profileGroupId) { return $q->where('profile_group_id', $profileGroupId); }

    // Helper Methods

    /**
     * Check if post is historical (imported from platform)
     */
    public function isHistorical(): bool
    {
        return $this->is_historical ?? false;
    }

    /**
     * Check if post has been analyzed
     */
    public function hasBeenAnalyzed(): bool
    {
        return $this->is_analyzed ?? false;
    }

    /**
     * Check if post is in knowledge base
     */
    public function isInKnowledgeBase(): bool
    {
        return $this->is_in_knowledge_base ?? false;
    }

    /**
     * Check if post is a success post (high performer)
     */
    public function isSuccessPost(float $threshold = 0.7): bool
    {
        return $this->success_score && $this->success_score >= $threshold;
    }

    /**
     * Mark post as analyzed
     */
    public function markAsAnalyzed(array $analysisData = []): void
    {
        $this->update(array_merge([
            'is_analyzed' => true,
            'analysis_status' => 'completed',
            'analyzed_at' => now(),
        ], $analysisData));
    }

    /**
     * Add to knowledge base
     */
    public function addToKnowledgeBase(): void
    {
        $this->update(['is_in_knowledge_base' => true]);
    }

    /**
     * Remove from knowledge base
     */
    public function removeFromKnowledgeBase(): void
    {
        $this->update(['is_in_knowledge_base' => false]);
    }

    /**
     * Calculate engagement rate from platform metrics
     */
    public function getEngagementRate(): ?float
    {
        if (!$this->platform_metrics) {
            return null;
        }

        $metrics = $this->platform_metrics;
        $reach = $metrics['reach'] ?? $metrics['impressions'] ?? 0;

        if ($reach === 0) {
            return null;
        }

        $engagements = ($metrics['likes'] ?? 0)
            + ($metrics['comments'] ?? 0)
            + ($metrics['shares'] ?? 0);

        return round(($engagements / $reach) * 100, 2);
    }

    /**
     * Get all extracted marketing dimensions
     */
    public function getAllExtractedDimensions(): array
    {
        return [
            'objectives' => $this->extracted_objectives ?? [],
            'tones' => $this->extracted_tones ?? [],
            'hooks' => $this->extracted_hooks ?? [],
            'ctas' => $this->extracted_ctas ?? [],
            'emotions' => $this->extracted_emotions ?? [],
            'entities' => $this->extracted_entities ?? [],
        ];
    }
}
