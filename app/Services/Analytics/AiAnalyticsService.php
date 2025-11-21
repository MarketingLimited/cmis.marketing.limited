<?php

namespace App\Services\Analytics;

use App\Repositories\Analytics\AiAnalyticsRepository;
use Illuminate\Support\Facades\Cache;

class AiAnalyticsService
{
    private AiAnalyticsRepository $repository;

    public function __construct(AiAnalyticsRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get comprehensive AI usage summary for organization
     */
    public function getUsageSummary(string $orgId, ?string $startDate = null, ?string $endDate = null): array
    {
        $cacheKey = "ai_usage_summary_{$orgId}_{$startDate}_{$endDate}";

        return Cache::remember($cacheKey, 300, function () use ($orgId, $startDate, $endDate) {
            return $this->repository->getUsageSummary($orgId, $startDate, $endDate);
        });
    }

    /**
     * Get daily usage trend for charts
     */
    public function getDailyTrend(string $orgId, int $days = 30): array
    {
        $cacheKey = "ai_daily_trend_{$orgId}_{$days}";

        return Cache::remember($cacheKey, 300, function () use ($orgId, $days) {
            return $this->repository->getDailyTrend($orgId, $days);
        });
    }

    /**
     * Get current quota status with health indicators
     */
    public function getQuotaStatus(string $orgId): array
    {
        $cacheKey = "ai_quota_status_{$orgId}";

        $quota = Cache::remember($cacheKey, 60, function () use ($orgId) {
            return $this->repository->getQuotaStatus($orgId);
        });

        // Add health indicators
        $quota['health'] = $this->calculateQuotaHealth($quota);

        return $quota;
    }

    /**
     * Get cost breakdown by campaign
     */
    public function getCostByCampaign(string $orgId, ?string $startDate = null, ?string $endDate = null): array
    {
        $cacheKey = "ai_cost_by_campaign_{$orgId}_{$startDate}_{$endDate}";

        return Cache::remember($cacheKey, 600, function () use ($orgId, $startDate, $endDate) {
            return $this->repository->getCostByCampaign($orgId, $startDate, $endDate);
        });
    }

    /**
     * Get generated media statistics
     */
    public function getGeneratedMediaStats(string $orgId, ?string $startDate = null, ?string $endDate = null): array
    {
        $cacheKey = "ai_media_stats_{$orgId}_{$startDate}_{$endDate}";

        return Cache::remember($cacheKey, 300, function () use ($orgId, $startDate, $endDate) {
            return $this->repository->getGeneratedMediaStats($orgId, $startDate, $endDate);
        });
    }

    /**
     * Get top performing generated media
     */
    public function getTopPerformingMedia(string $orgId, int $limit = 10): array
    {
        return $this->repository->getTopPerformingMedia($orgId, $limit);
    }

    /**
     * Get monthly cost comparison for trend analysis
     */
    public function getMonthlyCostComparison(string $orgId, int $months = 6): array
    {
        $cacheKey = "ai_monthly_comparison_{$orgId}_{$months}";

        return Cache::remember($cacheKey, 3600, function () use ($orgId, $months) {
            return $this->repository->getMonthlyCostComparison($orgId, $months);
        });
    }

    /**
     * Get comprehensive analytics dashboard data
     */
    public function getDashboardData(string $orgId): array
    {
        return [
            'summary' => $this->getUsageSummary($orgId),
            'quota' => $this->getQuotaStatus($orgId),
            'daily_trend' => $this->getDailyTrend($orgId, 30),
            'media_stats' => $this->getGeneratedMediaStats($orgId),
            'top_campaigns' => $this->getCostByCampaign($orgId),
            'monthly_comparison' => $this->getMonthlyCostComparison($orgId, 6)
        ];
    }

    /**
     * Calculate quota health indicators
     */
    private function calculateQuotaHealth(array $quota): array
    {
        $health = [
            'text' => $this->getHealthStatus($quota['text']['percentage_monthly'] ?? 0),
            'image' => $this->getHealthStatus($quota['image']['percentage_monthly'] ?? 0),
            'video' => $this->getHealthStatus($quota['video']['percentage_monthly'] ?? 0),
            'overall' => 'healthy'
        ];

        // Calculate overall health
        $statuses = [$health['text'], $health['image'], $health['video']];
        if (in_array('critical', $statuses)) {
            $health['overall'] = 'critical';
        } elseif (in_array('warning', $statuses)) {
            $health['overall'] = 'warning';
        }

        return $health;
    }

    /**
     * Determine health status based on usage percentage
     */
    private function getHealthStatus(float $percentage): string
    {
        if ($percentage >= 90) {
            return 'critical';
        } elseif ($percentage >= 75) {
            return 'warning';
        }
        return 'healthy';
    }

    /**
     * Clear analytics cache for organization
     */
    public function clearCache(string $orgId): void
    {
        $patterns = [
            "ai_usage_summary_{$orgId}_*",
            "ai_daily_trend_{$orgId}_*",
            "ai_quota_status_{$orgId}",
            "ai_cost_by_campaign_{$orgId}_*",
            "ai_media_stats_{$orgId}_*",
            "ai_monthly_comparison_{$orgId}_*"
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Get analytics export data (CSV format)
     */
    public function getExportData(string $orgId, string $type, ?string $startDate = null, ?string $endDate = null): array
    {
        return match ($type) {
            'usage' => $this->repository->getUsageSummary($orgId, $startDate, $endDate),
            'daily_trend' => $this->repository->getDailyTrend($orgId, 90),
            'campaigns' => $this->repository->getCostByCampaign($orgId, $startDate, $endDate),
            'media' => $this->repository->getGeneratedMediaStats($orgId, $startDate, $endDate),
            'monthly' => $this->repository->getMonthlyCostComparison($orgId, 12),
            default => throw new \InvalidArgumentException("Invalid export type: {$type}")
        };
    }

    /**
     * Get real-time quota alerts
     */
    public function getQuotaAlerts(string $orgId): array
    {
        $quota = $this->getQuotaStatus($orgId);
        $alerts = [];

        // Check each quota type
        foreach (['text', 'image', 'video'] as $type) {
            $dailyPercentage = $quota[$type]['percentage_daily'] ?? 0;
            $monthlyPercentage = $quota[$type]['percentage_monthly'] ?? 0;

            if ($dailyPercentage >= 90) {
                $alerts[] = [
                    'type' => $type,
                    'level' => 'critical',
                    'scope' => 'daily',
                    'percentage' => $dailyPercentage,
                    'message' => "Daily {$type} quota at {$dailyPercentage}%"
                ];
            } elseif ($dailyPercentage >= 75) {
                $alerts[] = [
                    'type' => $type,
                    'level' => 'warning',
                    'scope' => 'daily',
                    'percentage' => $dailyPercentage,
                    'message' => "Daily {$type} quota at {$dailyPercentage}%"
                ];
            }

            if ($monthlyPercentage >= 90) {
                $alerts[] = [
                    'type' => $type,
                    'level' => 'critical',
                    'scope' => 'monthly',
                    'percentage' => $monthlyPercentage,
                    'message' => "Monthly {$type} quota at {$monthlyPercentage}%"
                ];
            } elseif ($monthlyPercentage >= 75) {
                $alerts[] = [
                    'type' => $type,
                    'level' => 'warning',
                    'scope' => 'monthly',
                    'percentage' => $monthlyPercentage,
                    'message' => "Monthly {$type} quota at {$monthlyPercentage}%"
                ];
            }
        }

        return $alerts;
    }
}
