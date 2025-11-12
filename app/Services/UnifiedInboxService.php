<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UnifiedInboxService
{
    protected $orgId;

    public function __construct($orgId)
    {
        $this->orgId = $orgId;
    }

    /**
     * Get all messages for organization (unified inbox)
     */
    public function getMessages(array $filters = []): array
    {
        $query = DB::table('cmis_social.social_messages')
            ->where('org_id', $this->orgId)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['conversation_id'])) {
            $query->where('conversation_id', $filters['conversation_id']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('message_text', 'ILIKE', '%' . $filters['search'] . '%')
                  ->orWhere('sender_name', 'ILIKE', '%' . $filters['search'] . '%');
            });
        }

        $perPage = $filters['per_page'] ?? 50;
        $page = $filters['page'] ?? 1;

        $total = $query->count();
        $messages = $query->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return [
            'data' => $messages,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
        ];
    }

    /**
     * Get conversation thread
     */
    public function getConversation(string $conversationId): array
    {
        return DB::table('cmis_social.social_messages')
            ->where('org_id', $this->orgId)
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Send reply to message
     */
    public function sendReply(int $messageId, string $replyText, int $userId): array
    {
        // Get original message
        $message = DB::table('cmis_social.social_messages')
            ->where('message_id', $messageId)
            ->where('org_id', $this->orgId)
            ->first();

        if (!$message) {
            throw new \Exception('Message not found');
        }

        // Get integration
        $integration = DB::table('cmis_integrations.integrations')
            ->where('integration_id', $message->integration_id)
            ->first();

        if (!$integration) {
            throw new \Exception('Integration not found');
        }

        // Send reply based on platform
        $platformReply = $this->sendPlatformReply($message, $replyText, $integration);

        if (!$platformReply['success']) {
            throw new \Exception($platformReply['error'] ?? 'Failed to send reply');
        }

        // Store reply in database
        $replyId = DB::table('cmis_social.social_messages')->insertGetId([
            'org_id' => $this->orgId,
            'integration_id' => $message->integration_id,
            'platform' => $message->platform,
            'platform_message_id' => $platformReply['platform_message_id'] ?? null,
            'conversation_id' => $message->conversation_id,
            'sender_id' => null,
            'sender_name' => null,
            'message_text' => $replyText,
            'message_type' => 'text',
            'is_from_page' => true,
            'status' => 'sent',
            'sent_by_user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'message_id');

        // Mark original message as replied
        DB::table('cmis_social.social_messages')
            ->where('message_id', $messageId)
            ->update([
                'status' => 'replied',
                'replied_at' => now(),
                'replied_by' => $userId,
                'updated_at' => now(),
            ]);

        return [
            'success' => true,
            'reply_id' => $replyId,
            'platform_message_id' => $platformReply['platform_message_id'] ?? null,
        ];
    }

    /**
     * Send reply to platform-specific API
     */
    protected function sendPlatformReply($message, string $replyText, $integration): array
    {
        switch ($message->platform) {
            case 'facebook':
            case 'meta':
                return $this->sendFacebookReply($message, $replyText, $integration);

            case 'instagram':
                return $this->sendInstagramReply($message, $replyText, $integration);

            case 'twitter':
            case 'x':
                return $this->sendTwitterReply($message, $replyText, $integration);

            case 'linkedin':
                return $this->sendLinkedInReply($message, $replyText, $integration);

            default:
                return ['success' => false, 'error' => 'Platform not supported'];
        }
    }

    /**
     * Send Facebook Messenger reply
     */
    protected function sendFacebookReply($message, string $replyText, $integration): array
    {
        try {
            $response = \Http::post("https://graph.facebook.com/v19.0/me/messages", [
                'recipient' => ['id' => $message->sender_id],
                'message' => ['text' => $replyText],
                'access_token' => $integration->access_token,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'platform_message_id' => $response->json()['message_id'] ?? null,
                ];
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Failed to send Facebook reply: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send Instagram DM reply
     */
    protected function sendInstagramReply($message, string $replyText, $integration): array
    {
        try {
            $igAccountId = $integration->settings['instagram_account_id'] ?? null;
            if (!$igAccountId) {
                return ['success' => false, 'error' => 'Instagram account not configured'];
            }

            $response = \Http::post("https://graph.facebook.com/v19.0/{$igAccountId}/messages", [
                'recipient' => ['id' => $message->sender_id],
                'message' => ['text' => $replyText],
                'access_token' => $integration->access_token,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'platform_message_id' => $response->json()['message_id'] ?? null,
                ];
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Failed to send Instagram reply: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send Twitter/X reply
     */
    protected function sendTwitterReply($message, string $replyText, $integration): array
    {
        // Implementation for Twitter API v2
        return ['success' => false, 'error' => 'Not implemented yet'];
    }

    /**
     * Send LinkedIn reply
     */
    protected function sendLinkedInReply($message, string $replyText, $integration): array
    {
        // Implementation for LinkedIn API
        return ['success' => false, 'error' => 'Not implemented yet'];
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(array $messageIds): bool
    {
        return DB::table('cmis_social.social_messages')
            ->whereIn('message_id', $messageIds)
            ->where('org_id', $this->orgId)
            ->update([
                'status' => 'read',
                'read_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Assign message to user
     */
    public function assignToUser(int $messageId, int $userId): bool
    {
        return DB::table('cmis_social.social_messages')
            ->where('message_id', $messageId)
            ->where('org_id', $this->orgId)
            ->update([
                'assigned_to' => $userId,
                'assigned_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Add internal note to message
     */
    public function addNote(int $messageId, string $note, int $userId): int
    {
        return DB::table('cmis_social.message_notes')->insertGetId([
            'message_id' => $messageId,
            'user_id' => $userId,
            'note_text' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'note_id');
    }

    /**
     * Get saved replies templates
     */
    public function getSavedReplies(): array
    {
        return DB::table('cmis_social.saved_replies')
            ->where('org_id', $this->orgId)
            ->orderBy('title')
            ->get()
            ->toArray();
    }

    /**
     * Create saved reply template
     */
    public function createSavedReply(string $title, string $content, ?string $category = null): int
    {
        return DB::table('cmis_social.saved_replies')->insertGetId([
            'org_id' => $this->orgId,
            'title' => $title,
            'content' => $content,
            'category' => $category,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'reply_id');
    }

    /**
     * Get inbox statistics
     */
    public function getStatistics(): array
    {
        $total = DB::table('cmis_social.social_messages')
            ->where('org_id', $this->orgId)
            ->count();

        $unread = DB::table('cmis_social.social_messages')
            ->where('org_id', $this->orgId)
            ->where('status', 'unread')
            ->count();

        $replied = DB::table('cmis_social.social_messages')
            ->where('org_id', $this->orgId)
            ->where('status', 'replied')
            ->count();

        $avgResponseTime = DB::table('cmis_social.social_messages')
            ->where('org_id', $this->orgId)
            ->whereNotNull('replied_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (replied_at - created_at))) as avg_seconds')
            ->first();

        $byPlatform = DB::table('cmis_social.social_messages')
            ->where('org_id', $this->orgId)
            ->select('platform', DB::raw('COUNT(*) as count'))
            ->groupBy('platform')
            ->get();

        return [
            'total' => $total,
            'unread' => $unread,
            'replied' => $replied,
            'pending' => $total - $replied,
            'avg_response_time_minutes' => round(($avgResponseTime->avg_seconds ?? 0) / 60, 2),
            'by_platform' => $byPlatform,
        ];
    }
}
