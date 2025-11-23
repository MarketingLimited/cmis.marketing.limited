<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Core\Integration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Meta Webhook Controller
 *
 * Handles webhooks from Meta platforms (Facebook, Instagram)
 */
class MetaWebhookController extends Controller
{
    use ApiResponse;

    /**
     * Handle Meta (Facebook & Instagram) webhooks
     *
     * @param Request $request
     * @return JsonResponse|string
     */
    public function handle(Request $request)
    {
        // Verify webhook (GET request)
        if ($request->isMethod('get')) {
            return $this->verify($request);
        }

        // Verify signature for POST requests (CRITICAL SECURITY)
        if (!$this->verifySignature($request)) {
            Log::warning('Meta webhook signature verification failed', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);
            return $this->unauthorized('Invalid webhook signature');
        }

        // Handle webhook event (POST request)
        try {
            $data = $request->all();
            Log::info('Meta webhook received', ['data' => $data]);

            foreach ($data['entry'] ?? [] as $entry) {
                // Handle messaging events
                if (isset($entry['messaging'])) {
                    foreach ($entry['messaging'] as $event) {
                        $this->processMessagingEvent($event);
                    }
                }

                // Handle changes (comments, posts, etc.)
                if (isset($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        $this->processChange($change);
                    }
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error("Meta webhook error: {$e->getMessage()}");
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Verify webhook (GET request)
     */
    protected function verify(Request $request)
    {
        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.meta.webhook_verify_token')) {
            Log::info('Meta webhook verified');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Process Meta messaging event
     */
    protected function processMessagingEvent(array $event): void
    {
        if (isset($event['message'])) {
            $senderId = $event['sender']['id'];
            $recipientId = $event['recipient']['id'];
            $messageText = $event['message']['text'] ?? '';
            $messageId = $event['message']['mid'];

            // Find integration
            $integration = Integration::where('external_account_id', $recipientId)
                ->where('platform', 'meta')
                ->where('is_active', true)
                ->first();

            if (!$integration) {
                Log::warning("No integration found for recipient {$recipientId}");
                return;
            }

            // Initialize RLS context for multi-tenancy (CRITICAL)
            DB::statement(
                'SELECT cmis.init_transaction_context(?, ?)',
                [config('cmis.system_user_id'), $integration->org_id]
            );

            // Store message
            DB::table('cmis_social.social_messages')->insert([
                'message_id' => \Illuminate\Support\Str::uuid(),
                'org_id' => $integration->org_id,
                'integration_id' => $integration->integration_id,
                'platform' => 'meta',
                'platform_message_id' => $messageId,
                'sender_id' => $senderId,
                'message_text' => $messageText,
                'direction' => 'inbound',
                'received_at' => now(),
                'created_at' => now(),
            ]);

            Log::info("Stored Meta message {$messageId}");
        }
    }

    /**
     * Process Meta change event (comments, posts, etc.)
     */
    protected function processChange(array $change): void
    {
        $field = $change['field'] ?? null;
        $value = $change['value'] ?? [];

        switch ($field) {
            case 'comments':
                $this->processComment($value);
                break;
            case 'feed':
                $this->processPost($value);
                break;
        }
    }

    /**
     * Process Meta comment
     */
    protected function processComment(array $comment): void
    {
        $commentId = $comment['id'] ?? null;
        $postId = $comment['post_id'] ?? null;
        $message = $comment['message'] ?? '';
        $from = $comment['from'] ?? [];

        if (!$commentId || !$postId) return;

        // Find integration by post
        $post = DB::table('cmis_social.social_posts')
            ->where('platform_post_id', $postId)
            ->first();

        if (!$post) return;

        // Store comment
        DB::table('cmis_social.social_comments')->updateOrInsert(
            ['platform_comment_id' => $commentId],
            [
                'org_id' => $post->org_id,
                'integration_id' => $post->integration_id,
                'platform' => 'meta',
                'post_id' => $post->post_id,
                'comment_text' => $message,
                'commenter_id' => $from['id'] ?? null,
                'commenter_name' => $from['name'] ?? null,
                'created_at' => now(),
            ]
        );

        Log::info("Stored Meta comment {$commentId}");
    }

    /**
     * Process Meta post
     */
    protected function processPost(array $post): void
    {
        // Implementation for new posts
        Log::info("Meta post event received", $post);
    }

    /**
     * Verify Meta (Facebook) webhook signature
     *
     * CRITICAL SECURITY: Prevents unauthorized webhook calls
     * Uses HMAC-SHA256 with app secret
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (empty($signature)) {
            Log::warning('Meta webhook missing signature header');
            return false;
        }

        $appSecret = config('services.meta.app_secret');

        if (empty($appSecret)) {
            Log::error('Meta app secret not configured');
            return false;
        }

        // Get raw request body
        $payload = $request->getContent();

        // Compute expected signature
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

        // Constant-time comparison to prevent timing attacks
        return hash_equals($expectedSignature, $signature);
    }
}
