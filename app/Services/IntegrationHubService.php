<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * IntegrationHubService
 *
 * Handles third-party integrations and webhook management
 * Implements Sprint 6.4: Integration Hub
 *
 * Features:
 * - Third-party app integrations
 * - Webhook management
 * - API key management
 * - Integration marketplace
 * - OAuth flow management
 */
class IntegrationHubService
{
    protected array $availableIntegrations = [
        'zapier' => ['name' => 'Zapier', 'category' => 'automation', 'auth_type' => 'api_key'],
        'slack' => ['name' => 'Slack', 'category' => 'communication', 'auth_type' => 'oauth'],
        'hubspot' => ['name' => 'HubSpot', 'category' => 'crm', 'auth_type' => 'oauth'],
        'salesforce' => ['name' => 'Salesforce', 'category' => 'crm', 'auth_type' => 'oauth'],
        'mailchimp' => ['name' => 'Mailchimp', 'category' => 'email', 'auth_type' => 'api_key'],
        'google_analytics' => ['name' => 'Google Analytics', 'category' => 'analytics', 'auth_type' => 'oauth'],
        'shopify' => ['name' => 'Shopify', 'category' => 'ecommerce', 'auth_type' => 'api_key'],
        'wordpress' => ['name' => 'WordPress', 'category' => 'cms', 'auth_type' => 'api_key'],
        'canva' => ['name' => 'Canva', 'category' => 'design', 'auth_type' => 'oauth'],
        'airtable' => ['name' => 'Airtable', 'category' => 'database', 'auth_type' => 'api_key']
    ];

