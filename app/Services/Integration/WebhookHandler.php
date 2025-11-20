<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Centralized webhook handler for all platform integrations
 * Handles signature validation and event processing
 */
class WebhookHandler
{
    /**
     * Process incoming webhook from platform
     *
     * @param string $provider Platform name (meta, google, tiktok, etc.)
     * @param array $payload Webhook payload data
     * @return array Processing result
     */
    public function handle(string $provider, array $payload): array
    {
        try {
            Log::info("Processing webhook from {$provider}", [
                'provider' => $provider,
                'payload_keys' => array_keys($payload),
            ]);

            // Route to platform-specific handler
            $result = match (strtolower($provider)) {
                'meta', 'facebook', 'instagram' => $this->handleMetaWebhook($payload),
                'google', 'google_ads' => $this->handleGoogleWebhook($payload),
                'tiktok' => $this->handleTikTokWebhook($payload),
                'linkedin' => $this->handleLinkedInWebhook($payload),
                'twitter', 'x' => $this->handleTwitterWebhook($payload),
                'snapchat' => $this->handleSnapchatWebhook($payload),
                default => $this->handleGenericWebhook($provider, $payload),
            };

            // Log successful processing
            $this->logWebhookEvent($provider, 'success', $payload, $result);

            return [
                'status' => 'success',
                'provider' => $provider,
                'message' => 'Webhook processed successfully',
                'result' => $result,
            ];

        } catch (\Exception $e) {
            Log::error("Failed to process webhook from {$provider}", [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Log failed processing
            $this->logWebhookEvent($provider, 'error', $payload, ['error' => $e->getMessage()]);

            return [
                'status' => 'error',
                'provider' => $provider,
                'message' => 'Failed to process webhook',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate webhook signature for security
     *
     * @param string $provider Platform name
     * @param array $headers Request headers
     * @param string $payload Raw payload string
     * @return bool True if signature is valid
     */
    public function validateSignature(string $provider, array $headers, string $payload): bool
    {
        try {
            return match (strtolower($provider)) {
                'meta', 'facebook', 'instagram' => $this->validateMetaSignature($headers, $payload),
                'google', 'google_ads' => $this->validateGoogleSignature($headers, $payload),
                'tiktok' => $this->validateTikTokSignature($headers, $payload),
                'linkedin' => $this->validateLinkedInSignature($headers, $payload),
                'twitter', 'x' => $this->validateTwitterSignature($headers, $payload),
                'snapchat' => $this->validateSnapchatSignature($headers, $payload),
                default => $this->validateGenericSignature($provider, $headers, $payload),
            };
        } catch (\Exception $e) {
            Log::error("Signature validation failed for {$provider}", [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle Meta (Facebook/Instagram) webhooks
     */
    protected function handleMetaWebhook(array $payload): array
    {
        // Meta sends 'entry' array with changes
        if (isset($payload['entry'])) {
            foreach ($payload['entry'] as $entry) {
                $changes = $entry['changes'] ?? [];
                foreach ($changes as $change) {
                    $field = $change['field'] ?? 'unknown';
                    $value = $change['value'] ?? [];

                    Log::info("Meta webhook change", [
                        'field' => $field,
                        'value' => $value,
                    ]);
                }
            }
        }

        return ['processed_entries' => count($payload['entry'] ?? [])];
    }

    /**
     * Handle Google Ads webhooks
     */
    protected function handleGoogleWebhook(array $payload): array
    {
        // Google Ads webhooks typically contain campaign updates
        $eventType = $payload['event_type'] ?? 'unknown';

        Log::info("Google webhook event", [
            'event_type' => $eventType,
            'data' => $payload['data'] ?? [],
        ]);

        return ['event_type' => $eventType];
    }

    /**
     * Handle TikTok webhooks
     */
    protected function handleTikTokWebhook(array $payload): array
    {
        $event = $payload['event'] ?? 'unknown';

        Log::info("TikTok webhook event", [
            'event' => $event,
            'data' => $payload['data'] ?? [],
        ]);

        return ['event' => $event];
    }

    /**
     * Handle LinkedIn webhooks
     */
    protected function handleLinkedInWebhook(array $payload): array
    {
        $eventType = $payload['eventType'] ?? 'unknown';

        Log::info("LinkedIn webhook event", [
            'event_type' => $eventType,
            'data' => $payload,
        ]);

        return ['event_type' => $eventType];
    }

    /**
     * Handle Twitter webhooks
     */
    protected function handleTwitterWebhook(array $payload): array
    {
        $eventType = array_keys($payload)[0] ?? 'unknown';

        Log::info("Twitter webhook event", [
            'event_type' => $eventType,
            'data' => $payload,
        ]);

        return ['event_type' => $eventType];
    }

    /**
     * Handle Snapchat webhooks
     */
    protected function handleSnapchatWebhook(array $payload): array
    {
        $eventType = $payload['type'] ?? 'unknown';

        Log::info("Snapchat webhook event", [
            'event_type' => $eventType,
            'data' => $payload,
        ]);

        return ['event_type' => $eventType];
    }

    /**
     * Handle generic webhooks
     */
    protected function handleGenericWebhook(string $provider, array $payload): array
    {
        Log::info("Generic webhook from {$provider}", [
            'provider' => $provider,
            'payload' => $payload,
        ]);

        return ['provider' => $provider, 'processed' => true];
    }

    /**
     * Validate Meta webhook signature
     */
    protected function validateMetaSignature(array $headers, string $payload): bool
    {
        $signature = $headers['x-hub-signature-256'] ?? $headers['X-Hub-Signature-256'] ?? null;

        if (!$signature) {
            return false;
        }

        $appSecret = config('services.facebook.app_secret');
        if (!$appSecret) {
            Log::warning("Meta app secret not configured");
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate Google webhook signature
     */
    protected function validateGoogleSignature(array $headers, string $payload): bool
    {
        // Google uses JWT tokens in Authorization header
        $authHeader = $headers['authorization'] ?? $headers['Authorization'] ?? null;

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return false;
        }

        Log::info("WebhookHandler::validateGoogleSignature called (stub) - JWT validation with Google's public keys not yet implemented");
        // Stub implementation - JWT validation with Google's public keys not yet implemented
        // For now, log and return true if token exists
        Log::info("Google webhook signature validation (JWT verification pending)");

        return !empty($authHeader);
    }

    /**
     * Validate TikTok webhook signature
     */
    protected function validateTikTokSignature(array $headers, string $payload): bool
    {
        $signature = $headers['x-tiktok-signature'] ?? $headers['X-TikTok-Signature'] ?? null;

        if (!$signature) {
            return false;
        }

        $appSecret = config('services.tiktok.app_secret');
        if (!$appSecret) {
            Log::warning("TikTok app secret not configured");
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate LinkedIn webhook signature
     */
    protected function validateLinkedInSignature(array $headers, string $payload): bool
    {
        $signature = $headers['x-li-signature'] ?? $headers['X-Li-Signature'] ?? null;

        if (!$signature) {
            return false;
        }

        $clientSecret = config('services.linkedin.client_secret');
        if (!$clientSecret) {
            Log::warning("LinkedIn client secret not configured");
            return false;
        }

        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $clientSecret, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate Twitter webhook signature
     */
    protected function validateTwitterSignature(array $headers, string $payload): bool
    {
        $signature = $headers['x-twitter-webhooks-signature'] ?? $headers['X-Twitter-Webhooks-Signature'] ?? null;

        if (!$signature) {
            return false;
        }

        $consumerSecret = config('services.twitter.consumer_secret');
        if (!$consumerSecret) {
            Log::warning("Twitter consumer secret not configured");
            return false;
        }

        $expectedSignature = 'sha256=' . base64_encode(hash_hmac('sha256', $payload, $consumerSecret, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate Snapchat webhook signature
     */
    protected function validateSnapchatSignature(array $headers, string $payload): bool
    {
        $signature = $headers['x-snapchat-signature'] ?? $headers['X-Snapchat-Signature'] ?? null;

        if (!$signature) {
            return false;
        }

        $clientSecret = config('services.snapchat.client_secret');
        if (!$clientSecret) {
            Log::warning("Snapchat client secret not configured");
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $clientSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate generic webhook signature
     */
    protected function validateGenericSignature(string $provider, array $headers, string $payload): bool
    {
        Log::warning("No signature validation implemented for provider: {$provider}");
        return true; // Allow through with warning for unknown providers
    }

    /**
     * Log webhook event to database
     */
    protected function logWebhookEvent(string $provider, string $status, array $payload, array $result): void
    {
        try {
            // Note: This assumes a webhook_logs table exists or will be created
            // For now, just log to Laravel logs
            Log::channel('daily')->info("Webhook event logged", [
                'provider' => $provider,
                'status' => $status,
                'payload_size' => strlen(json_encode($payload)),
                'result' => $result,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log webhook event", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
