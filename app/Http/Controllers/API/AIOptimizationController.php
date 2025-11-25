<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Core\Org;
use App\Models\AdPlatform\AdCampaign;
use App\Services\AI\CampaignOptimizationService;
use Illuminate\Http\{Request, JsonResponse};

/**
 * @group AI Optimization
 *
 * AI-powered campaign analysis and optimization recommendations
 */
class AIOptimizationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CampaignOptimizationService $optimizationService
    ) {}

    /**
     * Analyze campaign performance
     *
     * Get AI-powered analysis and optimization recommendations for a campaign.
     * Includes performance score, KPI analysis, budget optimization, and predictions.
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     * @urlParam campaign string required Campaign UUID. Example: 660e8400-e29b-41d4-a716-446655440001
     *
     * @response 200 {
     *   "campaign_id": "660e8400-e29b-41d4-a716-446655440001",
     *   "campaign_name": "Summer Campaign",
     *   "analysis_date": "2024-01-15T15:00:00Z",
     *   "performance_score": 75,
     *   "kpis": {
     *     "ctr": {
     *       "value": 3.5,
     *       "status": "good",
     *       "benchmark": 3.0
     *     },
     *     "roi": {
     *       "value": 280,
     *       "status": "good",
     *       "benchmark": 250
     *     }
     *   },
     *   "recommendations": [
     *     {
     *       "type": "scaling",
     *       "priority": "medium",
     *       "action": "Scale successful campaign",
     *       "reason": "Excellent performance (score: 75/100)",
     *       "suggestions": ["Increase budget by 20-30%", "Expand to similar audiences"]
     *     }
     *   ],
     *   "budget_optimization": {
     *     "total_budget": 10000,
     *     "spent": 7500,
     *     "budget_used_pct": 75,
     *     "recommended_budget": 12000
     *   },
     *   "predicted_performance": {
     *     "next_7_days": {
     *       "predicted_spend": 2500,
     *       "predicted_conversions": 50,
     *       "confidence": "medium"
     *     }
     *   }
     * }
     *
     * @authenticated
     */
    public function analyzeCampaign(Org $org, AdCampaign $campaign): JsonResponse
    {
        // Verify campaign belongs to org
        if ($campaign->org_id !== $org->org_id) {
            return response()->json(['error' => 'Campaign not found'], 404);
        }

        $analysis = $this->optimizationService->analyzeCampaign($campaign);

        return response()->json($analysis);
    }

    /**
     * Get optimization recommendations for all campaigns
     *
     * Analyzes all active campaigns in an organization and returns
     * prioritized optimization recommendations.
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @queryParam min_score integer Minimum performance score (0-100). Example: 60
     * @queryParam priority string Filter by recommendation priority. Example: high
     *
     * @response 200 {
     *   "org_id": "550e8400-e29b-41d4-a716-446655440000",
     *   "total_campaigns": 10,
     *   "analyzed": 10,
     *   "avg_performance_score": 72,
     *   "campaigns": [
     *     {
     *       "campaign_id": "uuid",
     *       "campaign_name": "Summer Campaign",
     *       "performance_score": 85,
     *       "priority_recommendations": 2,
     *       "status": "excellent"
     *     }
     *   ],
     *   "top_recommendations": [
     *     {
     *       "campaign_name": "Winter Campaign",
     *       "type": "creative",
     *       "priority": "high",
     *       "action": "Improve ad creative"
     *     }
     *   ]
     * }
     *
     * @authenticated
     */
    public function analyzeAllCampaigns(Request $request, Org $org): JsonResponse
    {
        $minScore = $request->input('min_score', 0);
        $priority = $request->input('priority');

        $campaigns = AdCampaign::where('org_id', $org->org_id)
            ->where('status', 'active')
            ->get();

        $analyses = [];
        $totalScore = 0;
        $topRecommendations = [];

        foreach ($campaigns as $campaign) {
            $analysis = $this->optimizationService->analyzeCampaign($campaign);

            if ($analysis['performance_score'] >= $minScore) {
                $analyses[] = [
                    'campaign_id' => $campaign->campaign_id,
                    'campaign_name' => $campaign->name,
                    'performance_score' => $analysis['performance_score'],
                    'priority_recommendations' => count(array_filter(
                        $analysis['recommendations'],
                        fn($r) => $r['priority'] === 'high'
                    )),
                    'status' => $this->getPerformanceStatus($analysis['performance_score']),
                ];

                $totalScore += $analysis['performance_score'];

                // Collect high priority recommendations
                foreach ($analysis['recommendations'] as $rec) {
                    if (!$priority || $rec['priority'] === $priority) {
                        $topRecommendations[] = array_merge($rec, [
                            'campaign_name' => $campaign->name,
                        ]);
                    }
                }
            }
        }

        // Sort recommendations by priority
        usort($topRecommendations, function ($a, $b) {
            $priorities = ['high' => 3, 'medium' => 2, 'low' => 1];
            return ($priorities[$b['priority']] ?? 0) - ($priorities[$a['priority']] ?? 0);
        });

        return response()->json([
            'org_id' => $org->org_id,
            'total_campaigns' => $campaigns->count(),
            'analyzed' => count($analyses),
            'avg_performance_score' => count($analyses) > 0 ? round($totalScore / count($analyses)) : 0,
            'campaigns' => $analyses,
            'top_recommendations' => array_slice($topRecommendations, 0, 10),
        ]);
    }

    /**
     * Get performance status string
     */
    private function getPerformanceStatus(int $score): string
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'fair';
        return 'poor';
    }
}
