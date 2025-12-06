<?php

namespace App\Models\Website;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FAQ Item Model
 *
 * Individual FAQ questions and answers.
 */
class FaqItem extends BaseModel
{
    protected $table = 'cmis_website.faq_items';

    protected $fillable = [
        'category_id',
        'question_en',
        'question_ar',
        'answer_en',
        'answer_ar',
        'is_active',
        'is_featured',
        'sort_order',
        'views',
        'helpful_votes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'views' => 'integer',
        'helpful_votes' => 'integer',
    ];

    /**
     * Get localized question
     */
    public function getQuestionAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"question_{$locale}"} ?? $this->question_en;
    }

    /**
     * Get localized answer
     */
    public function getAnswerAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"answer_{$locale}"} ?? $this->answer_en;
    }

    /**
     * Get FAQ category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'category_id');
    }

    /**
     * Scope to active items
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to featured items
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
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    /**
     * Increment helpful votes
     */
    public function markAsHelpful(): void
    {
        $this->increment('helpful_votes');
    }
}
