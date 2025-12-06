<?php

namespace App\Models\Website;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Page Section Model
 *
 * Modular sections within CMS pages.
 */
class PageSection extends BaseModel
{
    protected $table = 'cmis_website.page_sections';

    protected $fillable = [
        'page_id',
        'type',
        'title_en',
        'title_ar',
        'subtitle_en',
        'subtitle_ar',
        'content_en',
        'content_ar',
        'settings',
        'background_color',
        'background_image_url',
        'text_color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Available section types
     */
    public const TYPES = [
        'hero',
        'text',
        'features',
        'cta',
        'testimonials',
        'faq',
        'contact',
        'gallery',
        'video',
        'team',
        'partners',
        'pricing',
        'stats',
    ];

    /**
     * Get localized title
     */
    public function getTitleAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"title_{$locale}"} ?? $this->title_en;
    }

    /**
     * Get localized subtitle
     */
    public function getSubtitleAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"subtitle_{$locale}"} ?? $this->subtitle_en;
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
     * Get parent page
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    /**
     * Get a setting value
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Scope to active sections
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope by section type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
