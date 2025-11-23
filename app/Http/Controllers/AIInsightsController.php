<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\AIInsightsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * AIInsightsController
 *
 * Provides AI-powered insights and recommendations
 * Implements Sprint 3.3: AI Insights
 *
 * Features:
 * - Content optimization recommendations
 * - Anomaly detection
 * - Predictive analytics
 * - Smart observations
 * - Optimization opportunities
 * - Competitive intelligence
 */
class AIInsightsController extends Controller
{
    use ApiResponse;

    protected AIInsightsService $insightsService;

    public function __construct(AIInsightsService $insightsService)
    {
        $this->insightsService = $insightsService;
    }

    /**
     * Get comprehensive AI insights for account
     *
     * GET /api/orgs/{org_id}/ai/insights/{social_account_id}?start_date=2025-01-01&end_date=2025-01-31
     *
     * Returns all AI-powered insights including:
     * - Content recommendations
     * - Anomaly detection
     * - Performance predictions
     * - Smart observations
     * - Optimization opportunities
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function accountInsights(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ];

        try {
            $insights = $this->insightsService->getAccountInsights($socialAccountId, $filters);

            return $this->success($insights, 'Retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to load AI insights: ' . $e->getMessage());
        }
    }

    /**
     * Get content recommendations only
     *
     * GET /api/orgs/{org_id}/ai/insights/{social_account_id}/recommendations?start_date=2025-01-01&end_date=2025-01-31
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function contentRecommendations(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ];

        try {
            $insights = $this->insightsService->getAccountInsights($socialAccountId, $filters);

            if (!$insights['success']) {
                return $this->error('Failed to generate recommendations', 500);
            }

            return $this->success([
                'recommendations' => $insights['insights']['content_recommendations'] ?? [],
                'generated_at' => $insights['generated_at'] ?? now()->toIso8601String()
            ], 'Content recommendations retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to load content recommendations: ' . $e->getMessage());
        }
    }

    /**
     * Get anomaly detection results
     *
     * GET /api/orgs/{org_id}/ai/insights/{social_account_id}/anomalies?start_date=2025-01-01&end_date=2025-01-31
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function anomalyDetection(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ];

        try {
            $insights = $this->insightsService->getAccountInsights($socialAccountId, $filters);

            if (!$insights['success']) {
                return $this->error('Failed to detect anomalies', 500);
            }

            return $this->success([
                'anomalies' => $insights['insights']['anomalies'] ?? [],
                'count' => count($insights['insights']['anomalies'] ?? []),
                'generated_at' => $insights['generated_at'] ?? now()->toIso8601String()
            ], 'Anomalies detected successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to detect anomalies: ' . $e->getMessage());
        }
    }

    /**
     * Get performance predictions
     *
     * GET /api/orgs/{org_id}/ai/insights/{social_account_id}/predictions
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function predictions(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        try {
            $insights = $this->insightsService->getAccountInsights($socialAccountId, []);

            if (!$insights['success']) {
                return $this->error('Failed to generate predictions', 500);
            }

            return $this->success([
                'predictions' => $insights['insights']['predictions'] ?? [],
                'generated_at' => $insights['generated_at'] ?? now()->toIso8601String()
            ], 'Predictions generated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to load predictions: ' . $e->getMessage());
        }
    }

    /**
     * Get smart observations
     *
     * GET /api/orgs/{org_id}/ai/insights/{social_account_id}/observations?start_date=2025-01-01&end_date=2025-01-31
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function observations(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ];

        try {
            $insights = $this->insightsService->getAccountInsights($socialAccountId, $filters);

            if (!$insights['success']) {
                return $this->error('Failed to generate observations', 500);
            }

            return $this->success([
                'observations' => $insights['insights']['observations'] ?? [],
                'generated_at' => $insights['generated_at'] ?? now()->toIso8601String()
            ], 'Observations retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to load observations: ' . $e->getMessage());
        }
    }

    /**
     * Get optimization opportunities
     *
     * GET /api/orgs/{org_id}/ai/insights/{social_account_id}/opportunities?start_date=2025-01-01&end_date=2025-01-31
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function optimizationOpportunities(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ];

        try {
            $insights = $this->insightsService->getAccountInsights($socialAccountId, $filters);

            if (!$insights['success']) {
                return $this->error('Failed to identify opportunities', 500);
            }

            return $this->success([
                'opportunities' => $insights['insights']['optimization_opportunities'] ?? [],
                'generated_at' => $insights['generated_at'] ?? now()->toIso8601String()
            ], 'Optimization opportunities identified successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to load optimization opportunities: ' . $e->getMessage());
        }
    }

    /**
     * Get competitive intelligence insights
     *
     * GET /api/orgs/{org_id}/ai/insights/{social_account_id}/competitive
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function competitiveInsights(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date']);
            $insights = $this->insightsService->getCompetitiveInsights($socialAccountId, $filters);

            return $this->success($insights, 'Retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to load competitive insights: ' . $e->getMessage());
        }
    }

    /**
     * Get insights summary for dashboard widget
     *
     * GET /api/orgs/{org_id}/ai/insights/{social_account_id}/summary
     *
     * Returns a condensed version of insights for dashboard display
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function insightsSummary(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        try {
            $insights = $this->insightsService->getAccountInsights($socialAccountId, []);

            if (!$insights['success']) {
                return $this->error('Failed to generate insights summary', 500);
            }

            // Extract top priority items from each category
            $recommendations = $insights['insights']['content_recommendations'] ?? [];
            $anomalies = $insights['insights']['anomalies'] ?? [];
            $predictions = $insights['insights']['predictions'] ?? [];
            $opportunities = $insights['insights']['optimization_opportunities'] ?? [];

            $summary = [
                'top_recommendation' => !empty($recommendations) && isset($recommendations[0])
                    ? $recommendations[0]
                    : null,
                'critical_anomalies' => array_filter($anomalies, fn($a) => $a['severity'] === 'high'),
                'key_prediction' => !empty($predictions) && isset($predictions[0])
                    ? $predictions[0]
                    : null,
                'priority_opportunity' => !empty($opportunities) && isset($opportunities[0])
                    ? $opportunities[0]
                    : null,
                'counts' => [
                    'total_recommendations' => count($recommendations),
                    'total_anomalies' => count($anomalies),
                    'total_predictions' => count($predictions),
                    'total_opportunities' => count($opportunities)
                ]
            ];

            return $this->success([
                'summary' => $summary,
                'generated_at' => $insights['generated_at'] ?? now()->toIso8601String()
            ], 'Insights summary retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to load insights summary: ' . $e->getMessage());
        }
    }
}
