<?php

namespace App\Services\Listening;

use App\Models\Listening\MonitoringAlert;
use App\Models\Listening\MonitoringKeyword;
use App\Models\Listening\SocialMention;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class AlertService
{
    /**
     * Create monitoring alert
     */
    public function createAlert(string $orgId, string $userId, array $data): MonitoringAlert
    {
        $alert = MonitoringAlert::create([
            'org_id' => $orgId,
            'created_by' => $userId,
            'alert_name' => $data['alert_name'],
            'alert_type' => $data['alert_type'],
            'description' => $data['description'] ?? null,
            'trigger_conditions' => $data['trigger_conditions'],
            'severity' => $data['severity'] ?? 'medium',
            'threshold_value' => $data['threshold_value'] ?? null,
            'threshold_unit' => $data['threshold_unit'] ?? null,
            'notification_channels' => $data['notification_channels'] ?? ['email'],
            'recipients' => $data['recipients'] ?? [],
            'notification_frequency' => $data['notification_frequency'] ?? 60,
        ]);

        Log::info('Monitoring alert created', [
            'alert_id' => $alert->alert_id,
            'name' => $alert->alert_name,
            'type' => $alert->alert_type,
        ]);

        return $alert;
    }

    /**
     * Process alert for keyword and mention
     */
    public function processAlert(MonitoringKeyword $keyword, SocialMention $mention): void
    {
        // Find all active alerts for this keyword
        $alerts = MonitoringAlert::where('org_id', $keyword->org_id)
            ->active()
            ->get();

        foreach ($alerts as $alert) {
            // Check if alert conditions are met
            if ($this->shouldTrigger($alert, $keyword, $mention)) {
                $this->triggerAlert($alert, $keyword, $mention);
            }
        }
    }

    /**
     * Check if alert should trigger
     */
    protected function shouldTrigger(
        MonitoringAlert $alert,
        MonitoringKeyword $keyword,
        SocialMention $mention
    ): bool {
        if (!$alert->isActive()) {
            return false;
        }

        if (!$alert->canSendNotification()) {
            return false;
        }

        $mentionData = [
            'keyword_id' => $keyword->keyword_id,
            'sentiment' => $mention->sentiment,
            'platform' => $mention->platform,
            'author_followers_count' => $mention->author_followers_count,
            'author_is_verified' => $mention->author_is_verified,
            'engagement_rate' => $mention->engagement_rate,
        ];

        return $alert->evaluateConditions($mentionData);
    }

    /**
     * Trigger alert and send notifications
     */
    protected function triggerAlert(
        MonitoringAlert $alert,
        MonitoringKeyword $keyword,
        SocialMention $mention
    ): void {
        $alert->trigger();

        Log::info('Alert triggered', [
            'alert_id' => $alert->alert_id,
            'keyword_id' => $keyword->keyword_id,
            'mention_id' => $mention->mention_id,
        ]);

        // Send notifications through configured channels
        foreach ($alert->notification_channels as $channel) {
            try {
                $this->sendNotification($channel, $alert, $keyword, $mention);
            } catch (\Exception $e) {
                Log::error('Failed to send alert notification', [
                    'channel' => $channel,
                    'alert_id' => $alert->alert_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $alert->markNotificationSent();
    }

    /**
     * Send notification through specific channel
     */
    protected function sendNotification(
        string $channel,
        MonitoringAlert $alert,
        MonitoringKeyword $keyword,
        SocialMention $mention
    ): void {
        $message = $this->buildNotificationMessage($alert, $keyword, $mention);

        switch ($channel) {
            case 'email':
                $this->sendEmailNotification($alert, $message);
                break;

            case 'slack':
                $this->sendSlackNotification($alert, $message);
                break;

            case 'webhook':
                $this->sendWebhookNotification($alert, $message);
                break;

            case 'sms':
                $this->sendSMSNotification($alert, $message);
                break;

            default:
                Log::warning('Unknown notification channel', ['channel' => $channel]);
        }
    }

    /**
     * Build notification message
     */
    protected function buildNotificationMessage(
        MonitoringAlert $alert,
        MonitoringKeyword $keyword,
        SocialMention $mention
    ): array {
        return [
            'alert_name' => $alert->alert_name,
            'severity' => $alert->severity,
            'keyword' => $keyword->keyword,
            'platform' => $mention->platform,
            'author' => $mention->author_username,
            'content' => $mention->getExcerpt(200),
            'sentiment' => $mention->sentiment,
            'url' => $mention->post_url,
            'published_at' => $mention->published_at->toDateTimeString(),
        ];
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification(MonitoringAlert $alert, array $message): void
    {
        Log::info('Sending email notification', [
            'alert_id' => $alert->alert_id,
            'recipients' => $alert->recipients,
        ]);

        // In production, implement actual email sending
        // Mail::to($alert->recipients)->send(new AlertNotification($message));
    }

    /**
     * Send Slack notification
     */
    protected function sendSlackNotification(MonitoringAlert $alert, array $message): void
    {
        Log::info('Sending Slack notification', [
            'alert_id' => $alert->alert_id,
        ]);

        // In production, implement Slack API integration
    }

    /**
     * Send webhook notification
     */
    protected function sendWebhookNotification(MonitoringAlert $alert, array $message): void
    {
        Log::info('Sending webhook notification', [
            'alert_id' => $alert->alert_id,
        ]);

        // In production, implement webhook POST
    }

    /**
     * Send SMS notification
     */
    protected function sendSMSNotification(MonitoringAlert $alert, array $message): void
    {
        Log::info('Sending SMS notification', [
            'alert_id' => $alert->alert_id,
        ]);

        // In production, implement SMS service (Twilio, etc.)
    }

    /**
     * Check volume-based alerts
     */
    public function checkVolumeAlerts(string $orgId): void
    {
        $alerts = MonitoringAlert::where('org_id', $orgId)
            ->active()
            ->ofType('volume')
            ->get();

        foreach ($alerts as $alert) {
            $this->evaluateVolumeAlert($alert);
        }
    }

    /**
     * Evaluate volume alert
     */
    protected function evaluateVolumeAlert(MonitoringAlert $alert): void
    {
        $conditions = $alert->trigger_conditions;
        $timeWindow = $conditions['time_window'] ?? 24; // hours
        $threshold = $alert->threshold_value;

        if (!$threshold) {
            return;
        }

        // Count mentions in time window
        $mentionCount = SocialMention::where('org_id', $alert->org_id)
            ->where('published_at', '>=', now()->subHours($timeWindow))
            ->count();

        if ($mentionCount >= $threshold) {
            Log::info('Volume alert threshold exceeded', [
                'alert_id' => $alert->alert_id,
                'mention_count' => $mentionCount,
                'threshold' => $threshold,
            ]);

            // Trigger alert
            $alert->trigger();
            // Send notification logic here
        }
    }

    /**
     * Check sentiment-based alerts
     */
    public function checkSentimentAlerts(string $orgId): void
    {
        $alerts = MonitoringAlert::where('org_id', $orgId)
            ->active()
            ->ofType('sentiment')
            ->get();

        foreach ($alerts as $alert) {
            $this->evaluateSentimentAlert($alert);
        }
    }

    /**
     * Evaluate sentiment alert
     */
    protected function evaluateSentimentAlert(MonitoringAlert $alert): void
    {
        $conditions = $alert->trigger_conditions;
        $timeWindow = $conditions['time_window'] ?? 24;
        $targetSentiment = $conditions['sentiment'] ?? ['negative'];

        // Count mentions with target sentiment
        $sentimentCount = SocialMention::where('org_id', $alert->org_id)
            ->whereIn('sentiment', $targetSentiment)
            ->where('published_at', '>=', now()->subHours($timeWindow))
            ->count();

        if ($sentimentCount >= ($alert->threshold_value ?? 5)) {
            Log::info('Sentiment alert threshold exceeded', [
                'alert_id' => $alert->alert_id,
                'sentiment_count' => $sentimentCount,
                'sentiment' => $targetSentiment,
            ]);

            $alert->trigger();
            // Send notification logic here
        }
    }

    /**
     * Get alert history
     */
    public function getAlertHistory(MonitoringAlert $alert, int $days = 30): array
    {
        // Return trigger history
        return [
            'alert_id' => $alert->alert_id,
            'total_triggers' => $alert->trigger_count,
            'last_triggered' => $alert->last_triggered_at,
            'avg_triggers_per_day' => $alert->trigger_count / max($days, 1),
        ];
    }
}
