<?php

namespace App\Models\Website;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Case Study Model
 *
 * Success stories and case studies.
 */
class CaseStudy extends BaseModel
{
    protected $table = 'cmis_website.case_studies';

    protected $fillable = [
        'slug',
        'title_en',
        'title_ar',
        'excerpt_en',
        'excerpt_ar',
        'client_name_en',
        'client_name_ar',
        'client_logo_url',
        'industry_en',
        'industry_ar',
        'challenge_en',
        'challenge_ar',
        'solution_en',
        'solution_ar',
        'results_en',
        'results_ar',
        'metrics',
        'featured_image_url',
        'gallery_images',
        'is_published',
        'is_featured',
        'published_at',
        'sort_order',
    ];

    protected $casts = [
        'metrics' => 'array',
        'gallery_images' => 'array',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    /**
     * Get localized title
     */
    public function getTitleAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"title_{$locale}"} ?? $this->title_en;
    }

    /**
     * Get localized excerpt
     */
    public function getExcerptAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"excerpt_{$locale}"} ?? $this->excerpt_en;
    }

    /**
     * Get localized client name
     */
    public function getClientNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"client_name_{$locale}"} ?? $this->client_name_en;
    }

    /**
     * Get localized industry
     */
    public function getIndustryAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"industry_{$locale}"} ?? $this->industry_en;
    }

    /**
     * Get localized challenge
     */
    public function getChallengeAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"challenge_{$locale}"} ?? $this->challenge_en;
    }

    /**
     * Get localized solution
     */
    public function getSolutionAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"solution_{$locale}"} ?? $this->solution_en;
    }

    /**
     * Get localized results
     */
    public function getResultsAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"results_{$locale}"} ?? $this->results_en;
    }

    /**
     * Get SEO metadata
     */
    public function seoMetadata(): MorphOne
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }

    /**
     * Get formatted metrics for display
     */
    public function getFormattedMetricsAttribute(): array
    {
        if (!$this->metrics) {
            return [];
        }

        $locale = app()->getLocale();
        return collect($this->metrics)->map(function ($metric) use ($locale) {
            return [
                'label' => $metric["label_{$locale}"] ?? $metric['label_en'] ?? '',
                'value' => $metric['value'] ?? '',
                'prefix' => $metric['prefix'] ?? '',
                'suffix' => $metric['suffix'] ?? '',
            ];
        })->toArray();
    }

    /**
     * Scope to published case studies
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    /**
     * Scope to featured case studies
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope ordered by publish date (newest first)
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('published_at', 'desc');
    }

    /**
     * Get route key name for URL binding
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get unique industries
     */
    public static function getIndustries(): array
    {
        return static::published()
            ->distinct()
            ->pluck('industry_en')
            ->toArray();
    }
}
