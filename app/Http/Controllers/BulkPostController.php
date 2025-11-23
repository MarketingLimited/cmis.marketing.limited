<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\ApiResponse;

use App\Services\BulkPostService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * BulkPostController
 *
 * Handles bulk post creation and management (Buffer-style bulk composer)
 * Implements Sprint 2.2: Bulk Compose
 *
 * Features:
 * - Create multiple posts from template
 * - CSV/Excel import
 * - Bulk editing
 * - Bulk deletion
 * - AI-powered variations
 */
class BulkPostController extends Controller
{
    use ApiResponse;

    protected BulkPostService $bulkPostService;

    public function __construct(BulkPostService $bulkPostService)
    {
        $this->bulkPostService = $bulkPostService;
    }

    /**
     * Create multiple posts from template
     *
     * POST /api/orgs/{org_id}/bulk-posts/create
     *
     * Request body:
     * {
     *   "template": {
     *     "content": "Check out our new product!",
     *     "platform": "facebook",
     *     "post_type": "text",
     *     "media_urls": ["https://example.com/image.jpg"],
     *     "hashtags": ["#newproduct", "#sale"]
     *   },
     *   "accounts": ["uuid1", "uuid2", "uuid3"],
     *   "options": {
     *     "auto_schedule": true,
     *     "use_ai_variations": false,
     *     "variation_style": "moderate"
     *   }
     * }
     *
     * @param Request $request
     * @param string $orgId
     * @return JsonResponse
     */
    public function createBulk(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template' => 'required|array',
            'template.content' => 'required|string|max:5000',
            'template.platform' => 'nullable|string|in:facebook,instagram,twitter,linkedin,tiktok',
            'template.post_type' => 'nullable|string|in:text,image,video,link,carousel',
            'template.media_urls' => 'nullable|array',
            'template.hashtags' => 'nullable|array',
            'accounts' => 'required|array|min:1',
            'accounts.*' => 'uuid',
            'options' => 'nullable|array',
            'options.auto_schedule' => 'nullable|boolean',
            'options.use_ai_variations' => 'nullable|boolean',
            'options.variation_style' => 'nullable|string|in:conservative,moderate,creative'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()
            , 'Validation error');
        }

        try {
            $result = $this->bulkPostService->createBulkPosts(
                $orgId,
                $request->input('template'),
                $request->input('accounts'),
                $request->input('options', [])
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully created {$result['created']} posts",
                'data' => $result
            ], 201);

        } catch (\Exception $e) {
            return $this->serverError('Failed to create bulk posts',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Import posts from CSV
     *
     * POST /api/orgs/{org_id}/bulk-posts/import-csv
     *
     * Request body:
     * {
     *   "csv_data": [
     *     {
     *       "content": "Post 1",
     *       "social_account_id": "uuid",
     *       "platform": "facebook",
     *       "scheduled_for": "2025-11-15 14:00:00",
     *       "hashtags": "#tag1,#tag2"
     *     }
     *   ]
     * }
     *
     * @param Request $request
     * @param string $orgId
     * @return JsonResponse
     */
    public function importCSV(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'csv_data' => 'required|array|min:1',
            'csv_data.*.content' => 'required|string|max:5000',
            'csv_data.*.social_account_id' => 'required|uuid',
            'csv_data.*.platform' => 'nullable|string',
            'csv_data.*.post_type' => 'nullable|string',
            'csv_data.*.scheduled_for' => 'nullable|date',
            'csv_data.*.hashtags' => 'nullable|string',
            'csv_data.*.media_urls' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()
            , 'Validation error');
        }

        try {
            $result = $this->bulkPostService->createFromCSV(
                $orgId,
                $request->input('csv_data')
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$result['created']} posts from CSV",
                'data' => $result
            ], 201);

        } catch (\Exception $e) {
            return $this->serverError('Failed to import CSV',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Update multiple posts
     *
     * PUT /api/orgs/{org_id}/bulk-posts/update
     *
     * Request body:
     * {
     *   "post_ids": ["uuid1", "uuid2", "uuid3"],
     *   "updates": {
     *     "status": "scheduled",
     *     "hashtags": ["#newtag"]
     *   }
     * }
     *
     * @param Request $request
     * @param string $orgId
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'post_ids' => 'required|array|min:1',
            'post_ids.*' => 'uuid',
            'updates' => 'required|array|min:1',
            'updates.content' => 'nullable|string|max:5000',
            'updates.status' => 'nullable|string|in:draft,scheduled,queued',
            'updates.hashtags' => 'nullable|array',
            'updates.media_urls' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()
            , 'Validation error');
        }

        try {
            // Sanitize updates (only allow specific fields)
            $allowedFields = ['content', 'status', 'hashtags', 'media_urls'];
            $updates = array_intersect_key(
                $request->input('updates'),
                array_flip($allowedFields)
            );

            // Convert arrays to JSON for storage
            if (isset($updates['hashtags'])) {
                $updates['hashtags'] = json_encode($updates['hashtags']);
            }
            if (isset($updates['media_urls'])) {
                $updates['media_urls'] = json_encode($updates['media_urls']);
            }

            $result = $this->bulkPostService->bulkUpdate(
                $request->input('post_ids'),
                $updates
            );

            return $this->success($result
            , "Successfully updated {$result['updated']} posts");

        } catch (\Exception $e) {
            return $this->serverError('Failed to update posts',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Delete multiple posts
     *
     * DELETE /api/orgs/{org_id}/bulk-posts/delete
     *
     * Request body:
     * {
     *   "post_ids": ["uuid1", "uuid2", "uuid3"]
     * }
     *
     * @param Request $request
     * @param string $orgId
     * @return JsonResponse
     */
    public function bulkDelete(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'post_ids' => 'required|array|min:1',
            'post_ids.*' => 'uuid'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()
            , 'Validation error');
        }

        try {
            $result = $this->bulkPostService->bulkDelete(
                $request->input('post_ids')
            );

            return $this->success($result
            , "Successfully deleted {$result['deleted']} posts");

        } catch (\Exception $e) {
            return $this->serverError('Failed to delete posts',
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Get template suggestions
     *
     * GET /api/orgs/{org_id}/bulk-posts/suggestions?topic=product&platform=facebook
     *
     * @param Request $request
     * @param string $orgId
     * @return JsonResponse
     */
    public function getTemplateSuggestions(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'topic' => 'required|string|max:255',
            'platform' => 'nullable|string|in:facebook,instagram,twitter,linkedin,tiktok',
            'limit' => 'nullable|integer|min:1|max:20'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()
            , 'Validation error');
        }

        try {
            $suggestions = $this->bulkPostService->getTemplateSuggestions(
                $request->input('topic'),
                $request->input('platform', 'facebook'),
                $request->input('limit', 5)
            );

            return $this->success($suggestions, 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get suggestions',
                'error' => $e->getMessage()
            );
        }
    }
}
