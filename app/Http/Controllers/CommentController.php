<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\ApiResponse;

use App\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * CommentController
 *
 * Handles comments and collaboration features
 * Implements Sprint 5.3: Comments & Collaboration
 *
 * Features:
 * - Add, update, delete comments
 * - Threaded replies
 * - @mentions with notifications
 * - Comment reactions
 * - Activity feed
 */
class CommentController extends Controller
{
    use ApiResponse;

    protected CommentService $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->middleware('auth:sanctum');
        $this->commentService = $commentService;
    }

    /**
     * Add a comment
     *
     * POST /api/orgs/{org_id}/comments
     *
     * Request body:
     * {
     *   "entity_type": "post",
     *   "entity_id": "uuid",
     *   "comment_text": "Great post! @john what do you think?"
     * }
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function create(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|in:post,campaign,ad,content',
            'entity_id' => 'required|uuid',
            'comment_text' => 'required|string|max:2000'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $userId = $request->user()->user_id ?? null;

            if (!$userId) {
                return $this->error('User must be authenticated', 401);
            }

            $result = $this->commentService->addComment([
                'entity_type' => $request->input('entity_type'),
                'entity_id' => $request->input('entity_id'),
                'user_id' => $userId,
                'comment_text' => $request->input('comment_text')
            ]);

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->created($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to add comment: ' . $e->getMessage());
        }
    }

    /**
     * Reply to a comment
     *
     * POST /api/orgs/{org_id}/comments/{comment_id}/reply
     *
     * Request body:
     * {
     *   "comment_text": "I agree! @sarah check this out"
     * }
     *
     * @param string $orgId
     * @param string $commentId
     * @param Request $request
     * @return JsonResponse
     */
    public function reply(string $orgId, string $commentId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'comment_text' => 'required|string|max:2000'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $userId = $request->user()->user_id ?? null;

            if (!$userId) {
                return $this->error('User must be authenticated', 401);
            }

            $result = $this->commentService->replyToComment($commentId, [
                'user_id' => $userId,
                'comment_text' => $request->input('comment_text')
            ]);

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->created($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to add reply: ' . $e->getMessage());
        }
    }

    /**
     * Update a comment
     *
     * PUT /api/orgs/{org_id}/comments/{comment_id}
     *
     * Request body:
     * {
     *   "comment_text": "Updated comment text @mike"
     * }
     *
     * @param string $orgId
     * @param string $commentId
     * @param Request $request
     * @return JsonResponse
     */
    public function update(string $orgId, string $commentId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'comment_text' => 'required|string|max:2000'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $userId = $request->user()->user_id ?? null;

            if (!$userId) {
                return $this->error('User must be authenticated', 401);
            }

            $result = $this->commentService->updateComment(
                $commentId,
                $userId,
                $request->input('comment_text')
            );

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to update comment: ' . $e->getMessage());
        }
    }

    /**
     * Delete a comment
     *
     * DELETE /api/orgs/{org_id}/comments/{comment_id}
     *
     * @param string $orgId
     * @param string $commentId
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(string $orgId, string $commentId, Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->user_id ?? null;

            if (!$userId) {
                return $this->error('User must be authenticated', 401);
            }

            $result = $this->commentService->deleteComment($commentId, $userId);

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to delete comment: ' . $e->getMessage());
        }
    }

    /**
     * Get comments for an entity
     *
     * GET /api/orgs/{org_id}/comments?entity_type=post&entity_id=uuid&top_level_only=true
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function list(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|in:post,campaign,ad,content',
            'entity_id' => 'required|uuid',
            'top_level_only' => 'nullable|boolean',
            'sort_by' => 'nullable|in:created_at,updated_at',
            'sort_order' => 'nullable|in:asc,desc'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->commentService->getComments(
                $request->input('entity_type'),
                $request->input('entity_id'),
                $request->only(['top_level_only', 'sort_by', 'sort_order'])
            );

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get comments: ' . $e->getMessage());
        }
    }

    /**
     * Add reaction to comment
     *
     * POST /api/orgs/{org_id}/comments/{comment_id}/reactions
     *
     * Request body:
     * {
     *   "reaction_type": "like"
     * }
     *
     * @param string $orgId
     * @param string $commentId
     * @param Request $request
     * @return JsonResponse
     */
    public function addReaction(string $orgId, string $commentId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reaction_type' => 'required|in:like,love,celebrate,insightful,support'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $userId = $request->user()->user_id ?? null;

            if (!$userId) {
                return $this->error('User must be authenticated', 401);
            }

            $result = $this->commentService->addReaction(
                $commentId,
                $userId,
                $request->input('reaction_type')
            );

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to add reaction: ' . $e->getMessage());
        }
    }

    /**
     * Remove reaction from comment
     *
     * DELETE /api/orgs/{org_id}/comments/{comment_id}/reactions
     *
     * @param string $orgId
     * @param string $commentId
     * @param Request $request
     * @return JsonResponse
     */
    public function removeReaction(string $orgId, string $commentId, Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->user_id ?? null;

            if (!$userId) {
                return $this->error('User must be authenticated', 401);
            }

            $result = $this->commentService->removeReaction($commentId, $userId);

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to remove reaction: ' . $e->getMessage());
        }
    }

    /**
     * Get activity feed
     *
     * GET /api/orgs/{org_id}/activity?activity_type=comment_added&entity_type=post&start_date=2025-01-01
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function getActivityFeed(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'activity_type' => 'nullable|in:comment_added,comment_updated,comment_deleted,mention',
            'entity_type' => 'nullable|in:post,campaign,ad,content',
            'user_id' => 'nullable|uuid',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->commentService->getActivityFeed($orgId, $request->all());

            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get activity feed: ' . $e->getMessage());
        }
    }
}
