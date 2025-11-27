<?php

namespace App\Models\Social;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BrandKnowledgeConfig Model
 *
 * Stores configuration for automatic brand knowledge base building per profile group.
 *
 * @property string $config_id
 * @property string $org_id
 * @property string $profile_group_id
 * @property boolean $auto_build_enabled
 * @property integer $auto_build_min_posts
 * @property integer $auto_build_min_days
 * @property boolean $auto_analyze_new_posts
 * @property array|null $enabled_dimensions
 * @property integer $total_posts_imported
 * @property integer $total_dimensions_extracted
 */
class BrandKnowledgeConfig extends BaseModel
{
    use HasOrganization, SoftDeletes;

    protected $table = 'cmis.brand_knowledge_config';
    protected $primaryKey = 'config_id';

    protected $fillable = [
        'org_id', 'profile_group_id',
        'auto_build_enabled', 'auto_build_min_posts', 'auto_build_min_days',
        'auto_analyze_new_posts',
        'enabled_dimensions', 'analysis_platforms', 'min_success_percentile',
        'analyze_visual_content', 'analyze_video_content',
        'total_posts_imported', 'total_posts_analyzed', 'total_success_posts',
        'total_dimensions_extracted',
        'first_import_at', 'last_import_at', 'last_analysis_at',
        'kb_built_at', 'kb_updated_at',
        'notify_on_kb_ready', 'notify_on_analysis_complete', 'notify_on_import_milestone',
        'notification_recipients',
        'max_concurrent_analysis', 'daily_analysis_limit',
        'monthly_ai_budget', 'current_month_spend',
        'metadata',
    ];

    protected $casts = [
        'auto_build_enabled' => 'boolean',
        'auto_build_min_posts' => 'integer',
        'auto_build_min_days' => 'integer',
        'auto_analyze_new_posts' => 'boolean',
        'enabled_dimensions' => 'array',
        'analysis_platforms' => 'array',
        'min_success_percentile' => 'integer',
        'analyze_visual_content' => 'boolean',
        'analyze_video_content' => 'boolean',
        'total_posts_imported' => 'integer',
        'total_posts_analyzed' => 'integer',
        'total_success_posts' => 'integer',
        'total_dimensions_extracted' => 'integer',
        'first_import_at' => 'datetime',
        'last_import_at' => 'datetime',
        'last_analysis_at' => 'datetime',
        'kb_built_at' => 'datetime',
        'kb_updated_at' => 'datetime',
        'notify_on_kb_ready' => 'boolean',
        'notify_on_analysis_complete' => 'boolean',
        'notify_on_import_milestone' => 'boolean',
        'notification_recipients' => 'array',
        'max_concurrent_analysis' => 'integer',
        'daily_analysis_limit' => 'integer',
        'monthly_ai_budget' => 'decimal:2',
        'current_month_spend' => 'decimal:2',
        'metadata' => 'array',
    ];

    // ===== Relationships =====

    public function profileGroup(): BelongsTo
    {
        return $this->belongsTo(ProfileGroup::class, 'profile_group_id', 'group_id');
    }

    // ===== Scopes =====

    public function scopeAutoBuildEnabled($query)
    {
        return $query->where('auto_build_enabled', true);
    }

    public function scopeReadyForAutoBuild($query)
    {
        return $query->where('auto_build_enabled', true)
            ->whereRaw('total_posts_imported >= auto_build_min_posts')
            ->whereRaw('first_import_at <= NOW() - INTERVAL \'1 day\' * auto_build_min_days');
    }

    // ===== Helper Methods =====

    /**
     * Check if auto-build is enabled and criteria are met
     */
    public function canAutoBuild(): bool
    {
        if (!$this->auto_build_enabled) {
            return false;
        }

        if ($this->total_posts_imported < $this->auto_build_min_posts) {
            return false;
        }

        if (!$this->first_import_at) {
            return false;
        }

        $daysSinceFirstImport = now()->diffInDays($this->first_import_at);
        return $daysSinceFirstImport >= $this->auto_build_min_days;
    }

    /**
     * Increment import counters
     */
    public function incrementImportStats(int $postsCount, int $successPostsCount = 0): void
    {
        $this->increment('total_posts_imported', $postsCount);

        if ($successPostsCount > 0) {
            $this->increment('total_success_posts', $successPostsCount);
        }

        $this->update([
            'last_import_at' => now(),
            'first_import_at' => $this->first_import_at ?? now(),
        ]);
    }

    /**
     * Increment analysis counters
     */
    public function incrementAnalysisStats(int $postsCount, int $dimensionsCount): void
    {
        $this->increment('total_posts_analyzed', $postsCount);
        $this->increment('total_dimensions_extracted', $dimensionsCount);

        $this->update([
            'last_analysis_at' => now(),
        ]);
    }

    /**
     * Mark knowledge base as built
     */
    public function markKnowledgeBaseBuilt(): void
    {
        $this->update([
            'kb_built_at' => now(),
            'kb_updated_at' => now(),
        ]);
    }

    /**
     * Mark knowledge base as updated
     */
    public function markKnowledgeBaseUpdated(): void
    {
        $this->update([
            'kb_updated_at' => now(),
        ]);
    }

    /**
     * Check if within AI budget
     */
    public function isWithinBudget(float $additionalCost = 0): bool
    {
        if (!$this->monthly_ai_budget) {
            return true; // No budget limit set
        }

        return ($this->current_month_spend + $additionalCost) <= $this->monthly_ai_budget;
    }

    /**
     * Add to current month spend
     */
    public function addToSpend(float $amount): void
    {
        $this->increment('current_month_spend', $amount);
    }

    /**
     * Reset monthly spend (call at start of new month)
     */
    public function resetMonthlySpend(): void
    {
        $this->update(['current_month_spend' => 0]);
    }

    /**
     * Check if daily analysis limit reached
     */
    public function hasReachedDailyLimit(): bool
    {
        if (!$this->daily_analysis_limit) {
            return false; // No limit set
        }

        if (!$this->last_analysis_at || !$this->last_analysis_at->isToday()) {
            return false; // Last analysis was not today
        }

        // Would need to track daily count separately
        // For now, return false
        return false;
    }

    /**
     * Get enabled dimension types as array
     */
    public function getEnabledDimensionTypes(): array
    {
        return $this->enabled_dimensions ?? [];
    }

    /**
     * Check if a specific dimension type is enabled
     */
    public function isDimensionEnabled(string $dimensionType): bool
    {
        $enabled = $this->getEnabledDimensionTypes();
        return empty($enabled) || in_array($dimensionType, $enabled);
    }

    /**
     * Get platforms to analyze
     */
    public function getAnalysisPlatforms(): array
    {
        return $this->analysis_platforms ?? [];
    }

    /**
     * Check if a platform should be analyzed
     */
    public function shouldAnalyzePlatform(string $platform): bool
    {
        $platforms = $this->getAnalysisPlatforms();
        return empty($platforms) || in_array($platform, $platforms);
    }
}
