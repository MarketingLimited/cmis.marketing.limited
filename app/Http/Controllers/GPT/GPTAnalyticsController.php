<?php

namespace App\Http\Controllers\GPT;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Campaign;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * GPT Analytics Controller
 *
 * Handles analytics and metrics retrieval for GPT/ChatGPT integration
 */
class GPTAnalyticsController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    /**
     * Get campaign analytics
     */
    public function show(Request $request, string $campaignId): JsonResponse
    {
        $campaign = Campaign::findOrFail($campaignId);
        $this->authorize('view', $campaign);

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $realtime = $request->boolean('realtime', false);

        // If real-time is requested, bypass cache
        $metrics = $realtime
            ? $this->analyticsService->getRealTimeMetrics($campaignId)
            : $this->analyticsService->getMetrics($campaignId, $startDate, $endDate);

        if (empty($metrics)) {
            return $this->error('Analytics not available');
        }

        $response = [
            'impressions' => $metrics['impressions'] ?? 0,
            'clicks' => $metrics['clicks'] ?? 0,
            'conversions' => $metrics['conversions'] ?? 0,
            'spend' => $metrics['spend'] ?? 0,
            'ctr' => $metrics['ctr'] ?? 0,
            'cpc' => $metrics['cpc'] ?? 0,
            'cpa' => $metrics['cpa'] ?? 0,
            'conversion_rate' => $metrics['conversion_rate'] ?? 0,
            'roas' => $metrics['roas'] ?? 0,
        ];

        // Add data freshness information
        if ($realtime) {
            $response['data_freshness'] = 'real-time';
            $response['last_updated'] = now()->toIso8601String();
        } else {
            $response['data_freshness'] = 'cached';
            $response['last_updated'] = $metrics['cached_at'] ?? null;
        }

        return $this->success($response);
    }

    /**
     * Get real-time analytics for today
     *
     * Provides fresh analytics data for GPT conversations without cache.
     */
    public function realtime(Request $request, string $campaignId): JsonResponse
    {
        $campaign = Campaign::findOrFail($campaignId);
        $this->authorize('view', $campaign);

        // Fetch real-time metrics directly from platforms
        $realTimeMetrics = $this->analyticsService->getRealTimeMetrics($campaignId);

        $response = [
            'campaign_id' => $campaignId,
            'campaign_name' => $campaign->name,
            'data_freshness' => 'real-time',
            'last_updated' => now()->toIso8601String(),
            'metrics' => [
                'today' => [
                    'impressions' => $realTimeMetrics['impressions'] ?? 0,
                    'clicks' => $realTimeMetrics['clicks'] ?? 0,
                    'conversions' => $realTimeMetrics['conversions'] ?? 0,
                    'spend' => $realTimeMetrics['spend'] ?? 0,
                ],
                'performance' => [
                    'ctr' => $realTimeMetrics['ctr'] ?? 0,
                    'cpc' => $realTimeMetrics['cpc'] ?? 0,
                    'cpa' => $realTimeMetrics['cpa'] ?? 0,
                    'conversion_rate' => $realTimeMetrics['conversion_rate'] ?? 0,
                    'roas' => $realTimeMetrics['roas'] ?? 0,
                ],
            ],
            'platform_breakdown' => $realTimeMetrics['by_platform'] ?? [],
        ];

        return $this->success($response, 'Real-time analytics retrieved successfully');
    }
}
