<?php

namespace App\Http\Controllers;

use App\Services\BestTimeAnalyzerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * BestTimeController
 *
 * Provides insights on best posting times based on historical engagement
 * Implements Sprint 2.3: AI-Suggested Timing
 */
class BestTimeController extends Controller
{
    use ApiResponse;

    protected BestTimeAnalyzerService $analyzer;

    public function __construct(BestTimeAnalyzerService $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    /**
     * Get best posting times for account
     *
     * GET /api/orgs/{org_id}/best-times/{social_account_id}?lookback_days=30&top_n=10
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function analyze(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lookback_days' => 'nullable|integer|min:7|max:90',
            'top_n' => 'nullable|integer|min:1|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $lookbackDays = $request->input('lookback_days', 30);
        $topN = $request->input('top_n', 10);

        $analysis = $this->analyzer->analyzeBestTimes($socialAccountId, $lookbackDays, $topN);

        return response()->json($analysis);
    }

    /**
     * Get simple recommendations
     *
     * GET /api/orgs/{org_id}/best-times/{social_account_id}/recommendations
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function recommendations(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $lookbackDays = $request->input('lookback_days', 30);

        $recommendations = $this->analyzer->getRecommendations($socialAccountId, $lookbackDays);

        return $this->success($recommendations
        );
    }

    /**
     * Compare actual vs recommended posting times
     *
     * GET /api/orgs/{org_id}/best-times/{social_account_id}/compare?start=2025-01-01&end=2025-01-31
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function compare(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start' => 'required|date',
            'end' => 'required|date|after:start'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $dateRange = [
            'start' => $request->input('start'),
            'end' => $request->input('end')
        ];

        $comparison = $this->analyzer->compareActualVsRecommended($socialAccountId, $dateRange);

        return response()->json($comparison);
    }

    /**
     * Get audience activity patterns
     *
     * GET /api/orgs/{org_id}/best-times/{social_account_id}/audience-activity
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @return JsonResponse
     */
    public function audienceActivity(string $orgId, string $socialAccountId): JsonResponse
    {
        $patterns = $this->analyzer->getAudienceActivityPatterns($socialAccountId);

        return $this->success($patterns
        );
    }
}
