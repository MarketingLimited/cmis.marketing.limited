<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignAnalytics;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    protected CampaignRepositoryInterface $campaignRepo;
    protected PermissionRepositoryInterface $permissionRepo;
    protected CacheService $cache;

    public function __construct(
        CampaignRepositoryInterface $campaignRepo,
        PermissionRepositoryInterface $permissionRepo,
        CacheService $cache
    ) {
        $this->campaignRepo = $campaignRepo;
        $this->permissionRepo = $permissionRepo;
        $this->cache = $cache;
    }

    /**
     * Create campaign with context safely using repository
     */
    public function createWithContext(array $campaignData, array $contextData): Campaign
    {
        try {
            // Initialize transaction context for security
            $userId = $campaignData['created_by'] ?? auth()->id();
            $orgId = $campaignData['org_id'];

            $this->permissionRepo->initTransactionContext($userId, $orgId);

            // Create campaign using repository method
            $results = $this->campaignRepo->createCampaignWithContext(
                $orgId,
                $contextData['offering_id'] ?? '',
                $contextData['segment_id'] ?? '',
                $campaignData['name'],
                $contextData['framework'] ?? 'default',
                $contextData['tone'] ?? 'professional',
                $contextData['tags'] ?? []
            );

            if ($results->isEmpty() || !isset($results[0]->campaign_id)) {
                throw new \Exception('فشل إنشاء الحملة مع السياق');
            }

            return Campaign::findOrFail($results[0]->campaign_id);

        } catch (\Exception $e) {
            Log::error('Campaign creation failed', [
                'error' => $e->getMessage(),
                'data' => $campaignData
            ]);
            throw $e;
        }
    }

    /**
     * Get campaign contexts using repository
     */
    public function getCampaignContexts(string $campaignId, bool $includeInactive = false): array
    {
        try {
            $results = $this->campaignRepo->getCampaignContexts($campaignId, $includeInactive);
            return $results->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to get campaign contexts', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Find related campaigns using repository
     */
    public function findRelatedCampaigns(string $campaignId, int $limit = 5): array
    {
        try {
            $results = $this->campaignRepo->findRelatedCampaigns($campaignId, $limit);
            return $results->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to find related campaigns', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get campaign analytics summary (with caching)
     */
    public function getAnalyticsSummary(string $campaignId): ?array
    {
        try {
            $cacheKey = $this->cache->campaignKey($campaignId, 'analytics:summary');

            return $this->cache->remember(
                $cacheKey,
                CacheService::TTL_SHORT,
                function() use ($campaignId) {
                    $campaign = Campaign::with(['performanceMetrics', 'analytics'])->findOrFail($campaignId);

                    $metrics = $campaign->performanceMetrics()
                        ->latest('collected_at')
                        ->limit(10)
                        ->get();

                    $analytics = CampaignAnalytics::where('campaign_id', $campaignId)
                        ->latest('calculated_at')
                        ->first();

                    return [
                        'campaign' => $campaign,
                        'recent_metrics' => $metrics,
                        'analytics' => $analytics,
                        'performance_summary' => [
                            'total_metrics' => $metrics->count(),
                            'avg_confidence' => $metrics->avg('confidence_level'),
                            'total_variance' => $metrics->sum('variance'),
                        ],
                    ];
                }
            );

        } catch (\Exception $e) {
            Log::error('Failed to get campaign analytics', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create a simple campaign without context
     */
    public function create(array $campaignData): Campaign
    {
        try {
            DB::beginTransaction();

            $campaign = Campaign::create($campaignData);

            DB::commit();

            // Invalidate org cache
            if (isset($campaignData['org_id'])) {
                $this->cache->invalidateOrg($campaignData['org_id']);
            }

            Log::info('Campaign created', ['campaign_id' => $campaign->campaign_id]);

            return $campaign;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create campaign', [
                'error' => $e->getMessage(),
                'data' => $campaignData
            ]);
            throw $e;
        }
    }

    /**
     * Update campaign
     */
    public function update(Campaign $campaign, array $data): Campaign
    {
        try {
            DB::beginTransaction();

            $campaign->update($data);

            DB::commit();

            // Invalidate caches
            $this->cache->invalidateCampaign($campaign->campaign_id);
            if ($campaign->org_id) {
                $this->cache->invalidateOrg($campaign->org_id);
            }

            Log::info('Campaign updated', ['campaign_id' => $campaign->campaign_id]);

            return $campaign->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update campaign', [
                'campaign_id' => $campaign->campaign_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete campaign
     */
    public function delete(Campaign $campaign): bool
    {
        try {
            DB::beginTransaction();

            $orgId = $campaign->org_id;
            $campaignId = $campaign->campaign_id;

            $campaign->delete();

            DB::commit();

            // Invalidate caches
            $this->cache->invalidateCampaign($campaignId);
            if ($orgId) {
                $this->cache->invalidateOrg($orgId);
            }

            Log::info('Campaign deleted', ['campaign_id' => $campaignId]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete campaign', [
                'campaign_id' => $campaign->campaign_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update campaign status with validation
     */
    public function updateStatus(string $campaignId, string $newStatus): bool
    {
        try {
            $campaign = Campaign::findOrFail($campaignId);

            $validStatuses = ['draft', 'active', 'paused', 'completed', 'archived'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new \InvalidArgumentException("حالة غير صالحة: {$newStatus}");
            }

            $campaign->update(['status' => $newStatus]);

            Log::info('Campaign status updated', [
                'campaign_id' => $campaignId,
                'old_status' => $campaign->getOriginal('status'),
                'new_status' => $newStatus
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update campaign status', [
                'campaign_id' => $campaignId,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if user has permission for campaign operation
     */
    public function checkPermission(string $userId, string $orgId, string $permissionCode): bool
    {
        return $this->permissionRepo->checkPermission($userId, $orgId, $permissionCode);
    }

    /**
     * Get performance metrics for a campaign within a date range
     *
     * @param string $campaignId
     * @param array $dateRange ['start' => Carbon, 'end' => Carbon]
     * @return array
     */
    public function getPerformanceMetrics(string $campaignId, array $dateRange = null): array
    {
        try {
            $campaign = Campaign::findOrFail($campaignId);

            // Default to last 30 days if no range provided
            if (!$dateRange) {
                $dateRange = [
                    'start' => now()->subDays(30),
                    'end' => now(),
                ];
            }

            // Get metrics from performance_metrics table
            $metrics = DB::table('cmis.performance_metrics')
                ->where('campaign_id', $campaignId)
                ->whereBetween('collected_at', [$dateRange['start'], $dateRange['end']])
                ->select(
                    DB::raw('SUM(CASE WHEN kpi = \'impressions\' THEN CAST(observed AS INTEGER) ELSE 0 END) as impressions'),
                    DB::raw('SUM(CASE WHEN kpi = \'clicks\' THEN CAST(observed AS INTEGER) ELSE 0 END) as clicks'),
                    DB::raw('SUM(CASE WHEN kpi = \'conversions\' THEN CAST(observed AS INTEGER) ELSE 0 END) as conversions'),
                    DB::raw('SUM(CASE WHEN kpi = \'spend\' THEN CAST(observed AS NUMERIC) ELSE 0 END) as spend'),
                    DB::raw('AVG(CASE WHEN kpi = \'ctr\' THEN CAST(observed AS NUMERIC) ELSE NULL END) as ctr'),
                    DB::raw('AVG(CASE WHEN kpi = \'cpc\' THEN CAST(observed AS NUMERIC) ELSE NULL END) as cpc'),
                    DB::raw('AVG(CASE WHEN kpi = \'cpa\' THEN CAST(observed AS NUMERIC) ELSE NULL END) as cpa'),
                    DB::raw('AVG(CASE WHEN kpi = \'roi\' THEN CAST(observed AS NUMERIC) ELSE NULL END) as roi')
                )
                ->first();

            return [
                'campaign_id' => $campaignId,
                'campaign_name' => $campaign->name,
                'date_range' => [
                    'start' => $dateRange['start']->toDateString(),
                    'end' => $dateRange['end']->toDateString(),
                ],
                'metrics' => [
                    'impressions' => (int) ($metrics->impressions ?? 0),
                    'clicks' => (int) ($metrics->clicks ?? 0),
                    'conversions' => (int) ($metrics->conversions ?? 0),
                    'spend' => (float) ($metrics->spend ?? 0),
                    'ctr' => round((float) ($metrics->ctr ?? 0), 2),
                    'cpc' => round((float) ($metrics->cpc ?? 0), 2),
                    'cpa' => round((float) ($metrics->cpa ?? 0), 2),
                    'roi' => round((float) ($metrics->roi ?? 0), 2),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get performance metrics', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Compare performance of multiple campaigns
     *
     * @param array $campaignIds
     * @param array $dateRange
     * @return array
     */
    public function compareCampaigns(array $campaignIds, array $dateRange = null): array
    {
        try {
            if (empty($campaignIds)) {
                return [];
            }

            // Default to last 30 days if no range provided
            if (!$dateRange) {
                $dateRange = [
                    'start' => now()->subDays(30),
                    'end' => now(),
                ];
            }

            $comparisons = [];

            foreach ($campaignIds as $campaignId) {
                try {
                    $metrics = $this->getPerformanceMetrics($campaignId, $dateRange);
                    $comparisons[] = $metrics;
                } catch (\Exception $e) {
                    Log::warning('Failed to get metrics for campaign in comparison', [
                        'campaign_id' => $campaignId,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            return [
                'campaigns' => $comparisons,
                'summary' => [
                    'total_campaigns' => count($comparisons),
                    'total_impressions' => array_sum(array_column(array_column($comparisons, 'metrics'), 'impressions')),
                    'total_clicks' => array_sum(array_column(array_column($comparisons, 'metrics'), 'clicks')),
                    'total_conversions' => array_sum(array_column(array_column($comparisons, 'metrics'), 'conversions')),
                    'total_spend' => array_sum(array_column(array_column($comparisons, 'metrics'), 'spend')),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to compare campaigns', [
                'campaign_ids' => $campaignIds,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get performance trends over time for a campaign
     *
     * @param string $campaignId
     * @param string $interval 'day', 'week', 'month'
     * @param int $periods Number of periods to fetch
     * @return array
     */
    public function getPerformanceTrends(string $campaignId, string $interval = 'day', int $periods = 30): array
    {
        try {
            $campaign = Campaign::findOrFail($campaignId);

            // Build date grouping based on interval
            $dateGrouping = match($interval) {
                'week' => "DATE_TRUNC('week', collected_at)",
                'month' => "DATE_TRUNC('month', collected_at)",
                default => "DATE(collected_at)", // day
            };

            $startDate = match($interval) {
                'week' => now()->subWeeks($periods),
                'month' => now()->subMonths($periods),
                default => now()->subDays($periods),
            };

            // Get daily/weekly/monthly trends
            $trends = DB::table('cmis.performance_metrics')
                ->where('campaign_id', $campaignId)
                ->where('collected_at', '>=', $startDate)
                ->select(
                    DB::raw("{$dateGrouping} as period"),
                    DB::raw('SUM(CASE WHEN kpi = \'impressions\' THEN CAST(observed AS INTEGER) ELSE 0 END) as impressions'),
                    DB::raw('SUM(CASE WHEN kpi = \'clicks\' THEN CAST(observed AS INTEGER) ELSE 0 END) as clicks'),
                    DB::raw('SUM(CASE WHEN kpi = \'conversions\' THEN CAST(observed AS INTEGER) ELSE 0 END) as conversions'),
                    DB::raw('SUM(CASE WHEN kpi = \'spend\' THEN CAST(observed AS NUMERIC) ELSE 0 END) as spend')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(function($row) {
                    return [
                        'period' => $row->period,
                        'impressions' => (int) $row->impressions,
                        'clicks' => (int) $row->clicks,
                        'conversions' => (int) $row->conversions,
                        'spend' => (float) $row->spend,
                    ];
                });

            return [
                'campaign_id' => $campaignId,
                'campaign_name' => $campaign->name,
                'interval' => $interval,
                'trends' => $trends->toArray(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get performance trends', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get top performing campaigns (automatically filtered by RLS)
     *
     * @param string $metric 'impressions', 'clicks', 'conversions', 'roi'
     * @param int $limit
     * @param array $dateRange
     * @return array
     */
    public function getTopPerformingCampaigns(
        string $metric = 'conversions',
        int $limit = 10,
        array $dateRange = null
    ): array {
        try {
            // Default to last 30 days if no range provided
            if (!$dateRange) {
                $dateRange = [
                    'start' => now()->subDays(30),
                    'end' => now(),
                ];
            }

            // Get all campaigns (RLS handles org filtering)
            $campaigns = Campaign::where('status', '!=', 'archived')
                ->pluck('campaign_id', 'name');

            $campaignPerformances = [];

            foreach ($campaigns as $name => $campaignId) {
                try {
                    $metrics = $this->getPerformanceMetrics($campaignId, $dateRange);
                    $campaignPerformances[] = [
                        'campaign_id' => $campaignId,
                        'name' => $name,
                        'metric_value' => $metrics['metrics'][$metric] ?? 0,
                        'all_metrics' => $metrics['metrics'],
                    ];
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Sort by the specified metric
            usort($campaignPerformances, function($a, $b) {
                return $b['metric_value'] <=> $a['metric_value'];
            });

            return [
                'metric' => $metric,
                'top_campaigns' => array_slice($campaignPerformances, 0, $limit),
                'total_campaigns' => count($campaignPerformances),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get top performing campaigns', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
