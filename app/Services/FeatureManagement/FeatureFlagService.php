<?php

namespace App\Services\FeatureManagement;

use App\Models\FeatureManagement\FeatureFlag;
use App\Models\FeatureManagement\FeatureFlagVariant;
use App\Models\FeatureManagement\FeatureFlagOverride;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class FeatureFlagService
{
    /**
     * Create a new feature flag
     */
    public function createFlag(array $data): FeatureFlag
    {
        $orgId = session('current_org_id');

        DB::beginTransaction();
        try {
            $flag = FeatureFlag::create(array_merge($data, [
                'org_id' => $orgId,
                'created_by' => auth()->id(),
            ]));

            // If creating an A/B test flag, create default variants
            if ($flag->type === FeatureFlag::TYPE_MULTIVARIATE && !empty($data['create_default_variants'])) {
                $this->createDefaultVariants($flag);
            }

            DB::commit();
            return $flag->fresh(['variants', 'overrides']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create default control and treatment variants
     */
    protected function createDefaultVariants(FeatureFlag $flag): void
    {
        // Control variant
        FeatureFlagVariant::create([
            'flag_id' => $flag->flag_id,
            'org_id' => $flag->org_id,
            'key' => 'control',
            'name' => 'Control',
            'description' => 'Original version (control group)',
            'value' => false,
            'weight' => 50,
            'is_control' => true,
            'is_active' => true,
        ]);

        // Treatment variant
        FeatureFlagVariant::create([
            'flag_id' => $flag->flag_id,
            'org_id' => $flag->org_id,
            'key' => 'treatment',
            'name' => 'Treatment',
            'description' => 'New version (treatment group)',
            'value' => true,
            'weight' => 50,
            'is_control' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Evaluate a feature flag for a user/org
     */
    public function evaluateFlag(FeatureFlag $flag, ?string $userId, ?string $orgId, array $context = []): bool
    {
        // Check if flag is active
        if (!$flag->isActive()) {
            return false;
        }

        // Check for overrides (highest priority)
        if ($override = $this->getActiveOverride($flag, $userId, $orgId)) {
            return $override->value;
        }

        // Check blacklist
        if ($userId && in_array($userId, $flag->blacklist_user_ids ?? [])) {
            return false;
        }

        // Check whitelist
        if ($userId && in_array($userId, $flag->whitelist_user_ids ?? [])) {
            return true;
        }

        // Evaluate targeting rules
        if (!empty($flag->targeting_rules)) {
            if (!$this->evaluateTargetingRules($flag->targeting_rules, $userId, $orgId, $context)) {
                return false;
            }
        }

        // For kill switch, return the flag's enabled state
        if ($flag->type === FeatureFlag::TYPE_KILL_SWITCH) {
            return $flag->is_enabled;
        }

        // For boolean flags, check rollout percentage
        if ($flag->type === FeatureFlag::TYPE_BOOLEAN) {
            return $this->isInRollout($flag, $userId ?? $orgId);
        }

        // For multivariate, return true if any variant is active
        return $flag->type === FeatureFlag::TYPE_MULTIVARIATE;
    }

    /**
     * Get active override for user/org
     */
    protected function getActiveOverride(FeatureFlag $flag, ?string $userId, ?string $orgId): ?FeatureFlagOverride
    {
        $overrides = $flag->overrides()
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        // Check user override first (highest priority)
        if ($userId) {
            $userOverride = $overrides->first(function ($override) use ($userId) {
                return $override->override_type === FeatureFlagOverride::TYPE_USER
                    && $override->override_id_value === $userId;
            });

            if ($userOverride) {
                return $userOverride;
            }
        }

        // Check org override
        if ($orgId) {
            $orgOverride = $overrides->first(function ($override) use ($orgId) {
                return $override->override_type === FeatureFlagOverride::TYPE_ORGANIZATION
                    && $override->override_id_value === $orgId;
            });

            if ($orgOverride) {
                return $orgOverride;
            }
        }

        // Check role override (requires auth user)
        if ($userId && auth()->check()) {
            $userRoles = auth()->user()->roles->pluck('role_id')->toArray();

            $roleOverride = $overrides->first(function ($override) use ($userRoles) {
                return $override->override_type === FeatureFlagOverride::TYPE_ROLE
                    && in_array($override->override_id_value, $userRoles);
            });

            if ($roleOverride) {
                return $roleOverride;
            }
        }

        return null;
    }

    /**
     * Evaluate targeting rules
     */
    protected function evaluateTargetingRules(array $rules, ?string $userId, ?string $orgId, array $context): bool
    {
        foreach ($rules as $rule) {
            $attribute = $rule['attribute'] ?? null;
            $operator = $rule['operator'] ?? 'equals';
            $value = $rule['value'] ?? null;

            // Get the actual value to compare
            $actualValue = match ($attribute) {
                'user_id' => $userId,
                'org_id' => $orgId,
                default => $context[$attribute] ?? null,
            };

            // Evaluate the rule
            if (!$this->evaluateRule($actualValue, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single targeting rule
     */
    protected function evaluateRule($actualValue, string $operator, $expectedValue): bool
    {
        return match ($operator) {
            'equals' => $actualValue == $expectedValue,
            'not_equals' => $actualValue != $expectedValue,
            'in' => is_array($expectedValue) && in_array($actualValue, $expectedValue),
            'not_in' => is_array($expectedValue) && !in_array($actualValue, $expectedValue),
            'contains' => is_string($actualValue) && str_contains($actualValue, $expectedValue),
            'not_contains' => is_string($actualValue) && !str_contains($actualValue, $expectedValue),
            'greater_than' => is_numeric($actualValue) && $actualValue > $expectedValue,
            'less_than' => is_numeric($actualValue) && $actualValue < $expectedValue,
            'greater_than_or_equal' => is_numeric($actualValue) && $actualValue >= $expectedValue,
            'less_than_or_equal' => is_numeric($actualValue) && $actualValue <= $expectedValue,
            default => false,
        };
    }

    /**
     * Check if identifier is in rollout percentage using consistent hashing
     */
    protected function isInRollout(FeatureFlag $flag, ?string $identifier): bool
    {
        if ($flag->rollout_percentage === null || $flag->rollout_percentage >= 100) {
            return true;
        }

        if ($flag->rollout_percentage <= 0 || !$identifier) {
            return false;
        }

        // Use consistent hashing to determine if user is in rollout
        $hash = $this->consistentHash($flag->flag_id . ':' . $identifier);
        $rolloutThreshold = $flag->rollout_percentage / 100;

        return $hash <= $rolloutThreshold;
    }

    /**
     * Get variant for multivariate flag
     */
    public function getVariant(FeatureFlag $flag, string $identifier): ?FeatureFlagVariant
    {
        if ($flag->type !== FeatureFlag::TYPE_MULTIVARIATE) {
            return null;
        }

        // Get active variants
        $variants = $flag->variants()
            ->where('is_active', true)
            ->orderBy('weight', 'desc')
            ->get();

        if ($variants->isEmpty()) {
            return null;
        }

        // Use consistent hashing to select variant
        $selectedVariant = $this->selectVariantByWeight($variants, $flag->flag_id . ':' . $identifier);

        // Record exposure
        if ($selectedVariant) {
            $selectedVariant->recordExposure();
        }

        return $selectedVariant;
    }

    /**
     * Select variant based on weights using consistent hashing
     */
    protected function selectVariantByWeight(Collection $variants, string $identifier): ?FeatureFlagVariant
    {
        $totalWeight = $variants->sum('weight');

        if ($totalWeight <= 0) {
            return $variants->first();
        }

        // Get consistent hash value between 0 and 1
        $hash = $this->consistentHash($identifier);

        // Convert hash to position in weight distribution
        $targetPosition = $hash * $totalWeight;

        $currentPosition = 0;
        foreach ($variants as $variant) {
            $currentPosition += $variant->weight;
            if ($targetPosition <= $currentPosition) {
                return $variant;
            }
        }

        return $variants->last();
    }

    /**
     * Consistent hashing function (returns value between 0 and 1)
     */
    protected function consistentHash(string $input): float
    {
        // Use CRC32 for consistent hashing
        $hash = crc32($input);

        // Normalize to 0-1 range
        return ($hash & 0xFFFFFFFF) / 0xFFFFFFFF;
    }

    /**
     * Get analytics for organization
     */
    public function getAnalytics(string $orgId): array
    {
        $cacheKey = "feature_flags:analytics:{$orgId}";

        return Cache::remember($cacheKey, 300, function () use ($orgId) {
            $flags = FeatureFlag::where('org_id', $orgId)->get();

            $totalFlags = $flags->count();
            $enabledFlags = $flags->where('is_enabled', true)->count();
            $disabledFlags = $totalFlags - $enabledFlags;

            $byType = [
                FeatureFlag::TYPE_BOOLEAN => $flags->where('type', FeatureFlag::TYPE_BOOLEAN)->count(),
                FeatureFlag::TYPE_MULTIVARIATE => $flags->where('type', FeatureFlag::TYPE_MULTIVARIATE)->count(),
                FeatureFlag::TYPE_KILL_SWITCH => $flags->where('type', FeatureFlag::TYPE_KILL_SWITCH)->count(),
            ];

            $totalEvaluations = $flags->sum('evaluation_count');

            // Get most evaluated flags
            $mostEvaluated = $flags
                ->sortByDesc('evaluation_count')
                ->take(5)
                ->map(fn($flag) => [
                    'flag_id' => $flag->flag_id,
                    'key' => $flag->key,
                    'name' => $flag->name,
                    'evaluation_count' => $flag->evaluation_count,
                ])
                ->values();

            // Get recently updated flags
            $recentlyUpdated = $flags
                ->sortByDesc('updated_at')
                ->take(5)
                ->map(fn($flag) => [
                    'flag_id' => $flag->flag_id,
                    'key' => $flag->key,
                    'name' => $flag->name,
                    'updated_at' => $flag->updated_at,
                ])
                ->values();

            return [
                'summary' => [
                    'total_flags' => $totalFlags,
                    'enabled_flags' => $enabledFlags,
                    'disabled_flags' => $disabledFlags,
                    'total_evaluations' => $totalEvaluations,
                ],
                'by_type' => $byType,
                'most_evaluated' => $mostEvaluated,
                'recently_updated' => $recentlyUpdated,
            ];
        });
    }

    /**
     * Bulk enable flags
     */
    public function bulkEnable(array $flagIds): int
    {
        $orgId = session('current_org_id');

        return FeatureFlag::where('org_id', $orgId)
            ->whereIn('flag_id', $flagIds)
            ->update([
                'is_enabled' => true,
                'updated_at' => now(),
            ]);
    }

    /**
     * Bulk disable flags
     */
    public function bulkDisable(array $flagIds): int
    {
        $orgId = session('current_org_id');

        return FeatureFlag::where('org_id', $orgId)
            ->whereIn('flag_id', $flagIds)
            ->update([
                'is_enabled' => false,
                'updated_at' => now(),
            ]);
    }

    /**
     * Clean up stale flags
     */
    public function cleanupStaleFlags(int $daysInactive = 90): int
    {
        $orgId = session('current_org_id');
        $threshold = now()->subDays($daysInactive);

        $staleFlags = FeatureFlag::where('org_id', $orgId)
            ->where('is_enabled', false)
            ->where('updated_at', '<', $threshold)
            ->whereDoesntHave('overrides', function ($query) {
                $query->active();
            })
            ->get();

        $archived = 0;
        foreach ($staleFlags as $flag) {
            $flag->archive();
            $archived++;
        }

        return $archived;
    }

    /**
     * Export flag configuration
     */
    public function exportFlag(FeatureFlag $flag): array
    {
        return [
            'key' => $flag->key,
            'name' => $flag->name,
            'description' => $flag->description,
            'type' => $flag->type,
            'is_enabled' => $flag->is_enabled,
            'rollout_percentage' => $flag->rollout_percentage,
            'targeting_rules' => $flag->targeting_rules,
            'whitelist_user_ids' => $flag->whitelist_user_ids,
            'blacklist_user_ids' => $flag->blacklist_user_ids,
            'variants' => $flag->variants->map(function ($variant) {
                return [
                    'key' => $variant->key,
                    'name' => $variant->name,
                    'description' => $variant->description,
                    'value' => $variant->value,
                    'weight' => $variant->weight,
                    'is_control' => $variant->is_control,
                    'configuration' => $variant->configuration,
                ];
            })->toArray(),
            'exported_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Import flag configuration
     */
    public function importFlag(array $config, bool $overwrite = false): FeatureFlag
    {
        $orgId = session('current_org_id');

        DB::beginTransaction();
        try {
            // Check if flag exists
            $existingFlag = FeatureFlag::where('org_id', $orgId)
                ->where('key', $config['key'])
                ->first();

            if ($existingFlag && !$overwrite) {
                throw new \Exception("Flag with key '{$config['key']}' already exists. Use overwrite=true to replace.");
            }

            if ($existingFlag && $overwrite) {
                // Delete existing variants
                $existingFlag->variants()->delete();
                $flag = $existingFlag;
            } else {
                $flag = new FeatureFlag();
                $flag->org_id = $orgId;
                $flag->created_by = auth()->id();
            }

            // Set flag properties
            $flag->key = $config['key'];
            $flag->name = $config['name'];
            $flag->description = $config['description'] ?? null;
            $flag->type = $config['type'];
            $flag->is_enabled = $config['is_enabled'] ?? false;
            $flag->rollout_percentage = $config['rollout_percentage'] ?? null;
            $flag->targeting_rules = $config['targeting_rules'] ?? null;
            $flag->whitelist_user_ids = $config['whitelist_user_ids'] ?? null;
            $flag->blacklist_user_ids = $config['blacklist_user_ids'] ?? null;
            $flag->save();

            // Import variants if present
            if (!empty($config['variants'])) {
                foreach ($config['variants'] as $variantConfig) {
                    FeatureFlagVariant::create([
                        'flag_id' => $flag->flag_id,
                        'org_id' => $orgId,
                        'key' => $variantConfig['key'],
                        'name' => $variantConfig['name'],
                        'description' => $variantConfig['description'] ?? null,
                        'value' => $variantConfig['value'],
                        'weight' => $variantConfig['weight'],
                        'is_control' => $variantConfig['is_control'] ?? false,
                        'configuration' => $variantConfig['configuration'] ?? null,
                        'is_active' => true,
                    ]);
                }
            }

            DB::commit();
            return $flag->fresh(['variants']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
