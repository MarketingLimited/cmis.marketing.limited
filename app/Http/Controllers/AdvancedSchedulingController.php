<?php

namespace App\Http\Controllers;

use App\Services\AdvancedSchedulingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * AdvancedSchedulingController
 *
 * Handles advanced scheduling features
 * Implements Sprint 6.3: Advanced Scheduling
 */
class AdvancedSchedulingController extends Controller
{
    use ApiResponse;

    protected AdvancedSchedulingService $schedulingService;

    public function __construct(AdvancedSchedulingService $schedulingService)
    {
        $this->schedulingService = $schedulingService;
    }

    /**
     * Create recurring template
     * POST /api/orgs/{org_id}/scheduling/recurring-templates
     */
    public function createRecurringTemplate(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'social_account_id' => 'required|uuid',
            'template_name' => 'required|string|max:255',
            'content_template' => 'required|string',
            'media_urls' => 'nullable|array',
            'hashtags' => 'nullable|array',
            'recurrence_pattern' => 'required|in:daily,weekly,monthly',
            'recurrence_interval' => 'nullable|integer|min:1',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|min:0|max:6',
            'time_of_day' => 'required|date_format:H:i',
            'timezone' => 'nullable|timezone',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $userId = $request->user()->user_id ?? null;
            if (!$userId) {
                return $this->error('Authentication required', 401);
            }

            $data = $request->all();
            $data['created_by'] = $userId;

            $result = $this->schedulingService->createRecurringTemplate($data);
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->created($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create template', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate posts from recurring template
     * POST /api/orgs/{org_id}/scheduling/recurring-templates/{template_id}/generate
     */
    public function generateRecurringPosts(string $orgId, string $templateId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days_ahead' => 'nullable|integer|min:1|max:365'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $daysAhead = $request->input('days_ahead', 30);
            $result = $this->schedulingService->generateRecurringPosts($templateId, $daysAhead);
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to generate posts', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get scheduling queue
     * GET /api/orgs/{org_id}/scheduling/queue/{account_id}
     */
    public function getSchedulingQueue(string $orgId, string $accountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->schedulingService->getSchedulingQueue($accountId, $request->all());
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to get queue', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Recycle a post
     * POST /api/orgs/{org_id}/scheduling/recycle/{post_id}
     */
    public function recyclePost(string $orgId, string $postId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'scheduled_for' => 'nullable|date|after:now',
            'content' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->schedulingService->recyclePost($postId, $request->all());
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->created($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to recycle post', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Resolve scheduling conflicts
     * POST /api/orgs/{org_id}/scheduling/resolve-conflicts/{account_id}
     */
    public function resolveConflicts(string $orgId, string $accountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'strategy' => 'nullable|in:space_evenly,prioritize_important,move_to_optimal'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $strategy = $request->input('strategy', 'space_evenly');
            $result = $this->schedulingService->resolveConflicts($accountId, $strategy);
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to resolve conflicts', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Bulk reschedule posts
     * POST /api/orgs/{org_id}/scheduling/bulk-reschedule
     */
    public function bulkReschedule(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'post_ids' => 'required|array|min:1',
            'post_ids.*' => 'uuid',
            'start_date' => 'required|date',
            'strategy' => 'nullable|in:preserve_order,optimize_times',
            'timezone' => 'nullable|timezone'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->schedulingService->bulkReschedule(
                $request->input('post_ids'),
                $request->only(['start_date', 'strategy', 'timezone'])
            );

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to reschedule', 'error' => $e->getMessage()], 500);
        }
    }
}
