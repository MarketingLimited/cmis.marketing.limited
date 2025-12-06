<?php

namespace App\Services\Webhook;

use App\Models\Webhook\WebhookConfiguration;
use App\Models\Webhook\WebhookDeliveryLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

/**
 * WebhookForwardingService
 *
 * Handles forwarding webhook events to user-configured endpoints.
 * Implements verification flow, payload signing, and delivery logging.
 */
class WebhookForwardingService
{
    /**
     * Verify a webhook endpoint
     *
     * Sends a verification request to the callback URL with the verify token.
     * The endpoint must respond with the challenge value.
     *
     * @param WebhookConfiguration $config
     * @return array{success: bool, message: string}
     */
    public function verifyEndpoint(WebhookConfiguration $config): array
    {
        $challenge = bin2hex(random_bytes(16));

        try {
            $response = Http::timeout($config->timeout_seconds)
                ->get($config->callback_url, [
                    'hub_mode' => 'subscribe',
                    'hub_verify_token' => $config->verify_token,
                    'hub_challenge' => $challenge,
                ]);

            if ($response->successful() && trim($response->body()) === $challenge) {
                $config->markVerified();

                Log::info('Webhook endpoint verified', [
                    'config_id' => $config->id,
                    'org_id' => $config->org_id,
                    'callback_url' => $config->callback_url,
                ]);

                return [
                    'success' => true,
                    'message' => 'Webhook endpoint verified successfully.',
                ];
            }

            $errorMessage = $response->successful()
                ? 'Challenge response did not match. Expected: ' . $challenge
                : 'HTTP ' . $response->status() . ': ' . $response->body();

            Log::warning('Webhook endpoint verification failed', [
                'config_id' => $config->id,
                'callback_url' => $config->callback_url,
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        } catch (RequestException $e) {
            Log::error('Webhook endpoint verification request failed', [
                'config_id' => $config->id,
                'callback_url' => $config->callback_url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('Webhook endpoint verification error', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Verification error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Forward an event to all matching webhook configurations
     *
     * @param string $orgId Organization ID
     * @param string $eventType Event type (e.g., 'message.received')
     * @param array $payload Event payload
     * @param string|null $platform Platform filter
     * @param string|null $webhookEventId Original webhook event ID
     * @return int Number of webhooks triggered
     */
    public function forwardEvent(
        string $orgId,
        string $eventType,
        array $payload,
        ?string $platform = null,
        ?string $webhookEventId = null
    ): int {
        // Find matching webhook configurations
        $query = WebhookConfiguration::where('org_id', $orgId)
            ->active()
            ->verified()
            ->subscribedTo($eventType);

        if ($platform) {
            $query->forPlatform($platform);
        }

        $configurations = $query->get();

        $triggered = 0;

        foreach ($configurations as $config) {
            $success = $this->deliverToEndpoint($config, $eventType, $payload, $webhookEventId);
            if ($success) {
                $triggered++;
            }
        }

        return $triggered;
    }

    /**
     * Deliver event to a specific webhook endpoint
     *
     * @param WebhookConfiguration $config
     * @param string $eventType
     * @param array $payload
     * @param string|null $webhookEventId
     * @return bool
     */
    public function deliverToEndpoint(
        WebhookConfiguration $config,
        string $eventType,
        array $payload,
        ?string $webhookEventId = null
    ): bool {
        // Prepare the webhook payload
        $webhookPayload = [
            'event' => $eventType,
            'timestamp' => now()->toIso8601String(),
            'data' => $payload,
        ];

        $jsonPayload = json_encode($webhookPayload);
        $signature = $config->signPayload($jsonPayload);

        // Prepare headers
        $headers = [
            'Content-Type' => $config->content_type,
            'X-CMIS-Signature' => $signature,
            'X-CMIS-Event' => $eventType,
            'X-CMIS-Timestamp' => (string) time(),
            'User-Agent' => 'CMIS-Webhook/1.0',
        ];

        // Add custom headers if configured
        if (!empty($config->custom_headers)) {
            $headers = array_merge($headers, $config->custom_headers);
        }

        // Create delivery log
        $deliveryLog = WebhookDeliveryLog::create([
            'webhook_config_id' => $config->id,
            'webhook_event_id' => $webhookEventId,
            'org_id' => $config->org_id,
            'callback_url' => $config->callback_url,
            'event_type' => $eventType,
            'payload' => $webhookPayload,
            'request_headers' => $headers,
            'status' => WebhookDeliveryLog::STATUS_PENDING,
            'attempt_number' => 1,
        ]);

        return $this->attemptDelivery($deliveryLog, $config, $jsonPayload, $headers);
    }

    /**
     * Attempt to deliver a webhook
     *
     * @param WebhookDeliveryLog $deliveryLog
     * @param WebhookConfiguration $config
     * @param string $jsonPayload
     * @param array $headers
     * @return bool
     */
    protected function attemptDelivery(
        WebhookDeliveryLog $deliveryLog,
        WebhookConfiguration $config,
        string $jsonPayload,
        array $headers
    ): bool {
        $startTime = microtime(true);

        try {
            $response = Http::timeout($config->timeout_seconds)
                ->withHeaders($headers)
                ->withBody($jsonPayload, $config->content_type)
                ->post($config->callback_url);

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $deliveryLog->markSuccess(
                    $response->status(),
                    substr($response->body(), 0, 10000), // Limit stored response
                    $response->headers(),
                    $responseTimeMs
                );

                $config->recordSuccess();

                Log::info('Webhook delivered successfully', [
                    'config_id' => $config->id,
                    'delivery_id' => $deliveryLog->id,
                    'event_type' => $deliveryLog->event_type,
                    'response_time_ms' => $responseTimeMs,
                ]);

                return true;
            }

            // Non-2xx response
            $deliveryLog->markFailed(
                "HTTP {$response->status()}: " . substr($response->body(), 0, 500),
                $response->status(),
                substr($response->body(), 0, 10000)
            );

            $config->recordFailure("HTTP {$response->status()}");

            Log::warning('Webhook delivery failed with non-2xx response', [
                'config_id' => $config->id,
                'delivery_id' => $deliveryLog->id,
                'status' => $response->status(),
            ]);

            return false;
        } catch (RequestException $e) {
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            $deliveryLog->markFailed(
                'Request failed: ' . $e->getMessage(),
                $e->response?->status(),
                $e->response?->body() ? substr($e->response->body(), 0, 10000) : null
            );

            $config->recordFailure($e->getMessage());

            Log::error('Webhook delivery request failed', [
                'config_id' => $config->id,
                'delivery_id' => $deliveryLog->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        } catch (\Exception $e) {
            $deliveryLog->markFailed('Unexpected error: ' . $e->getMessage());
            $config->recordFailure($e->getMessage());

            Log::error('Webhook delivery unexpected error', [
                'config_id' => $config->id,
                'delivery_id' => $deliveryLog->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Retry failed deliveries
     *
     * @param int $limit Maximum number to retry
     * @return int Number retried
     */
    public function retryFailedDeliveries(int $limit = 100): int
    {
        $deliveries = WebhookDeliveryLog::readyForRetry()
            ->with('configuration')
            ->limit($limit)
            ->get();

        $retried = 0;

        foreach ($deliveries as $delivery) {
            $config = $delivery->configuration;

            if (!$config || !$config->is_active || !$config->is_verified) {
                $delivery->update(['status' => WebhookDeliveryLog::STATUS_FAILED]);
                continue;
            }

            $delivery->prepareRetry();

            $jsonPayload = json_encode($delivery->payload);
            $signature = $config->signPayload($jsonPayload);

            $headers = $delivery->request_headers ?? [];
            $headers['X-CMIS-Signature'] = $signature;
            $headers['X-CMIS-Retry-Attempt'] = (string) $delivery->attempt_number;

            $this->attemptDelivery($delivery, $config, $jsonPayload, $headers);
            $retried++;
        }

        return $retried;
    }

    /**
     * Test a webhook configuration with a test event
     *
     * @param WebhookConfiguration $config
     * @return array{success: bool, message: string, response_time_ms: int|null}
     */
    public function testWebhook(WebhookConfiguration $config): array
    {
        $testPayload = [
            'event' => 'test.webhook',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message' => 'This is a test webhook from CMIS',
                'webhook_id' => $config->id,
                'webhook_name' => $config->name,
            ],
        ];

        $jsonPayload = json_encode($testPayload);
        $signature = $config->signPayload($jsonPayload);

        $headers = [
            'Content-Type' => $config->content_type,
            'X-CMIS-Signature' => $signature,
            'X-CMIS-Event' => 'test.webhook',
            'X-CMIS-Timestamp' => (string) time(),
            'User-Agent' => 'CMIS-Webhook/1.0',
        ];

        $startTime = microtime(true);

        try {
            $response = Http::timeout($config->timeout_seconds)
                ->withHeaders($headers)
                ->withBody($jsonPayload, $config->content_type)
                ->post($config->callback_url);

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => "Test webhook sent successfully. Response: HTTP {$response->status()}",
                    'response_time_ms' => $responseTimeMs,
                ];
            }

            return [
                'success' => false,
                'message' => "HTTP {$response->status()}: " . substr($response->body(), 0, 200),
                'response_time_ms' => $responseTimeMs,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Request failed: ' . $e->getMessage(),
                'response_time_ms' => null,
            ];
        }
    }
}
