<?php

namespace App\Http\Controllers;

use App\Services\UnifiedInboxService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Concerns\ApiResponse;

class UnifiedInboxController extends Controller
{
    use ApiResponse;

    protected $inboxService;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get unified inbox messages
     */
    public function index(Request $request): View
    {
        // If it's a web request (not API), return the view
        if (!$request->expectsJson()) {
            return view('inbox.index');
        }

        $orgId = $request->route('org_id');
        $this->inboxService = new UnifiedInboxService($orgId);

        try {
            $filters = [
                'platform' => $request->input('platform'),
                'status' => $request->input('status'),
                'assigned_to' => $request->input('assigned_to'),
                'search' => $request->input('search'),
                'page' => $request->input('page', 1),
                'per_page' => $request->input('per_page', 50),
            ];

            $messages = $this->inboxService->getMessages(array_filter($filters));

            return response()->json([
                'success' => true,
                'messages' => $messages['data'],
                'pagination' => [
                    'total' => $messages['total'],
                    'per_page' => $messages['per_page'],
                    'current_page' => $messages['current_page'],
                    'last_page' => $messages['last_page'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get inbox messages: ' . $e->getMessage());
            return $this->serverError('فشل في جلب الرسائل' . ': ' . $e->getMessage());
        }
    }

    /**
     * Get conversation thread
     */
    public function conversation(Request $request, $orgId, $conversationId): JsonResponse
    {
        $this->inboxService = new UnifiedInboxService($orgId);

        try {
            $messages = $this->inboxService->getConversation($conversationId);

            return response()->json([
                'success' => true,
                'conversation_id' => $conversationId,
                'messages' => $messages,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get conversation: ' . $e->getMessage());
            return $this->serverError('فشل في جلب المحادثة');
        }
    }

    /**
     * Send reply to message
     */
    public function reply(Request $request, $orgId, $messageId): JsonResponse
    {
        $this->inboxService = new UnifiedInboxService($orgId);

        $validated = $request->validate([
            'reply_text' => 'required|string|max:5000',
        ]);

        try {
            $result = $this->inboxService->sendReply(
                $messageId,
                $validated['reply_text'],
                $request->user()->user_id
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الرد بنجاح',
                'reply_id' => $result['reply_id'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send reply: ' . $e->getMessage());
            return $this->serverError('فشل في إرسال الرد' . ': ' . $e->getMessage());
        }
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request, $orgId): JsonResponse
    {
        $this->inboxService = new UnifiedInboxService($orgId);

        $validated = $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'integer',
        ]);

        try {
            $this->inboxService->markAsRead($validated['message_ids']);

            return $this->success(null, 'تم تحديث حالة الرسائل');
        } catch (\Exception $e) {
            Log::error('Failed to mark messages as read: ' . $e->getMessage());
            return $this->serverError('فشل في تحديث الرسائل');
        }
    }

    /**
     * Assign message to user
     */
    public function assign(Request $request, $orgId, $messageId): JsonResponse
    {
        $this->inboxService = new UnifiedInboxService($orgId);

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:cmis.users,user_id',
        ]);

        try {
            $this->inboxService->assignToUser($messageId, $validated['user_id']);

            return $this->success(null, 'تم تعيين الرسالة بنجاح');
        } catch (\Exception $e) {
            Log::error('Failed to assign message: ' . $e->getMessage());
            return $this->serverError('فشل في تعيين الرسالة');
        }
    }

    /**
     * Add note to message
     */
    public function addNote(Request $request, $orgId, $messageId): JsonResponse
    {
        $this->inboxService = new UnifiedInboxService($orgId);

        $validated = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        try {
            $noteId = $this->inboxService->addNote(
                $messageId,
                $validated['note'],
                $request->user()->user_id
            );

            return response()->json([
                'success' => true,
                'message' => 'تمت إضافة الملاحظة',
                'note_id' => $noteId
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to add note: ' . $e->getMessage());
            return $this->serverError('فشل في إضافة الملاحظة');
        }
    }

    /**
     * Get saved replies
     */
    public function savedReplies(Request $request, $orgId): JsonResponse
    {
        $this->inboxService = new UnifiedInboxService($orgId);

        try {
            $replies = $this->inboxService->getSavedReplies();

            return response()->json([
                'success' => true,
                'replies' => $replies
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get saved replies: ' . $e->getMessage());
            return $this->serverError('فشل في جلب الردود المحفوظة');
        }
    }

    /**
     * Create saved reply
     */
    public function createSavedReply(Request $request, $orgId): JsonResponse
    {
        $this->inboxService = new UnifiedInboxService($orgId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:5000',
            'category' => 'nullable|string|max:100',
        ]);

        try {
            $replyId = $this->inboxService->createSavedReply(
                $validated['title'],
                $validated['content'],
                $validated['category'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الرد',
                'reply_id' => $replyId
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create saved reply: ' . $e->getMessage());
            return $this->serverError('فشل في حفظ الرد');
        }
    }

    /**
     * Get inbox statistics
     */
    public function statistics(Request $request, $orgId): JsonResponse
    {
        $this->inboxService = new UnifiedInboxService($orgId);

        try {
            $stats = $this->inboxService->getStatistics();

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get inbox statistics: ' . $e->getMessage());
            return $this->serverError('فشل في جلب الإحصائيات');
        }
    }
}
