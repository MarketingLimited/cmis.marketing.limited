<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PermissionsCache - Permission code lookup table
 *
 * This table serves as a reference/dictionary for permission codes,
 * mapping permission_code to permission_id and category.
 * It is NOT a user-specific cache.
 *
 * Note: This model does NOT extend BaseModel because:
 * 1. It has no org_id (not org-scoped)
 * 2. It has no deleted_at (no soft deletes)
 * 3. Primary key is permission_code (string), not UUID
 */
class PermissionsCache extends Model
{
    use HasFactory;

    protected $table = 'cmis.permissions_cache';
    protected $primaryKey = 'permission_code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'permission_code',
        'permission_id',
        'category',
        'last_used',
    ];

    protected $casts = [
        'permission_id' => 'string',
        'last_used' => 'datetime',
    ];

    /**
     * Get permission by code
     */
    public static function getByCode(string $permissionCode): ?self
    {
        return static::where('permission_code', $permissionCode)->first();
    }

    /**
     * Get permission by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get recently used permissions
     */
    public function scopeRecentlyUsed($query, int $minutes = 60)
    {
        return $query->where('last_used', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope to get stale cache entries
     */
    public function scopeStale($query, int $hours = 24)
    {
        return $query->where('last_used', '<', now()->subHours($hours));
    }

    /**
     * Update last used timestamp
     */
    public function touch($attribute = null)
    {
        if ($attribute === null) {
            return $this->update(['last_used' => now()]);
        }

        return parent::touch($attribute);
    }

    /**
     * Update or create permission cache entry
     */
    public static function updateOrCreateEntry(string $permissionCode, string $permissionId, string $category): self
    {
        return static::updateOrCreate(
            ['permission_code' => $permissionCode],
            [
                'permission_id' => $permissionId,
                'category' => $category,
                'last_used' => now(),
            ]
        );
    }

    /**
     * Clean up stale entries
     */
    public static function cleanupStale(int $hours = 24): int
    {
        return static::where('last_used', '<', now()->subHours($hours))->delete();
    }
}
