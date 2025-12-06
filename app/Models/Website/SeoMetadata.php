<?php

namespace App\Models\Website;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * SEO Metadata Model
 *
 * Polymorphic SEO data for pages, posts, and case studies.
 */
class SeoMetadata extends BaseModel
{
    protected $table = 'cmis_website.seo_metadata';

    protected $fillable = [
        'seoable_id',
        'seoable_type',
        'meta_title_en',
        'meta_title_ar',
        'meta_description_en',
        'meta_description_ar',
        'meta_keywords_en',
        'meta_keywords_ar',
        'og_title_en',
        'og_title_ar',
        'og_description_en',
        'og_description_ar',
        'og_image_url',
        'twitter_card',
        'canonical_url',
        'robots',
        'structured_data',
    ];

    protected $casts = [
        'structured_data' => 'array',
    ];

    /**
     * Get the parent model (Page, BlogPost, CaseStudy)
     */
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get localized meta title
     */
    public function getMetaTitleAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"meta_title_{$locale}"} ?? $this->meta_title_en;
    }

    /**
     * Get localized meta description
     */
    public function getMetaDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"meta_description_{$locale}"} ?? $this->meta_description_en;
    }

    /**
     * Get localized meta keywords
     */
    public function getMetaKeywordsAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"meta_keywords_{$locale}"} ?? $this->meta_keywords_en;
    }

    /**
     * Get localized OG title
     */
    public function getOgTitleAttribute(): ?string
    {
        $locale = app()->getLocale();
        $ogTitle = $this->{"og_title_{$locale}"} ?? $this->og_title_en;
        return $ogTitle ?? $this->meta_title;
    }

    /**
     * Get localized OG description
     */
    public function getOgDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        $ogDesc = $this->{"og_description_{$locale}"} ?? $this->og_description_en;
        return $ogDesc ?? $this->meta_description;
    }

    /**
     * Get robots meta content
     */
    public function getRobotsMetaAttribute(): string
    {
        return $this->robots ?? 'index, follow';
    }

    /**
     * Check if page should be indexed
     */
    public function shouldIndex(): bool
    {
        return str_contains($this->robots ?? 'index', 'index');
    }

    /**
     * Check if page should be followed
     */
    public function shouldFollow(): bool
    {
        return str_contains($this->robots ?? 'follow', 'follow');
    }

    /**
     * Get structured data as JSON-LD string
     */
    public function getJsonLdAttribute(): ?string
    {
        if (!$this->structured_data) {
            return null;
        }

        return '<script type="application/ld+json">' .
               json_encode($this->structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) .
               '</script>';
    }

    /**
     * Generate meta tags HTML
     */
    public function toMetaTags(): string
    {
        $tags = [];

        if ($this->meta_title) {
            $tags[] = '<title>' . e($this->meta_title) . '</title>';
        }

        if ($this->meta_description) {
            $tags[] = '<meta name="description" content="' . e($this->meta_description) . '">';
        }

        if ($this->meta_keywords) {
            $tags[] = '<meta name="keywords" content="' . e($this->meta_keywords) . '">';
        }

        $tags[] = '<meta name="robots" content="' . e($this->robots_meta) . '">';

        // Open Graph
        if ($this->og_title) {
            $tags[] = '<meta property="og:title" content="' . e($this->og_title) . '">';
        }

        if ($this->og_description) {
            $tags[] = '<meta property="og:description" content="' . e($this->og_description) . '">';
        }

        if ($this->og_image_url) {
            $tags[] = '<meta property="og:image" content="' . e($this->og_image_url) . '">';
        }

        // Twitter
        if ($this->twitter_card) {
            $tags[] = '<meta name="twitter:card" content="' . e($this->twitter_card) . '">';
        }

        // Canonical
        if ($this->canonical_url) {
            $tags[] = '<link rel="canonical" href="' . e($this->canonical_url) . '">';
        }

        // Structured data
        if ($this->json_ld) {
            $tags[] = $this->json_ld;
        }

        return implode("\n", $tags);
    }
}
