<?php

namespace App\Models\Website;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feature Model
 *
 * Platform features for marketing showcase.
 */
class Feature extends BaseModel
{
    protected $table = 'cmis_website.features';

    protected $fillable = [
        'category_id',
        'slug',
        'title_en',
        'title_ar',
        'description_en',
        'description_ar',
        'details_en',
        'details_ar',
        'icon',
        'image_url',
        'video_url',
        'is_active',
        'is_featured',
        'is_new',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
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
     * Get localized description
     */
    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"description_{$locale}"} ?? $this->description_en;
    }

    /**
     * Get localized details
     */
    public function getDetailsAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"details_{$locale}"} ?? $this->details_en;
    }

    /**
     * Get feature category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FeatureCategory::class, 'category_id');
    }

    /**
     * Scope to active features
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to featured features
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to new features
     */
    public function scopeNew(Builder $query): Builder
    {
        return $query->where('is_new', true);
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title_en');
    }

    /**
     * Get route key name for URL binding
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
