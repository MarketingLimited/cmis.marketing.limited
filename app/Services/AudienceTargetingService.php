<?php

namespace App\Services;

use App\Models\AdPlatform\AdAudience;
use App\Models\AdPlatform\AdAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service for audience targeting and management
 * Implements Sprint 4.3: Targeting & Audiences
 *
 * Features:
 * - Audience creation (saved, custom, lookalike)
 * - Targeting specification builder
 * - Audience size estimation
 * - Demographic, interest, and behavioral targeting
 * - Custom and lookalike audiences
 * - Audience insights and analytics
 */
class AudienceTargetingService
{
    /**
     * Create new audience
     *
     * @param array $data
     * @return array
     */
    public function createAudience(array $data): array
    {
        try {
            DB::beginTransaction();

            $audienceId = \Illuminate\Support\Str::uuid()->toString();

            // Validate ad account
            $adAccount = AdAccount::where('ad_account_id', $data['ad_account_id'])->first();
            if (!$adAccount) {
                throw new \Exception('Ad account not found');
            }

            // Prepare targeting spec
            $targetingSpec = $this->buildTargetingSpec($data);

            // Estimate audience size
            $audienceSize = $this->estimateAudienceSize($targetingSpec, $data['platform']);

            // Create audience
            $audience = AdAudience::create([
                'ad_audience_id' => $audienceId,
                'ad_account_id' => $data['ad_account_id'],
                'platform' => $data['platform'],
                'audience_name' => $data['audience_name'],
                'audience_type' => $data['audience_type'] ?? 'saved',
                'audience_size' => $audienceSize,
                'targeting_spec' => $targetingSpec,
                'exclusions' => $data['exclusions'] ?? [],
                'lookalike_source' => $data['lookalike_source'] ?? null,
                'lookalike_ratio' => $data['lookalike_ratio'] ?? null,
                'custom_audience_source' => $data['custom_audience_source'] ?? null,
                'retention_days' => $data['retention_days'] ?? 180,
                'status' => 'active',
                'metadata' => $data['metadata'] ?? [],
                'provider' => 'cmis'
            ]);

            // Sync to platform if requested
            if ($data['sync_to_platform'] ?? false) {
                $syncResult = $this->syncAudienceToPlatform($audience);
                if ($syncResult['success']) {
                    $audience->audience_external_id = $syncResult['external_id'];
                    $audience->last_synced_at = now();
                    $audience->save();
                }
            }

            DB::commit();

            Log::info('Audience created', [
                'audience_id' => $audienceId,
                'type' => $audience->audience_type,
                'platform' => $audience->platform,
                'estimated_size' => $audienceSize
            ]);

            return [
                'success' => true,
                'data' => $audience->fresh()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create audience', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update audience
     *
     * @param string $audienceId
     * @param array $data
     * @return array
     */
    public function updateAudience(string $audienceId, array $data): array
    {
        try {
            DB::beginTransaction();

            $audience = AdAudience::where('ad_audience_id', $audienceId)->first();
            if (!$audience) {
                throw new \Exception('Audience not found');
            }

            // Update targeting spec if provided
            if (isset($data['targeting_spec'])) {
                $targetingSpec = $this->buildTargetingSpec($data);
                $data['targeting_spec'] = $targetingSpec;
                $data['audience_size'] = $this->estimateAudienceSize($targetingSpec, $audience->platform);
            }

            // Update audience
            $audience->update(array_filter([
                'audience_name' => $data['audience_name'] ?? $audience->audience_name,
                'targeting_spec' => $data['targeting_spec'] ?? $audience->targeting_spec,
                'exclusions' => $data['exclusions'] ?? $audience->exclusions,
                'audience_size' => $data['audience_size'] ?? $audience->audience_size,
                'status' => $data['status'] ?? $audience->status,
                'metadata' => $data['metadata'] ?? $audience->metadata,
            ], fn($value) => $value !== null));

            DB::commit();

            Log::info('Audience updated', ['audience_id' => $audienceId]);

            return [
                'success' => true,
                'data' => $audience->fresh()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update audience', [
                'audience_id' => $audienceId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get audience details
     *
     * @param string $audienceId
     * @param bool $includeInsights
     * @return array
     */
    public function getAudience(string $audienceId, bool $includeInsights = false): array
    {
        try {
            $audience = AdAudience::where('ad_audience_id', $audienceId)
                ->with('adAccount')
                ->first();

            if (!$audience) {
                throw new \Exception('Audience not found');
            }

            $data = [
                'audience' => $audience,
            ];

            if ($includeInsights) {
                $data['insights'] = $this->getAudienceInsights($audienceId);
                $data['reach_estimate'] = $this->getReachEstimate($audience);
            }

            return [
                'success' => true,
                'data' => $data
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get audience', [
                'audience_id' => $audienceId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List audiences with filters
     *
     * @param array $filters
     * @return array
     */
    public function listAudiences(array $filters = []): array
    {
        try {
            $query = AdAudience::query()->with('adAccount');

            // Apply filters
            if (isset($filters['ad_account_id'])) {
                $query->where('ad_account_id', $filters['ad_account_id']);
            }

            if (isset($filters['platform'])) {
                $query->where('platform', $filters['platform']);
            }

            if (isset($filters['audience_type'])) {
                $query->where('audience_type', $filters['audience_type']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['search'])) {
                $query->where('audience_name', 'ILIKE', '%' . $filters['search'] . '%');
            }

            // Sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $filters['per_page'] ?? 20;
            $audiences = $query->paginate($perPage);

            return [
                'success' => true,
                'data' => $audiences->items(),
                'pagination' => [
                    'total' => $audiences->total(),
                    'per_page' => $audiences->perPage(),
                    'current_page' => $audiences->currentPage(),
                    'last_page' => $audiences->lastPage()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to list audiences', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create lookalike audience
     *
     * @param string $sourceAudienceId
     * @param array $options
     * @return array
     */
    public function createLookalikeAudience(string $sourceAudienceId, array $options): array
    {
        try {
            $sourceAudience = AdAudience::where('ad_audience_id', $sourceAudienceId)->first();
            if (!$sourceAudience) {
                throw new \Exception('Source audience not found');
            }

            $lookalike = $this->createAudience([
                'ad_account_id' => $sourceAudience->ad_account_id,
                'platform' => $sourceAudience->platform,
                'audience_name' => $options['audience_name'] ?? ($sourceAudience->audience_name . ' - Lookalike'),
                'audience_type' => 'lookalike',
                'lookalike_source' => $sourceAudienceId,
                'lookalike_ratio' => $options['lookalike_ratio'] ?? 1.0,
                'targeting_spec' => [
                    'geo_locations' => $options['geo_locations'] ?? ['countries' => ['US']],
                ],
                'metadata' => array_merge(
                    $options['metadata'] ?? [],
                    ['source_audience_id' => $sourceAudienceId]
                ),
                'sync_to_platform' => $options['sync_to_platform'] ?? false
            ]);

            return $lookalike;

        } catch (\Exception $e) {
            Log::error('Failed to create lookalike audience', [
                'source_audience_id' => $sourceAudienceId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete audience
     *
     * @param string $audienceId
     * @param bool $permanent
     * @return bool
     */
    public function deleteAudience(string $audienceId, bool $permanent = false): bool
    {
        try {
            $audience = AdAudience::where('ad_audience_id', $audienceId)->first();
            if (!$audience) {
                throw new \Exception('Audience not found');
            }

            if ($permanent) {
                $audience->forceDelete();
            } else {
                $audience->delete();
            }

            Log::info('Audience deleted', [
                'audience_id' => $audienceId,
                'permanent' => $permanent
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete audience', [
                'audience_id' => $audienceId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Estimate audience size
     *
     * @param array $targetingSpec
     * @param string $platform
     * @return int
     */
    public function estimateAudienceSize(array $targetingSpec, string $platform): int
    {
        // This would integrate with platform APIs for real estimates
        // For now, return a calculated estimate

        $baseSize = 1000000; // Base potential reach

        // Adjust based on geo targeting
        if (isset($targetingSpec['geo_locations'])) {
            $countries = $targetingSpec['geo_locations']['countries'] ?? [];
            $baseSize = count($countries) * 500000;
        }

        // Adjust based on age range
        if (isset($targetingSpec['age_min']) || isset($targetingSpec['age_max'])) {
            $baseSize = (int)($baseSize * 0.7);
        }

        // Adjust based on gender
        if (isset($targetingSpec['genders']) && count($targetingSpec['genders']) === 1) {
            $baseSize = (int)($baseSize * 0.48);
        }

        // Adjust based on interests
        $interestCount = count($targetingSpec['interests'] ?? []);
        if ($interestCount > 0) {
            $baseSize = (int)($baseSize * (0.8 - ($interestCount * 0.05)));
        }

        // Adjust based on behaviors
        $behaviorCount = count($targetingSpec['behaviors'] ?? []);
        if ($behaviorCount > 0) {
            $baseSize = (int)($baseSize * (0.85 - ($behaviorCount * 0.05)));
        }

        return max($baseSize, 1000); // Minimum 1000
    }

    /**
     * Build targeting specification
     *
     * @param array $data
     * @return array
     */
    protected function buildTargetingSpec(array $data): array
    {
        $spec = [];

        // Geographic targeting
        if (isset($data['geo_locations'])) {
            $spec['geo_locations'] = $data['geo_locations'];
        }

        // Demographic targeting
        if (isset($data['age_min'])) {
            $spec['age_min'] = $data['age_min'];
        }
        if (isset($data['age_max'])) {
            $spec['age_max'] = $data['age_max'];
        }
        if (isset($data['genders'])) {
            $spec['genders'] = $data['genders'];
        }
        if (isset($data['languages'])) {
            $spec['languages'] = $data['languages'];
        }

        // Interest targeting
        if (isset($data['interests'])) {
            $spec['interests'] = $data['interests'];
        }

        // Behavioral targeting
        if (isset($data['behaviors'])) {
            $spec['behaviors'] = $data['behaviors'];
        }

        // Detailed targeting
        if (isset($data['detailed_targeting'])) {
            $spec['detailed_targeting'] = $data['detailed_targeting'];
        }

        // Custom audiences
        if (isset($data['custom_audiences'])) {
            $spec['custom_audiences'] = $data['custom_audiences'];
        }

        // Device targeting
        if (isset($data['device_platforms'])) {
            $spec['device_platforms'] = $data['device_platforms'];
        }

        // Publisher platforms
        if (isset($data['publisher_platforms'])) {
            $spec['publisher_platforms'] = $data['publisher_platforms'];
        }

        // Connection types
        if (isset($data['connections'])) {
            $spec['connections'] = $data['connections'];
        }

        return $spec;
    }

    /**
     * Get audience insights
     *
     * @param string $audienceId
     * @return array
     */
    protected function getAudienceInsights(string $audienceId): array
    {
        // This would integrate with platform APIs for real insights
        // For now, return structured placeholder data

        return [
            'demographics' => [
                'age_distribution' => [
                    '18-24' => 15,
                    '25-34' => 35,
                    '35-44' => 25,
                    '45-54' => 15,
                    '55-64' => 8,
                    '65+' => 2
                ],
                'gender_distribution' => [
                    'male' => 52,
                    'female' => 46,
                    'other' => 2
                ]
            ],
            'top_locations' => [
                ['country' => 'United States', 'percentage' => 45],
                ['country' => 'Canada', 'percentage' => 15],
                ['country' => 'United Kingdom', 'percentage' => 12],
                ['country' => 'Australia', 'percentage' => 10],
                ['country' => 'Germany', 'percentage' => 8]
            ],
            'top_interests' => [
                ['interest' => 'Technology', 'affinity' => 'high'],
                ['interest' => 'Business', 'affinity' => 'high'],
                ['interest' => 'Entrepreneurship', 'affinity' => 'medium'],
                ['interest' => 'Marketing', 'affinity' => 'medium']
            ],
            'device_usage' => [
                'mobile' => 68,
                'desktop' => 28,
                'tablet' => 4
            ],
            'note' => 'Insights require platform API integration'
        ];
    }

    /**
     * Get reach estimate
     *
     * @param AdAudience $audience
     * @return array
     */
    protected function getReachEstimate(AdAudience $audience): array
    {
        $targetingSpec = $audience->targeting_spec ?? [];
        $estimatedReach = $this->estimateAudienceSize($targetingSpec, $audience->platform);

        return [
            'estimated_reach' => $estimatedReach,
            'min_reach' => (int)($estimatedReach * 0.7),
            'max_reach' => (int)($estimatedReach * 1.3),
            'confidence' => 'medium',
            'daily_reach_potential' => (int)($estimatedReach * 0.15),
            'note' => 'Estimates require platform API integration for accuracy'
        ];
    }

    /**
     * Sync audience to platform API
     *
     * @param AdAudience $audience
     * @return array
     */
    protected function syncAudienceToPlatform(AdAudience $audience): array
    {
        // This would integrate with platform-specific APIs
        // For now, return placeholder

        Log::info('Audience sync to platform requested', [
            'audience_id' => $audience->ad_audience_id,
            'platform' => $audience->platform
        ]);

        return [
            'success' => true,
            'external_id' => 'audience_' . uniqid(),
            'note' => 'Platform API integration required for ' . $audience->platform
        ];
    }

    /**
     * Get targeting suggestions based on objectives
     *
     * @param string $objective
     * @param string $platform
     * @return array
     */
    public function getTargetingSuggestions(string $objective, string $platform): array
    {
        $suggestions = [
            'awareness' => [
                'age_range' => ['min' => 18, 'max' => 65],
                'recommended_interests' => ['Broad targeting for maximum reach'],
                'recommended_placements' => ['Feed', 'Stories', 'Reels'],
                'tips' => [
                    'Use broad targeting to maximize reach',
                    'Focus on demographic targeting rather than detailed interests',
                    'Consider video content for better engagement'
                ]
            ],
            'traffic' => [
                'age_range' => ['min' => 25, 'max' => 54],
                'recommended_interests' => ['Technology', 'Business', 'Online Shopping'],
                'recommended_placements' => ['Feed', 'Audience Network'],
                'tips' => [
                    'Target users who have shown intent to click',
                    'Use link click optimization',
                    'Test different creatives to improve CTR'
                ]
            ],
            'leads' => [
                'age_range' => ['min' => 25, 'max' => 54],
                'recommended_interests' => ['Professional services', 'Business decision makers'],
                'recommended_placements' => ['Feed', 'Instant Articles'],
                'tips' => [
                    'Target engaged users with purchasing power',
                    'Use lead form ads for easier conversion',
                    'Retarget website visitors'
                ]
            ],
            'sales' => [
                'age_range' => ['min' => 25, 'max' => 64],
                'recommended_interests' => ['Online shopping', 'E-commerce'],
                'recommended_placements' => ['Feed', 'Marketplace', 'Stories'],
                'tips' => [
                    'Use purchase-based lookalike audiences',
                    'Retarget cart abandoners',
                    'Optimize for conversion value'
                ]
            ]
        ];

        return [
            'success' => true,
            'data' => $suggestions[$objective] ?? $suggestions['awareness'],
            'objective' => $objective,
            'platform' => $platform
        ];
    }
}
