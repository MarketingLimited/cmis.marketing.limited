<?php

namespace App\Models\Platform;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * PlatformAsset Model
 *
 * Stores canonical asset data from all platforms (Meta, Google, TikTok, etc.).
 * This is a SHARED table with PUBLIC RLS - assets are not org-specific.
 * The same Facebook Page can be accessed by multiple organizations.
 *
 * Access control is enforced via the org_asset_access table, not on this table.
 *
 * @property string $asset_id UUID primary key
 * @property string $platform Platform identifier (meta, google, tiktok, linkedin, twitter, snapchat, pinterest)
 * @property string $platform_asset_id External ID from the platform
 * @property string $asset_type Asset type (page, instagram, ad_account, pixel, etc.)
 * @property string|null $asset_name Human-readable name
 * @property array $asset_data Full asset data from platform API (JSONB)
 * @property string|null $ownership_type Ownership context (owned, client, personal, managed, unknown)
 * @property string|null $parent_asset_id FK to parent asset (e.g., Page that owns Instagram)
 * @property string|null $business_id Business/Manager ID from platform
 * @property string|null $business_name Business/Manager name
 * @property \Carbon\Carbon $first_seen_at When this asset was first discovered
 * @property \Carbon\Carbon $last_synced_at Last time asset data was refreshed
 * @property string|null $last_sync_source connection_id that last synced this asset
 * @property int $sync_count Number of times this asset has been synced
 * @property bool $is_active Whether asset is active/valid
 * @property \Carbon\Carbon|null $deleted_at Soft delete timestamp
 */
class PlatformAsset extends BaseModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'cmis.platform_assets';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'asset_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'asset_id',
        'platform',
        'platform_asset_id',
        'asset_type',
        'asset_name',
        'asset_data',
        'ownership_type',
        'parent_asset_id',
        'business_id',
        'business_name',
        'first_seen_at',
        'last_synced_at',
        'last_sync_source',
        'sync_count',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'asset_data' => 'array',
        'first_seen_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'sync_count' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Valid ownership types
     */
    public const OWNERSHIP_TYPES = [
        'owned',      // Owned by the authenticated user's business
        'client',     // Client business asset
        'personal',   // Personal/User token asset
        'managed',    // Managed via partnership/agency relationship
        'unknown',    // Ownership cannot be determined
    ];

    /**
     * Supported platforms
     */
    public const PLATFORMS = [
        'meta',
        'google',
        'tiktok',
        'linkedin',
        'twitter',
        'snapchat',
        'pinterest',
    ];

    // ===== Relationships =====

    /**
     * Get the parent asset (e.g., Page that owns this Instagram account)
     */
    public function parentAsset(): BelongsTo
    {
        return $this->belongsTo(PlatformAsset::class, 'parent_asset_id', 'asset_id');
    }

    /**
     * Get child assets (e.g., Instagram accounts owned by this Page)
     */
    public function childAssets(): HasMany
    {
        return $this->hasMany(PlatformAsset::class, 'parent_asset_id', 'asset_id');
    }

    /**
     * Get all org access records for this asset
     */
    public function orgAccess(): HasMany
    {
        return $this->hasMany(OrgAssetAccess::class, 'asset_id', 'asset_id');
    }

    /**
     * Get parent relationships (where this asset is the child)
     */
    public function parentRelationships(): HasMany
    {
        return $this->hasMany(AssetRelationship::class, 'child_asset_id', 'asset_id');
    }

    /**
     * Get child relationships (where this asset is the parent)
     */
    public function childRelationships(): HasMany
    {
        return $this->hasMany(AssetRelationship::class, 'parent_asset_id', 'asset_id');
    }

    // ===== Scopes =====

    /**
     * Scope to filter by platform
     */
    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to filter by asset type
     */
    public function scopeOfType(Builder $query, string $assetType): Builder
    {
        return $query->where('asset_type', $assetType);
    }

    /**
     * Scope to filter by platform and type
     */
    public function scopeForPlatformAndType(Builder $query, string $platform, string $assetType): Builder
    {
        return $query->where('platform', $platform)->where('asset_type', $assetType);
    }

    /**
     * Scope to get active assets only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get stale assets (not synced within threshold)
     */
    public function scopeStale(Builder $query, int $hoursThreshold = 6): Builder
    {
        return $query->where('last_synced_at', '<', now()->subHours($hoursThreshold));
    }

    /**
     * Scope to get assets by ownership type
     */
    public function scopeWithOwnership(Builder $query, string $ownershipType): Builder
    {
        return $query->where('ownership_type', $ownershipType);
    }

    /**
     * Scope to get assets by business
     */
    public function scopeForBusiness(Builder $query, string $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    // ===== Helpers =====

    /**
     * Find or create an asset by platform identifiers
     */
    public static function findOrCreateByPlatformId(
        string $platform,
        string $platformAssetId,
        string $assetType,
        array $data = []
    ): self {
        return static::firstOrCreate(
            [
                'platform' => $platform,
                'platform_asset_id' => $platformAssetId,
                'asset_type' => $assetType,
            ],
            array_merge($data, [
                'first_seen_at' => now(),
                'last_synced_at' => now(),
            ])
        );
    }

    /**
     * Update asset data and sync metadata
     */
    public function updateFromApi(array $assetData, ?string $syncSource = null): bool
    {
        return $this->update([
            'asset_data' => array_merge($this->asset_data ?? [], $assetData),
            'asset_name' => $assetData['name'] ?? $assetData['title'] ?? $this->asset_name,
            'last_synced_at' => now(),
            'last_sync_source' => $syncSource,
            'sync_count' => $this->sync_count + 1,
        ]);
    }

    /**
     * Check if asset data is fresh (within threshold)
     */
    public function isFresh(int $hoursThreshold = 6): bool
    {
        if (!$this->last_synced_at) {
            return false;
        }
        return $this->last_synced_at->isAfter(now()->subHours($hoursThreshold));
    }

    /**
     * Get a specific value from asset_data
     */
    public function getData(string $key, mixed $default = null): mixed
    {
        return data_get($this->asset_data, $key, $default);
    }

    /**
     * Get human-readable platform name
     */
    public function getPlatformDisplayName(): string
    {
        return match($this->platform) {
            'meta' => 'Meta (Facebook/Instagram)',
            'google' => 'Google',
            'tiktok' => 'TikTok',
            'linkedin' => 'LinkedIn',
            'twitter' => 'Twitter/X',
            'snapchat' => 'Snapchat',
            'pinterest' => 'Pinterest',
            default => ucfirst($this->platform),
        };
    }

    /**
     * Get human-readable asset type name
     */
    public function getAssetTypeDisplayName(): string
    {
        return match($this->asset_type) {
            'page' => 'Facebook Page',
            'instagram' => 'Instagram Account',
            'threads' => 'Threads Account',
            'ad_account' => 'Ad Account',
            'pixel' => 'Pixel',
            'catalog' => 'Product Catalog',
            'whatsapp' => 'WhatsApp Account',
            'business' => 'Business Manager',
            'custom_conversion' => 'Custom Conversion',
            'offline_event_set' => 'Offline Event Set',
            'youtube_channel' => 'YouTube Channel',
            'campaign' => 'Campaign',
            'organization' => 'Organization',
            'board' => 'Pinterest Board',
            default => str_replace('_', ' ', ucfirst($this->asset_type)),
        };
    }
}
