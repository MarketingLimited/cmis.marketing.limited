<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Illuminate\Support\Facades\Cache;

/**
 * Global Setting Model
 *
 * Stores platform-wide settings that can be configured by super admins.
 */
class GlobalSetting extends BaseModel
{
    protected $table = 'cmis.global_settings';

    protected $fillable = [
        'key',
        'group',
        'value',
        'type',
        'label',
        'description',
        'is_public',
        'is_encrypted',
        'validation_rules',
        'options',
        'sort_order',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_encrypted' => 'boolean',
        'validation_rules' => 'array',
        'options' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * Cache key for all settings
     */
    const CACHE_KEY = 'global_settings';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, $default = null)
    {
        $settings = static::getAllCached();
        $setting = $settings->firstWhere('key', $key);

        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, $value): bool
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        $setting->value = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;
        $setting->save();

        static::clearCache();

        return true;
    }

    /**
     * Get all settings, cached.
     */
    public static function getAllCached()
    {
        return Cache::remember(static::CACHE_KEY, static::CACHE_TTL, function () {
            return static::orderBy('group')->orderBy('sort_order')->get();
        });
    }

    /**
     * Get settings by group.
     */
    public static function getByGroup(string $group)
    {
        return static::getAllCached()->where('group', $group);
    }

    /**
     * Get all public settings.
     */
    public static function getPublic()
    {
        return static::getAllCached()->where('is_public', true);
    }

    /**
     * Get all groups.
     */
    public static function getGroups(): array
    {
        return static::getAllCached()
            ->pluck('group')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Clear the settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    /**
     * Cast value to appropriate type.
     */
    protected static function castValue($value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            case 'text':
            case 'string':
            default:
                return $value;
        }
    }

    /**
     * Get the typed value attribute.
     */
    public function getTypedValueAttribute()
    {
        return static::castValue($this->value, $this->type);
    }

    /**
     * Scope to filter by group.
     */
    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope to filter public settings.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
