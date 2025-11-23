<?php

namespace App\Http\Controllers;

use App\Services\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * ApprovalController
 *
 * Manages post approval workflows (Creator → Reviewer → Publisher)
 * Implements Sprint 2.4: Approval Workflow
 */
class ApprovalController extends Controller
{
    use ApiResponse;

    protected ApprovalWorkflowService $approvalService;

    public function __construct(ApprovalWorkflowService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Request approval for post
     *
     * POST /api/orgs/{org_id}/approvals/request
     *
     * Request body:
     * {
     *   "post_id": "uuid",
     *   "assigned_to": "uuid" // optional
     * }
     *
     * @param Request $request
     * @param string $orgId
     * @return JsonResponse
     */
    public function requestApproval(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|uuid',
            'assigned_to' => 'nullable|uuid'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $approval = $this->approvalService->requestApproval(
                $request->input('post_id'),
                $request->user()->user_id,
                $request->input('assigned_to')
            );

            return $this->created($approval
            , 'Approval requested successfully');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to request approval',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve post
     *
     * POST /api/orgs/{org_id}/approvals/{approval_id}/approve
     *
     * Request body:
     * {
     *   "comments": "Looks good!" // optional
     * }
     *
     * @param Request $request
     * @param string $orgId
     * @param string $approvalId
     * @return JsonResponse
     */
    public function approve(Request $request, string $orgId, string $approvalId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'comments' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $success = $this->approvalService->approve(
            $approvalId,
            $request->user()->user_id,
            $request->input('comments')
        );

        if ($success) {
            return $this->success(null, 'Post approved successfully');
        }

        return $this->error('Failed to approve post', 400);
    }

    /**
     * Reject post
     *
     * POST /api/orgs/{org_id}/approvals/{approval_id}/reject
     *
     * Request body:
     * {
     *   "comments": "Please fix the typo in line 2" // required
     * }
     *
     * @param Request $request
     * @param string $orgId
     * @param string $approvalId
     * @return JsonResponse
     */
    public function reject(Request $request, string $orgId, string $approvalId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'comments' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()
            , 'Rejection reason is required');
        }

        $success = $this->approvalService->reject(
            $approvalId,
            $request->user()->user_id,
            $request->input('comments')
        );

        if ($success) {
            return $this->success(null, 'Post rejected successfully');
        }

        return $this->error('Failed to reject post', 400);
    }

    /**
     * Reassign approval to different reviewer
     *
     * POST /api/orgs/{org_id}/approvals/{approval_id}/reassign
     *
     * Request body:
     * {
     *   "assigned_to": "uuid"
     * }
     *
     * @param Request $request
     * @param string $orgId
     * @param string $approvalId
     * @return JsonResponse
     */
    public function reassign(Request $request, string $orgId, string $approvalId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $success = $this->approvalService->reassign(
            $approvalId,
            $request->input('assigned_to')
        );

        if ($success) {
            return $this->success(null, 'Approval reassigned successfully');
        }

        return $this->error('Failed to reassign approval', 400);
    }

    /**
     * Get pending approvals for current user
     *
     * GET /api/orgs/{org_id}/approvals/pending
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function pending(string $orgId, Request $request): JsonResponse
    {
        $approvals = $this->approvalService->getPendingApprovals(
            $request->user()->user_id,
            $orgId
        );

        return response()->json([
            'success' => true,
            'data' => $approvals,
            'count' => $approvals->count()
        ]);
    }

    /**
     * Get approval history for post
     *
     * GET /api/orgs/{org_id}/approvals/post/{post_id}/history
     *
     * @param string $orgId
     * @param string $postId
     * @return JsonResponse
     */
    public function history(string $orgId, string $postId): JsonResponse
    {
        $history = $this->approvalService->getApprovalHistory($postId);

        return response()->json([
            'success' => true,
            'data' => $history,
            'count' => $history->count()
        ]);
    }

    /**
     * Get approval statistics
     *
     * GET /api/orgs/{org_id}/approvals/statistics?start=2025-01-01&end=2025-01-31
     *
     * @param Request $request
     * @param string $orgId
     * @return JsonResponse
     */
    public function statistics(Request $request, string $orgId): JsonResponse
    {
        $dateRange = [];

        if ($request->has('start') && $request->has('end')) {
            $validator = Validator::make($request->all(), [
                'start' => 'required|date',
                'end' => 'required|date|after:start'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $dateRange = [
                'start' => $request->input('start'),
                'end' => $request->input('end')
            ];
        }

        $stats = $this->approvalService->getApprovalStats($orgId, $dateRange);

        return $this->success($stats
        );
    }
}
