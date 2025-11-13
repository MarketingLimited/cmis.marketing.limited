<?php

namespace App\Http\Controllers;

use App\Services\AdCreativeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * AdCreativeController
 *
 * Handles ad creative management and AI generation
 * Implements Sprint 4.2: Ad Creative Builder
 */
class AdCreativeController extends Controller
{
    protected AdCreativeService $creativeService;

    public function __construct(AdCreativeService $creativeService)
    {
        $this->creativeService = $creativeService;
    }

    /**
     * Create new ad creative
     * POST /api/orgs/{org_id}/ad-creatives
     */
    public function create(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:meta,google,linkedin,twitter,tiktok',
            'ad_name' => 'required|string|max:255',
            'ad_type' => 'required|in:single_image,video,carousel,collection',
            'ad_set_id' => 'nullable|uuid',
            'headline' => 'required|string|max:255',
            'description' => 'nullable|string',
            'call_to_action' => 'nullable|string',
            'destination_url' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->creativeService->createCreative($request->all());
        return response()->json($result, $result['success'] ? 201 : 500);
    }

    /**
     * Get creative details
     * GET /api/orgs/{org_id}/ad-creatives/{creative_id}
     */
    public function show(string $orgId, string $creativeId): JsonResponse
    {
        $result = $this->creativeService->getCreative($creativeId);
        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * List creatives
     * GET /api/orgs/{org_id}/ad-creatives
     */
    public function index(string $orgId, Request $request): JsonResponse
    {
        $result = $this->creativeService->listCreatives($request->all());
        return response()->json($result);
    }

    /**
     * Update creative
     * PUT /api/orgs/{org_id}/ad-creatives/{creative_id}
     */
    public function update(string $orgId, string $creativeId, Request $request): JsonResponse
    {
        $result = $this->creativeService->updateCreative($creativeId, $request->all());
        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Delete creative
     * DELETE /api/orgs/{org_id}/ad-creatives/{creative_id}
     */
    public function destroy(string $orgId, string $creativeId): JsonResponse
    {
        $success = $this->creativeService->deleteCreative($creativeId);
        return response()->json(['success' => $success], $success ? 200 : 500);
    }

    /**
     * Create variations
     * POST /api/orgs/{org_id}/ad-creatives/{creative_id}/variations
     */
    public function createVariations(string $orgId, string $creativeId, Request $request): JsonResponse
    {
        $result = $this->creativeService->createVariations($creativeId, $request->input('variations', []));
        return response()->json($result, $result['success'] ? 201 : 500);
    }

    /**
     * Generate AI creative
     * POST /api/orgs/{org_id}/ad-creatives/ai-generate
     */
    public function generateAI(string $orgId, Request $request): JsonResponse
    {
        $result = $this->creativeService->generateAICreative($request->all());
        return response()->json($result);
    }

    /**
     * Get templates
     * GET /api/orgs/{org_id}/ad-creatives/templates
     */
    public function templates(string $orgId, Request $request): JsonResponse
    {
        $result = $this->creativeService->getTemplates($request->all());
        return response()->json($result);
    }
}
