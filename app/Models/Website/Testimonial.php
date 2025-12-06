<?php

namespace App\Models\Website;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Testimonial Model
 *
 * Customer testimonials and reviews.
 */
class Testimonial extends BaseModel
{
    protected $table = 'cmis_website.testimonials';

    protected $fillable = [
        'author_name_en',
        'author_name_ar',
        'author_role_en',
        'author_role_ar',
        'company_name_en',
        'company_name_ar',
        'quote_en',
        'quote_ar',
        'author_image_url',
        'company_logo_url',
        'rating',
        'industry',
        'is_active',
        'is_featured',
        'is_video',
        'video_url',
        'sort_order',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_video' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get localized author name
     */
    public function getAuthorNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"author_name_{$locale}"} ?? $this->author_name_en;
    }

    /**
     * Get localized author role
     */
    public function getAuthorRoleAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"author_role_{$locale}"} ?? $this->author_role_en;
    }

    /**
     * Get localized company name
     */
    public function getCompanyNameAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"company_name_{$locale}"} ?? $this->company_name_en;
    }

    /**
     * Get localized quote
     */
    public function getQuoteAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"quote_{$locale}"} ?? $this->quote_en;
    }

    /**
     * Get author attribution line
     */
    public function getAttributionAttribute(): string
    {
        $parts = [$this->author_name];

        if ($this->author_role) {
            $parts[] = $this->author_role;
        }

        if ($this->company_name) {
            $parts[] = $this->company_name;
        }

        return implode(', ', $parts);
    }

    /**
     * Get star rating as array (for display)
     */
    public function getStarsAttribute(): array
    {
        $rating = min(5, max(1, $this->rating));
        return [
            'filled' => $rating,
            'empty' => 5 - $rating,
        ];
    }

    /**
     * Scope to active testimonials
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to featured testimonials
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to video testimonials
     */
    public function scopeVideo(Builder $query): Builder
    {
        return $query->where('is_video', true);
    }

    /**
     * Scope to text testimonials
     */
    public function scopeText(Builder $query): Builder
    {
        return $query->where('is_video', false);
    }

    /**
     * Scope by industry
     */
    public function scopeByIndustry(Builder $query, string $industry): Builder
    {
        return $query->where('industry', $industry);
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get unique industries
     */
    public static function getIndustries(): array
    {
        return static::active()
            ->whereNotNull('industry')
            ->distinct()
            ->pluck('industry')
            ->toArray();
    }
}
