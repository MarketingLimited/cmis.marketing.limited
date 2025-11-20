<?php

namespace App\Jobs\Webhooks;

use App\Models\Webhook\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
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

        Log::info('Processing webhook delivery', [
            'webhook_id' => $this->webhook->webhook_id,
            'url' => $this->webhook->url,
            'event' => $this->webhook->event,
        ]);

        try {
            // Check if webhook is active
            if (!$this->webhook->is_active) {
                Log::warning('Webhook is inactive, skipping delivery', [
                    'webhook_id' => $this->webhook->webhook_id,
                ]);

                $result['success'] = false;
                $result['error'] = 'Webhook is inactive';
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

                // Update success statistics
                $this->updateStatistics(true);

                Log::info('Webhook delivered successfully', [
                    'webhook_id' => $this->webhook->webhook_id,
                    'status_code' => $response->status(),
                ]);
            } else {
                $result['success'] = false;
                $result['status_code'] = $response->status();
                $result['error'] = 'HTTP request failed';

                // Update failure statistics
                $this->updateStatistics(false);

                Log::warning('Webhook delivery failed', [
                    'webhook_id' => $this->webhook->webhook_id,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);

                // Retry if configured
                if (($this->webhook->max_retries ?? 0) > 0 && $this->attempts() < $this->webhook->max_retries) {
                    throw new \Exception("Webhook delivery failed with status {$response->status()}");
                }
            }

        } catch (\Exception $e) {
            $result['success'] = false;
            $result['error'] = $e->getMessage();

            // Update failure statistics
            $this->updateStatistics(false);

            Log::error('Webhook delivery exception', [
                'webhook_id' => $this->webhook->webhook_id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry if within retry limit
            if (($this->webhook->max_retries ?? 0) > 0 && $this->attempts() < $this->webhook->max_retries) {
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
        }

        return $headers;
    }

    protected function updateStatistics(bool $success): void
    {
        try {
            $this->webhook->increment('total_deliveries');

            if ($success) {
                $this->webhook->increment('successful_deliveries');
                $this->webhook->update([
                    'last_delivery_at' => now(),
                    'last_delivery_status' => 'success',
                ]);
            } else {
                $this->webhook->increment('failed_deliveries');
                $this->webhook->update([
                    'last_delivery_at' => now(),
                    'last_delivery_status' => 'failed',
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to update webhook statistics: {$e->getMessage()}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook delivery job failed permanently', [
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
        } catch (\Exception $e) {
            Log::warning("Failed to update webhook failure status: {$e->getMessage()}");
        }
    }
}
