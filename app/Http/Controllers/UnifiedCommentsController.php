<?php

namespace App\Http\Controllers;

use App\Services\UnifiedCommentsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Concerns\ApiResponse;

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
    public function index(Request $request, $orgId)
    {
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
            return response()->json([
                'success' => false,
                'error' => 'فشل في جلب التعليقات',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reply to comment
     */
    public function reply(Request $request, $orgId, $commentId)
    {
        $this->commentsService = new UnifiedCommentsService($orgId);

        $validator = Validator::make($request->all(), [
            'reply_text' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
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
            return response()->json([
                'success' => false,
                'error' => 'فشل في الرد على التعليق',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hide comment
     */
    public function hide(Request $request, $orgId, $commentId)
    {
        $this->commentsService = new UnifiedCommentsService($orgId);

        try {
            $result = $this->commentsService->hideComment($commentId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إخفاء التعليق بنجاح'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'فشل في إخفاء التعليق'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to hide comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في إخفاء التعليق',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete comment
     */
    public function delete(Request $request, $orgId, $commentId)
    {
        $this->commentsService = new UnifiedCommentsService($orgId);

        try {
            $result = $this->commentsService->deleteComment($commentId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف التعليق بنجاح'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'فشل في حذف التعليق'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في حذف التعليق',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Like comment
     */
    public function like(Request $request, $orgId, $commentId)
    {
        $this->commentsService = new UnifiedCommentsService($orgId);

        try {
            $result = $this->commentsService->likeComment($commentId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم الإعجاب بالتعليق'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'فشل في الإعجاب بالتعليق'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to like comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في الإعجاب بالتعليق',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk actions on comments
     */
    public function bulkAction(Request $request, $orgId)
    {
        $this->commentsService = new UnifiedCommentsService($orgId);

        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:hide,delete,like',
            'comment_ids' => 'required|array',
            'comment_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->commentsService->bulkAction(
                $request->input('action'),
                $request->input('comment_ids')
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تنفيذ العملية الجماعية',
                'results' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to execute bulk action: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في تنفيذ العملية الجماعية',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comments statistics
     */
    public function statistics(Request $request, $orgId)
    {
        $this->commentsService = new UnifiedCommentsService($orgId);

        try {
            $stats = $this->commentsService->getStatistics();

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get comment statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في جلب إحصائيات التعليقات',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
