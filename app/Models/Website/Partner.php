<?php

namespace App\Models\Website;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Partner Model
 *
 * Partners, clients, and integrations for trust section.
 */
class Partner extends BaseModel
{
    protected $table = 'cmis_website.partners';

    protected $fillable = [
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'logo_url',
        'website_url',
        'type',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Partner types
     */
    public const TYPE_PARTNER = 'partner';
    public const TYPE_CLIENT = 'client';
    public const TYPE_INTEGRATION = 'integration';

    /**
     * Get localized name
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"name_{$locale}"} ?? $this->name_en;
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
     * Scope to active partners
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to featured partners
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope by type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to partners only
     */
    public function scopePartners(Builder $query): Builder
    {
        return $query->ofType(self::TYPE_PARTNER);
    }

    /**
     * Scope to clients only
     */
    public function scopeClients(Builder $query): Builder
    {
        return $query->ofType(self::TYPE_CLIENT);
    }

    /**
     * Scope to integrations only
     */
    public function scopeIntegrations(Builder $query): Builder
    {
        return $query->ofType(self::TYPE_INTEGRATION);
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name_en');
    }
}
