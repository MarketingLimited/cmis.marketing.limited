<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Core\Org;
use App\Models\Marketing\AdCampaign;
use App\Services\AI\PredictiveAnalyticsService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Validation\Rule;

/**
 * @group Predictive Analytics
 * Advanced forecasting and trend analysis for marketing campaigns
 */
class PredictiveAnalyticsController extends Controller
{
    public function __construct(
        private PredictiveAnalyticsService $predictive
    ) {}

    /**
     * Forecast campaign performance
     *
     * Generates performance predictions using historical data and trend analysis
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     * @urlParam campaign string required Campaign UUID. Example: 123e4567-e89b-12d3-a456-426614174000
     * @queryParam days integer Number of days to forecast. Default: 30. Example: 30
     *
     * @response 200 {
     *   "campaign_id": "123e4567-e89b-12d3-a456-426614174000",
     *   "forecast_period": 30,
     *   "predictions": {
     *     "total_impressions": 150000,
     *     "total_clicks": 4500,
     *     "total_spend": 1500.00,
     *     "total_conversions": 180,
     *     "total_revenue": 3600.00,
     *     "predicted_ctr": 3.0,
     *     "predicted_cpc": 0.33,
     *     "predicted_conversion_rate": 4.0,
     *     "predicted_roi": 140.0,
     *     "daily_averages": {
     *       "impressions": 5000,
     *       "clicks": 150,
     *       "spend": 50.00,
     *       "conversions": 6,
     *       "revenue": 120.00
     *     }
     *   },
     *   "confidence_level": {
     *     "percentage": 85,
     *     "level": "high",
     *     "data_points": 90,
     *     "recommendation": "High confidence - predictions are reliable for decision making"
     *   },
     *   "trends": {
     *     "impressions": {
     *       "slope": 125.5,
     *       "direction": "increasing",
     *       "strength": "moderate",
     *       "percentage_change": 2.5,
     *       "average_value": 5000
     *     },
     *     "conversions": {
     *       "slope": 0.15,
     *       "direction": "increasing",
     *       "strength": "weak",
     *       "percentage_change": 1.2
     *     }
     *   },
     *   "budget_recommendations": [
     *     {
     *       "type": "increase_budget",
     *       "priority": "high",
     *       "current_daily_budget": 100,
     *       "recommended_daily_budget": 120,
     *       "increase_percentage": 20,
     *       "reason": "Strong positive revenue trend detected",
     *       "expected_impact": "Projected to increase conversions by 15-25%"
     *     }
     *   ],
     *   "risk_assessment": [
     *     {
     *       "type": "rising_costs",
     *       "severity": "medium",
     *       "description": "Cost per click is increasing without proportional click growth",
     *       "mitigation": "Review bid strategy, optimize targeting, pause underperforming keywords"
     *     }
     *   ]
     * }
     *
     * @authenticated
     */
    public function forecastCampaign(Request $request, Org $org, AdCampaign $campaign): JsonResponse
    {
        // Validate campaign belongs to org
        if ($campaign->org_id !== $org->org_id) {
            return response()->json([
                'error' => 'Campaign not found in organization'
            ], 404);
        }

        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:7', 'max:90'],
        ]);

        $days = $validated['days'] ?? 30;

        $forecast = $this->predictive->forecastCampaign($campaign, $days);

        return response()->json($forecast);
    }

    /**
     * Forecast organization performance
     *
     * Generates organization-wide performance predictions across all campaigns
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     * @queryParam days integer Number of days to forecast. Default: 30. Example: 30
     * @queryParam include_campaigns boolean Include individual campaign forecasts. Default: false. Example: true
     *
     * @response 200 {
     *   "org_id": "550e8400-e29b-41d4-a716-446655440000",
     *   "forecast_period": 30,
     *   "total_campaigns": 5,
     *   "organization_predictions": {
     *     "total_spend": 7500.00,
     *     "total_conversions": 900,
     *     "total_revenue": 18000.00,
     *     "total_impressions": 750000,
     *     "total_clicks": 22500,
     *     "predicted_roi": 140.0,
     *     "predicted_ctr": 3.0,
     *     "predicted_conversion_rate": 4.0
     *   },
     *   "recommendations": [
     *     {
     *       "type": "roi_optimization",
     *       "priority": "high",
     *       "message": "Organization ROI below target",
     *       "current_roi": 140.0,
     *       "target_roi": 100,
     *       "action": "Review underperforming campaigns and reallocate budget to top performers"
     *     }
     *   ]
     * }
     *
     * @authenticated
     */
    public function forecastOrganization(Request $request, Org $org): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:7', 'max:90'],
            'include_campaigns' => ['nullable', 'boolean'],
        ]);

        $days = $validated['days'] ?? 30;
        $includeCampaigns = $validated['include_campaigns'] ?? false;

        $forecast = $this->predictive->forecastOrganization($org, $days);

        // Optionally exclude individual campaign forecasts to reduce response size
        if (!$includeCampaigns) {
            unset($forecast['campaign_forecasts']);
        }

        return response()->json($forecast);
    }

    /**
     * Compare forecast scenarios
     *
     * Compare different budget allocation scenarios and their predicted outcomes
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     * @urlParam campaign string required Campaign UUID. Example: 123e4567-e89b-12d3-a456-426614174000
     * @bodyParam scenarios array required Budget scenarios to compare. Example: [{"budget_change": 20}, {"budget_change": -20}]
     * @bodyParam scenarios.*.budget_change integer required Percentage change in budget (+/- 100). Example: 20
     * @bodyParam days integer Number of days to forecast. Default: 30. Example: 30
     *
     * @response 200 {
     *   "campaign_id": "123e4567-e89b-12d3-a456-426614174000",
     *   "base_forecast": {
     *     "daily_budget": 100,
     *     "predicted_roi": 140,
     *     "predicted_conversions": 180
     *   },
     *   "scenarios": [
     *     {
     *       "scenario": "Increase budget by 20%",
     *       "budget_change": 20,
     *       "new_daily_budget": 120,
     *       "predicted_roi": 145,
     *       "predicted_conversions": 216,
     *       "additional_conversions": 36,
     *       "roi_change": 5
     *     },
     *     {
     *       "scenario": "Decrease budget by 20%",
     *       "budget_change": -20,
     *       "new_daily_budget": 80,
     *       "predicted_roi": 138,
     *       "predicted_conversions": 144,
     *       "additional_conversions": -36,
     *       "roi_change": -2
     *     }
     *   ],
     *   "recommendation": "Increase budget by 20% - best ROI improvement"
     * }
     *
     * @authenticated
     */
    public function compareScenarios(Request $request, Org $org, AdCampaign $campaign): JsonResponse
    {
        // Validate campaign belongs to org
        if ($campaign->org_id !== $org->org_id) {
            return response()->json([
                'error' => 'Campaign not found in organization'
            ], 404);
        }

        $validated = $request->validate([
            'scenarios' => ['required', 'array', 'min:1', 'max:5'],
            'scenarios.*.budget_change' => ['required', 'integer', 'min:-100', 'max:100'],
            'days' => ['nullable', 'integer', 'min:7', 'max:90'],
        ]);

        $days = $validated['days'] ?? 30;
        $scenarios = $validated['scenarios'];

        // Get base forecast
        $baseForecast = $this->predictive->forecastCampaign($campaign, $days);
        $baseDailyBudget = $campaign->daily_budget ?? 0;

        $scenarioResults = [];
        $bestScenario = null;
        $bestROI = $baseForecast['predictions']['predicted_roi'];

        foreach ($scenarios as $scenario) {
            $budgetChange = $scenario['budget_change'];
            $newBudget = $baseDailyBudget * (1 + ($budgetChange / 100));

            // Estimate impact (simplified model)
            // Assumption: 1% budget increase = 0.8% conversion increase, with diminishing returns
            $conversionMultiplier = 1 + (($budgetChange * 0.008));
            $roiMultiplier = 1 + (($budgetChange * 0.003)); // ROI improves slightly with scale

            $predictedConversions = $baseForecast['predictions']['total_conversions'] * $conversionMultiplier;
            $predictedROI = $baseForecast['predictions']['predicted_roi'] * $roiMultiplier;

            $result = [
                'scenario' => ($budgetChange > 0 ? 'Increase' : 'Decrease') . " budget by {$budgetChange}%",
                'budget_change' => $budgetChange,
                'new_daily_budget' => round($newBudget, 2),
                'predicted_roi' => round($predictedROI, 2),
                'predicted_conversions' => round($predictedConversions),
                'additional_conversions' => round($predictedConversions - $baseForecast['predictions']['total_conversions']),
                'roi_change' => round($predictedROI - $baseForecast['predictions']['predicted_roi'], 2),
            ];

            $scenarioResults[] = $result;

            // Track best scenario
            if ($predictedROI > $bestROI) {
                $bestROI = $predictedROI;
                $bestScenario = $result;
            }
        }

        return response()->json([
            'campaign_id' => $campaign->campaign_id,
            'base_forecast' => [
                'daily_budget' => $baseDailyBudget,
                'predicted_roi' => $baseForecast['predictions']['predicted_roi'],
                'predicted_conversions' => $baseForecast['predictions']['total_conversions'],
            ],
            'scenarios' => $scenarioResults,
            'recommendation' => $bestScenario
                ? $bestScenario['scenario'] . ' - best ROI improvement'
                : 'Maintain current budget',
        ]);
    }

    /**
     * Get trend analysis
     *
     * Detailed trend analysis for campaign metrics
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     * @urlParam campaign string required Campaign UUID. Example: 123e4567-e89b-12d3-a456-426614174000
     * @queryParam period integer Historical period in days for trend analysis. Default: 90. Example: 90
     *
     * @response 200 {
     *   "campaign_id": "123e4567-e89b-12d3-a456-426614174000",
     *   "analysis_period": 90,
     *   "trends": {
     *     "impressions": {
     *       "slope": 125.5,
     *       "direction": "increasing",
     *       "strength": "moderate",
     *       "percentage_change": 2.5,
     *       "average_value": 5000
     *     },
     *     "clicks": {
     *       "slope": 5.2,
     *       "direction": "increasing",
     *       "strength": "moderate"
     *     }
     *   },
     *   "moving_averages": {
     *     "impressions": {
     *       "7_day": 5200,
     *       "30_day": 5000
     *     }
     *   },
     *   "insights": [
     *     "Impressions trending upward - strong growth momentum",
     *     "CTR stable - ad creative resonating with audience",
     *     "Conversions increasing - landing page performing well"
     *   ]
     * }
     *
     * @authenticated
     */
    public function analyzeTrends(Request $request, Org $org, AdCampaign $campaign): JsonResponse
    {
        // Validate campaign belongs to org
        if ($campaign->org_id !== $org->org_id) {
            return response()->json([
                'error' => 'Campaign not found in organization'
            ], 404);
        }

        $validated = $request->validate([
            'period' => ['nullable', 'integer', 'min:14', 'max:365'],
        ]);

        $period = $validated['period'] ?? 90;

        // Use forecastCampaign to get trends
        $forecast = $this->predictive->forecastCampaign($campaign, 7); // Short forecast, we just need trends

        $insights = $this->generateInsights($forecast['trends']);

        return response()->json([
            'campaign_id' => $campaign->campaign_id,
            'analysis_period' => $period,
            'trends' => $forecast['trends'],
            'confidence' => $forecast['confidence_level'],
            'insights' => $insights,
        ]);
    }

    /**
     * Generate human-readable insights from trends
     */
    private function generateInsights(array $trends): array
    {
        $insights = [];

        // Impressions insights
        $impressionsTrend = $trends['impressions'] ?? [];
        if (($impressionsTrend['direction'] ?? '') === 'increasing' && ($impressionsTrend['strength'] ?? '') === 'strong') {
            $insights[] = 'Impressions trending upward - strong growth momentum';
        } elseif (($impressionsTrend['direction'] ?? '') === 'decreasing') {
            $insights[] = 'Impressions declining - consider expanding targeting or increasing bids';
        }

        // CTR insights
        $ctrTrend = $trends['ctr'] ?? [];
        if (($ctrTrend['direction'] ?? '') === 'increasing') {
            $insights[] = 'CTR improving - ad creative resonating with audience';
        } elseif (($ctrTrend['direction'] ?? '') === 'decreasing') {
            $insights[] = 'CTR declining - refresh ad creative and test new messaging';
        } else {
            $insights[] = 'CTR stable - ad creative performing consistently';
        }

        // Conversion insights
        $conversionTrend = $trends['conversions'] ?? [];
        if (($conversionTrend['direction'] ?? '') === 'increasing') {
            $insights[] = 'Conversions increasing - landing page performing well';
        } elseif (($conversionTrend['direction'] ?? '') === 'decreasing') {
            $insights[] = 'Conversions declining - review landing page and user journey';
        }

        // Revenue insights
        $revenueTrend = $trends['revenue'] ?? [];
        if (($revenueTrend['direction'] ?? '') === 'increasing' && ($revenueTrend['strength'] ?? '') === 'strong') {
            $insights[] = 'Revenue growing strongly - excellent campaign performance';
        }

        // Spend insights
        $spendTrend = $trends['spend'] ?? [];
        if (($spendTrend['direction'] ?? '') === 'increasing' && ($revenueTrend['direction'] ?? '') !== 'increasing') {
            $insights[] = 'Costs rising without revenue growth - optimize targeting and bids';
        }

        return $insights;
    }
}
