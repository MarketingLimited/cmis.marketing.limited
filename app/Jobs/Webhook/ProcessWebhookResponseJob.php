<?php

namespace App\Jobs\Webhook;

use App\Models\Webhook\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessWebhookResponseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    protected Webhook $webhook;
    protected array $payload;

    public function __construct(Webhook $webhook, array $payload)
    {
        $this->webhook = $webhook;
        $this->payload = $payload;
    }

    public function handle(): array
    {
        $result = [
            'success' => true,
        ];

        Log::info('Processing webhook response', [
            'webhook_id' => $this->webhook->webhook_id,
            'url' => $this->webhook->url,
            'event' => $this->webhook->event ?? $this->payload['event'] ?? 'unknown',
        ]);

        try {
            // Check if webhook is active
            if ($this->webhook->status !== 'active') {
                Log::warning('Webhook is inactive, skipping delivery', [
                    'webhook_id' => $this->webhook->webhook_id,
                ]);

                $result['success'] = false;
                $result['error'] = 'Webhook is inactive';
                return $result;
            }

            // Check retry limit
            $retryCount = $this->webhook->retry_count ?? 0;
            $maxRetries = $this->webhook->max_retries ?? 3;

            if ($retryCount >= $maxRetries) {
                Log::warning('Webhook exceeded max retries', [
                    'webhook_id' => $this->webhook->webhook_id,
                    'retry_count' => $retryCount,
                    'max_retries' => $maxRetries,
                ]);

                $result['success'] = false;
                $result['error'] = 'Max retries exceeded';
                return $result;
            }

            // Prepare headers
            $headers = $this->prepareHeaders();

            // Send HTTP POST request
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->post($this->webhook->url, $this->payload);

            if ($response->successful()) {
                $result['status_code'] = $response->status();
                $result['response_time'] = $response->handlerStats()['total_time'] ?? null;
                $result['response_body'] = $response->json();

                // Update success statistics
                $this->updateStatistics(true);

                // Log delivery
                $this->logDelivery(true, $response->status(), $response->body());

                Log::info('Webhook response processed successfully', [
                    'webhook_id' => $this->webhook->webhook_id,
                    'status_code' => $response->status(),
                ]);

                // Reset retry count on success
                $this->webhook->update(['retry_count' => 0]);

            } else {
                $result['success'] = false;
                $result['status_code'] = $response->status();
                $result['error'] = 'HTTP request failed';

                // Update failure statistics
                $this->updateStatistics(false);

                // Log delivery failure
                $this->logDelivery(false, $response->status(), $response->body());

                Log::warning('Webhook response processing failed', [
                    'webhook_id' => $this->webhook->webhook_id,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);

                // Increment retry count
                $this->webhook->increment('retry_count');

                // Retry on specific status codes
                if (in_array($response->status(), [408, 429, 500, 502, 503, 504])) {
                    if ($this->attempts() < $this->tries) {
                        throw new \Exception("Webhook delivery failed with retriable status {$response->status()}");
                    }
                }
            }

        } catch (\Exception $e) {
            $result['success'] = false;
            $result['error'] = $e->getMessage();

            // Update failure statistics
            $this->updateStatistics(false);

            // Log delivery failure
            $this->logDelivery(false, null, $e->getMessage());

            Log::error('Webhook response processing exception', [
                'webhook_id' => $this->webhook->webhook_id,
                'error' => $e->getMessage(),
            ]);

            // Increment retry count
            $this->webhook->increment('retry_count');

            // Re-throw to trigger retry if within retry limit
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }

        return $result;
    }

    protected function prepareHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'CMIS-Webhook/1.0',
        ];

        // Add custom headers from webhook configuration
        if ($this->webhook->headers) {
            $headers = array_merge($headers, $this->webhook->headers);
        }

        // Generate HMAC signature if secret is set
        if ($this->webhook->secret) {
            $signature = hash_hmac('sha256', json_encode($this->payload), $this->webhook->secret);
            $headers['X-Webhook-Signature'] = $signature;
            $headers['X-Webhook-Signature-256'] = 'sha256=' . $signature;
        }

        return $headers;
    }

    protected function updateStatistics(bool $success): void
    {
        try {
            // Increment delivery count
            $this->webhook->increment('delivery_count');

            if ($success) {
                $this->webhook->increment('success_count');
                $this->webhook->update([
                    'last_delivery_at' => now(),
                    'last_delivery_status' => 'success',
                ]);
            } else {
                $this->webhook->increment('failure_count');
                $this->webhook->update([
                    'last_delivery_at' => now(),
                    'last_delivery_status' => 'failed',
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to update webhook statistics: {$e->getMessage()}");
        }
    }

    protected function logDelivery(bool $success, ?int $statusCode, ?string $response): void
    {
        try {
            DB::table('cmis_webhook.webhook_delivery_logs')->insert([
                'webhook_id' => $this->webhook->webhook_id,
                'org_id' => $this->webhook->org_id,
                'event' => $this->webhook->event ?? $this->payload['event'] ?? null,
                'payload' => json_encode($this->payload),
                'status' => $success ? 'success' : 'failed',
                'status_code' => $statusCode,
                'response' => $response ? substr($response, 0, 1000) : null, // Limit to 1000 chars
                'attempt' => $this->attempts(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to log webhook delivery: {$e->getMessage()}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook response processing job failed permanently', [
            'webhook_id' => $this->webhook->webhook_id,
            'url' => $this->webhook->url,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Update webhook as failed
        try {
            $this->webhook->update([
                'last_delivery_at' => now(),
                'last_delivery_status' => 'failed',
                'last_error' => $exception->getMessage(),
            ]);

            // Log final failure
            $this->logDelivery(false, null, "Job failed: " . $exception->getMessage());
        } catch (\Exception $e) {
            Log::warning("Failed to update webhook failure status: {$e->getMessage()}");
        }
    }
}
