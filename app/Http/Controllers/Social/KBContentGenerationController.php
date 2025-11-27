<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\Social\KnowledgeBaseContentGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * KB-Enhanced Content Generation Controller
 *
 * Generates AI content informed by brand knowledge base.
 */
class KBContentGenerationController extends Controller
{
    use ApiResponse;

    private KnowledgeBaseContentGenerationService $kbContentService;

    public function __construct(KnowledgeBaseContentGenerationService $kbContentService)
    {
        $this->kbContentService = $kbContentService;
    }

    /**
     * Generate social post using KB insights
     *
     * POST /api/social/kb-content/generate-post
     */
    public function generatePost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_group_id' => 'required|uuid',
            'objective' => 'required|string',
            'platform' => 'required|string|in:instagram,facebook,twitter,linkedin,tiktok',
            'topic' => 'nullable|string|max:500',
            'creativity' => 'nullable|numeric|min:0|max:1',
            'include_cta' => 'nullable|boolean',
            'include_hashtags' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = session('current_org_id');

        try {
            $result = $this->kbContentService->generateSocialPost(
                $orgId,
                $request->profile_group_id,
                $request->objective,
                $request->platform,
                $request->get('topic'),
                $request->only(['creativity', 'include_cta', 'include_hashtags'])
            );

            return $this->success($result, 'Content generated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to generate content: ' . $e->getMessage());
        }
    }

    /**
     * Generate ad copy using KB insights
     *
     * POST /api/social/kb-content/generate-ad-copy
     */
    public function generateAdCopy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_group_id' => 'required|uuid',
            'objective' => 'required|string',
            'product_description' => 'required|string',
            'platform' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = session('current_org_id');

        try {
            $result = $this->kbContentService->generateAdCopy(
                $orgId,
                $request->profile_group_id,
                $request->objective,
                $request->product_description,
                $request->only(['platform'])
            );

            return $this->success($result, 'Ad copy generated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to generate ad copy: ' . $e->getMessage());
        }
    }

    /**
     * Generate content variations
     *
     * POST /api/social/kb-content/generate-variations
     */
    public function generateVariations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_group_id' => 'required|uuid',
            'content' => 'required|string',
            'count' => 'nullable|integer|min:1|max:10',
            'objective' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = session('current_org_id');

        try {
            $result = $this->kbContentService->generateVariations(
                $orgId,
                $request->profile_group_id,
                $request->content,
                $request->get('count', 3),
                $request->get('objective')
            );

            return $this->success($result, 'Variations generated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to generate variations: ' . $e->getMessage());
        }
    }

    /**
     * Get content suggestions based on KB
     *
     * GET /api/social/kb-content/suggestions
     */
    public function getSuggestions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_group_id' => 'required|uuid',
            'objective' => 'required|string',
            'platform' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = session('current_org_id');

        try {
            $suggestions = $this->kbContentService->getContentSuggestions(
                $orgId,
                $request->profile_group_id,
                $request->objective,
                $request->get('platform')
            );

            return $this->success($suggestions, 'Content suggestions retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get suggestions: ' . $e->getMessage());
        }
    }

    /**
     * Analyze content fit with brand DNA
     *
     * POST /api/social/kb-content/analyze-fit
     */
    public function analyzeContentFit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_group_id' => 'required|uuid',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = session('current_org_id');

        try {
            $analysis = $this->kbContentService->analyzeContentFit(
                $orgId,
                $request->profile_group_id,
                $request->content
            );

            return $this->success($analysis, 'Content analyzed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to analyze content: ' . $e->getMessage());
        }
    }
}
