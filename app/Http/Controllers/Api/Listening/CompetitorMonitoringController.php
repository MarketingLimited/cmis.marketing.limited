<?php

namespace App\Http\Controllers\Api\Listening;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Listening\CompetitorProfile;
use App\Services\Listening\CompetitorMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Competitor Monitoring Controller
 *
 * Manages competitor profiles and competitive intelligence
 */
class CompetitorMonitoringController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CompetitorMonitoringService $competitorService
    ) {}

    /**
     * Get competitors
     *
     * GET /api/listening/competitors
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $competitors = CompetitorProfile::where('org_id', $orgId);

        if ($request->has('status')) {
            $competitors->where('status', $request->status);
        }

        $competitors = $competitors->orderBy('competitor_name')->get();

        return $this->success($competitors, 'Competitors retrieved successfully');
    }

    /**
     * Create competitor
     *
     * POST /api/listening/competitors
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'competitor_name' => 'required|string|max:255',
            'industry' => 'string|max:100',
            'description' => 'string',
            'website' => 'url|nullable',
            'social_accounts' => 'array',
        ]);

        $competitor = $this->competitorService->createCompetitor(
            $request->user()->org_id,
            $request->user()->id,
            $validated
        );

        return $this->created($competitor, 'Competitor created successfully');
    }

    /**
     * Analyze competitor
     *
     * POST /api/listening/competitors/{id}/analyze
     */
    public function analyze(string $id): JsonResponse
    {
        $competitor = CompetitorProfile::findOrFail($id);

        $results = $this->competitorService->analyzeCompetitor($competitor);
        $insights = $this->competitorService->getInsights($competitor);

        return $this->success([
            'analysis' => $results,
            'insights' => $insights,
        ], 'Competitor analysis completed');
    }

    /**
     * Compare competitors
     *
     * POST /api/listening/competitors/compare
     */
    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'competitor_ids' => 'required|array|min:2',
            'competitor_ids.*' => 'uuid',
        ]);

        $comparison = $this->competitorService->compareCompetitors(
            $request->user()->org_id,
            $validated['competitor_ids']
        );

        return $this->success($comparison, 'Competitor comparison completed');
    }
}
