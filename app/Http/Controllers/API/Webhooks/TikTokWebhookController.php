<?php

namespace App\Http\Controllers\API\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Core\Integration;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TikTok Webhook Handler
 *
 * Handles incoming webhooks from TikTok Ads Platform
 * @see https://business-api.tiktok.com/portal/docs?id=1739939228355585
 */
class TikTokWebhookController extends Controller
{
    use ApiResponse;

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle incoming TikTok webhook
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            // Verify webhook signature
            if (!$this->verifySignature($request)) {
                Log::warning('TikTok webhook signature verification failed', [
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->unauthorized('Invalid webhook signature');
            }

            $payload = $request->all();

            Log::info('TikTok webhook received', [
                'event_type' => $payload['event_type'] ?? 'unknown',
                'advertiser_id' => $payload['advertiser_id'] ?? null,
            ]);

            // Handle different webhook event types
            $eventType = $payload['event_type'] ?? null;

            $result = match ($eventType) {
                'CAMPAIGN_STATUS_UPDATE' => $this->handleCampaignStatusUpdate($payload),
                'AD_STATUS_UPDATE' => $this->handleAdStatusUpdate($payload),
                'BUDGET_ALERT' => $this->handleBudgetAlert($payload),
                'CONVERSION_EVENT' => $this->handleConversionEvent($payload),
                default => $this->handleUnknownEvent($payload),
            };

            return $this->success($result, 'Webhook processed successfully');

        } catch (\Exception $e) {
            Log::error('Failed to process TikTok webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->serverError('Failed to process webhook');
        }
    }

    /**
     * Verify TikTok webhook signature
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-TikTok-Signature');

        if (!$signature) {
            return false;
        }

        $verifyToken = config('services.tiktok.webhook_verify_token');

        if (!$verifyToken) {
            Log::error('TikTok webhook verify token not configured');
            return false;
        }

        // TikTok webhook signature verification
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $verifyToken);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle campaign status update webhook
     */
    protected function handleCampaignStatusUpdate(array $payload): array
    {
        $campaignId = $payload['campaign_id'] ?? null;
        $newStatus = $payload['status'] ?? null;
        $advertiserId = $payload['advertiser_id'] ?? null;

        if (!$campaignId || !$newStatus) {
            Log::warning('Invalid campaign status update payload', ['payload' => $payload]);
            return ['processed' => false, 'reason' => 'Invalid payload'];
        }

        // Find integration by advertiser ID
        $integration = Integration::where('platform', 'tiktok')
            ->where('is_active', true)
            ->whereJsonContains('metadata->advertiser_id', $advertiserId)
            ->first();

        if (!$integration) {
            Log::warning('Integration not found for TikTok advertiser', [
                'advertiser_id' => $advertiserId,
            ]);
            return ['processed' => false, 'reason' => 'Integration not found'];
        }

        // Update campaign status in unified_metrics or campaign table
        DB::table('cmis.unified_metrics')
            ->where('platform', 'tiktok')
            ->where('entity_type', 'campaign')
            ->where('entity_id', $campaignId)
            ->update([
                'metric_data->status' => $newStatus,
                'updated_at' => now(),
            ]);

        Log::info('TikTok campaign status updated', [
            'campaign_id' => $campaignId,
            'new_status' => $newStatus,
        ]);

        return ['processed' => true, 'campaign_id' => $campaignId];
    }

    /**
     * Handle ad status update webhook
     */
    protected function handleAdStatusUpdate(array $payload): array
    {
        $adId = $payload['ad_id'] ?? null;
        $newStatus = $payload['status'] ?? null;

        if (!$adId || !$newStatus) {
            return ['processed' => false, 'reason' => 'Invalid payload'];
        }

        // Update ad status
        DB::table('cmis.unified_metrics')
            ->where('platform', 'tiktok')
            ->where('entity_type', 'ad')
            ->where('entity_id', $adId)
            ->update([
                'metric_data->status' => $newStatus,
                'updated_at' => now(),
            ]);

        return ['processed' => true, 'ad_id' => $adId];
    }

    /**
     * Handle budget alert webhook
     */
    protected function handleBudgetAlert(array $payload): array
    {
        $campaignId = $payload['campaign_id'] ?? null;
        $advertiserId = $payload['advertiser_id'] ?? null;
        $budgetRemaining = $payload['budget_remaining'] ?? 0;
        $budgetTotal = $payload['budget_total'] ?? 0;
        $budgetThreshold = $payload['threshold_percentage'] ?? 20;
        $alertLevel = $payload['alert_level'] ?? 'warning';

        Log::warning('TikTok budget alert received', [
            'campaign_id' => $campaignId,
            'budget_remaining' => $budgetRemaining,
            'budget_total' => $budgetTotal,
            'threshold' => $budgetThreshold,
            'alert_level' => $alertLevel,
        ]);

        // Find integration by advertiser ID
        $integration = Integration::where('platform', 'tiktok')
            ->where('is_active', true)
            ->whereJsonContains('metadata->advertiser_id', $advertiserId)
            ->first();

        if (!$integration) {
            Log::warning('Integration not found for TikTok budget alert', [
                'advertiser_id' => $advertiserId,
                'campaign_id' => $campaignId,
            ]);
            return ['processed' => false, 'reason' => 'Integration not found'];
        }

        // Get campaign details for notification
        $campaign = DB::table('cmis_ads.ad_campaigns')
            ->where('platform_campaign_id', $campaignId)
            ->where('platform', 'tiktok')
            ->first();

        $campaignName = $campaign->campaign_name ?? "Campaign {$campaignId}";
        $remainingPercentage = $budgetTotal > 0 ? round(($budgetRemaining / $budgetTotal) * 100, 1) : 0;

        // Determine priority based on remaining budget
        $priority = match (true) {
            $remainingPercentage <= 5 => NotificationService::PRIORITY_CRITICAL,
            $remainingPercentage <= 15 => NotificationService::PRIORITY_HIGH,
            $remainingPercentage <= 30 => NotificationService::PRIORITY_MEDIUM,
            default => NotificationService::PRIORITY_LOW,
        };

        // Determine notification channels based on severity
        $channels = match (true) {
            $remainingPercentage <= 10 => ['in_app', 'email', 'slack'],
            $remainingPercentage <= 25 => ['in_app', 'email'],
            default => ['in_app'],
        };

        // Get users to notify (campaign owner + admins)
        $userIds = $this->getNotifyUserIds($integration, $campaign);

        // Send notifications
        foreach ($userIds as $userId) {
            $this->notificationService->notify(
                $userId,
                NotificationService::TYPE_BUDGET_ALERT,
                __('notifications.budget_alert_title', [
                    'campaign' => $campaignName,
                    'platform' => 'TikTok',
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
                        'platform' => 'tiktok',
                    ],
                    'action_url' => route('campaigns.show', ['id' => $campaign->id ?? $campaignId], false),
                    'channels' => $channels,
                ]
            );
        }

        // Create budget alert record
        DB::table('cmis.alerts')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $integration->org_id,
            'type' => 'budget_alert',
            'severity' => $remainingPercentage <= 10 ? 'critical' : ($remainingPercentage <= 25 ? 'warning' : 'info'),
            'title' => __('notifications.budget_alert_title', [
                'campaign' => $campaignName,
                'platform' => 'TikTok',
            ]),
            'message' => __('notifications.budget_alert_message', [
                'campaign' => $campaignName,
                'remaining' => $remainingPercentage,
                'amount' => number_format($budgetRemaining, 2),
            ]),
            'related_entity_type' => 'campaign',
            'related_entity_id' => $campaign->id ?? $campaignId,
            'metadata' => json_encode([
                'campaign_id' => $campaignId,
                'budget_remaining' => $budgetRemaining,
                'budget_total' => $budgetTotal,
                'remaining_percentage' => $remainingPercentage,
                'threshold' => $budgetThreshold,
            ]),
            'is_read' => false,
            'created_at' => now(),
        ]);

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

