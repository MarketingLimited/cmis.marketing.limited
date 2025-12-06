<?php

namespace App\Models\Website;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Navigation Item Model
 *
 * Individual menu items with hierarchical support.
 */
class NavigationItem extends BaseModel
{
    protected $table = 'cmis_website.navigation_items';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'label_en',
        'label_ar',
        'url',
        'route_name',
        'icon',
        'target',
        'type',
        'attributes',
        'is_active',
        'is_highlighted',
        'highlight_color',
        'sort_order',
    ];

    protected $casts = [
        'attributes' => 'array',
        'is_active' => 'boolean',
        'is_highlighted' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get localized label
     */
    public function getLabelAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"label_{$locale}"} ?? $this->label_en;
    }

    /**
     * Get the full URL for this item
     */
    public function getFullUrlAttribute(): string
    {
        if ($this->route_name) {
            try {
                return route($this->route_name);
            } catch (\Exception $e) {
                return $this->url ?? '#';
            }
        }
        return $this->url ?? '#';
    }

    /**
     * Get the parent menu
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'menu_id');
    }

    /**
     * Get parent item
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavigationItem::class, 'parent_id');
    }

    /**
     * Get child items
     */
    public function children(): HasMany
    {
        return $this->hasMany(NavigationItem::class, 'parent_id');
    }

    /**
     * Get active children
     */
    public function activeChildren(): HasMany
    {
        return $this->children()->active()->ordered();
    }

    /**
     * Check if item has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->active()->exists();
    }

    /**
     * Check if this is a dropdown menu
     */
    public function getIsDropdownAttribute(): bool
    {
        return $this->type === 'dropdown' || $this->hasChildren();
    }

    /**
     * Scope to active items
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
     * Scope to root items (no parent)
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
