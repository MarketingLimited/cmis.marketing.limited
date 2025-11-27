<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Workflow\ApprovalWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * ApprovalWorkflowController
 *
 * Manages multi-step approval workflows for content review and publishing.
 * Includes triggers, approval chains, and notification settings.
 */
class ApprovalWorkflowController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List all approval workflows for a profile group
     *
     * GET /api/orgs/{org_id}/profile-groups/{group_id}/approval-workflows
     */
    public function index(string $orgId, string $groupId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'platform' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $query = ApprovalWorkflow::where('profile_group_id', $groupId);

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('description', 'ILIKE', "%{$search}%");
                });
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->filled('platform')) {
                $query->forPlatform($request->input('platform'));
            }

            $query->with(['creator', 'profileGroup']);

            $perPage = $request->input('per_page', 15);
            $workflows = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->paginated($workflows, 'Approval workflows retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve approval workflows: ' . $e->getMessage());
        }
    }

    /**
     * Create a new approval workflow
     *
     * POST /api/orgs/{org_id}/profile-groups/{group_id}/approval-workflows
     */
    public function store(string $orgId, string $groupId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'apply_to_platforms' => 'nullable|array',
            'apply_to_platforms.*' => 'string|max:50',
            'apply_to_users' => 'nullable|array',
            'apply_to_users.*' => 'uuid',
            'apply_to_post_types' => 'nullable|array',
            'apply_to_post_types.*' => 'string|max:50',
            'approval_steps' => 'required|array|min:1',
            'approval_steps.*.name' => 'required|string|max:255',
            'approval_steps.*.approvers' => 'required|array|min:1',
            'approval_steps.*.approvers.*' => 'uuid',
            'approval_steps.*.require_all' => 'nullable|boolean',
            'approval_steps.*.auto_approve_after_hours' => 'nullable|integer|min:1|max:720',
            'notify_on_submission' => 'nullable|boolean',
            'notify_on_approval' => 'nullable|boolean',
            'notify_on_rejection' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $workflow = ApprovalWorkflow::create([
                'org_id' => $orgId,
                'profile_group_id' => $groupId,
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'is_active' => $request->input('is_active', true),
                'apply_to_platforms' => $request->input('apply_to_platforms', []),
                'apply_to_users' => $request->input('apply_to_users', []),
                'apply_to_post_types' => $request->input('apply_to_post_types', []),
                'approval_steps' => $request->input('approval_steps'),
                'notify_on_submission' => $request->input('notify_on_submission', true),
                'notify_on_approval' => $request->input('notify_on_approval', true),
                'notify_on_rejection' => $request->input('notify_on_rejection', true),
                'created_by' => Auth::id(),
            ]);

            $workflow->load(['creator', 'profileGroup']);

            return $this->created($workflow, 'Approval workflow created successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to create approval workflow: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific approval workflow
     *
     * GET /api/orgs/{org_id}/profile-groups/{group_id}/approval-workflows/{workflow_id}
     */
    public function show(string $orgId, string $groupId, string $workflowId): JsonResponse
    {
        try {
            $workflow = ApprovalWorkflow::with(['creator', 'profileGroup'])
                ->where('profile_group_id', $groupId)
                ->findOrFail($workflowId);

            return $this->success($workflow, 'Approval workflow retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Approval workflow not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve approval workflow: ' . $e->getMessage());
        }
    }

    /**
     * Update an approval workflow
     *
     * PUT /api/orgs/{org_id}/profile-groups/{group_id}/approval-workflows/{workflow_id}
     */
    public function update(string $orgId, string $groupId, string $workflowId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'apply_to_platforms' => 'nullable|array',
            'apply_to_users' => 'nullable|array',
            'apply_to_post_types' => 'nullable|array',
            'approval_steps' => 'sometimes|required|array|min:1',
            'approval_steps.*.name' => 'required|string|max:255',
            'approval_steps.*.approvers' => 'required|array|min:1',
            'approval_steps.*.approvers.*' => 'uuid',
            'approval_steps.*.require_all' => 'nullable|boolean',
            'approval_steps.*.auto_approve_after_hours' => 'nullable|integer|min:1|max:720',
            'notify_on_submission' => 'nullable|boolean',
            'notify_on_approval' => 'nullable|boolean',
            'notify_on_rejection' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $workflow = ApprovalWorkflow::where('profile_group_id', $groupId)
                ->findOrFail($workflowId);

            $workflow->update($request->only([
                'name', 'description', 'is_active',
                'apply_to_platforms', 'apply_to_users', 'apply_to_post_types',
                'approval_steps', 'notify_on_submission', 'notify_on_approval',
                'notify_on_rejection',
            ]));

            $workflow->load(['creator', 'profileGroup']);

            return $this->success($workflow, 'Approval workflow updated successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Approval workflow not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update approval workflow: ' . $e->getMessage());
        }
    }

    /**
     * Delete an approval workflow (soft delete)
     *
     * DELETE /api/orgs/{org_id}/profile-groups/{group_id}/approval-workflows/{workflow_id}
     */
    public function destroy(string $orgId, string $groupId, string $workflowId): JsonResponse
    {
        try {
            $workflow = ApprovalWorkflow::where('profile_group_id', $groupId)
                ->findOrFail($workflowId);

            $workflow->delete();

            return $this->deleted('Approval workflow deleted successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Approval workflow not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete approval workflow: ' . $e->getMessage());
        }
    }

    /**
     * Toggle workflow active status
     *
     * POST /api/orgs/{org_id}/profile-groups/{group_id}/approval-workflows/{workflow_id}/toggle
     */
    public function toggle(string $orgId, string $groupId, string $workflowId): JsonResponse
    {
        try {
            $workflow = ApprovalWorkflow::where('profile_group_id', $groupId)
                ->findOrFail($workflowId);

            $workflow->is_active = !$workflow->is_active;
            $workflow->save();

            $status = $workflow->is_active ? 'activated' : 'deactivated';

            return $this->success($workflow, "Approval workflow {$status} successfully");

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Approval workflow not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to toggle approval workflow: ' . $e->getMessage());
        }
    }
}
