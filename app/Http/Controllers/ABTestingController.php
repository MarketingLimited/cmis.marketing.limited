<?php

namespace App\Http\Controllers;

use App\Services\ABTestingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * ABTestingController
 *
 * Handles A/B testing for ad campaigns
 * Implements Sprint 4.6: A/B Testing
 *
 * Features:
 * - Create and manage A/B tests
 * - Add variations
 * - Start/stop tests
 * - View results with statistical analysis
 * - Select winners
 */
class ABTestingController extends Controller
{
    use ApiResponse;

    protected ABTestingService $abTestingService;

    public function __construct(ABTestingService $abTestingService)
    {
        $this->abTestingService = $abTestingService;
    }

    /**
     * Create a new A/B test
     *
     * POST /api/orgs/{org_id}/ab-tests
     *
     * Request body:
     * {
     *   "ad_account_id": "uuid",
     *   "test_name": "Creative Test - Image vs Video",
     *   "test_type": "creative",
     *   "entity_type": "ad",
     *   "entity_id": "uuid",
     *   "metric_to_optimize": "ctr",
     *   "budget_per_variation": 100,
     *   "test_duration_days": 7,
     *   "variations": [
     *     {"variation_name": "Control - Image", "entity_id": "ad_uuid_1", "config": {}},
     *     {"variation_name": "Test - Video", "entity_id": "ad_uuid_2", "config": {}}
     *   ]
     * }
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function createTest(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ad_account_id' => 'required|uuid',
            'test_name' => 'required|string|max:255',
            'test_type' => 'required|in:creative,audience,placement,delivery_optimization',
            'entity_type' => 'nullable|in:ad,ad_set,campaign',
            'entity_id' => 'nullable|uuid',
            'metric_to_optimize' => 'nullable|in:ctr,conversion_rate,cpa,roas,cpc,cpm',
            'budget_per_variation' => 'nullable|numeric|min:1',
            'test_duration_days' => 'nullable|integer|min:1|max:90',
            'min_sample_size' => 'nullable|integer|min:100',
            'confidence_level' => 'nullable|numeric|min:0.8|max:0.99',
            'variations' => 'nullable|array|min:2',
            'variations.*.variation_name' => 'required|string',
            'variations.*.entity_id' => 'nullable|uuid',
            'variations.*.traffic_allocation' => 'nullable|integer|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->abTestingService->createABTest($request->all());

            return response()->json($result, $result['success'] ? 201 : 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create A/B test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add variation to existing test
     *
     * POST /api/orgs/{org_id}/ab-tests/{test_id}/variations
     *
     * Request body:
     * {
     *   "variation_name": "Variation B",
     *   "entity_id": "uuid",
     *   "traffic_allocation": 50,
     *   "config": {}
     * }
     *
     * @param string $orgId
     * @param string $testId
     * @param Request $request
     * @return JsonResponse
     */
    public function addVariation(string $orgId, string $testId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'variation_name' => 'required|string|max:255',
            'entity_id' => 'nullable|uuid',
            'traffic_allocation' => 'nullable|integer|min:0|max:100',
            'config' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->abTestingService->addVariation(
                $testId,
                $request->all(),
                $request->input('is_control', false)
            );

            return response()->json($result, $result['success'] ? 201 : 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add variation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start an A/B test
     *
     * POST /api/orgs/{org_id}/ab-tests/{test_id}/start
     *
     * @param string $orgId
     * @param string $testId
     * @return JsonResponse
     */
    public function startTest(string $orgId, string $testId): JsonResponse
    {
        try {
            $result = $this->abTestingService->startTest($testId);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop an A/B test
     *
     * POST /api/orgs/{org_id}/ab-tests/{test_id}/stop
     *
     * Request body:
     * {
     *   "reason": "Reached statistical significance"
     * }
     *
     * @param string $orgId
     * @param string $testId
     * @param Request $request
     * @return JsonResponse
     */
    public function stopTest(string $orgId, string $testId, Request $request): JsonResponse
    {
        try {
            $result = $this->abTestingService->stopTest(
                $testId,
                $request->input('reason')
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get test results with statistical analysis
     *
     * GET /api/orgs/{org_id}/ab-tests/{test_id}/results
     *
     * @param string $orgId
     * @param string $testId
     * @return JsonResponse
     */
    public function getResults(string $orgId, string $testId): JsonResponse
    {
        try {
            $result = $this->abTestingService->getTestResults($testId);

            return response()->json($result, $result['success'] ? 200 : 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get test results',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Select winning variation
     *
     * POST /api/orgs/{org_id}/ab-tests/{test_id}/select-winner
     *
     * Request body:
     * {
     *   "variation_id": "uuid" // Optional, will auto-select if not provided
     * }
     *
     * @param string $orgId
     * @param string $testId
     * @param Request $request
     * @return JsonResponse
     */
    public function selectWinner(string $orgId, string $testId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'variation_id' => 'nullable|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->abTestingService->selectWinner(
                $testId,
                $request->input('variation_id')
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to select winner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all A/B tests
     *
     * GET /api/orgs/{org_id}/ab-tests?ad_account_id=uuid&test_status=running
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function listTests(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ad_account_id' => 'nullable|uuid',
            'test_status' => 'nullable|in:draft,running,stopped,completed',
            'test_type' => 'nullable|in:creative,audience,placement,delivery_optimization',
            'entity_type' => 'nullable|in:ad,ad_set,campaign',
            'sort_by' => 'nullable|in:created_at,started_at,test_name',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->abTestingService->listTests($request->all());

            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list tests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extend test duration
     *
     * POST /api/orgs/{org_id}/ab-tests/{test_id}/extend
     *
     * Request body:
     * {
     *   "additional_days": 7
     * }
     *
     * @param string $orgId
     * @param string $testId
     * @param Request $request
     * @return JsonResponse
     */
    public function extendTest(string $orgId, string $testId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'additional_days' => 'required|integer|min:1|max:90'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->abTestingService->extendTest(
                $testId,
                $request->input('additional_days')
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to extend test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an A/B test (only draft status)
     *
     * DELETE /api/orgs/{org_id}/ab-tests/{test_id}
     *
     * @param string $orgId
     * @param string $testId
     * @return JsonResponse
     */
    public function deleteTest(string $orgId, string $testId): JsonResponse
    {
        try {
            $result = $this->abTestingService->deleteTest($testId);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete test',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
