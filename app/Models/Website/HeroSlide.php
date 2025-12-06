<?php

namespace App\Models\Website;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hero Slide Model
 *
 * Homepage hero carousel slides.
 */
class HeroSlide extends BaseModel
{
    protected $table = 'cmis_website.hero_slides';

    protected $fillable = [
        'headline_en',
        'headline_ar',
        'subheadline_en',
        'subheadline_ar',
        'cta_text_en',
        'cta_text_ar',
        'cta_url',
        'secondary_cta_text_en',
        'secondary_cta_text_ar',
        'secondary_cta_url',
        'background_image_url',
        'background_video_url',
        'overlay_color',
        'text_color',
        'text_alignment',
        'stats',
        'is_active',
        'sort_order',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'stats' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Get localized headline
     */
    public function getHeadlineAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"headline_{$locale}"} ?? $this->headline_en;
    }

    /**
     * Get localized subheadline
     */
    public function getSubheadlineAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"subheadline_{$locale}"} ?? $this->subheadline_en;
    }

    /**
     * Get localized CTA text
     */
    public function getCtaTextAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"cta_text_{$locale}"} ?? $this->cta_text_en;
    }

    /**
     * Get localized secondary CTA text
     */
    public function getSecondaryCtaTextAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"secondary_cta_text_{$locale}"} ?? $this->secondary_cta_text_en;
    }

    /**
     * Get localized stats
     */
    public function getLocalizedStatsAttribute(): array
    {
        if (!$this->stats) {
            return [];
        }

        $locale = app()->getLocale();
        return collect($this->stats)->map(function ($stat) use ($locale) {
            return [
                'label' => $stat["label_{$locale}"] ?? $stat['label_en'] ?? '',
                'value' => $stat['value'] ?? '',
            ];
        })->toArray();
    }

    /**
     * Check if slide has video background
     */
    public function hasVideoBackground(): bool
    {
        return !empty($this->background_video_url);
    }

    /**
     * Check if slide is currently valid (within date range)
     */
    public function isCurrentlyValid(): bool
    {
        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Scope to active slides
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to currently valid slides (within date range)
     */
    public function scopeCurrentlyValid(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('start_date')
              ->orWhere('start_date', '<=', now());
        })->where(function ($q) {
            $q->whereNull('end_date')
              ->orWhere('end_date', '>=', now());
        });
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get all displayable slides
     */
    public static function getDisplayable(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->currentlyValid()
            ->ordered()
            ->get();
    }
}
