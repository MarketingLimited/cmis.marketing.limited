<?php

namespace App\Repositories\Analytics;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AiAnalyticsRepository
{
    /**
     * Get AI usage summary for organization
     */
    public function getUsageSummary(string $orgId, ?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::now()->subDays(30);
        $end = $endDate ? Carbon::parse($endDate) : Carbon::now();

        // Get usage by type
        $usageByType = DB::table('cmis_ai.ai_usage_logs')
            ->where('org_id', $orgId)
            ->whereBetween('created_at', [$start, $end])
            ->select(
                'generation_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(tokens_used) as total_tokens'),
                DB::raw('SUM(cost_usd) as total_cost')
            )
            ->groupBy('generation_type')
            ->get();

        // Get total usage
        $totalUsage = DB::table('cmis_ai.ai_usage_logs')
            ->where('org_id', $orgId)
            ->whereBetween('created_at', [$start, $end])
            ->select(
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('SUM(tokens_used) as total_tokens'),
                DB::raw('SUM(cost_usd) as total_cost')
            )
            ->first();

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'days' => $start->diffInDays($end)
            ],
            'summary' => [
                'total_requests' => $totalUsage->total_requests ?? 0,
                'total_tokens' => $totalUsage->total_tokens ?? 0,
                'total_cost' => (float) ($totalUsage->total_cost ?? 0)
            ],
            'by_type' => $usageByType->map(function ($item) {
                return [
                    'type' => $item->generation_type,
                    'count' => $item->count,
                    'tokens' => $item->total_tokens,
                    'cost' => (float) $item->total_cost
                ];
            })->toArray()
        ];
    }

    /**
     * Get daily usage trend
     */
    public function getDailyTrend(string $orgId, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        $trend = DB::table('cmis_ai.ai_usage_logs')
            ->where('org_id', $orgId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as requests'),
                DB::raw('SUM(tokens_used) as tokens'),
                DB::raw('SUM(cost_usd) as cost')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return $trend->map(function ($item) {
            return [
                'date' => $item->date,
                'requests' => $item->requests,
                'tokens' => $item->tokens,
                'cost' => (float) $item->cost
            ];
        })->toArray();
    }

    /**
     * Get current quota status
     */
    public function getQuotaStatus(string $orgId): array
    {
        $quota = DB::table('cmis.ai_usage_quotas')
            ->where('org_id', $orgId)
            ->first();

        if (!$quota) {
            return [
                'quota_type' => 'free',
                'text' => ['daily' => 0, 'monthly' => 0, 'used_daily' => 0, 'used_monthly' => 0],
                'image' => ['daily' => 0, 'monthly' => 0, 'used_daily' => 0, 'used_monthly' => 0],
                'video' => ['daily' => 0, 'monthly' => 0, 'used_daily' => 0, 'used_monthly' => 0]
            ];
        }

        return [
            'quota_type' => $quota->quota_type,
            'text' => [
                'daily' => $quota->gpt_quota_daily ?? 0,
                'monthly' => $quota->gpt_quota_monthly ?? 0,
                'used_daily' => $quota->gpt_used_daily ?? 0,
                'used_monthly' => $quota->gpt_used_monthly ?? 0,
                'percentage_daily' => $this->calculatePercentage($quota->gpt_used_daily, $quota->gpt_quota_daily),
                'percentage_monthly' => $this->calculatePercentage($quota->gpt_used_monthly, $quota->gpt_quota_monthly)
            ],
            'image' => [
                'daily' => $quota->image_quota_daily ?? 0,
                'monthly' => $quota->image_quota_monthly ?? 0,
                'used_daily' => $quota->image_used_daily ?? 0,
                'used_monthly' => $quota->image_used_monthly ?? 0,
                'percentage_daily' => $this->calculatePercentage($quota->image_used_daily, $quota->image_quota_daily),
                'percentage_monthly' => $this->calculatePercentage($quota->image_used_monthly, $quota->image_quota_monthly)
            ],
            'video' => [
                'daily' => $quota->video_quota_daily ?? 0,
                'monthly' => $quota->video_quota_monthly ?? 0,
                'used_daily' => $quota->video_used_daily ?? 0,
                'used_monthly' => $quota->video_used_monthly ?? 0,
                'percentage_daily' => $this->calculatePercentage($quota->video_used_daily, $quota->video_quota_daily),
                'percentage_monthly' => $this->calculatePercentage($quota->video_used_monthly, $quota->video_quota_monthly)
            ],
            'reset_date' => $quota->reset_date
        ];
    }

    /**
     * Get cost breakdown by campaign
     */
    public function getCostByCampaign(string $orgId, ?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::now()->subDays(30);
        $end = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $costs = DB::table('cmis_ai.generated_media as gm')
            ->leftJoin('cmis.campaigns as c', 'gm.campaign_id', '=', 'c.id')
            ->where('gm.org_id', $orgId)
            ->whereBetween('gm.created_at', [$start, $end])
            ->select(
                'c.id as campaign_id',
                'c.name as campaign_name',
                DB::raw('COUNT(gm.id) as media_count'),
                DB::raw('SUM(gm.generation_cost) as total_cost'),
                DB::raw('AVG(gm.generation_cost) as avg_cost')
            )
            ->groupBy('c.id', 'c.name')
            ->orderBy('total_cost', 'desc')
            ->limit(20)
            ->get();

        return $costs->map(function ($item) {
            return [
                'campaign_id' => $item->campaign_id,
                'campaign_name' => $item->campaign_name ?? 'No Campaign',
                'media_count' => $item->media_count,
                'total_cost' => (float) ($item->total_cost ?? 0),
                'avg_cost' => (float) ($item->avg_cost ?? 0)
            ];
        })->toArray();
    }

    /**
     * Get generated media statistics
     */
    public function getGeneratedMediaStats(string $orgId, ?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::now()->subDays(30);
        $end = $endDate ? Carbon::parse($endDate) : Carbon::now();

        // By media type
        $byType = DB::table('cmis_ai.generated_media')
            ->where('org_id', $orgId)
            ->whereBetween('created_at', [$start, $end])
            ->select(
                'media_type',
                'status',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('media_type', 'status')
            ->get();

        // By AI model
        $byModel = DB::table('cmis_ai.generated_media')
            ->where('org_id', $orgId)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->select(
                'ai_model',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(generation_cost) as total_cost')
            )
            ->groupBy('ai_model')
            ->get();

        return [
            'by_type' => $byType->groupBy('media_type')->map(function ($items, $type) {
                $stats = [
                    'type' => $type,
                    'total' => $items->sum('count')
                ];
                foreach ($items as $item) {
                    $stats[$item->status] = $item->count;
                }
                return $stats;
            })->values()->toArray(),
            'by_model' => $byModel->map(function ($item) {
                return [
                    'model' => $item->ai_model,
                    'count' => $item->count,
                    'total_cost' => (float) ($item->total_cost ?? 0)
                ];
            })->toArray()
        ];
    }

    /**
     * Get top performing generated media
     */
    public function getTopPerformingMedia(string $orgId, int $limit = 10): array
    {
        return DB::table('cmis_ai.generated_media')
            ->where('org_id', $orgId)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($media) {
                return [
                    'id' => $media->id,
                    'media_type' => $media->media_type,
                    'ai_model' => $media->ai_model,
                    'media_url' => $media->media_url,
                    'generation_cost' => (float) $media->generation_cost,
                    'created_at' => $media->created_at
                ];
            })
            ->toArray();
    }

    /**
     * Calculate percentage with handling for unlimited quota (-1)
     */
    private function calculatePercentage(?int $used, ?int $quota): float
    {
        if (!$quota || $quota <= 0 || $quota === -1) {
            return 0.0;
        }

        if (!$used) {
            return 0.0;
        }

        return round(($used / $quota) * 100, 2);
    }

    /**
     * Get monthly cost comparison
     */
    public function getMonthlyCostComparison(string $orgId, int $months = 6): array
    {
        $results = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();

            $cost = DB::table('cmis_ai.ai_usage_logs')
                ->where('org_id', $orgId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('cost_usd');

            $results[] = [
                'month' => $monthStart->format('Y-m'),
                'month_name' => $monthStart->format('F Y'),
                'cost' => (float) ($cost ?? 0)
            ];
        }

        return $results;
    }
}
