<?php

namespace App\Models\Website;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Page Model
 *
 * CMS pages for the marketing website.
 */
class Page extends BaseModel
{
    protected $table = 'cmis_website.pages';

    protected $fillable = [
        'slug',
        'title_en',
        'title_ar',
        'excerpt_en',
        'excerpt_ar',
        'content_en',
        'content_ar',
        'template',
        'featured_image_url',
        'is_published',
        'is_featured',
        'show_in_navigation',
        'published_at',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'show_in_navigation' => 'boolean',
        'sort_order' => 'integer',
        'published_at' => 'datetime',
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
     * Get localized content
     */
    public function getContentAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"content_{$locale}"} ?? $this->content_en;
    }

    /**
     * Get page sections
     */
    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class, 'page_id');
    }

    /**
     * Get active sections ordered
     */
    public function activeSections(): HasMany
    {
        return $this->sections()->active()->ordered();
    }

    /**
     * Get SEO metadata
     */
    public function seoMetadata(): MorphOne
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }

    /**
     * Scope to published pages
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to featured pages
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to pages shown in navigation
     */
    public function scopeInNavigation(Builder $query): Builder
    {
        return $query->where('show_in_navigation', true);
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get route key name for URL binding
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Find page by slug or fail
     */
    public static function findBySlugOrFail(string $slug): self
    {
        return static::where('slug', $slug)->published()->firstOrFail();
    }
}
