<?php

namespace App\Http\Controllers\Api\Listening;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Listening\SocialMention;
use App\Services\Listening\SocialListeningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Social Mention Controller
 *
 * Manages social media mentions captured by monitoring keywords
 */
class SocialMentionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SocialListeningService $listeningService
    ) {}

    /**
     * Get all mentions
     *
     * GET /api/listening/mentions
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $mentions = SocialMention::where('org_id', $orgId);

        if ($request->has('keyword_id')) {
            $mentions->where('keyword_id', $request->keyword_id);
        }

        if ($request->has('platform')) {
            $mentions->where('platform', $request->platform);
        }

        if ($request->has('sentiment')) {
            $mentions->where('sentiment', $request->sentiment);
        }

        if ($request->has('status')) {
            $mentions->where('status', $request->status);
        }

        $mentions = $mentions->with(['keyword', 'sentimentAnalysis'])
            ->recentFirst()
            ->paginate($request->per_page ?? 50);

        return $this->paginated($mentions, 'Mentions retrieved successfully');
    }

    /**
     * Get single mention details
     *
     * GET /api/listening/mentions/{id}
     */
    public function show(string $id): JsonResponse
    {
        $mention = SocialMention::with(['keyword', 'sentimentAnalysis', 'conversations'])
            ->findOrFail($id);

        return $this->success($mention, 'Mention retrieved successfully');
    }

    /**
     * Search mentions
     *
     * POST /api/listening/mentions/search
     */
    public function search(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $criteria = $request->only([
            'keyword',
            'content',
            'author',
            'platform',
            'sentiment',
            'start_date',
            'end_date',
            'min_engagement',
            'influencers_only',
        ]);

        $mentions = $this->listeningService->searchMentions($orgId, $criteria);

        return $this->success([
            'mentions' => $mentions,
            'count' => $mentions->count(),
        ], 'Mentions search completed');
    }

    /**
     * Update mention status
     *
     * PATCH /api/listening/mentions/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $mention = SocialMention::findOrFail($id);

        $validated = $request->validate([
            'status' => 'in:new,reviewed,responded,archived,flagged',
            'requires_response' => 'boolean',
            'assigned_to' => 'uuid|nullable',
            'internal_notes' => 'string|nullable',
        ]);

        $mention->update($validated);

        return $this->success($mention->fresh(), 'Mention updated successfully');
    }
}
