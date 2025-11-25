<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\AudienceTargetingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * AudienceController
 *
 * Handles audience targeting and management
 * Implements Sprint 4.3: Targeting & Audiences
 *
 * Features:
 * - Audience CRUD operations
 * - Targeting specification management
 * - Lookalike audience creation
 * - Audience insights and size estimation
 * - Targeting suggestions
 */
class AudienceController extends Controller
{
    use ApiResponse;

    protected AudienceTargetingService $audienceService;

    public function __construct(AudienceTargetingService $audienceService)
    {
        $this->audienceService = $audienceService;
    }

    /**
     * Create new audience
     *
     * POST /api/orgs/{org_id}/audiences
     *
     * Request body:
     * {
     *   "ad_account_id": "uuid",
     *   "platform": "meta|google|linkedin",
     *   "audience_name": "string",
     *   "audience_type": "saved|custom|lookalike",
     *   "geo_locations": {"countries": ["US", "CA"]},
     *   "age_min": 25,
     *   "age_max": 54,
     *   "genders": [1, 2],
     *   "interests": ["technology", "business"],
     *   "behaviors": ["online_shoppers"],
     *   "sync_to_platform": true
     * }
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function create(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ad_account_id' => 'required|uuid',
            'platform' => 'required|in:meta,google,linkedin,twitter,tiktok',
            'audience_name' => 'required|string|max:255',
            'audience_type' => 'nullable|in:saved,custom,lookalike',
            'geo_locations' => 'nullable|array',
            'age_min' => 'nullable|integer|min:13|max:65',
            'age_max' => 'nullable|integer|min:13|max:65',
            'genders' => 'nullable|array',
            'languages' => 'nullable|array',
            'interests' => 'nullable|array',
            'behaviors' => 'nullable|array',
            'detailed_targeting' => 'nullable|array',
            'custom_audiences' => 'nullable|array',
            'device_platforms' => 'nullable|array',
            'exclusions' => 'nullable|array',
            'lookalike_source' => 'nullable|uuid',
            'lookalike_ratio' => 'nullable|numeric|min:0.01|max:10',
            'custom_audience_source' => 'nullable|array',
            'retention_days' => 'nullable|integer|min:1|max:540',
            'metadata' => 'nullable|array',
            'sync_to_platform' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->audienceService->createAudience($request->all());

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create audience',
                    'error' => $result['error']
                ], 500);
            }

            return response()->json($result, 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create audience',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get audience details
     *
     * GET /api/orgs/{org_id}/audiences/{audience_id}?include_insights=true
     *
     * @param string $orgId
     * @param string $audienceId
     * @param Request $request
     * @return JsonResponse
     */
    public function show(string $orgId, string $audienceId, Request $request): JsonResponse
    {
        try {
            $includeInsights = $request->boolean('include_insights', false);
            $result = $this->audienceService->getAudience($audienceId, $includeInsights);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Audience not found',
                    'error' => $result['error']
                ], 404);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get audience',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List audiences with filters
     *
     * GET /api/orgs/{org_id}/audiences?platform=meta&audience_type=saved&search=tech
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function index(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ad_account_id' => 'nullable|uuid',
            'platform' => 'nullable|in:meta,google,linkedin,twitter,tiktok',
            'audience_type' => 'nullable|in:saved,custom,lookalike',
            'status' => 'nullable|in:active,paused,archived',
            'search' => 'nullable|string',
            'sort_by' => 'nullable|in:created_at,audience_name,audience_size',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->audienceService->listAudiences($request->all());

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list audiences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update audience
     *
     * PUT /api/orgs/{org_id}/audiences/{audience_id}
     *
     * @param string $orgId
     * @param string $audienceId
     * @param Request $request
     * @return JsonResponse
     */
    public function update(string $orgId, string $audienceId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'audience_name' => 'nullable|string|max:255',
            'geo_locations' => 'nullable|array',
            'age_min' => 'nullable|integer|min:13|max:65',
            'age_max' => 'nullable|integer|min:13|max:65',
            'genders' => 'nullable|array',
            'interests' => 'nullable|array',
            'behaviors' => 'nullable|array',
            'exclusions' => 'nullable|array',
            'status' => 'nullable|in:active,paused,archived',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->audienceService->updateAudience($audienceId, $request->all());

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update audience',
                    'error' => $result['error']
                ], 500);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update audience',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete audience
     *
     * DELETE /api/orgs/{org_id}/audiences/{audience_id}?permanent=false
     *
     * @param string $orgId
     * @param string $audienceId
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(string $orgId, string $audienceId, Request $request): JsonResponse
    {
        try {
            $permanent = $request->boolean('permanent', false);
            $success = $this->audienceService->deleteAudience($audienceId, $permanent);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Audience deleted successfully'
                ]);
            }

            return $this->error('Failed to delete audience', 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete audience',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create lookalike audience
     *
     * POST /api/orgs/{org_id}/audiences/{source_audience_id}/lookalike
     *
     * Request body:
     * {
     *   "audience_name": "Lookalike - High Value Customers",
     *   "lookalike_ratio": 1.0,
     *   "geo_locations": {"countries": ["US"]},
     *   "sync_to_platform": true
     * }
     *
     * @param string $orgId
     * @param string $sourceAudienceId
     * @param Request $request
     * @return JsonResponse
     */
    public function createLookalike(string $orgId, string $sourceAudienceId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'audience_name' => 'nullable|string|max:255',
            'lookalike_ratio' => 'nullable|numeric|min:0.01|max:10',
            'geo_locations' => 'required|array',
            'sync_to_platform' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->audienceService->createLookalikeAudience($sourceAudienceId, $request->all());

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create lookalike audience',
                    'error' => $result['error']
                ], 500);
            }

            return response()->json($result, 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lookalike audience',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estimate audience size
     *
     * POST /api/orgs/{org_id}/audiences/estimate-size
     *
     * Request body:
     * {
     *   "platform": "meta",
     *   "geo_locations": {"countries": ["US"]},
     *   "age_min": 25,
     *   "age_max": 54,
     *   "interests": ["technology"]
     * }
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function estimateSize(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:meta,google,linkedin,twitter,tiktok',
            'geo_locations' => 'nullable|array',
            'age_min' => 'nullable|integer|min:13|max:65',
            'age_max' => 'nullable|integer|min:13|max:65',
            'genders' => 'nullable|array',
            'interests' => 'nullable|array',
            'behaviors' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $targetingSpec = $request->except('platform');
            $estimatedSize = $this->audienceService->estimateAudienceSize(
                $targetingSpec,
                $request->input('platform')
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'estimated_size' => $estimatedSize,
                    'min_estimate' => (int)($estimatedSize * 0.7),
                    'max_estimate' => (int)($estimatedSize * 1.3),
                    'targeting_spec' => $targetingSpec,
                    'note' => 'Estimates require platform API integration for accuracy'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to estimate audience size',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get targeting suggestions
     *
     * GET /api/orgs/{org_id}/audiences/targeting-suggestions?objective=leads&platform=meta
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function targetingSuggestions(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'objective' => 'required|in:awareness,traffic,engagement,leads,sales',
            'platform' => 'required|in:meta,google,linkedin,twitter,tiktok'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->audienceService->getTargetingSuggestions(
                $request->input('objective'),
                $request->input('platform')
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get targeting suggestions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
