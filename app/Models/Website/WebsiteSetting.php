<?php

namespace App\Models\Website;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Website Settings Model
 *
 * Key-value store for global website configuration.
 */
class WebsiteSetting extends BaseModel
{
    protected $table = 'cmis_website.website_settings';

    protected $fillable = [
        'key',
        'group',
        'value_en',
        'value_ar',
        'type',
        'description_en',
        'description_ar',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get localized value
     */
    public function getValueAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"value_{$locale}"} ?? $this->value_en;
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
     * Scope to active settings
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to settings by group
     */
    public function scopeByGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('key');
    }

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->active()->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key
     */
    public static function setValue(string $key, string $valueEn, ?string $valueAr = null, string $group = 'general'): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value_en' => $valueEn,
                'value_ar' => $valueAr ?? $valueEn,
                'group' => $group,
            ]
        );
    }

    /**
     * Get all settings as array
     */
    public static function getAllAsArray(): array
    {
        return static::active()
            ->ordered()
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }
}
