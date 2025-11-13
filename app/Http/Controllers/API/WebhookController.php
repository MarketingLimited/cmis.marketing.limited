<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Core\Integration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controller for handling webhooks from various platforms
 * Supports: Meta (Facebook, Instagram), WhatsApp, TikTok, Twitter
 */
class WebhookController extends Controller
{
    /**
     * Handle Meta (Facebook & Instagram) webhooks
     *
     * @param Request $request
     * @return JsonResponse|string
     */
    public function handleMetaWebhook(Request $request)
    {
        // Verify webhook (GET request)
        if ($request->isMethod('get')) {
            $mode = $request->input('hub_mode');
            $token = $request->input('hub_verify_token');
            $challenge = $request->input('hub_challenge');

            if ($mode === 'subscribe' && $token === config('services.meta.webhook_verify_token')) {
                Log::info('Meta webhook verified');
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }

            return response('Forbidden', 403);
        }

        // Handle webhook event (POST request)
        try {
            $data = $request->all();
            Log::info('Meta webhook received', ['data' => $data]);

            foreach ($data['entry'] ?? [] as $entry) {
                // Handle messaging events
                if (isset($entry['messaging'])) {
                    foreach ($entry['messaging'] as $event) {
                        $this->processMetaMessagingEvent($event);
                    }
                }

                // Handle changes (comments, posts, etc.)
                if (isset($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        $this->processMetaChange($change);
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
     * Handle WhatsApp webhooks
     *
     * @param Request $request
     * @return JsonResponse|string
     */
    public function handleWhatsAppWebhook(Request $request)
    {
        // Verify webhook (GET request)
        if ($request->isMethod('get')) {
            $mode = $request->input('hub_mode');
            $token = $request->input('hub_verify_token');
            $challenge = $request->input('hub_challenge');

            if ($mode === 'subscribe' && $token === config('services.whatsapp.webhook_verify_token')) {
                Log::info('WhatsApp webhook verified');
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }

            return response('Forbidden', 403);
        }

        // Handle webhook event (POST request)
        try {
            $data = $request->all();
            Log::info('WhatsApp webhook received', ['data' => $data]);

            foreach ($data['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    if ($change['field'] === 'messages') {
                        $this->processWhatsAppMessage($change['value']);
                    }
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error("WhatsApp webhook error: {$e->getMessage()}");
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Handle TikTok webhooks
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleTikTokWebhook(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            Log::info('TikTok webhook received', ['data' => $data]);

            // Verify signature
            $signature = $request->header('X-TikTok-Signature');
            if (!$this->verifyTikTokSignature($request->getContent(), $signature)) {
                return response()->json(['success' => false, 'error' => 'Invalid signature'], 401);
            }

            // Process event
            $event = $data['event'] ?? null;
            switch ($event) {
                case 'comment':
                    $this->processTikTokComment($data);
                    break;
                case 'video_update':
                    $this->processTikTokVideoUpdate($data);
                    break;
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error("TikTok webhook error: {$e->getMessage()}");
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Handle Twitter/X webhooks
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleTwitterWebhook(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            Log::info('Twitter webhook received', ['data' => $data]);

            // Process tweet events
            if (isset($data['tweet_create_events'])) {
                foreach ($data['tweet_create_events'] as $tweet) {
                    $this->processTwitterTweet($tweet);
                }
            }

            // Process direct messages
            if (isset($data['direct_message_events'])) {
                foreach ($data['direct_message_events'] as $dm) {
                    $this->processTwitterDM($dm);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error("Twitter webhook error: {$e->getMessage()}");
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Process Meta messaging event
     */
    protected function processMetaMessagingEvent(array $event): void
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
    protected function processMetaChange(array $change): void
    {
        $field = $change['field'] ?? null;
        $value = $change['value'] ?? [];

        switch ($field) {
            case 'comments':
                $this->processMetaComment($value);
                break;
            case 'feed':
                $this->processMetaPost($value);
                break;
        }
    }

    /**
     * Process Meta comment
     */
    protected function processMetaComment(array $comment): void
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
    protected function processMetaPost(array $post): void
    {
        // Implementation for new posts
        Log::info("Meta post event received", $post);
    }

    /**
     * Process WhatsApp message
     */
    protected function processWhatsAppMessage(array $data): void
    {
        foreach ($data['messages'] ?? [] as $message) {
            $messageId = $message['id'];
            $from = $message['from'];
            $text = $message['text']['body'] ?? '';

            // Find integration
            $phoneNumberId = $data['metadata']['phone_number_id'] ?? null;
            $integration = Integration::where('settings->phone_number_id', $phoneNumberId)
                ->where('platform', 'whatsapp')
                ->where('is_active', true)
                ->first();

            if (!$integration) return;

            // Store message
            DB::table('cmis_social.social_messages')->insert([
                'message_id' => \Illuminate\Support\Str::uuid(),
                'org_id' => $integration->org_id,
                'integration_id' => $integration->integration_id,
                'platform' => 'whatsapp',
                'platform_message_id' => $messageId,
                'sender_id' => $from,
                'message_text' => $text,
                'direction' => 'inbound',
                'received_at' => now(),
                'created_at' => now(),
            ]);

            Log::info("Stored WhatsApp message {$messageId}");
        }
    }

    /**
     * Process TikTok comment
     */
    protected function processTikTokComment(array $data): void
    {
        Log::info("TikTok comment event received", $data);
        // Implementation for TikTok comments
    }

    /**
     * Process TikTok video update
     */
    protected function processTikTokVideoUpdate(array $data): void
    {
        Log::info("TikTok video update event received", $data);
        // Implementation for TikTok video updates
    }

    /**
     * Process Twitter tweet
     */
    protected function processTwitterTweet(array $tweet): void
    {
        Log::info("Twitter tweet event received", $tweet);
        // Implementation for Twitter tweets
    }

    /**
     * Process Twitter DM
     */
    protected function processTwitterDM(array $dm): void
    {
        Log::info("Twitter DM event received", $dm);
        // Implementation for Twitter DMs
    }

    /**
     * Verify TikTok webhook signature
     */
    protected function verifyTikTokSignature(string $payload, ?string $signature): bool
    {
        if (!$signature) return false;

        $secret = config('services.tiktok.client_secret');
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