        // Clear budget-related caches
        Cache::forget("campaign:budget:{$campaignId}");
        Cache::forget("dashboard:org:{$integration->org_id}");

        Log::info('TikTok budget alert processed and notifications sent', [
            'campaign_id' => $campaignId,
            'remaining_percentage' => $remainingPercentage,
            'users_notified' => count($userIds),
        ]);

        return [
            'processed' => true,
            'alert_type' => 'budget',
            'campaign_id' => $campaignId,
            'remaining_percentage' => $remainingPercentage,
            'notifications_sent' => count($userIds),
        ];
    }

    /**
     * Get user IDs to notify for budget alerts
     */
    protected function getNotifyUserIds(Integration $integration, ?object $campaign): array
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

        // Org admins for critical alerts
        $admins = DB::table('cmis.users')
            ->where('org_id', $integration->org_id)
            ->where('is_super_admin', true)
            ->limit(3)
            ->pluck('id')
            ->toArray();

        return array_unique(array_merge($userIds, $admins));
    }

    /**
     * Handle conversion event webhook
     */
    protected function handleConversionEvent(array $payload): array
    {
        $eventName = $payload['event_name'] ?? null;
        $eventTime = $payload['event_time'] ?? null;
        $eventValue = $payload['event_value'] ?? null;

        // Store conversion event
        DB::table('cmis.conversion_events')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'platform' => 'tiktok',
            'event_name' => $eventName,
            'event_time' => $eventTime,
            'event_value' => $eventValue,
            'event_data' => json_encode($payload),
            'created_at' => now(),
        ]);

        return ['processed' => true, 'event' => $eventName];
    }

    /**
     * Handle unknown webhook event type
     */
    protected function handleUnknownEvent(array $payload): array
    {
        Log::warning('Unknown TikTok webhook event type', [
            'payload' => $payload,
        ]);

        return ['processed' => false, 'reason' => 'Unknown event type'];
    }
}
