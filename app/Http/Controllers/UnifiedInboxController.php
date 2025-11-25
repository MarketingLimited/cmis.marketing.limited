<?php

namespace App\Http\Controllers;

use App\Services\UnifiedInboxService;
use Illuminate\Http\Request;
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
    public function index(Request $request, string $org)
    {
        // If it's a web request (not API), return the view
        if (!$request->expectsJson()) {
            return view('inbox.index');
        }

        $this->inboxService = new UnifiedInboxService($org);

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
            return response()->json([
                'success' => false,
                'error' => 'فشل في جلب الرسائل',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conversation thread
     */
    public function conversation(Request $request, string $org, $conversationId)
    {
        $this->inboxService = new UnifiedInboxService($org);

        try {
            $messages = $this->inboxService->getConversation($conversationId);

            return response()->json([
                'success' => true,
                'conversation_id' => $conversationId,
                'messages' => $messages,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get conversation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في جلب المحادثة'
            ], 500);
        }
    }

    /**
     * Send reply to message
     */
    public function reply(Request $request, string $org, $messageId)
    {
        $this->inboxService = new UnifiedInboxService($org);

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
            return response()->json([
                'success' => false,
                'error' => 'فشل في إرسال الرد',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request, string $org)
    {
        $this->inboxService = new UnifiedInboxService($org);

        $validated = $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'integer',
        ]);

        try {
            $this->inboxService->markAsRead($validated['message_ids']);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الرسائل'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark messages as read: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في تحديث الرسائل'
            ], 500);
        }
    }

    /**
     * Assign message to user
     */
    public function assign(Request $request, string $org, $messageId)
    {
        $this->inboxService = new UnifiedInboxService($org);

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:cmis.users,user_id',
        ]);

        try {
            $this->inboxService->assignToUser($messageId, $validated['user_id']);

            return response()->json([
                'success' => true,
                'message' => 'تم تعيين الرسالة بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to assign message: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في تعيين الرسالة'
            ], 500);
        }
    }

    /**
     * Add note to message
     */
    public function addNote(Request $request, string $org, $messageId)
    {
        $this->inboxService = new UnifiedInboxService($org);

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
            return response()->json([
                'success' => false,
                'error' => 'فشل في إضافة الملاحظة'
            ], 500);
        }
    }

    /**
     * Get saved replies
     */
    public function savedReplies(Request $request, string $org)
    {
        $this->inboxService = new UnifiedInboxService($org);

        try {
            $replies = $this->inboxService->getSavedReplies();

            return response()->json([
                'success' => true,
                'replies' => $replies
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get saved replies: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في جلب الردود المحفوظة'
            ], 500);
        }
    }

    /**
     * Create saved reply
     */
    public function createSavedReply(Request $request, string $org)
    {
        $this->inboxService = new UnifiedInboxService($org);

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
            return response()->json([
                'success' => false,
                'error' => 'فشل في حفظ الرد'
            ], 500);
        }
    }

    /**
     * Get inbox statistics
     */
    public function statistics(Request $request, string $org)
    {
        $this->inboxService = new UnifiedInboxService($org);

        try {
            $stats = $this->inboxService->getStatistics();

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get inbox statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في جلب الإحصائيات'
            ], 500);
        }
    }
}
