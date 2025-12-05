<?php

namespace App\Models\Platform;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * AssetRelationship Model
 *
 * Stores parent-child and other relationships between platform assets.
 * This is a SHARED table with PUBLIC RLS - relationships are structural data
 * shared across organizations.
 *
 * Example relationships:
 * - page_owns_instagram: A Facebook Page owns an Instagram Business Account
 * - business_manages_page: A Business Manager manages a Facebook Page
 * - business_owns_ad_account: A Business owns an Ad Account
 * - ad_account_has_pixel: An Ad Account has a Pixel attached
 * - channel_belongs_to_account: A YouTube Channel belongs to a Google Account
 *
 * @property string $relationship_id UUID primary key
 * @property string $parent_asset_id FK to platform_assets (the "owner" side)
 * @property string $child_asset_id FK to platform_assets (the "owned" side)
 * @property string $relationship_type Type of relationship
 * @property array $relationship_data Additional relationship metadata (JSONB)
 * @property \Carbon\Carbon $discovered_at When this relationship was discovered
 * @property \Carbon\Carbon $last_verified_at Last time relationship was verified
 * @property \Carbon\Carbon|null $deleted_at Soft delete timestamp
 */
class AssetRelationship extends BaseModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'cmis.asset_relationships';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'relationship_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'relationship_id',
        'parent_asset_id',
        'child_asset_id',
        'relationship_type',
        'relationship_data',
        'discovered_at',
        'last_verified_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'relationship_data' => 'array',
        'discovered_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Common relationship types
     */
    public const RELATIONSHIP_TYPES = [
        // Meta relationships
        'page_owns_instagram' => 'Facebook Page owns Instagram Business Account',
        'page_owns_threads' => 'Facebook Page owns Threads Account',
        'business_manages_page' => 'Business Manager manages Facebook Page',
        'business_owns_ad_account' => 'Business owns Ad Account',
        'business_owns_pixel' => 'Business owns Pixel',
        'ad_account_has_pixel' => 'Ad Account has Pixel attached',
        'ad_account_has_catalog' => 'Ad Account has Product Catalog',
        'page_has_whatsapp' => 'Facebook Page linked to WhatsApp',

        // Google relationships
        'account_owns_channel' => 'Google Account owns YouTube Channel',
        'account_owns_ad_account' => 'Google Account owns Ads Account',
        'ad_account_has_campaign' => 'Ad Account has Campaign',
        'mcc_manages_account' => 'MCC (Manager Account) manages Ad Account',

        // TikTok relationships
        'business_owns_advertiser' => 'Business Center owns Advertiser Account',
        'advertiser_has_pixel' => 'Advertiser has Pixel',

        // LinkedIn relationships
        'org_owns_ad_account' => 'Organization owns Ad Account',

        // Generic relationships
        'parent_child' => 'Generic parent-child relationship',
        'linked' => 'Assets are linked together',
    ];

    // ===== Relationships =====

    /**
     * Get the parent asset in this relationship
     */
    public function parentAsset(): BelongsTo
    {
        return $this->belongsTo(PlatformAsset::class, 'parent_asset_id', 'asset_id');
    }

    /**
     * Get the child asset in this relationship
     */
    public function childAsset(): BelongsTo
    {
        return $this->belongsTo(PlatformAsset::class, 'child_asset_id', 'asset_id');
    }

    // ===== Scopes =====

    /**
     * Scope to filter by relationship type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('relationship_type', $type);
    }

    /**
     * Scope to get relationships for a parent asset
     */
    public function scopeForParent(Builder $query, string $parentAssetId): Builder
    {
        return $query->where('parent_asset_id', $parentAssetId);
    }

    /**
     * Scope to get relationships for a child asset
     */
    public function scopeForChild(Builder $query, string $childAssetId): Builder
    {
        return $query->where('child_asset_id', $childAssetId);
    }

    /**
     * Scope to get relationships involving an asset (as parent or child)
     */
    public function scopeInvolving(Builder $query, string $assetId): Builder
    {
        return $query->where(function ($q) use ($assetId) {
            $q->where('parent_asset_id', $assetId)
              ->orWhere('child_asset_id', $assetId);
        });
    }

    /**
     * Scope to get stale relationships (not verified within threshold)
     */
    public function scopeStale(Builder $query, int $hoursThreshold = 24): Builder
    {
        return $query->where('last_verified_at', '<', now()->subHours($hoursThreshold));
    }

    /**
     * Scope with eager-loaded assets
     */
    public function scopeWithAssets(Builder $query): Builder
    {
        return $query->with(['parentAsset', 'childAsset']);
    }

    // ===== Helpers =====

    /**
     * Find or create a relationship
     */
    public static function findOrCreateRelationship(
        string $parentAssetId,
        string $childAssetId,
        string $relationshipType,
        array $data = []
    ): self {
        return static::firstOrCreate(
            [
                'parent_asset_id' => $parentAssetId,
                'child_asset_id' => $childAssetId,
                'relationship_type' => $relationshipType,
            ],
            array_merge([
                'relationship_data' => [],
                'discovered_at' => now(),
                'last_verified_at' => now(),
            ], $data)
        );
    }

    /**
     * Update verification timestamp
     */
    public function markVerified(): bool
    {
        return $this->update([
            'last_verified_at' => now(),
        ]);
    }

    /**
     * Update relationship data
     */
    public function updateData(array $data): bool
    {
        return $this->update([
            'relationship_data' => array_merge($this->relationship_data ?? [], $data),
            'last_verified_at' => now(),
        ]);
    }

    /**
     * Get a value from relationship_data
     */
    public function getData(string $key, mixed $default = null): mixed
    {
        return data_get($this->relationship_data, $key, $default);
    }

    /**
     * Check if relationship is fresh (verified within threshold)
     */
    public function isFresh(int $hoursThreshold = 24): bool
    {
        if (!$this->last_verified_at) {
            return false;
        }
        return $this->last_verified_at->isAfter(now()->subHours($hoursThreshold));
    }

    /**
     * Get human-readable relationship type name
     */
    public function getRelationshipDisplayName(): string
    {
        return self::RELATIONSHIP_TYPES[$this->relationship_type]
            ?? str_replace('_', ' ', ucfirst($this->relationship_type));
    }

    /**
     * Check if this is a Meta relationship
     */
    public function isMetaRelationship(): bool
    {
        return str_starts_with($this->relationship_type, 'page_')
            || str_starts_with($this->relationship_type, 'business_')
            || str_starts_with($this->relationship_type, 'ad_account_');
    }

    /**
     * Get all child assets for a parent by type
     */
    public static function getChildrenByType(string $parentAssetId, string $relationshipType): \Illuminate\Support\Collection
    {
        return static::where('parent_asset_id', $parentAssetId)
            ->where('relationship_type', $relationshipType)
            ->with('childAsset')
            ->get()
            ->pluck('childAsset')
            ->filter();
    }

    /**
     * Get all parent assets for a child by type
     */
    public static function getParentsByType(string $childAssetId, string $relationshipType): \Illuminate\Support\Collection
    {
        return static::where('child_asset_id', $childAssetId)
            ->where('relationship_type', $relationshipType)
            ->with('parentAsset')
            ->get()
            ->pluck('parentAsset')
            ->filter();
    }
}
