<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Core\Integration;
use App\Models\Leads\Lead;
use App\Models\Platform\WebhookEvent;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * LinkedIn Webhook Controller
 *
 * Handles incoming webhooks from LinkedIn Marketing API
 * - Lead Gen Form submissions
 * - Campaign notifications
 * - Ad account updates
 *
 * SECURITY: All webhooks MUST pass signature verification
 *
 * @see https://docs.microsoft.com/en-us/linkedin/marketing/integrations/ads/lead-gen-forms
 */
class LinkedInWebhookController extends Controller
{
    use ApiResponse;

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle Lead Gen Form submission webhook
     *
     * LinkedIn sends this when a user submits a Lead Gen Form
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleLeadGenForm(Request $request): JsonResponse
    {
        $webhookEvent = null;

        try {
            // Verify LinkedIn signature
            $signatureValid = $this->verifyLinkedInSignature($request);

            $payload = $request->all();

            // Store webhook event for audit and reliable processing
            $webhookEvent = WebhookEvent::createFromRequest(
                platform: 'linkedin',
                payload: $payload,
                headers: $request->headers->all(),
                rawPayload: config('webhook.store_raw') ? $request->getContent() : null,
                signature: $request->header('X-LinkedIn-Signature'),
                signatureValid: $signatureValid,
                sourceIp: $request->ip(),
                userAgent: $request->userAgent()
            );

            if (!$signatureValid) {
                Log::warning('LinkedIn webhook signature verification failed', [
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);

                $webhookEvent->markFailed('Invalid signature', 'INVALID_SIGNATURE');
                return $this->unauthorized('Invalid webhook signature');
            }

            Log::info('LinkedIn Lead Gen Form webhook received', [
                'payload' => $payload,
            ]);

            // Extract lead data from webhook
            $leadData = $this->extractLeadData($payload);

            if (empty($leadData)) {
                Log::warning('LinkedIn webhook received but no lead data found', [
                    'payload' => $payload,
                ]);

                $webhookEvent->markIgnored('No lead data found in payload');
                return $this->success(null, 'Webhook received but no actionable data');
            }

            // Find integration by form ID or account ID
            $integration = $this->findIntegrationByFormId($leadData['form_id'] ?? null);

            if (!$integration) {
                Log::warning('LinkedIn webhook received but no matching integration found', [
                    'form_id' => $leadData['form_id'] ?? null,
                ]);

                $webhookEvent->markIgnored('No matching integration for form_id: ' . ($leadData['form_id'] ?? 'null'));
                return $this->success(null, 'Webhook received but no matching integration');
            }

            // Initialize RLS context for database operations
            DB::statement(
                'SELECT cmis.init_transaction_context(?, ?)',
                [config('cmis.system_user_id'), $integration->org_id]
            );

            // Create lead in CMIS
            $lead = $this->createLead($integration, $leadData);

            Log::info('LinkedIn Lead Gen Form submission processed', [
                'lead_id' => $lead->id,
                'integration_id' => $integration->integration_id,
                'form_id' => $leadData['form_id'] ?? null,
            ]);

            // Trigger CRM sync if configured
            if (config('services.linkedin.auto_sync_crm')) {
                // Dispatch job to sync lead to CRM
                // SyncLeadToCRMJob::dispatch($lead);
            }

            // Mark webhook event as processed with org context
            $webhookEvent->markProcessed($integration->org_id, $integration->integration_id);

            return $this->success([
                'lead_id' => $lead->id,
                'processed' => true,
            ], 'Lead Gen Form submission processed successfully');

        } catch (\Exception $e) {
            Log::error('LinkedIn webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            // Mark webhook event as failed
            if ($webhookEvent) {
                $webhookEvent->markFailed($e->getMessage());
            }

            // Return 200 to LinkedIn to prevent retries for processing errors
            return $this->success(null, 'Webhook received');
        }
    }

    /**
     * Handle campaign notification webhooks
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleCampaignNotification(Request $request): JsonResponse
    {
        $webhookEvent = null;

        try {
            // Verify LinkedIn signature
            $signatureValid = $this->verifyLinkedInSignature($request);

            $payload = $request->all();

            // Store webhook event for audit and reliable processing
            $webhookEvent = WebhookEvent::createFromRequest(
                platform: 'linkedin',
                payload: $payload,
                headers: $request->headers->all(),
                rawPayload: config('webhook.store_raw') ? $request->getContent() : null,
                signature: $request->header('X-LinkedIn-Signature'),
                signatureValid: $signatureValid,
                sourceIp: $request->ip(),
                userAgent: $request->userAgent()
            );

            if (!$signatureValid) {
                Log::warning('LinkedIn campaign webhook signature verification failed');
                $webhookEvent->markFailed('Invalid signature', 'INVALID_SIGNATURE');
                return $this->unauthorized('Invalid webhook signature');
            }

            Log::info('LinkedIn campaign notification received', [
                'payload' => $payload,
            ]);

            // Determine notification type and process accordingly
            $notificationType = $payload['notificationType'] ?? $payload['eventType'] ?? null;

            $result = match ($notificationType) {
                'CAMPAIGN_STATUS_CHANGE' => $this->processCampaignStatusChange($payload),
                'BUDGET_ALERT' => $this->processBudgetAlert($payload),
                'PERFORMANCE_ALERT' => $this->processPerformanceAlert($payload),
                'AD_STATUS_CHANGE' => $this->processAdStatusChange($payload),
                'ACCOUNT_UPDATE' => $this->processAccountUpdate($payload),
                default => $this->processGenericNotification($payload),
            };

            // Find integration for org context
            $accountId = $payload['accountId'] ?? $payload['account_id'] ?? null;
            $integration = $this->findIntegrationByAccountId($accountId);

            // Mark webhook event as processed with org context
            if ($integration) {
                $webhookEvent->markProcessed($integration->org_id, $integration->integration_id);
            } else {
                $webhookEvent->markProcessed();
            }

            return $this->success($result, 'Campaign notification processed');

        } catch (\Exception $e) {
            Log::error('LinkedIn campaign webhook failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark webhook event as failed
            if ($webhookEvent) {
                $webhookEvent->markFailed($e->getMessage());
            }

            return $this->success(null, 'Webhook received');
        }
    }

    /**
     * Process campaign status change notification
     */
    protected function processCampaignStatusChange(array $payload): array
    {
        $campaignId = $payload['campaignId'] ?? $payload['campaign_id'] ?? null;
        $oldStatus = $payload['oldStatus'] ?? $payload['previous_status'] ?? 'unknown';
        $newStatus = $payload['newStatus'] ?? $payload['status'] ?? 'unknown';
        $accountId = $payload['accountId'] ?? $payload['account_id'] ?? null;
        $reason = $payload['reason'] ?? null;

        Log::info('LinkedIn campaign status change', [
            'campaign_id' => $campaignId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        // Find integration
        $integration = $this->findIntegrationByAccountId($accountId);
        if (!$integration) {
            return ['processed' => false, 'reason' => 'Integration not found'];
        }

        // Get campaign details
        $campaign = DB::table('cmis_ads.ad_campaigns')
            ->where('platform_campaign_id', $campaignId)
            ->where('platform', 'linkedin')
            ->first();

        $campaignName = $campaign->campaign_name ?? "Campaign {$campaignId}";

        // Update campaign status in database
        if ($campaign) {
            DB::table('cmis_ads.ad_campaigns')
                ->where('id', $campaign->id)
                ->update([
                    'status' => strtolower($newStatus),
                    'status_updated_at' => now(),
                    'status_reason' => $reason,
                    'updated_at' => now(),
                ]);
        }

        // Determine notification priority
        $priority = match (strtolower($newStatus)) {
            'rejected', 'suspended', 'error' => NotificationService::PRIORITY_CRITICAL,
            'paused', 'pending_review' => NotificationService::PRIORITY_HIGH,
            'active', 'approved' => NotificationService::PRIORITY_MEDIUM,
            default => NotificationService::PRIORITY_LOW,
        };

        // Get users to notify
        $userIds = $this->getCampaignNotifyUserIds($integration, $campaign);

        // Send notifications
        foreach ($userIds as $userId) {
            $this->notificationService->notify(
                $userId,
                NotificationService::TYPE_CAMPAIGN_STATUS,
                __('notifications.campaign_status_title', [
                    'campaign' => $campaignName,
                    'platform' => 'LinkedIn',
                ]),
                __('notifications.campaign_status_message', [
                    'campaign' => $campaignName,
                    'old_status' => ucfirst($oldStatus),
                    'new_status' => ucfirst($newStatus),
                    'reason' => $reason ?? 'No reason provided',
                ]),
                [
                    'org_id' => $integration->org_id,
                    'priority' => $priority,
                    'category' => 'campaign',
                    'related_entity_type' => 'campaign',
                    'related_entity_id' => $campaign->id ?? $campaignId,
                    'data' => [
                        'campaign_id' => $campaignId,
                        'campaign_name' => $campaignName,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'reason' => $reason,
                        'platform' => 'linkedin',
                    ],
                    'action_url' => route('campaigns.show', ['id' => $campaign->id ?? $campaignId], false),
                    'channels' => $priority === NotificationService::PRIORITY_CRITICAL
                        ? ['in_app', 'email', 'slack']
                        : ['in_app', 'email'],
                ]
            );
        }

        // Clear caches
        Cache::forget("campaign:status:{$campaignId}");
        Cache::forget("dashboard:org:{$integration->org_id}");

        return [
            'processed' => true,
            'notification_type' => 'status_change',
            'campaign_id' => $campaignId,
            'new_status' => $newStatus,
            'notifications_sent' => count($userIds),
        ];
    }

    /**
     * Process budget alert notification
     */
    protected function processBudgetAlert(array $payload): array
    {
        $campaignId = $payload['campaignId'] ?? $payload['campaign_id'] ?? null;
        $accountId = $payload['accountId'] ?? $payload['account_id'] ?? null;
        $budgetRemaining = $payload['budgetRemaining'] ?? $payload['budget_remaining'] ?? 0;
        $budgetTotal = $payload['budgetTotal'] ?? $payload['budget_total'] ?? 0;
        $thresholdPercentage = $payload['thresholdPercentage'] ?? $payload['threshold'] ?? 20;

        // Find integration
        $integration = $this->findIntegrationByAccountId($accountId);
        if (!$integration) {
            return ['processed' => false, 'reason' => 'Integration not found'];
        }

        // Get campaign details
        $campaign = DB::table('cmis_ads.ad_campaigns')
            ->where('platform_campaign_id', $campaignId)
            ->where('platform', 'linkedin')
            ->first();

        $campaignName = $campaign->campaign_name ?? "Campaign {$campaignId}";
        $remainingPercentage = $budgetTotal > 0 ? round(($budgetRemaining / $budgetTotal) * 100, 1) : 0;

        // Determine priority
        $priority = match (true) {
            $remainingPercentage <= 5 => NotificationService::PRIORITY_CRITICAL,
            $remainingPercentage <= 15 => NotificationService::PRIORITY_HIGH,
            $remainingPercentage <= 30 => NotificationService::PRIORITY_MEDIUM,
            default => NotificationService::PRIORITY_LOW,
        };

        // Determine channels based on severity
        $channels = match (true) {
            $remainingPercentage <= 10 => ['in_app', 'email', 'slack'],
            $remainingPercentage <= 25 => ['in_app', 'email'],
            default => ['in_app'],
        };

        // Get users to notify
        $userIds = $this->getCampaignNotifyUserIds($integration, $campaign);

        // Send notifications
        foreach ($userIds as $userId) {
            $this->notificationService->notify(
                $userId,
                NotificationService::TYPE_BUDGET_ALERT,
                __('notifications.budget_alert_title', [
                    'campaign' => $campaignName,
                    'platform' => 'LinkedIn',
                ]),
                __('notifications.budget_alert_message', [
                    'campaign' => $campaignName,
                    'remaining' => $remainingPercentage,
                    'amount' => number_format($budgetRemaining, 2),
                ]),
                [
                    'org_id' => $integration->org_id,
                    'priority' => $priority,
                    'category' => 'budget',
                    'related_entity_type' => 'campaign',
                    'related_entity_id' => $campaign->id ?? $campaignId,
                    'data' => [
                        'campaign_id' => $campaignId,
                        'campaign_name' => $campaignName,
                        'budget_remaining' => $budgetRemaining,
                        'budget_total' => $budgetTotal,
                        'remaining_percentage' => $remainingPercentage,
                        'platform' => 'linkedin',
                    ],
                    'action_url' => route('campaigns.show', ['id' => $campaign->id ?? $campaignId], false),
                    'channels' => $channels,
                ]
            );
        }

        // Update campaign budget status
        if ($campaign) {
            DB::table('cmis_ads.ad_campaigns')
                ->where('id', $campaign->id)
                ->update([
                    'budget_status' => $remainingPercentage <= 10 ? 'depleted' : ($remainingPercentage <= 25 ? 'low' : 'normal'),
                    'budget_alert_sent_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        // Create alert record
        DB::table('cmis.alerts')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $integration->org_id,
            'type' => 'budget_alert',
            'severity' => $remainingPercentage <= 10 ? 'critical' : ($remainingPercentage <= 25 ? 'warning' : 'info'),
            'title' => __('notifications.budget_alert_title', [
                'campaign' => $campaignName,
                'platform' => 'LinkedIn',
            ]),
            'message' => __('notifications.budget_alert_message', [
                'campaign' => $campaignName,
                'remaining' => $remainingPercentage,
                'amount' => number_format($budgetRemaining, 2),
            ]),
            'related_entity_type' => 'campaign',
            'related_entity_id' => $campaign->id ?? $campaignId,
            'is_read' => false,
            'created_at' => now(),
        ]);

        return [
            'processed' => true,
            'notification_type' => 'budget_alert',
            'campaign_id' => $campaignId,
            'remaining_percentage' => $remainingPercentage,
            'notifications_sent' => count($userIds),
        ];
    }

    /**
     * Process performance alert notification
     */
    protected function processPerformanceAlert(array $payload): array
    {
        $campaignId = $payload['campaignId'] ?? $payload['campaign_id'] ?? null;
        $accountId = $payload['accountId'] ?? $payload['account_id'] ?? null;
        $alertType = $payload['alertType'] ?? 'performance';
        $metricName = $payload['metricName'] ?? $payload['metric'] ?? 'unknown';
        $currentValue = $payload['currentValue'] ?? $payload['current_value'] ?? 0;
        $thresholdValue = $payload['thresholdValue'] ?? $payload['threshold_value'] ?? 0;
        $direction = $payload['direction'] ?? 'below'; // 'above' or 'below'

        // Find integration
        $integration = $this->findIntegrationByAccountId($accountId);
        if (!$integration) {
            return ['processed' => false, 'reason' => 'Integration not found'];
        }

        // Get campaign details
        $campaign = DB::table('cmis_ads.ad_campaigns')
            ->where('platform_campaign_id', $campaignId)
            ->where('platform', 'linkedin')
            ->first();

        $campaignName = $campaign->campaign_name ?? "Campaign {$campaignId}";

        // Determine priority based on metric type and deviation
        $deviation = abs($currentValue - $thresholdValue);
        $priority = match (true) {
            $metricName === 'conversions' && $direction === 'below' => NotificationService::PRIORITY_HIGH,
            $metricName === 'spend' && $direction === 'above' => NotificationService::PRIORITY_HIGH,
            $deviation > 50 => NotificationService::PRIORITY_HIGH,
            $deviation > 25 => NotificationService::PRIORITY_MEDIUM,
            default => NotificationService::PRIORITY_LOW,
        };

        // Get users to notify
        $userIds = $this->getCampaignNotifyUserIds($integration, $campaign);

        // Send notifications
        foreach ($userIds as $userId) {
            $this->notificationService->notify(
                $userId,
                NotificationService::TYPE_PERFORMANCE_ALERT,
                __('notifications.performance_alert_title', [
                    'campaign' => $campaignName,
                    'platform' => 'LinkedIn',
                ]),
                __('notifications.performance_alert_message', [
                    'metric' => ucfirst($metricName),
                    'current' => $currentValue,
                    'threshold' => $thresholdValue,
                    'direction' => $direction,
                ]),
                [
                    'org_id' => $integration->org_id,
                    'priority' => $priority,
                    'category' => 'performance',
                    'related_entity_type' => 'campaign',
                    'related_entity_id' => $campaign->id ?? $campaignId,
                    'data' => [
                        'campaign_id' => $campaignId,
                        'campaign_name' => $campaignName,
                        'metric_name' => $metricName,
                        'current_value' => $currentValue,
                        'threshold_value' => $thresholdValue,
                        'direction' => $direction,
                        'platform' => 'linkedin',
                    ],
                    'action_url' => route('campaigns.show', ['id' => $campaign->id ?? $campaignId], false),
                    'channels' => ['in_app', 'email'],
                ]
            );
        }

        // Create alert record
        DB::table('cmis.alerts')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $integration->org_id,
            'type' => 'performance_alert',
            'severity' => $priority === NotificationService::PRIORITY_HIGH ? 'warning' : 'info',
            'title' => __('notifications.performance_alert_title', [
                'campaign' => $campaignName,
                'platform' => 'LinkedIn',
            ]),
            'message' => __('notifications.performance_alert_message', [
                'metric' => ucfirst($metricName),
                'current' => $currentValue,
                'threshold' => $thresholdValue,
                'direction' => $direction,
            ]),
            'related_entity_type' => 'campaign',
            'related_entity_id' => $campaign->id ?? $campaignId,
            'is_read' => false,
            'created_at' => now(),
        ]);

        return [
            'processed' => true,
            'notification_type' => 'performance_alert',
            'campaign_id' => $campaignId,
            'metric' => $metricName,
            'notifications_sent' => count($userIds),
        ];
    }

    /**
     * Process ad status change notification
     */
    protected function processAdStatusChange(array $payload): array
    {
        $adId = $payload['adId'] ?? $payload['ad_id'] ?? null;
        $campaignId = $payload['campaignId'] ?? $payload['campaign_id'] ?? null;
        $newStatus = $payload['newStatus'] ?? $payload['status'] ?? 'unknown';
        $accountId = $payload['accountId'] ?? $payload['account_id'] ?? null;

        Log::info('LinkedIn ad status change', [
            'ad_id' => $adId,
            'campaign_id' => $campaignId,
            'new_status' => $newStatus,
        ]);

        // Update ad status in database
        DB::table('cmis_ads.ads')
            ->where('platform_ad_id', $adId)
            ->where('platform', 'linkedin')
            ->update([
                'status' => strtolower($newStatus),
                'status_updated_at' => now(),
                'updated_at' => now(),
            ]);

        return [
            'processed' => true,
            'notification_type' => 'ad_status_change',
            'ad_id' => $adId,
            'new_status' => $newStatus,
        ];
    }

    /**
     * Process account update notification
     */
    protected function processAccountUpdate(array $payload): array
    {
        $accountId = $payload['accountId'] ?? $payload['account_id'] ?? null;
        $updateType = $payload['updateType'] ?? $payload['update_type'] ?? 'unknown';

        Log::info('LinkedIn account update', [
            'account_id' => $accountId,
            'update_type' => $updateType,
        ]);

        // Find integration
        $integration = $this->findIntegrationByAccountId($accountId);
        if (!$integration) {
            return ['processed' => false, 'reason' => 'Integration not found'];
        }

        // Clear account-related caches
        Cache::forget("linkedin:account:{$accountId}");
        Cache::forget("dashboard:org:{$integration->org_id}");

        return [
            'processed' => true,
            'notification_type' => 'account_update',
            'account_id' => $accountId,
            'update_type' => $updateType,
        ];
    }

    /**
     * Process generic/unknown notification
     */
    protected function processGenericNotification(array $payload): array
    {
        Log::info('LinkedIn generic notification processed', [
            'payload_keys' => array_keys($payload),
        ]);

        return [
            'processed' => true,
            'notification_type' => 'generic',
            'payload_received' => true,
        ];
    }

    /**
     * Find integration by LinkedIn account ID
     */
    protected function findIntegrationByAccountId(?string $accountId): ?Integration
    {
        if (empty($accountId)) {
            return null;
        }

        return Integration::where('platform', 'linkedin')
            ->where('is_active', true)
            ->where(function ($query) use ($accountId) {
                $query->where('external_account_id', $accountId)
                    ->orWhereRaw("metadata->>'account_id' = ?", [$accountId]);
            })
            ->first();
    }

    /**
     * Get user IDs to notify for campaign events
     */
    protected function getCampaignNotifyUserIds(Integration $integration, ?object $campaign): array
    {
        $userIds = [];

        // Campaign owner
        if ($campaign && !empty($campaign->created_by)) {
            $userIds[] = $campaign->created_by;
        }

        // Integration owner
        if (!empty($integration->created_by)) {
            $userIds[] = $integration->created_by;
        }

        // Org admins
        $admins = DB::table('cmis.users')
            ->where('org_id', $integration->org_id)
            ->where('is_super_admin', true)
            ->limit(3)
            ->pluck('id')
            ->toArray();

        return array_unique(array_merge($userIds, $admins));
    }

    /**
     * Verify LinkedIn webhook signature
     *
     * CRITICAL: This prevents unauthorized webhook calls
     *
     * LinkedIn signs webhooks with HMAC-SHA256
     *
     * @param Request $request
     * @return bool
     */
    protected function verifyLinkedInSignature(Request $request): bool
    {
        $signature = $request->header('X-LinkedIn-Signature');

        if (empty($signature)) {
            Log::warning('LinkedIn webhook missing signature header');
            return false;
        }

        $webhookSecret = config('services.linkedin.webhook_secret');

        if (empty($webhookSecret)) {
            Log::error('LinkedIn webhook secret not configured');
            return false;
        }

        // Get raw request body
        $payload = $request->getContent();

        // Compute expected signature
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        // Constant-time comparison to prevent timing attacks
        $isValid = hash_equals($expectedSignature, $signature);

        if (!$isValid) {
            Log::warning('LinkedIn webhook signature mismatch', [
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);
        }

        return $isValid;
    }

    /**
     * Extract lead data from LinkedIn webhook payload
     *
     * @param array $payload
     * @return array
     */
    protected function extractLeadData(array $payload): array
    {
        // LinkedIn Lead Gen Form webhook structure:
        // {
        //   "eventType": "LEAD_GEN_FORM_RESPONSE",
        //   "leadGenFormUrn": "urn:li:leadGenForm:12345",
        //   "leadGenFormResponseUrn": "urn:li:leadGenFormResponse:67890",
        //   "submittedAt": 1620000000000,
        //   "leadData": {
        //     "FIRST_NAME": "John",
        //     "LAST_NAME": "Doe",
        //     "EMAIL": "john@example.com",
        //     ...
        //   }
        // }

        if ($payload['eventType'] !== 'LEAD_GEN_FORM_RESPONSE') {
            return [];
        }

        $leadData = $payload['leadData'] ?? [];

        return [
            'form_id' => $this->extractUrnId($payload['leadGenFormUrn'] ?? ''),
            'response_id' => $this->extractUrnId($payload['leadGenFormResponseUrn'] ?? ''),
            'submitted_at' => isset($payload['submittedAt'])
                ? \Carbon\Carbon::createFromTimestampMs($payload['submittedAt'])
                : now(),
            'first_name' => $leadData['FIRST_NAME'] ?? null,
            'last_name' => $leadData['LAST_NAME'] ?? null,
            'email' => $leadData['EMAIL'] ?? null,
            'phone' => $leadData['PHONE'] ?? null,
            'company' => $leadData['COMPANY'] ?? null,
            'job_title' => $leadData['JOB_TITLE'] ?? null,
            'raw_data' => $leadData,
        ];
    }

    /**
     * Extract numeric ID from LinkedIn URN
     *
     * Example: "urn:li:leadGenForm:12345" => "12345"
     *
     * @param string $urn
     * @return string|null
     */
    protected function extractUrnId(string $urn): ?string
    {
        if (empty($urn)) {
            return null;
        }

        $parts = explode(':', $urn);
        return end($parts) ?: null;
    }

    /**
     * Find integration by Lead Gen Form ID
     *
     * @param string|null $formId
     * @return Integration|null
     */
    protected function findIntegrationByFormId(?string $formId): ?Integration
    {
        if (empty($formId)) {
            return null;
        }

        // Find integration that has this form ID in metadata
        return Integration::where('platform', 'linkedin')
            ->where('is_active', true)
            ->whereRaw("metadata->>'lead_gen_forms' @> ?", [json_encode([$formId])])
            ->first();
    }

    /**
     * Create lead in CMIS system
     *
     * @param Integration $integration
     * @param array $leadData
     * @return Lead
     */
    protected function createLead(Integration $integration, array $leadData): Lead
    {
        // Create lead with RLS context already initialized
        $lead = Lead::create([
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'source' => 'linkedin_lead_gen',
            'platform_lead_id' => $leadData['response_id'],
            'first_name' => $leadData['first_name'],
            'last_name' => $leadData['last_name'],
            'email' => $leadData['email'],
            'phone' => $leadData['phone'],
            'company' => $leadData['company'],
            'job_title' => $leadData['job_title'],
            'status' => 'new',
            'submitted_at' => $leadData['submitted_at'],
            'metadata' => [
                'form_id' => $leadData['form_id'],
                'raw_response' => $leadData['raw_data'],
                'webhook_received_at' => now()->toIso8601String(),
            ],
        ]);

        // Fire event for downstream processing
        // event(new LinkedInLeadGenerated($lead));

        return $lead;
    }

    /**
     * Webhook verification endpoint (for LinkedIn to verify webhook URL)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        // LinkedIn sends a challenge parameter for verification
        $challenge = $request->input('challenge');

        if (empty($challenge)) {
            return $this->error('Missing challenge parameter', 400);
        }

        return response()->json([
            'challenge' => $challenge,
        ]);
    }
}
