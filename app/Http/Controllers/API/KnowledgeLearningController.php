<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Core\Org;
use App\Models\AdPlatform\AdCampaign;
use App\Services\AI\KnowledgeLearningService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Http\JsonResponse;

/**
 * @group Knowledge Learning
 * AI-powered learning system that analyzes historical data to identify patterns and best practices
 */
class KnowledgeLearningController extends Controller
{
    use ApiResponse;

    public function __construct(
        private KnowledgeLearningService $knowledge
    ) {}

    /**
     * Learn from organization's campaign history
     *
     * Analyzes all historical campaign data to identify patterns, best practices, and insights
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 200 {
     *   "org_id": "550e8400-e29b-41d4-a716-446655440000",
     *   "total_campaigns_analyzed": 25,
     *   "performance_patterns": {
     *     "platform_performance": {
     *       "meta": {
     *         "campaigns": 10,
     *         "avg_roi": 145.5,
     *         "avg_ctr": 3.2,
     *         "performance_rating": "excellent"
     *       },
     *       "google": {
     *         "campaigns": 8,
     *         "avg_roi": 98.2,
     *         "avg_ctr": 2.8,
     *         "performance_rating": "good"
     *       }
     *     },
     *     "objective_performance": {
     *       "conversions": {
     *         "campaigns": 15,
     *         "avg_roi": 120.5
     *       }
     *     },
     *     "budget_performance": {
     *       "medium": {
     *         "campaigns": 12,
     *         "avg_roi": 135.2
     *       }
     *     }
     *   },
     *   "best_practices": {
     *     "budget_range": {
     *       "min": 80,
     *       "max": 120,
     *       "optimal": 100,
     *       "recommendation": "Based on top performing campaigns"
     *     },
     *     "preferred_platforms": {
     *       "meta": 6,
     *       "google": 3,
     *       "tiktok": 1
     *     },
     *     "performance_benchmarks": {
     *       "target_ctr": 3.5,
     *       "target_conversion_rate": 4.2,
     *       "target_roi": 150.0
     *     },
     *     "key_recommendations": [
     *       "Replicate successful campaign structures",
     *       "Focus on platforms showing best results"
     *     ]
     *   },
     *   "success_factors": {
     *     "count": 8,
     *     "percentage": 32.0,
     *     "key_factors": {
     *       "budget_discipline": {
     *         "occurrence": "87.5%",
     *         "importance": "critical"
     *       },
     *       "good_targeting": {
     *         "occurrence": "75.0%",
     *         "importance": "high"
     *       }
     *     }
     *   },
     *   "failure_patterns": {
     *     "count": 5,
     *     "percentage": 20.0,
     *     "common_reasons": {
     *       "poor_targeting": 3,
     *       "weak_creative": 2
     *     },
     *     "mitigation_strategies": {
     *       "poor_targeting": "Refine audience parameters, use lookalike audiences"
     *     }
     *   },
     *   "recommendations": [
     *     {
     *       "type": "platform_focus",
     *       "priority": "high",
     *       "recommendation": "Focus more budget on meta",
     *       "reason": "Highest average ROI: 145.50%",
     *       "confidence": "high"
     *     }
     *   ],
     *   "insights": [
     *     {
     *       "category": "performance_distribution",
     *       "insight": "Campaign ROI Distribution",
     *       "interpretation": "Strong portfolio with 32.0% high-performing campaigns"
     *     }
     *   ]
     * }
     *
     * @authenticated
     */
    public function learnFromHistory(Org $org): JsonResponse
    {
        $learnings = $this->knowledge->learnOrganizationPatterns($org);

        return $this->success($learnings, 'Retrieved successfully');
    }

