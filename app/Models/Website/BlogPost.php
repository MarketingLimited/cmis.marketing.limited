<?php

namespace App\Models\Website;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Blog Post Model
 *
 * Blog articles for the marketing website.
 */
class BlogPost extends BaseModel
{
    protected $table = 'cmis_website.blog_posts';

    protected $fillable = [
        'category_id',
        'author_id',
        'slug',
        'title_en',
        'title_ar',
        'excerpt_en',
        'excerpt_ar',
        'content_en',
        'content_ar',
        'featured_image_url',
        'tags',
        'reading_time_minutes',
        'views',
        'is_published',
        'is_featured',
        'allow_comments',
        'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'allow_comments' => 'boolean',
        'reading_time_minutes' => 'integer',
        'views' => 'integer',
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
    public function getContentAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"content_{$locale}"} ?? $this->content_en;
    }

    /**
     * Get blog category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    /**
     * Get SEO metadata
     */
    public function seoMetadata(): MorphOne
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }

    /**
     * Scope to published posts
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    /**
     * Scope to featured posts
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope ordered by publish date (newest first)
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('published_at', 'desc');
    }

    /**
     * Scope to posts by tag
     */
    public function scopeWithTag(Builder $query, string $tag): Builder
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    /**
     * Get formatted reading time
     */
    public function getReadingTimeAttribute(): string
    {
        $minutes = $this->reading_time_minutes;
        if ($minutes <= 1) {
            return __('marketing.blog.reading_time_1_min');
        }
        return __('marketing.blog.reading_time_mins', ['count' => $minutes]);
    }

    /**
     * Get route key name for URL binding
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
