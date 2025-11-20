<?php

namespace App\Services\FeatureToggle;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Exceptions\FeatureDisabledException;

/**
 * Feature Flag Service - Manages feature toggles with multi-tenant support
 *
 * Supports hierarchical feature flag resolution:
 * 1. User Override (highest priority)
 * 2. Platform Override
 * 3. Organization Override
 * 4. System Default (lowest priority)
 */
class FeatureFlagService
{
    /**
     * Cache TTL in seconds (5 minutes default)
     */
    protected int $cacheTtl = 300;

    /**
     * Current organization ID from context
     */
    protected ?string $orgId = null;

    /**
     * Current user ID from context
     */
    protected ?string $userId = null;

    /**
     * Check if a feature is enabled
     *
     * @param string $featureKey Feature key (e.g., 'scheduling.meta.enabled')
     * @param string|null $scopeId Optional scope ID (org, user, platform)
     * @return bool
     */
    public function isEnabled(string $featureKey, ?string $scopeId = null): bool
    {
        try {
            // Extract context if not provided
            if ($scopeId === null) {
                $this->extractContext();
            }

            // Build cache key
            $cacheKey = $this->buildCacheKey($featureKey, $scopeId ?? $this->userId);

            // Try cache first
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return (bool) $cached;
            }

            // Resolve feature flag value through hierarchy
            $value = $this->resolveFeatureFlag($featureKey, $scopeId);

            // Cache the result
            Cache::put($cacheKey, $value, $this->cacheTtl);

            return $value;

        } catch (\Exception $e) {
            Log::error('Feature flag check failed', [
                'feature_key' => $featureKey,
                'error' => $e->getMessage(),
            ]);

            // Default to disabled on error (fail-safe)
            return false;
        }
    }

    /**
     * Resolve feature flag value through hierarchy
     *
     * Priority: User Override > Platform Override > Org Override > System Default
     *
     * @param string $featureKey
     * @param string|null $scopeId
     * @return bool
     */
    protected function resolveFeatureFlag(string $featureKey, ?string $scopeId = null): bool
    {
        // 1. Check user override (highest priority)
        if ($this->userId) {
            $userOverride = $this->checkOverride($featureKey, $this->userId, 'user');
            if ($userOverride !== null) {
                return $userOverride;
            }
        }

        // 2. Check platform override (if feature key contains platform)
        $platform = $this->extractPlatform($featureKey);
        if ($platform) {
            $platformValue = $this->checkFlag($featureKey, 'platform', $platform);
            if ($platformValue !== null) {
                return $platformValue;
            }
        }

        // 3. Check organization-level flag
        if ($this->orgId) {
            $orgValue = $this->checkFlag($featureKey, 'organization', $this->orgId);
            if ($orgValue !== null) {
                return $orgValue;
            }
        }

        // 4. Check system-level flag (default)
        $systemValue = $this->checkFlag($featureKey, 'system', null);
        if ($systemValue !== null) {
            return $systemValue;
        }

        // If no flag found, default to disabled
        return false;
    }

    /**
     * Check for user/org override
     *
     * @param string $featureKey
     * @param string $targetId
     * @param string $targetType ('user' or 'organization')
     * @return bool|null
     */
    protected function checkOverride(string $featureKey, string $targetId, string $targetType): ?bool
    {
        $result = DB::table('cmis.feature_flag_overrides as fo')
            ->join('cmis.feature_flags as ff', 'fo.feature_flag_id', '=', 'ff.id')
            ->where('ff.feature_key', $featureKey)
            ->where('fo.target_id', $targetId)
            ->where('fo.target_type', $targetType)
            ->where(function ($query) {
                // Check expiration
                $query->whereNull('fo.expires_at')
                    ->orWhere('fo.expires_at', '>', now());
            })
            ->select('fo.value')
            ->first();

        return $result ? (bool) $result->value : null;
    }

    /**
     * Check feature flag value at specific scope
     *
     * @param string $featureKey
     * @param string $scopeType ('system', 'organization', 'platform', 'user')
     * @param string|null $scopeId
     * @return bool|null
     */
    protected function checkFlag(string $featureKey, string $scopeType, ?string $scopeId): ?bool
    {
        $query = DB::table('cmis.feature_flags')
            ->where('feature_key', $featureKey)
            ->where('scope_type', $scopeType);

        if ($scopeType !== 'system') {
            $query->where('scope_id', $scopeId);
        } else {
            $query->whereNull('scope_id');
        }

        $result = $query->select('value')->first();

        return $result ? (bool) $result->value : null;
    }

    /**
     * Set/update a feature flag
     *
     * @param string $featureKey
     * @param bool $value
     * @param string $scopeType
     * @param string|null $scopeId
     * @param array $metadata
     * @return bool
     */
    public function set(
        string $featureKey,
        bool $value,
        string $scopeType = 'system',
        ?string $scopeId = null,
        array $metadata = []
    ): bool {
        try {
            // Set admin context for RLS bypass
            $this->setAdminContext();

            // Check if flag exists
            $existing = DB::table('cmis.feature_flags')
                ->where('feature_key', $featureKey)
                ->where('scope_type', $scopeType)
                ->where(function ($query) use ($scopeId, $scopeType) {
                    if ($scopeType === 'system') {
                        $query->whereNull('scope_id');
                    } else {
                        $query->where('scope_id', $scopeId);
                    }
                })
                ->first();

            if ($existing) {
                // Update existing flag
                DB::table('cmis.feature_flags')
                    ->where('id', $existing->id)
                    ->update([
                        'value' => $value,
                        'metadata' => json_encode($metadata),
                        'updated_at' => now(),
                    ]);
            } else {
                // Insert new flag
                DB::table('cmis.feature_flags')->insert([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'feature_key' => $featureKey,
                    'scope_type' => $scopeType,
                    'scope_id' => $scopeId,
                    'value' => $value,
                    'metadata' => json_encode($metadata),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Clear cache
            $this->clearCache($featureKey);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to set feature flag', [
                'feature_key' => $featureKey,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create or update a user/org override
     *
     * @param string $featureKey
     * @param string $targetId
     * @param string $targetType
     * @param bool $value
     * @param string|null $reason
     * @param \DateTimeInterface|null $expiresAt
     * @return bool
     */
    public function setOverride(
        string $featureKey,
        string $targetId,
        string $targetType,
        bool $value,
        ?string $reason = null,
        ?\DateTimeInterface $expiresAt = null
    ): bool {
        try {
            $this->setAdminContext();

            // Get feature flag ID
            $flag = DB::table('cmis.feature_flags')
                ->where('feature_key', $featureKey)
                ->first();

            if (!$flag) {
                // Create system-level flag first
                $flagId = \Illuminate\Support\Str::uuid();
                DB::table('cmis.feature_flags')->insert([
                    'id' => $flagId,
                    'feature_key' => $featureKey,
                    'scope_type' => 'system',
                    'scope_id' => null,
                    'value' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $flagId = $flag->id;
            }

            // Check if override exists
            $existing = DB::table('cmis.feature_flag_overrides')
                ->where('feature_flag_id', $flagId)
                ->where('target_id', $targetId)
                ->where('target_type', $targetType)
                ->first();

            if ($existing) {
                // Update
                DB::table('cmis.feature_flag_overrides')
                    ->where('id', $existing->id)
                    ->update([
                        'value' => $value,
                        'reason' => $reason,
                        'expires_at' => $expiresAt,
                        'updated_at' => now(),
                    ]);
            } else {
                // Insert
                DB::table('cmis.feature_flag_overrides')->insert([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'feature_flag_id' => $flagId,
                    'target_id' => $targetId,
                    'target_type' => $targetType,
                    'value' => $value,
                    'reason' => $reason,
                    'expires_at' => $expiresAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Clear cache
            $this->clearCache($featureKey);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to set feature override', [
                'feature_key' => $featureKey,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get all enabled platforms for a feature category
     *
     * @param string $featureCategory (e.g., 'scheduling', 'paid_campaigns')
     * @return array ['meta', 'tiktok', ...]
     */
    public function getEnabledPlatforms(string $featureCategory): array
    {
        $platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
        $enabled = [];

        foreach ($platforms as $platform) {
            if ($this->isEnabled("{$featureCategory}.{$platform}.enabled")) {
                $enabled[] = $platform;
            }
        }

        return $enabled;
    }

    /**
     * Get feature matrix for all platforms
     *
     * @param array $features Feature categories
     * @return array
     */
    public function getFeatureMatrix(array $features = ['scheduling', 'paid_campaigns', 'analytics']): array
    {
        $platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
        $matrix = [];

        foreach ($features as $feature) {
            $matrix[$feature] = [];
            foreach ($platforms as $platform) {
                $matrix[$feature][$platform] = $this->isEnabled("{$feature}.{$platform}.enabled");
            }
        }

        return $matrix;
    }

    /**
     * Extract platform name from feature key
     *
     * @param string $featureKey (e.g., 'scheduling.meta.enabled')
     * @return string|null
     */
    protected function extractPlatform(string $featureKey): ?string
    {
        $parts = explode('.', $featureKey);
        if (count($parts) >= 2) {
            $platform = $parts[1];
            // Validate it's a known platform
            if (in_array($platform, ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'])) {
                return $platform;
            }
        }
        return null;
    }

    /**
     * Extract context from database transaction settings
     */
    protected function extractContext(): void
    {
        try {
            $this->orgId = DB::selectOne("SELECT current_setting('app.current_org_id', true) as org_id")->org_id ?? null;
            $this->userId = DB::selectOne("SELECT current_setting('app.current_user_id', true) as user_id")->user_id ?? null;
        } catch (\Exception $e) {
            // Context not set, will use defaults
        }
    }

    /**
     * Set admin context for RLS bypass
     */
    protected function setAdminContext(): void
    {
        try {
            DB::statement("SET LOCAL app.is_admin = true");
        } catch (\Exception $e) {
            Log::warning('Failed to set admin context', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Build cache key
     */
    protected function buildCacheKey(string $featureKey, ?string $scopeId): string
    {
        return sprintf('feature_flag:%s:%s:%s',
            $featureKey,
            $this->orgId ?? 'global',
            $scopeId ?? 'default'
        );
    }

    /**
     * Clear cache for a feature key
     */
    protected function clearCache(string $featureKey): void
    {
        // Clear all variations of this feature key
        Cache::forget($this->buildCacheKey($featureKey, null));
        Cache::forget($this->buildCacheKey($featureKey, $this->userId));
        Cache::forget($this->buildCacheKey($featureKey, $this->orgId));
    }

    /**
     * Throw exception if feature is disabled
     *
     * @param string $featureKey
     * @param string|null $message
     * @throws FeatureDisabledException
     */
    public function requireEnabled(string $featureKey, ?string $message = null): void
    {
        if (!$this->isEnabled($featureKey)) {
            throw new FeatureDisabledException(
                $message ?? "Feature '{$featureKey}' is not enabled"
            );
        }
    }
}
