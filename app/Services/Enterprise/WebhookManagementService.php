<?php

namespace App\Services\Enterprise;

use Illuminate\Support\Facades\{DB, Log, Http};
use Carbon\Carbon;

/**
 * Webhook Management Service (Phase 5 - Enterprise Features)
 *
 * Comprehensive webhook system for external integrations:
 * - Webhook subscriptions
 * - Event-based triggers
 * - Retry logic with exponential backoff
 * - Webhook authentication (HMAC signatures)
 * - Delivery tracking and logs
 * - Rate limiting
 */
class WebhookManagementService
{
    // Webhook events
    const EVENT_CAMPAIGN_CREATED = 'campaign.created';
    const EVENT_CAMPAIGN_UPDATED = 'campaign.updated';
    const EVENT_CAMPAIGN_COMPLETED = 'campaign.completed';
    const EVENT_ALERT_TRIGGERED = 'alert.triggered';
    const EVENT_BUDGET_THRESHOLD = 'budget.threshold_reached';
    const EVENT_PERFORMANCE_ANOMALY = 'performance.anomaly_detected';
    const EVENT_REPORT_GENERATED = 'report.generated';

    // Webhook status
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_FAILED = 'failed';

    // Delivery status
    const DELIVERY_PENDING = 'pending';
    const DELIVERY_SUCCESS = 'success';
    const DELIVERY_FAILED = 'failed';
    const DELIVERY_RETRYING = 'retrying';

    // Retry settings
    const MAX_RETRIES = 5;
    const RETRY_DELAYS = [60, 300, 900, 3600, 7200]; // seconds

