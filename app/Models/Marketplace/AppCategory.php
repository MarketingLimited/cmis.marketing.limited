<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * App Category Model
 *
 * Represents a category of marketplace apps (e.g., Marketing, Analytics, AI).
 * Categories are system-level entities visible to all organizations.
 *
 * Note: This model does NOT extend BaseModel because:
 * 1. It's a system-level table, not org-scoped
 * 2. It doesn't have soft deletes (system data)
 * 3. It doesn't need RLS filtering
 *
 * @property string $category_id
 * @property string $slug
 * @property string $name_key
 * @property string $description_key
 * @property string $icon
 * @property int $sort_order
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 */
class AppCategory extends Model
{
    use HasUuids;

    /**
     * The connection name for the model.
     */
    protected $connection = 'pgsql';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key ID.
     */
    protected $keyType = 'string';
    /**
     * The table associated with the model.
     */
    protected $table = 'cmis.app_categories';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'category_id';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'slug',
        'name_key',
        'description_key',
        'icon',
        'sort_order',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the translated name.
     */
    public function getNameAttribute(): string
    {
        return __($this->name_key);
    }

    /**
     * Get the translated description.
     */
    public function getDescriptionAttribute(): string
    {
        return __($this->description_key);
    }

    /**
     * Get the apps belonging to this category.
     */
    public function apps(): HasMany
    {
        return $this->hasMany(MarketplaceApp::class, 'category', 'slug')
            ->orderBy('sort_order');
    }

    /**
     * Scope to only include active categories.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
