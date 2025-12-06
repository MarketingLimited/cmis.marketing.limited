<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Core\Integration;
use App\Models\Platform\WebhookEvent;
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
    use ApiResponse;

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

        // Verify signature for POST requests (CRITICAL SECURITY)
        if (!$this->verifyMetaSignature($request)) {
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

            // Store webhook event for audit and reliable processing
            $webhookEvent = WebhookEvent::createFromRequest(
                platform: 'meta',
                payload: $data,
                headers: $request->headers->all(),
                rawPayload: config('webhook.store_raw') ? $request->getContent() : null,
                signature: $request->header('X-Hub-Signature-256'),
                signatureValid: true, // Already verified above
                sourceIp: $request->ip(),
                userAgent: $request->userAgent()
            );

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

            // Mark webhook event as processed
            $webhookEvent->markProcessed();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error("Meta webhook error: {$e->getMessage()}");

            // Mark webhook event as failed (will retry based on max_attempts)
            if (isset($webhookEvent)) {
                $webhookEvent->markFailed($e->getMessage());
            }

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

        // Verify signature for POST requests (CRITICAL SECURITY)
        if (!$this->verifyWhatsAppSignature($request)) {
            Log::warning('WhatsApp webhook signature verification failed', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);
            return $this->unauthorized('Invalid webhook signature');
        }

        // Handle webhook event (POST request)
        try {
            $data = $request->all();
            Log::info('WhatsApp webhook received', ['data' => $data]);

            // Store webhook event for audit and reliable processing
            $webhookEvent = WebhookEvent::createFromRequest(
                platform: 'whatsapp',
                payload: $data,
                headers: $request->headers->all(),
                rawPayload: config('webhook.store_raw') ? $request->getContent() : null,
                signature: $request->header('X-Hub-Signature-256'),
                signatureValid: true, // Already verified above
                sourceIp: $request->ip(),
                userAgent: $request->userAgent()
            );

            foreach ($data['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    if ($change['field'] === 'messages') {
                        $this->processWhatsAppMessage($change['value']);
                    }
                }
            }

            // Mark webhook event as processed
            $webhookEvent->markProcessed();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error("WhatsApp webhook error: {$e->getMessage()}");

            // Mark webhook event as failed
            if (isset($webhookEvent)) {
                $webhookEvent->markFailed($e->getMessage());
            }

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
        $webhookEvent = null;

        try {
            $data = $request->all();
            Log::info('TikTok webhook received', ['data' => $data]);

            // Verify signature
            $signature = $request->header('X-TikTok-Signature');
            $signatureValid = $this->verifyTikTokSignature($request->getContent(), $signature);

            // Store webhook event for audit (before processing, includes signature status)
            $webhookEvent = WebhookEvent::createFromRequest(
                platform: 'tiktok',
                payload: $data,
                headers: $request->headers->all(),
                rawPayload: config('webhook.store_raw') ? $request->getContent() : null,
                signature: $signature,
                signatureValid: $signatureValid,
                sourceIp: $request->ip(),
                userAgent: $request->userAgent()
            );

            if (!$signatureValid) {
                $webhookEvent->markFailed('Invalid signature', 'INVALID_SIGNATURE');
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

            // Mark webhook event as processed
            $webhookEvent->markProcessed();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error("TikTok webhook error: {$e->getMessage()}");

            // Mark webhook event as failed
            if ($webhookEvent) {
                $webhookEvent->markFailed($e->getMessage());
            }

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
        // Verify signature for POST requests (CRITICAL SECURITY)
        if (!$this->verifyTwitterSignature($request)) {
            Log::warning('Twitter webhook signature verification failed', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            // Store failed signature event for audit
            WebhookEvent::createFromRequest(
                platform: 'twitter',
                payload: $request->all(),
                headers: $request->headers->all(),
                signature: $request->header('X-Twitter-Webhooks-Signature'),
                signatureValid: false,
                sourceIp: $request->ip(),
                userAgent: $request->userAgent()
            )->markFailed('Invalid signature', 'INVALID_SIGNATURE');

            return $this->unauthorized('Invalid webhook signature');
        }

        try {
            $data = $request->all();
            Log::info('Twitter webhook received', ['data' => $data]);

            // Store webhook event for audit and reliable processing
            $webhookEvent = WebhookEvent::createFromRequest(
                platform: 'twitter',
                payload: $data,
                headers: $request->headers->all(),
                rawPayload: config('webhook.store_raw') ? $request->getContent() : null,
                signature: $request->header('X-Twitter-Webhooks-Signature'),
                signatureValid: true, // Already verified above
                sourceIp: $request->ip(),
                userAgent: $request->userAgent()
            );

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

            // Mark webhook event as processed
            $webhookEvent->markProcessed();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error("Twitter webhook error: {$e->getMessage()}");

            // Mark webhook event as failed
            if (isset($webhookEvent)) {
                $webhookEvent->markFailed($e->getMessage());
            }

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

    /**
     * Verify Meta (Facebook) webhook signature
     *
     * CRITICAL SECURITY: Prevents unauthorized webhook calls
     * Uses HMAC-SHA256 with app secret
     */
    protected function verifyMetaSignature(Request $request): bool
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

    /**
     * Verify WhatsApp webhook signature
     *
     * CRITICAL SECURITY: Prevents unauthorized webhook calls
     * Uses same signature method as Meta (HMAC-SHA256)
     */
    protected function verifyWhatsAppSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (empty($signature)) {
            Log::warning('WhatsApp webhook missing signature header');
            return false;
        }

        $appSecret = config('services.whatsapp.app_secret');

        if (empty($appSecret)) {
            Log::error('WhatsApp app secret not configured');
            return false;
        }

        // Get raw request body
        $payload = $request->getContent();

        // Compute expected signature
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

        // Constant-time comparison to prevent timing attacks
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Twitter/X webhook signature
     *
     * CRITICAL SECURITY: Prevents unauthorized webhook calls
     * Uses SHA256 HMAC with consumer secret
     */
    protected function verifyTwitterSignature(Request $request): bool
    {
        $signature = $request->header('X-Twitter-Webhooks-Signature');

        if (empty($signature)) {
            Log::warning('Twitter webhook missing signature header');
            return false;
        }

        $consumerSecret = config('services.twitter.consumer_secret');

        if (empty($consumerSecret)) {
            Log::error('Twitter consumer secret not configured');
            return false;
        }

        // Get raw request body
        $payload = $request->getContent();

        // Compute expected signature
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $consumerSecret);

        // Constant-time comparison to prevent timing attacks
        return hash_equals($expectedSignature, $signature);
    }
}
