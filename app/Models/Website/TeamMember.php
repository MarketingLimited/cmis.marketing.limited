<?php

namespace App\Models\Website;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Team Member Model
 *
 * Team members for the About page.
 */
class TeamMember extends BaseModel
{
    protected $table = 'cmis_website.team_members';

    protected $fillable = [
        'name_en',
        'name_ar',
        'role_en',
        'role_ar',
        'bio_en',
        'bio_ar',
        'image_url',
        'email',
        'phone',
        'social_links',
        'department',
        'is_active',
        'is_featured',
        'show_contact_info',
        'sort_order',
    ];

    protected $casts = [
        'social_links' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'show_contact_info' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get localized name
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"name_{$locale}"} ?? $this->name_en;
    }

    /**
     * Get localized role
     */
    public function getRoleAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"role_{$locale}"} ?? $this->role_en;
    }

    /**
     * Get localized bio
     */
    public function getBioAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"bio_{$locale}"} ?? $this->bio_en;
    }

    /**
     * Get social link by platform
     */
    public function getSocialLink(string $platform): ?string
    {
        return data_get($this->social_links, $platform);
    }

    /**
     * Get LinkedIn URL
     */
    public function getLinkedInAttribute(): ?string
    {
        return $this->getSocialLink('linkedin');
    }

    /**
     * Get Twitter URL
     */
    public function getTwitterAttribute(): ?string
    {
        return $this->getSocialLink('twitter');
    }

    /**
     * Scope to active members
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to featured members
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope by department
     */
    public function scopeByDepartment(Builder $query, string $department): Builder
    {
        return $query->where('department', $department);
    }

    /**
     * Scope ordered by sort_order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name_en');
    }

    /**
     * Get unique departments
     */
    public static function getDepartments(): array
    {
        return static::active()
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->toArray();
    }
}
