<?php

namespace App\Http\Controllers;

use App\Services\UnifiedCommentsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class UnifiedCommentsController extends Controller
{
    use ApiResponse;

    protected $commentsService;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get unified comments from all platforms
     */
    public function index(Request $request, $orgId = null): JsonResponse
    {
        // If it's a web request (not API), return the view
        if (!$request->expectsJson()) {
            return view('inbox.comments');
        }

        $this->commentsService = new UnifiedCommentsService($orgId);

        try {
            $filters = [
                'platform' => $request->input('platform'),
                'sentiment' => $request->input('sentiment'),
                'is_replied' => $request->input('is_replied'),
                'is_hidden' => $request->input('is_hidden'),
                'post_id' => $request->input('post_id'),
                'search' => $request->input('search'),
                'page' => $request->input('page', 1),
                'per_page' => $request->input('per_page', 50),
            ];

            $comments = $this->commentsService->getComments(array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            }));

            return response()->json([
                'success' => true,
                'comments' => $comments['data'],
                'pagination' => [
                    'total' => $comments['total'],
                    'per_page' => $comments['per_page'],
                    'current_page' => $comments['current_page'],
                    'last_page' => $comments['last_page'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get comments: ' . $e->getMessage());
            return $this->serverError('فشل في جلب التعليقات' . ': ' . $e->getMessage());
        }
    }

    /**
     * Reply to comment
     */
    public function reply(Request $request, $orgId, $commentId): JsonResponse
    {
        $this->commentsService = new UnifiedCommentsService($orgId);

        $validator = Validator::make($request->all(), [
            'reply_text' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->commentsService->replyToComment(
                $commentId,
                $request->input('reply_text'),
                $request->user()->user_id
            );

            return response()->json([
                'success' => true,
                'message' => 'تم الرد على التعليق بنجاح',
                'reply_id' => $result['reply_id'],
                'platform_comment_id' => $result['platform_comment_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reply to comment: ' . $e->getMessage());
            return $this->serverError('فشل في الرد على التعليق' . ': ' . $e->getMessage());
        }
    }

    /**
     * Hide comment
     */
    public function hide(Request $request, $orgId, $commentId): JsonResponse
    {
        $this->commentsService = new UnifiedCommentsService($orgId);

        try {
            $result = $this->commentsService->hideComment($commentId);

            if ($result['success']) {
                return $this->success(null, 'تم إخفاء التعليق بنجاح');
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'فشل في إخفاء التعليق'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to hide comment: ' . $e->getMessage());
            return $this->serverError('فشل في إخفاء التعليق' . ': ' . $e->getMessage());
        }
    }

    /**
     * Delete comment
     */
    public function delete(Request $request, $orgId, $commentId): JsonResponse
    {
        $this->commentsService = new UnifiedCommentsService($orgId);

        try {
            $result = $this->commentsService->deleteComment($commentId);

            if ($result['success']) {
                return $this->success(null, 'تم حذف التعليق بنجاح');
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'فشل في حذف التعليق'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete comment: ' . $e->getMessage());
            return $this->serverError('فشل في حذف التعليق' . ': ' . $e->getMessage());
        }
    }

    /**
     * Like comment
     */
    public function like(Request $request, $orgId, $commentId): JsonResponse
    {
        $this->commentsService = new UnifiedCommentsService($orgId);

        try {
            $result = $this->commentsService->likeComment($commentId);

            if ($result['success']) {
                return $this->success(null, 'تم الإعجاب بالتعليق');
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'فشل في الإعجاب بالتعليق'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to like comment: ' . $e->getMessage());
            return $this->serverError('فشل في الإعجاب بالتعليق' . ': ' . $e->getMessage());
        }
    }

    /**
     * Bulk actions on comments
     */
    public function bulkAction(Request $request, $orgId): JsonResponse
    {
        $this->commentsService = new UnifiedCommentsService($orgId);

        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:hide,delete,like',
            'comment_ids' => 'required|array',
            'comment_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->commentsService->bulkAction(
                $request->input('action'),
                $request->input('comment_ids')
            );

            return $this->success(['message' => 'تم تنفيذ العملية الجماعية',
                'results' => $result], 'Operation completed successfully');
        } catch (\Exception $e) {
            Log::error('Failed to execute bulk action: ' . $e->getMessage());
            return $this->serverError('فشل في تنفيذ العملية الجماعية' . ': ' . $e->getMessage());
        }
    }

    /**
     * Get comments statistics
     */
    public function statistics(Request $request, $orgId): JsonResponse
    {
        $this->commentsService = new UnifiedCommentsService($orgId);

        try {
            $stats = $this->commentsService->getStatistics();

            return $this->success(['statistics' => $stats], 'Operation completed successfully');
        } catch (\Exception $e) {
            Log::error('Failed to get comment statistics: ' . $e->getMessage());
            return $this->serverError('فشل في جلب إحصائيات التعليقات' . ': ' . $e->getMessage());
        }
    }
}
