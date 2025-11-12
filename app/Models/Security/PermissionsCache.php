<?php

namespace App\Models\Security;

use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionsCache extends Model
{
    use HasFactory;

    protected $table = 'cmis.permissions_cache';
    protected $connection = 'pgsql';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'org_id',
        'permission_code',
        'has_permission',
        'last_used',
        'cache_metadata',
    ];

    protected $casts = [
        'user_id' => 'string',
        'org_id' => 'string',
        'has_permission' => 'boolean',
        'last_used' => 'datetime',
        'cache_metadata' => 'array',
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Scope to get permissions for a specific user
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get permissions for a specific org
     */
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Scope to get granted permissions
     */
    public function scopeGranted($query)
    {
        return $query->where('has_permission', true);
    }

    /**
     * Scope to get denied permissions
     */
    public function scopeDenied($query)
    {
        return $query->where('has_permission', false);
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
    public function touch(): void
    {
        $this->update(['last_used' => now()]);
    }

    /**
     * Get or create cache entry
     */
    public static function getOrCreate(string $userId, string $orgId, string $permissionCode, bool $hasPermission): self
    {
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'org_id' => $orgId,
                'permission_code' => $permissionCode,
            ],
            [
                'has_permission' => $hasPermission,
                'last_used' => now(),
            ]
        );
    }

    /**
     * Clear cache for user in org
     */
    public static function clearForUser(string $userId, ?string $orgId = null): int
    {
        $query = static::where('user_id', $userId);

        if ($orgId) {
            $query->where('org_id', $orgId);
        }

        return $query->delete();
    }

    /**
     * Clear cache for all users in org
     */
    public static function clearForOrg(string $orgId): int
    {
        return static::where('org_id', $orgId)->delete();
    }

    /**
     * Clean up stale entries
     */
    public static function cleanupStale(int $hours = 24): int
    {
        return static::where('last_used', '<', now()->subHours($hours))->delete();
    }
}
