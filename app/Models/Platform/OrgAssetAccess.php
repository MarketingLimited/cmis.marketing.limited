<?php

namespace App\Models\Platform;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * OrgAssetAccess Model
 *
 * Tracks which organizations have access to which platform assets via which connections.
 * This table has RLS enabled with org_id filtering for multi-tenancy isolation.
 *
 * Key Concept: The same PlatformAsset (e.g., a Facebook Page) can be accessed by
 * multiple organizations through different connections. This table tracks each
 * org's access separately.
 *
 * @property string $access_id UUID primary key
 * @property string $org_id Organization that has access
 * @property string $asset_id FK to platform_assets
 * @property string $connection_id FK to platform_connections (how access is granted)
 * @property array $access_types Access levels (read, write, admin, etc.)
 * @property array $permissions Platform-specific permissions (e.g., Meta tasks)
 * @property array $roles Platform-specific roles
 * @property \Carbon\Carbon $granted_at When access was first discovered/granted
 * @property string|null $granted_by_user_id User who added this access (manual selection)
 * @property \Carbon\Carbon $last_verified_at Last time access was verified via API
 * @property int $verification_count Number of times access has been verified
 * @property bool $is_active Whether access record is active
 * @property bool $is_selected Whether user explicitly selected this asset for use
 * @property \Carbon\Carbon|null $deleted_at Soft delete timestamp
 */
class OrgAssetAccess extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    /**
     * The table associated with the model.
     */
    protected $table = 'cmis.org_asset_access';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'access_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'access_id',
        'org_id',
        'asset_id',
        'connection_id',
        'access_types',
        'permissions',
        'roles',
        'granted_at',
        'granted_by_user_id',
        'last_verified_at',
        'verification_count',
        'is_active',
        'is_selected',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'access_types' => 'array',
        'permissions' => 'array',
        'roles' => 'array',
        'granted_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'verification_count' => 'integer',
        'is_active' => 'boolean',
        'is_selected' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Common access types
     */
    public const ACCESS_TYPES = [
        'read',     // Can read asset data
        'write',    // Can modify asset
        'admin',    // Full administrative access
        'publish',  // Can publish content
        'analyze',  // Can view analytics/insights
        'manage',   // Can manage settings
    ];

    // ===== Relationships =====

    /**
     * Get the platform asset this access record is for
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(PlatformAsset::class, 'asset_id', 'asset_id');
    }

    /**
     * Get the connection through which access is granted
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(PlatformConnection::class, 'connection_id', 'connection_id');
    }

    /**
     * Get the user who granted/selected this access (if manual)
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id', 'user_id');
    }

    // ===== Scopes =====

    /**
     * Scope to get active access records only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get selected assets only
     */
    public function scopeSelected(Builder $query): Builder
    {
        return $query->where('is_selected', true);
    }

    /**
     * Scope to get access via specific connection
     */
    public function scopeViaConnection(Builder $query, string $connectionId): Builder
    {
        return $query->where('connection_id', $connectionId);
    }

    /**
     * Scope to get access for specific asset
     */
    public function scopeForAsset(Builder $query, string $assetId): Builder
    {
        return $query->where('asset_id', $assetId);
    }

    /**
     * Scope to get stale access records (not verified within threshold)
     */
    public function scopeStale(Builder $query, int $hoursThreshold = 24): Builder
    {
        return $query->where('last_verified_at', '<', now()->subHours($hoursThreshold));
    }

    /**
     * Scope to filter by access type
     */
    public function scopeWithAccessType(Builder $query, string $accessType): Builder
    {
        return $query->whereJsonContains('access_types', $accessType);
    }

    /**
     * Scope with eager-loaded asset
     */
    public function scopeWithAsset(Builder $query): Builder
    {
        return $query->with('asset');
    }

    /**
     * Scope with eager-loaded connection
     */
    public function scopeWithConnection(Builder $query): Builder
    {
        return $query->with('connection');
    }

    // ===== Helpers =====

    /**
     * Find or create access record
     */
    public static function findOrCreateAccess(
        string $orgId,
        string $assetId,
        string $connectionId,
        array $data = []
    ): self {
        return static::firstOrCreate(
            [
                'org_id' => $orgId,
                'asset_id' => $assetId,
                'connection_id' => $connectionId,
            ],
            array_merge([
                'granted_at' => now(),
                'last_verified_at' => now(),
                'verification_count' => 1,
                'is_active' => true,
                'is_selected' => false,
                'access_types' => [],
                'permissions' => [],
                'roles' => [],
            ], $data)
        );
    }

    /**
     * Update access verification timestamp
     */
    public function markVerified(): bool
    {
        return $this->update([
            'last_verified_at' => now(),
            'verification_count' => $this->verification_count + 1,
            'is_active' => true,
        ]);
    }

    /**
     * Mark access as inactive (e.g., lost access)
     */
    public function markInactive(): bool
    {
        return $this->update([
            'is_active' => false,
        ]);
    }

    /**
     * Toggle selection state
     */
    public function toggleSelection(): bool
    {
        return $this->update([
            'is_selected' => !$this->is_selected,
        ]);
    }

    /**
     * Select this asset for use
     */
    public function select(?string $userId = null): bool
    {
        return $this->update([
            'is_selected' => true,
            'granted_by_user_id' => $userId ?? $this->granted_by_user_id,
        ]);
    }

    /**
     * Deselect this asset
     */
    public function deselect(): bool
    {
        return $this->update([
            'is_selected' => false,
        ]);
    }

    /**
     * Check if has specific access type
     */
    public function hasAccessType(string $type): bool
    {
        return in_array($type, $this->access_types ?? []);
    }

    /**
     * Check if has any of the specified access types
     */
    public function hasAnyAccessType(array $types): bool
    {
        return !empty(array_intersect($types, $this->access_types ?? []));
    }

    /**
     * Add access type
     */
    public function addAccessType(string $type): bool
    {
        $types = $this->access_types ?? [];
        if (!in_array($type, $types)) {
            $types[] = $type;
            return $this->update(['access_types' => $types]);
        }
        return true;
    }

    /**
     * Remove access type
     */
    public function removeAccessType(string $type): bool
    {
        $types = array_filter($this->access_types ?? [], fn($t) => $t !== $type);
        return $this->update(['access_types' => array_values($types)]);
    }

    /**
     * Update permissions from API response
     */
    public function updatePermissions(array $permissions): bool
    {
        return $this->update([
            'permissions' => $permissions,
            'last_verified_at' => now(),
        ]);
    }

    /**
     * Get a specific permission value
     */
    public function getPermission(string $key, mixed $default = null): mixed
    {
        return data_get($this->permissions, $key, $default);
    }

    /**
     * Check if access is fresh (verified within threshold)
     */
    public function isFresh(int $hoursThreshold = 24): bool
    {
        if (!$this->last_verified_at) {
            return false;
        }
        return $this->last_verified_at->isAfter(now()->subHours($hoursThreshold));
    }
}
