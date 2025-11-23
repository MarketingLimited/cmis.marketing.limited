<?php

namespace App\Http\Controllers\Api\Listening;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Listening\MonitoringKeyword;
use App\Services\Listening\SocialListeningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Monitoring Keyword Controller
 *
 * Manages social media monitoring keywords and their configurations
 */
class MonitoringKeywordController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SocialListeningService $listeningService
    ) {}

    /**
     * Get all monitoring keywords
     *
     * GET /api/listening/keywords
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $keywords = MonitoringKeyword::where('org_id', $orgId);

        if ($request->has('status')) {
            $keywords->where('status', $request->status);
        }

        if ($request->has('type')) {
            $keywords->where('keyword_type', $request->type);
        }

        $keywords = $keywords->orderBy('created_at', 'desc')->get();

        return $this->success($keywords, 'Keywords retrieved successfully');
    }

    /**
     * Create monitoring keyword
     *
     * POST /api/listening/keywords
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'keyword_type' => 'required|in:brand,hashtag,keyword,phrase,mention',
            'variations' => 'array',
            'case_sensitive' => 'boolean',
            'platforms' => 'array',
            'enable_alerts' => 'boolean',
            'alert_threshold' => 'in:low,medium,high',
            'alert_conditions' => 'array',
        ]);

        $keyword = $this->listeningService->createKeyword(
            $request->user()->org_id,
            $request->user()->id,
            $validated
        );

        return $this->created($keyword, 'Keyword created successfully');
    }

    /**
     * Show single monitoring keyword
     *
     * GET /api/listening/keywords/{id}
     */
    public function show(string $id): JsonResponse
    {
        $keyword = MonitoringKeyword::findOrFail($id);

        return $this->success($keyword, 'Keyword retrieved successfully');
    }

    /**
     * Update monitoring keyword
     *
     * PUT/PATCH /api/listening/keywords/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $keyword = MonitoringKeyword::findOrFail($id);

        $validated = $request->validate([
            'keyword' => 'string|max:255',
            'variations' => 'array',
            'platforms' => 'array',
            'enable_alerts' => 'boolean',
            'status' => 'in:active,paused,archived',
        ]);

        $keyword = $this->listeningService->updateKeyword($keyword, $validated);

        return $this->success($keyword, 'Keyword updated successfully');
    }

    /**
     * Delete monitoring keyword
     *
     * DELETE /api/listening/keywords/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $keyword = MonitoringKeyword::findOrFail($id);
        $keyword->delete();

        return $this->deleted('Keyword deleted successfully');
    }
}