    /**
     * Create webhook subscription
     *
     * @param array $data
     * @return array
     */
    public function createWebhook(array $data): array
    {
        try {
            $webhookId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $secret = $this->generateSecret();

            DB::table('cmis_enterprise.webhooks')->insert([
                'webhook_id' => $webhookId,
                'org_id' => $data['org_id'],
                'name' => $data['name'],
                'url' => $data['url'],
                'events' => json_encode($data['events']),
                'secret' => hash('sha256', $secret),
                'status' => self::STATUS_ACTIVE,
                'is_active' => true,
                'metadata' => json_encode($data['metadata'] ?? []),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            Log::info('Webhook created', [
                'webhook_id' => $webhookId,
                'org_id' => $data['org_id'],
                'events' => $data['events']
            ]);

            return [
                'success' => true,
                'webhook_id' => $webhookId,
                'secret' => $secret, // Return once, store hash
                'message' => 'Webhook created successfully. Save the secret key securely.'
            ];

        } catch (\Exception $e) {
            Log::error('Webhook creation error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update webhook
     *
     * @param string $webhookId
     * @param string $orgId
     * @param array $data
     * @return array
     */
    public function updateWebhook(string $webhookId, string $orgId, array $data): array
    {
        try {
            $updateData = ['updated_at' => Carbon::now()];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }

            if (isset($data['url'])) {
                $updateData['url'] = $data['url'];
            }

            if (isset($data['events'])) {
                $updateData['events'] = json_encode($data['events']);
            }

            if (isset($data['is_active'])) {
                $updateData['is_active'] = $data['is_active'];
                $updateData['status'] = $data['is_active'] ? self::STATUS_ACTIVE : self::STATUS_INACTIVE;
            }

            DB::table('cmis_enterprise.webhooks')
                ->where('webhook_id', $webhookId)
                ->where('org_id', $orgId)
                ->update($updateData);

            return [
                'success' => true,
                'message' => 'Webhook updated successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Webhook update error', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete webhook
     *
     * @param string $webhookId
     * @param string $orgId
     * @return bool
     */
    public function deleteWebhook(string $webhookId, string $orgId): bool
    {
        try {
            DB::table('cmis_enterprise.webhooks')
                ->where('webhook_id', $webhookId)
                ->where('org_id', $orgId)
                ->delete();

            Log::info('Webhook deleted', [
                'webhook_id' => $webhookId,
                'org_id' => $orgId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Webhook deletion error', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Trigger webhook event
     *
     * @param string $orgId
     * @param string $event
     * @param array $payload
     * @return array
     */
    public function triggerEvent(string $orgId, string $event, array $payload): array
    {
        $results = [
            'event' => $event,
            'webhooks_triggered' => 0,
            'deliveries' => []
        ];

        try {
            // Get webhooks subscribed to this event
            $webhooks = DB::table('cmis_enterprise.webhooks')
                ->where('org_id', $orgId)
                ->where('is_active', true)
                ->whereRaw("events::jsonb @> ?", [json_encode([$event])])
                ->get();

            foreach ($webhooks as $webhook) {
                $results['webhooks_triggered']++;

                // Queue webhook delivery
                $deliveryId = $this->queueDelivery($webhook, $event, $payload);

                $results['deliveries'][] = [
                    'webhook_id' => $webhook->webhook_id,
                    'delivery_id' => $deliveryId,
                    'status' => self::DELIVERY_PENDING
                ];
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('Webhook event trigger error', [
                'org_id' => $orgId,
                'event' => $event,
                'error' => $e->getMessage()
            ]);

            return array_merge($results, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Queue webhook delivery
     *
     * @param object $webhook
     * @param string $event
     * @param array $payload
     * @return string
     */
    protected function queueDelivery(object $webhook, string $event, array $payload): string
    {
        $deliveryId = \Ramsey\Uuid\Uuid::uuid4()->toString();

        // Build payload with signature
        $timestamp = time();
        $fullPayload = [
            'event' => $event,
            'timestamp' => $timestamp,
            'data' => $payload
        ];

        $signature = $this->generateSignature($fullPayload, $webhook->secret);

        DB::table('cmis_enterprise.webhook_deliveries')->insert([
            'delivery_id' => $deliveryId,
            'webhook_id' => $webhook->webhook_id,
            'event' => $event,
            'payload' => json_encode($fullPayload),
            'signature' => $signature,
            'status' => self::DELIVERY_PENDING,
            'attempts' => 0,
            'scheduled_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        return $deliveryId;
    }

    /**
     * Process pending webhook deliveries
     *
     * @return array
     */
    public function processDeliveries(): array
    {
        $results = [
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'retrying' => 0
        ];

        try {
            // Get pending deliveries
            $deliveries = DB::table('cmis_enterprise.webhook_deliveries')
                ->whereIn('status', [self::DELIVERY_PENDING, self::DELIVERY_RETRYING])
                ->where('scheduled_at', '<=', Carbon::now())
                ->limit(100)
                ->get();

            foreach ($deliveries as $delivery) {
                $results['processed']++;

                try {
                    $webhook = DB::table('cmis_enterprise.webhooks')
                        ->where('webhook_id', $delivery->webhook_id)
                        ->first();

                    if (!$webhook || !$webhook->is_active) {
                        // Mark as failed if webhook no longer exists or inactive
                        $this->updateDeliveryStatus($delivery->delivery_id, self::DELIVERY_FAILED, 'Webhook inactive');
                        $results['failed']++;
                        continue;
                    }

                    // Attempt delivery
                    $response = $this->sendWebhook($webhook, $delivery);

                    if ($response['success']) {
                        $this->updateDeliveryStatus(
                            $delivery->delivery_id,
                            self::DELIVERY_SUCCESS,
                            null,
                            $response['http_status']
                        );
                        $results['succeeded']++;
                    } else {
                        // Handle failure with retry logic
                        if ($delivery->attempts < self::MAX_RETRIES) {
                            $this->scheduleRetry($delivery);
                            $results['retrying']++;
                        } else {
                            $this->updateDeliveryStatus(
                                $delivery->delivery_id,
                                self::DELIVERY_FAILED,
                                $response['error'],
                                $response['http_status'] ?? null
                            );
                            $results['failed']++;
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('Webhook delivery error', [
                        'delivery_id' => $delivery->delivery_id,
                        'error' => $e->getMessage()
                    ]);

                    if ($delivery->attempts < self::MAX_RETRIES) {
                        $this->scheduleRetry($delivery);
                        $results['retrying']++;
                    } else {
                        $this->updateDeliveryStatus($delivery->delivery_id, self::DELIVERY_FAILED, $e->getMessage());
                        $results['failed']++;
                    }
                }
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('Process webhook deliveries error', [
                'error' => $e->getMessage()
            ]);

            return array_merge($results, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send webhook HTTP request
     *
     * @param object $webhook
     * @param object $delivery
     * @return array
     */
    protected function sendWebhook(object $webhook, object $delivery): array
    {
        try {
            $payload = json_decode($delivery->payload, true);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $delivery->signature,
                    'X-Webhook-Event' => $delivery->event,
                    'X-Webhook-Delivery' => $delivery->delivery_id
                ])
                ->post($webhook->url, $payload);

            $httpStatus = $response->status();

            // Consider 2xx responses as success
            if ($response->successful()) {
                Log::info('Webhook delivered successfully', [
                    'webhook_id' => $webhook->webhook_id,
                    'delivery_id' => $delivery->delivery_id,
                    'http_status' => $httpStatus
                ]);

                return [
                    'success' => true,
                    'http_status' => $httpStatus
                ];
            } else {
                Log::warning('Webhook delivery failed', [
                    'webhook_id' => $webhook->webhook_id,
                    'delivery_id' => $delivery->delivery_id,
                    'http_status' => $httpStatus,
                    'response' => $response->body()
                ]);

                return [
                    'success' => false,
                    'http_status' => $httpStatus,
                    'error' => "HTTP {$httpStatus}: " . substr($response->body(), 0, 200)
                ];
            }

        } catch (\Exception $e) {
            Log::error('Webhook HTTP request error', [
                'webhook_id' => $webhook->webhook_id,
                'delivery_id' => $delivery->delivery_id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Schedule retry for failed delivery
     *
     * @param object $delivery
     * @return void
     */
    protected function scheduleRetry(object $delivery): void
    {
        $attempt = $delivery->attempts;
        $delay = self::RETRY_DELAYS[$attempt] ?? self::RETRY_DELAYS[count(self::RETRY_DELAYS) - 1];

        DB::table('cmis_enterprise.webhook_deliveries')
            ->where('delivery_id', $delivery->delivery_id)
            ->update([
                'status' => self::DELIVERY_RETRYING,
                'attempts' => $attempt + 1,
                'scheduled_at' => Carbon::now()->addSeconds($delay),
                'updated_at' => Carbon::now()
            ]);

        Log::info('Webhook delivery scheduled for retry', [
            'delivery_id' => $delivery->delivery_id,
            'attempt' => $attempt + 1,
            'delay_seconds' => $delay
        ]);
    }

    /**
     * Update delivery status
     *
     * @param string $deliveryId
     * @param string $status
     * @param string|null $error
     * @param int|null $httpStatus
     * @return void
     */
    protected function updateDeliveryStatus(
        string $deliveryId,
        string $status,
        ?string $error = null,
        ?int $httpStatus = null
    ): void {
        $updateData = [
            'status' => $status,
            'updated_at' => Carbon::now()
        ];

        if ($status === self::DELIVERY_SUCCESS) {
            $updateData['delivered_at'] = Carbon::now();
        } elseif ($status === self::DELIVERY_FAILED) {
            $updateData['failed_at'] = Carbon::now();
        }

        if ($error) {
            $updateData['error'] = $error;
        }

        if ($httpStatus) {
            $updateData['http_status'] = $httpStatus;
        }

        DB::table('cmis_enterprise.webhook_deliveries')
            ->where('delivery_id', $deliveryId)
            ->update($updateData);
    }

    /**
     * Generate webhook secret
     *
     * @return string
     */
    protected function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate HMAC signature for payload
     *
     * @param array $payload
     * @param string $secret
     * @return string
     */
    protected function generateSignature(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Verify webhook signature
     *
     * @param string $payload
     * @param string $signature
     * @param string $secret
     * @return bool
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Get webhooks for organization
     *
     * @param string $orgId
     * @return array
     */
    public function getWebhooks(string $orgId): array
    {
        $webhooks = DB::table('cmis_enterprise.webhooks')
            ->where('org_id', $orgId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $webhooks->map(function ($webhook) {
            return [
                'webhook_id' => $webhook->webhook_id,
                'name' => $webhook->name,
                'url' => $webhook->url,
                'events' => json_decode($webhook->events, true),
                'status' => $webhook->status,
                'is_active' => $webhook->is_active,
                'created_at' => $webhook->created_at,
                'updated_at' => $webhook->updated_at
            ];
        })->toArray();
    }

    /**
     * Get delivery history for webhook
     *
     * @param string $webhookId
     * @param int $limit
     * @return array
     */
    public function getDeliveryHistory(string $webhookId, int $limit = 50): array
    {
        $deliveries = DB::table('cmis_enterprise.webhook_deliveries')
            ->where('webhook_id', $webhookId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $deliveries->map(function ($delivery) {
            return [
                'delivery_id' => $delivery->delivery_id,
                'event' => $delivery->event,
                'status' => $delivery->status,
                'attempts' => $delivery->attempts,
                'http_status' => $delivery->http_status,
                'error' => $delivery->error,
                'created_at' => $delivery->created_at,
                'delivered_at' => $delivery->delivered_at,
                'failed_at' => $delivery->failed_at
            ];
        })->toArray();
    }

    /**
     * Get delivery statistics
     *
     * @param string $webhookId
     * @param int $days
     * @return array
     */
    public function getDeliveryStatistics(string $webhookId, int $days = 30): array
    {
        $since = Carbon::now()->subDays($days);

        $stats = DB::table('cmis_enterprise.webhook_deliveries')
            ->where('webhook_id', $webhookId)
            ->where('created_at', '>=', $since)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $total = $stats->sum('count');
        $successful = $stats->firstWhere('status', self::DELIVERY_SUCCESS)->count ?? 0;

        return [
            'period_days' => $days,
            'total_deliveries' => $total,
            'successful' => $successful,
            'failed' => $stats->firstWhere('status', self::DELIVERY_FAILED)->count ?? 0,
            'pending' => $stats->firstWhere('status', self::DELIVERY_PENDING)->count ?? 0,
            'retrying' => $stats->firstWhere('status', self::DELIVERY_RETRYING)->count ?? 0,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0
        ];
    }
}
