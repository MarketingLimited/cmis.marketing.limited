<?php

namespace App\Http\Controllers\API\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Core\Integration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $budgetRemaining = $payload['budget_remaining'] ?? null;
        $budgetThreshold = $payload['threshold_percentage'] ?? null;

        Log::warning('TikTok budget alert received', [
            'campaign_id' => $campaignId,
            'budget_remaining' => $budgetRemaining,
            'threshold' => $budgetThreshold,
        ]);

        // TODO: Implement budget alert notification
        // - Send email/SMS notification to campaign owner
        // - Create in-app notification
        // - Update campaign budget status

        return ['processed' => true, 'alert_type' => 'budget'];
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
