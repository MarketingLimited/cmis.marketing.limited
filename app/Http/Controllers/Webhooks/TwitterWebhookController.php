<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Twitter/X Webhook Controller
 *
 * Handles webhooks from Twitter/X Platform
 */
class TwitterWebhookController extends Controller
{
    use ApiResponse;

    /**
     * Handle Twitter/X webhooks
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        // Verify signature for POST requests (CRITICAL SECURITY)
        if (!$this->verifySignature($request)) {
            Log::warning('Twitter webhook signature verification failed', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);
            return $this->unauthorized('Invalid webhook signature');
        }

        try {
            $data = $request->all();
            Log::info('Twitter webhook received', ['data' => $data]);

            // Process tweet events
            if (isset($data['tweet_create_events'])) {
                foreach ($data['tweet_create_events'] as $tweet) {
                    $this->processTweet($tweet);
                }
            }

            // Process direct messages
            if (isset($data['direct_message_events'])) {
                foreach ($data['direct_message_events'] as $dm) {
                    $this->processDirectMessage($dm);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error("Twitter webhook error: {$e->getMessage()}");
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Process Twitter tweet
     */
    protected function processTweet(array $tweet): void
    {
        Log::info("Twitter tweet event received", $tweet);
        // Implementation for Twitter tweets
    }

    /**
     * Process Twitter DM
     */
    protected function processDirectMessage(array $dm): void
    {
        Log::info("Twitter DM event received", $dm);
        // Implementation for Twitter DMs
    }

    /**
     * Verify Twitter/X webhook signature
     *
     * CRITICAL SECURITY: Prevents unauthorized webhook calls
     * Uses SHA256 HMAC with consumer secret
     */
    protected function verifySignature(Request $request): bool
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
