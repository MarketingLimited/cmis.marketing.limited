<?php

namespace App\Models\Website;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Navigation Menu Model
 *
 * Container for navigation items (header, footer, mobile menus).
 */
class NavigationMenu extends BaseModel
{
    protected $table = 'cmis_website.navigation_menus';

    protected $fillable = [
        'name',
        'location',
        'description_en',
        'description_ar',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get localized description
     */
    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"description_{$locale}"} ?? $this->description_en;
    }

    /**
     * Get navigation items for this menu
     */
    public function items(): HasMany
    {
        return $this->hasMany(NavigationItem::class, 'menu_id');
    }

    /**
     * Get root (top-level) navigation items
     */
    public function rootItems(): HasMany
    {
        return $this->items()->whereNull('parent_id')->active()->ordered();
    }

    /**
     * Get all active items with hierarchy
     */
    public function getItemsTreeAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->items()
            ->whereNull('parent_id')
            ->active()
            ->ordered()
            ->with(['children' => fn($q) => $q->active()->ordered()])
            ->get();
    }

    /**
     * Scope to active menus
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to menus by location
     */
    public function scopeByLocation(Builder $query, string $location): Builder
    {
        return $query->where('location', $location);
    }

    /**
     * Get menu by name
     */
    public static function getByName(string $name): ?self
    {
        return static::where('name', $name)->active()->first();
    }

    /**
     * Get header menu
     */
    public static function header(): ?self
    {
        return static::getByName('header');
    }

    /**
     * Get footer menu
     */
    public static function footer(): ?self
    {
        return static::getByName('footer');
    }

    /**
     * Get mobile menu
     */
    public static function mobile(): ?self
    {
        return static::getByName('mobile');
    }
}
