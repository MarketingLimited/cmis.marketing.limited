<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Marketplace App Model
 *
 * Represents an application/feature that can be enabled or disabled
 * per organization in the Apps Marketplace.
 *
 * Note: This model does NOT extend BaseModel because:
 * 1. It's a system-level table, not org-scoped
 * 2. It doesn't have soft deletes (system data)
 * 3. It doesn't need RLS filtering
 *
 * @property string $app_id
 * @property string $slug
 * @property string $name_key
 * @property string $description_key
 * @property string $icon
 * @property string $category
 * @property string|null $route_name
 * @property string|null $route_prefix
 * @property bool $is_core
 * @property bool $is_premium
 * @property int $sort_order
 * @property array $dependencies
 * @property array $required_permissions
 * @property array $metadata
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MarketplaceApp extends Model
{
    use HasUuids;

    /**
     * The connection name for the model.
     */
    protected $connection = 'pgsql';

    /**
     * The table associated with the model.
     */
    protected $table = 'cmis.marketplace_apps';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'app_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'slug',
        'name_key',
        'description_key',
        'icon',
        'category',
        'route_name',
        'route_prefix',
        'is_core',
        'is_premium',
        'sort_order',
        'dependencies',
        'required_permissions',
        'metadata',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_core' => 'boolean',
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'dependencies' => 'array',
        'required_permissions' => 'array',
        'metadata' => 'array',
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
     * Get the category relationship.
     */
    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(AppCategory::class, 'category', 'slug');
    }

    /**
     * Get all organization app records for this app.
     */
    public function organizationApps(): HasMany
    {
        return $this->hasMany(OrganizationApp::class, 'app_id', 'app_id');
    }

    /**
     * Scope to only include active apps.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only include core apps.
     */
    public function scopeCore(Builder $query): Builder
    {
        return $query->where('is_core', true);
    }

    /**
     * Scope to only include optional (non-core) apps.
     */
    public function scopeOptional(Builder $query): Builder
    {
        return $query->where('is_core', false);
    }

    /**
     * Scope to only include premium apps.
     */
    public function scopePremium(Builder $query): Builder
    {
        return $query->where('is_premium', true);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeInCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get sub-routes from metadata.
     */
    public function getSubRoutes(): array
    {
        return $this->metadata['sub_routes'] ?? [];
    }

    /**
     * Check if this app has dependencies.
     */
    public function hasDependencies(): bool
    {
        return !empty($this->dependencies);
    }

    /**
     * Get all dependency slugs (including nested).
     */
    public function getAllDependencies(): array
    {
        $allDeps = [];
        $toProcess = $this->dependencies;

        while (!empty($toProcess)) {
            $slug = array_shift($toProcess);
            if (!in_array($slug, $allDeps)) {
                $allDeps[] = $slug;
                $dep = static::where('slug', $slug)->first();
                if ($dep && !empty($dep->dependencies)) {
                    $toProcess = array_merge($toProcess, $dep->dependencies);
                }
            }
        }

        return $allDeps;
    }

    /**
     * Find app by slug.
     */
    public static function findBySlug(string $slug): ?static
    {
        return static::where('slug', $slug)->first();
    }
}