    /**
     * Get decision support
     *
     * Provides AI-powered recommendations for specific campaign decisions
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     * @urlParam campaign string required Campaign UUID. Example: 123e4567-e89b-12d3-a456-426614174000
     *
     * @bodyParam decision_type string required Type of decision. Example: budget_adjustment
     * @bodyParam options object Additional options for the decision. Example: {"proposed_budget": 150}
     *
     * @response 200 {
     *   "decision": "budget_adjustment",
     *   "current_budget": 100,
     *   "proposed_budget": 150,
     *   "change_percentage": 50,
     *   "recommendation": "APPROVE",
     *   "confidence": "high",
     *   "analysis": [
     *     "Campaign showing strong ROI - good candidate for budget increase",
     *     "Similar campaigns historically performed with 145.2% ROI"
     *   ]
     * }
     *
     * @authenticated
     */
    public function getDecisionSupport(Request $request, Org $org, AdCampaign $campaign): JsonResponse
    {
        // Validate campaign belongs to org
        if ($campaign->org_id !== $org->org_id) {
            return $this->notFound('Campaign not found in organization');
        }

        $validated = $request->validate([
            'decision_type' => ['required', 'string', 'in:budget_adjustment,pause_or_continue,creative_refresh,targeting_adjustment,bid_strategy'],
            'options' => ['nullable', 'array'],
        ]);

        $decisionType = $validated['decision_type'];
        $options = $validated['options'] ?? [];

        $support = $this->knowledge->getDecisionSupport($campaign, $decisionType, $options);

        return $this->success($support, 'Retrieved successfully');
    }

    /**
     * Get best practices
     *
     * Extract best practices from top-performing campaigns
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 200 {
     *   "best_practices": {
     *     "budget_range": {
     *       "optimal": 100,
     *       "min": 80,
     *       "max": 120
     *     },
     *     "performance_benchmarks": {
     *       "target_ctr": 3.5,
     *       "target_conversion_rate": 4.2,
     *       "target_roi": 150.0
     *     },
     *     "key_recommendations": [
     *       "Replicate successful campaign structures"
     *     ]
     *   }
     * }
     *
     * @authenticated
     */
    public function getBestPractices(Org $org): JsonResponse
    {
        $learnings = $this->knowledge->learnOrganizationPatterns($org);

        return response()->json([
            'best_practices' => $learnings['best_practices'],
            'success_factors' => $learnings['success_factors'],
        ]);
    }

    /**
     * Get performance insights
     *
     * Automated insights from campaign performance data
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 200 {
     *   "insights": [
     *     {
     *       "category": "performance_distribution",
     *       "insight": "Campaign ROI Distribution",
     *       "data": {
     *         "negative": 2,
     *         "low": 5,
     *         "medium": 10,
     *         "high": 8
     *       },
     *       "interpretation": "Strong portfolio with 32.0% high-performing campaigns"
     *     },
     *     {
     *       "category": "opportunities",
     *       "insight": "Growth Opportunities Identified",
     *       "data": [
     *         {
     *           "campaign_id": "xxx",
     *           "type": "scale_budget",
     *           "message": "Excellent ROI with room to increase budget"
     *         }
     *       ],
     *       "actionable": true
     *     }
     *   ],
     *   "recommendations": [
     *     {
     *       "type": "platform_focus",
     *       "priority": "high",
     *       "recommendation": "Focus more budget on meta"
     *     }
     *   ]
     * }
     *
     * @authenticated
     */
    public function getInsights(Org $org): JsonResponse
    {
        $learnings = $this->knowledge->learnOrganizationPatterns($org);

        return response()->json([
            'insights' => $learnings['insights'],
            'recommendations' => $learnings['recommendations'],
            'performance_patterns' => $learnings['performance_patterns'],
        ]);
    }

    /**
     * Get failure patterns
     *
     * Identify common patterns in underperforming campaigns
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @response 200 {
     *   "failure_patterns": {
     *     "count": 5,
     *     "percentage": 20.0,
     *     "common_reasons": {
     *       "poor_targeting": 3,
     *       "weak_creative": 2,
     *       "high_cpc": 1
     *     },
     *     "mitigation_strategies": {
     *       "poor_targeting": "Refine audience parameters, use lookalike audiences from converters",
     *       "weak_creative": "A/B test new ad creative, improve messaging and visuals"
     *     }
     *   },
     *   "at_risk_campaigns": [
     *     {
     *       "campaign_id": "xxx",
     *       "risk_type": "high_loss",
     *       "severity": "critical"
     *     }
     *   ]
     * }
     *
     * @authenticated
     */
    public function getFailurePatterns(Org $org): JsonResponse
    {
        $learnings = $this->knowledge->learnOrganizationPatterns($org);

        return response()->json([
            'failure_patterns' => $learnings['failure_patterns'],
        ]);
    }