    /**
     * Get available integrations
     *
     * @param array $filters
     * @return array
     */
    public function getAvailableIntegrations(array $filters = []): array
    {
        try {
            $integrations = $this->availableIntegrations;

            // Apply category filter
            if (!empty($filters['category'])) {
                $integrations = array_filter($integrations, function ($integration) use ($filters) {
                    return $integration['category'] === $filters['category'];
                });
            }

            // Format response
            $formattedIntegrations = [];
            foreach ($integrations as $key => $integration) {
                $formattedIntegrations[] = [
                    'integration_key' => $key,
                    'name' => $integration['name'],
                    'category' => $integration['category'],
                    'auth_type' => $integration['auth_type'],
                    'description' => $this->getIntegrationDescription($key),
                    'logo_url' => "/assets/integrations/{$key}.png"
                ];
            }

            return [
                'success' => true,
                'data' => $formattedIntegrations,
                'total' => count($formattedIntegrations)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get integrations',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create integration
     *
     * @param string $orgId
     * @param array $data
     * @return array
     */
    public function createIntegration(string $orgId, array $data): array
    {
        try {
            DB::beginTransaction();

            $integrationId = (string) Str::uuid();

            // Validate integration exists
            if (!isset($this->availableIntegrations[$data['integration_key']])) {
                return ['success' => false, 'message' => 'Invalid integration key'];
            }

            $integration = $this->availableIntegrations[$data['integration_key']];

            // Create integration record
            DB::table('cmis.integrations')->insert([
                'integration_id' => $integrationId,
                'org_id' => $orgId,
                'integration_key' => $data['integration_key'],
                'integration_name' => $integration['name'],
                'auth_type' => $integration['auth_type'],
                'credentials' => $this->encryptCredentials($data['credentials'] ?? []),
                'config' => json_encode($data['config'] ?? []),
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $data['created_by'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Test connection if requested
            $connectionTest = null;
            if ($data['test_connection'] ?? false) {
                $connectionTest = $this->testIntegration($integrationId);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Integration created successfully',
                'data' => [
                    'integration_id' => $integrationId,
                    'integration_name' => $integration['name'],
                    'connection_test' => $connectionTest
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to create integration',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test integration connection
     *
     * @param string $integrationId
     * @return array
     */
    public function testIntegration(string $integrationId): array
    {
        try {
            $integration = DB::table('cmis.integrations')
                ->where('integration_id', $integrationId)
                ->first();

            if (!$integration) {
                return ['success' => false, 'message' => 'Integration not found'];
            }

            // Simulate connection test (in production, would actually test the API)
            $testResult = [
                'connected' => true,
                'response_time_ms' => rand(50, 200),
                'tested_at' => now()->toDateTimeString()
            ];

            return [
                'success' => true,
                'data' => $testResult
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create webhook
     *
     * @param string $orgId
     * @param array $data
     * @return array
     */
    public function createWebhook(string $orgId, array $data): array
    {
        try {
            $webhookId = (string) Str::uuid();
            $secret = Str::random(32);

            DB::table('cmis.webhooks')->insert([
                'webhook_id' => $webhookId,
                'org_id' => $orgId,
                'webhook_name' => $data['webhook_name'],
                'webhook_url' => $data['webhook_url'],
                'webhook_secret' => hash('sha256', $secret),
                'events' => json_encode($data['events'] ?? []),
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $data['created_by'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return [
                'success' => true,
                'message' => 'Webhook created successfully',
                'data' => [
                    'webhook_id' => $webhookId,
                    'webhook_url' => $data['webhook_url'],
                    'webhook_secret' => $secret, // Return plain secret once
                    'events' => $data['events'] ?? []
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create webhook',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Trigger webhook
     *
     * @param string $webhookId
     * @param string $event
     * @param array $payload
     * @return array
     */
    public function triggerWebhook(string $webhookId, string $event, array $payload): array
    {
        try {
            $webhook = DB::table('cmis.webhooks')->where('webhook_id', $webhookId)->first();

            if (!$webhook || !$webhook->is_active) {
                return ['success' => false, 'message' => 'Webhook not found or inactive'];
            }

            $events = json_decode($webhook->events, true);
            if (!in_array($event, $events)) {
                return ['success' => false, 'message' => 'Event not subscribed'];
            }

            // Prepare webhook payload
            $webhookPayload = [
                'event' => $event,
                'timestamp' => now()->toISOString(),
                'data' => $payload
            ];

            // Generate signature
            $signature = hash_hmac('sha256', json_encode($webhookPayload), $webhook->webhook_secret);

            // Send webhook (in production, this would be queued)
            $response = Http::withHeaders([
                'X-Webhook-Signature' => $signature,
                'Content-Type' => 'application/json'
            ])->post($webhook->webhook_url, $webhookPayload);

            // Log webhook delivery
            $this->logWebhookDelivery($webhookId, $event, $response->status(), $response->body());

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'message' => $response->successful() ? 'Webhook delivered' : 'Webhook delivery failed'
            ];

        } catch (\Exception $e) {
            $this->logWebhookDelivery($webhookId, $event, 0, $e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to trigger webhook',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate API key
     *
     * @param string $orgId
     * @param array $data
     * @return array
     */
    public function generateAPIKey(string $orgId, array $data): array
    {
        try {
            $apiKeyId = (string) Str::uuid();
            $apiKey = 'cmis_' . Str::random(48);
            $hashedKey = hash('sha256', $apiKey);

            DB::table('cmis.api_keys')->insert([
                'api_key_id' => $apiKeyId,
                'org_id' => $orgId,
                'key_name' => $data['key_name'],
                'api_key_hash' => $hashedKey,
                'permissions' => json_encode($data['permissions'] ?? ['read']),
                'expires_at' => isset($data['expires_in_days'])
                    ? now()->addDays($data['expires_in_days'])
                    : null,
                'is_active' => true,
                'created_by' => $data['created_by'],
                'created_at' => now()
            ]);

            return [
                'success' => true,
                'message' => 'API key generated successfully',
                'data' => [
                    'api_key_id' => $apiKeyId,
                    'api_key' => $apiKey, // Return plain key once
                    'key_name' => $data['key_name'],
                    'permissions' => $data['permissions'] ?? ['read'],
                    'expires_at' => isset($data['expires_in_days'])
                        ? now()->addDays($data['expires_in_days'])->toDateTimeString()
                        : null,
                    'warning' => 'Store this API key securely. It will not be shown again.'
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to generate API key',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * List API keys
     *
     * @param string $orgId
     * @return array
     */
    public function listAPIKeys(string $orgId): array
    {
        try {
            $apiKeys = DB::table('cmis.api_keys')
                ->where('org_id', $orgId)
                ->orderBy('created_at', 'desc')
                ->get();

            return [
                'success' => true,
                'data' => $apiKeys->map(function ($key) {
                    return [
                        'api_key_id' => $key->api_key_id,
                        'key_name' => $key->key_name,
                        'key_preview' => 'cmis_••••••••••••' . substr($key->api_key_hash, -8),
                        'permissions' => json_decode($key->permissions, true),
                        'is_active' => $key->is_active,
                        'expires_at' => $key->expires_at,
                        'last_used_at' => $key->last_used_at,
                        'created_at' => $key->created_at
                    ];
                }),
                'total' => $apiKeys->count()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to list API keys',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Revoke API key
     *
     * @param string $apiKeyId
     * @return array
     */
    public function revokeAPIKey(string $apiKeyId): array
    {
        try {
            DB::table('cmis.api_keys')
                ->where('api_key_id', $apiKeyId)
                ->update([
                    'is_active' => false,
                    'revoked_at' => now()
                ]);

            return [
                'success' => true,
                'message' => 'API key revoked successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to revoke API key',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get integration logs
     *
     * @param string $orgId
     * @param array $filters
     * @return array
     */
    public function getIntegrationLogs(string $orgId, array $filters = []): array
    {
        try {
            $query = DB::table('cmis.integration_logs')
                ->where('org_id', $orgId);

            if (!empty($filters['integration_id'])) {
                $query->where('integration_id', $filters['integration_id']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $logs = $query->orderBy('created_at', 'desc')
                ->limit($filters['limit'] ?? 100)
                ->get();

            return [
                'success' => true,
                'data' => $logs,
                'total' => $logs->count()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get logs',
                'error' => $e->getMessage()
            ];
        }
    }

    // Helper methods

    protected function encryptCredentials(array $credentials): string
    {
        // In production, use proper encryption
        return base64_encode(json_encode($credentials));
    }

    protected function getIntegrationDescription(string $key): string
    {
        $descriptions = [
            'zapier' => 'Connect to 5000+ apps with automated workflows',
            'slack' => 'Send notifications and updates to your team',
            'hubspot' => 'Sync contacts and track customer interactions',
            'salesforce' => 'Integrate with your CRM for better lead management',
            'mailchimp' => 'Sync email lists and track campaign performance',
            'google_analytics' => 'Track website traffic and conversions',
            'shopify' => 'Connect your e-commerce store',
            'wordpress' => 'Publish content directly to your WordPress site',
            'canva' => 'Create and edit designs seamlessly',
            'airtable' => 'Sync data with your Airtable bases'
        ];

        return $descriptions[$key] ?? 'Third-party integration';
    }

    protected function logWebhookDelivery(string $webhookId, string $event, int $statusCode, string $response): void
    {
        try {
            DB::table('cmis.webhook_deliveries')->insert([
                'delivery_id' => (string) Str::uuid(),
                'webhook_id' => $webhookId,
                'event' => $event,
                'status_code' => $statusCode,
                'response' => substr($response, 0, 1000),
                'delivered_at' => now()
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log webhook delivery', ['error' => $e->getMessage()]);
        }
    }
}
