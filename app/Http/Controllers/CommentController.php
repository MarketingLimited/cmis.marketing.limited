<?php

namespace App\Http\Controllers;

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
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = $request->user()->user_id ?? null;

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User must be authenticated'
                ], 401);
            }

            $result = $this->commentService->addComment([
                'entity_type' => $request->input('entity_type'),
                'entity_id' => $request->input('entity_id'),
                'user_id' => $userId,
                'comment_text' => $request->input('comment_text')
            ]);

            return response()->json($result, $result['success'] ? 201 : 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $e->getMessage()
            ], 500);
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
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = $request->user()->user_id ?? null;

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User must be authenticated'
                ], 401);
            }

            $result = $this->commentService->replyToComment($commentId, [
                'user_id' => $userId,
                'comment_text' => $request->input('comment_text')
            ]);

            return response()->json($result, $result['success'] ? 201 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add reply',
                'error' => $e->getMessage()
            ], 500);
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
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = $request->user()->user_id ?? null;

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User must be authenticated'
                ], 401);
            }

            $result = $this->commentService->updateComment(
                $commentId,
                $userId,
                $request->input('comment_text')
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update comment',
                'error' => $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'User must be authenticated'
                ], 401);
            }

            $result = $this->commentService->deleteComment($commentId, $userId);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment',
                'error' => $e->getMessage()
            ], 500);
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
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->commentService->getComments(
                $request->input('entity_type'),
                $request->input('entity_id'),
                $request->only(['top_level_only', 'sort_by', 'sort_order'])
            );

            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get comments',
                'error' => $e->getMessage()
            ], 500);
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
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = $request->user()->user_id ?? null;

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User must be authenticated'
                ], 401);
            }

            $result = $this->commentService->addReaction(
                $commentId,
                $userId,
                $request->input('reaction_type')
            );

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add reaction',
                'error' => $e->getMessage()
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'User must be authenticated'
                ], 401);
            }

            $result = $this->commentService->removeReaction($commentId, $userId);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove reaction',
                'error' => $e->getMessage()
            ], 500);
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
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->commentService->getActivityFeed($orgId, $request->all());

            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get activity feed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
