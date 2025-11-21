<?php

namespace App\Services\Analytics;

use App\Models\Analytics\AlertHistory;
use App\Models\Analytics\AlertNotification;
use App\Models\Analytics\AlertRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notification Delivery Service (Phase 13)
 *
 * Handles multi-channel notification delivery for triggered alerts
 *
 * Supported Channels:
 * - Email
 * - In-app notifications
 * - Slack
 * - Webhooks
 */
class NotificationDeliveryService
{
    /**
     * Deliver notifications for a triggered alert
     *
     * @param AlertHistory $alert
     * @param AlertRule $rule
     * @return array Delivery statistics
     */
    public function deliverAlert(AlertHistory $alert, AlertRule $rule): array
    {
        $stats = [
            'total' => 0,
            'sent' => 0,
            'failed' => 0
        ];

        foreach ($rule->notification_channels as $channel) {
            $stats['total']++;

            try {
                $this->sendToChannel($alert, $rule, $channel);
                $stats['sent']++;
            } catch (\Exception $e) {
                $stats['failed']++;
                Log::error("Failed to send alert notification via {$channel}", [
                    'alert_id' => $alert->alert_id,
                    'channel' => $channel,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $stats;
    }

    /**
     * Send notification to specific channel
     *
     * @param AlertHistory $alert
     * @param AlertRule $rule
     * @param string $channel
     * @return void
     */
    protected function sendToChannel(AlertHistory $alert, AlertRule $rule, string $channel): void
    {
        match ($channel) {
            'email' => $this->sendEmail($alert, $rule),
            'in_app' => $this->sendInApp($alert, $rule),
            'slack' => $this->sendSlack($alert, $rule),
            'webhook' => $this->sendWebhook($alert, $rule),
            default => throw new \InvalidArgumentException("Unsupported channel: {$channel}")
        };
    }

    /**
     * Send email notification
     *
     * @param AlertHistory $alert
     * @param AlertRule $rule
     * @return void
     */
    protected function sendEmail(AlertHistory $alert, AlertRule $rule): void
    {
        $config = $rule->notification_config['email'] ?? [];
        $recipients = $config['recipients'] ?? [$rule->creator->email];

        foreach ($recipients as $recipient) {
            $notification = $this->createNotificationRecord($alert, 'email', $recipient);

            try {
                Mail::send('emails.alert_notification', [
                    'alert' => $alert,
                    'rule' => $rule,
                    'severity' => $alert->severity,
                    'message' => $alert->message,
                    'triggeredAt' => $alert->triggered_at->format('Y-m-d H:i:s'),
                    'actualValue' => $alert->actual_value,
                    'threshold' => $alert->threshold_value,
                    'entityType' => $alert->entity_type,
                    'entityId' => $alert->entity_id
                ], function ($message) use ($recipient, $alert, $rule) {
                    $message->to($recipient)
                        ->subject($this->getEmailSubject($alert, $rule));
                });

                $notification->markSent();
                $notification->markDelivered();
            } catch (\Exception $e) {
                $notification->markFailed($e->getMessage());
                throw $e;
            }
        }
    }

    /**
     * Send in-app notification
     *
     * @param AlertHistory $alert
     * @param AlertRule $rule
     * @return void
     */
    protected function sendInApp(AlertHistory $alert, AlertRule $rule): void
    {
        $config = $rule->notification_config['in_app'] ?? [];
        $userIds = $config['user_ids'] ?? [$rule->created_by];

        foreach ($userIds as $userId) {
            $notification = $this->createNotificationRecord($alert, 'in_app', $userId);

            try {
                // Create in-app notification record
                \DB::table('cmis.user_notifications')->insert([
                    'notification_id' => \Str::uuid()->toString(),
                    'user_id' => $userId,
                    'type' => 'alert',
                    'title' => $this->getNotificationTitle($alert),
                    'message' => $alert->message,
                    'data' => json_encode([
                        'alert_id' => $alert->alert_id,
                        'rule_id' => $rule->rule_id,
                        'severity' => $alert->severity,
                        'entity_type' => $alert->entity_type,
                        'entity_id' => $alert->entity_id
                    ]),
                    'severity' => $alert->severity,
                    'is_read' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $notification->markSent();
                $notification->markDelivered();
            } catch (\Exception $e) {
                $notification->markFailed($e->getMessage());
                throw $e;
            }
        }
    }

    /**
     * Send Slack notification
     *
     * @param AlertHistory $alert
     * @param AlertRule $rule
     * @return void
     */
    protected function sendSlack(AlertHistory $alert, AlertRule $rule): void
    {
        $config = $rule->notification_config['slack'] ?? [];
        $webhookUrl = $config['webhook_url'] ?? null;

        if (!$webhookUrl) {
            throw new \RuntimeException('Slack webhook URL not configured');
        }

        $notification = $this->createNotificationRecord($alert, 'slack', $webhookUrl);

        try {
            $payload = $this->buildSlackPayload($alert, $rule);

            $response = Http::post($webhookUrl, $payload);

            if ($response->successful()) {
                $notification->markSent();
                $notification->markDelivered();
            } else {
                $notification->markFailed("HTTP {$response->status()}: {$response->body()}");
            }
        } catch (\Exception $e) {
            $notification->markFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Send webhook notification
     *
     * @param AlertHistory $alert
     * @param AlertRule $rule
     * @return void
     */
    protected function sendWebhook(AlertHistory $alert, AlertRule $rule): void
    {
        $config = $rule->notification_config['webhook'] ?? [];
        $url = $config['url'] ?? null;

        if (!$url) {
            throw new \RuntimeException('Webhook URL not configured');
        }

        $notification = $this->createNotificationRecord($alert, 'webhook', $url);

        try {
            $payload = [
                'event' => 'alert.triggered',
                'alert_id' => $alert->alert_id,
                'rule_id' => $rule->rule_id,
                'rule_name' => $rule->name,
                'triggered_at' => $alert->triggered_at->toIso8601String(),
                'severity' => $alert->severity,
                'status' => $alert->status,
                'entity' => [
                    'type' => $alert->entity_type,
                    'id' => $alert->entity_id
                ],
                'metric' => [
                    'name' => $alert->metric,
                    'actual_value' => $alert->actual_value,
                    'threshold_value' => $alert->threshold_value,
                    'condition' => $alert->condition
                ],
                'message' => $alert->message,
                'metadata' => $alert->metadata
            ];

            $headers = $config['headers'] ?? [];
            $headers['Content-Type'] = 'application/json';
            $headers['X-CMIS-Event'] = 'alert.triggered';
            $headers['X-CMIS-Signature'] = $this->generateWebhookSignature($payload, $config['secret'] ?? '');

            $response = Http::withHeaders($headers)
                ->timeout($config['timeout'] ?? 10)
                ->post($url, $payload);

            if ($response->successful()) {
                $notification->markSent();
                $notification->markDelivered();
            } else {
                $notification->markFailed("HTTP {$response->status()}: {$response->body()}");
            }
        } catch (\Exception $e) {
            $notification->markFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Create notification tracking record
     *
     * @param AlertHistory $alert
     * @param string $channel
     * @param string $recipient
     * @return AlertNotification
     */
    protected function createNotificationRecord(AlertHistory $alert, string $channel, string $recipient): AlertNotification
    {
        return AlertNotification::create([
            'alert_id' => $alert->alert_id,
            'org_id' => $alert->org_id,
            'channel' => $channel,
            'recipient' => $recipient,
            'sent_at' => now(),
            'status' => 'pending'
        ]);
    }

    /**
     * Get email subject line
     *
     * @param AlertHistory $alert
     * @param AlertRule $rule
     * @return string
     */
    protected function getEmailSubject(AlertHistory $alert, AlertRule $rule): string
    {
        $severityEmoji = match ($alert->severity) {
            'critical' => '🚨',
            'high' => '⚠️',
            'medium' => '📊',
            'low' => 'ℹ️',
            default => ''
        };

        return "{$severityEmoji} CMIS Alert: {$rule->name}";
    }

    /**
     * Get notification title
     *
     * @param AlertHistory $alert
     * @return string
     */
    protected function getNotificationTitle(AlertHistory $alert): string
    {
        return ucfirst($alert->severity) . ' Alert: ' . ucfirst($alert->metric);
    }

    /**
     * Build Slack message payload
     *
     * @param AlertHistory $alert
     * @param AlertRule $rule
     * @return array
     */
    protected function buildSlackPayload(AlertHistory $alert, AlertRule $rule): array
    {
        $color = match ($alert->severity) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => '#3AA3E3',
            'low' => 'good',
            default => '#808080'
        };

        return [
            'text' => "Alert Triggered: {$rule->name}",
            'attachments' => [
                [
                    'color' => $color,
                    'title' => $rule->name,
                    'text' => $alert->message,
                    'fields' => [
                        [
                            'title' => 'Severity',
                            'value' => ucfirst($alert->severity),
                            'short' => true
                        ],
                        [
                            'title' => 'Metric',
                            'value' => ucfirst(str_replace('_', ' ', $alert->metric)),
                            'short' => true
                        ],
                        [
                            'title' => 'Actual Value',
                            'value' => number_format($alert->actual_value, 2),
                            'short' => true
                        ],
                        [
                            'title' => 'Threshold',
                            'value' => number_format($alert->threshold_value, 2),
                            'short' => true
                        ],
                        [
                            'title' => 'Entity',
                            'value' => "{$alert->entity_type}: {$alert->entity_id}",
                            'short' => false
                        ]
                    ],
                    'footer' => 'CMIS Analytics',
                    'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
                    'ts' => $alert->triggered_at->timestamp
                ]
            ]
        ];
    }

    /**
     * Generate webhook signature for verification
     *
     * @param array $payload
     * @param string $secret
     * @return string
     */
    protected function generateWebhookSignature(array $payload, string $secret): string
    {
        $data = json_encode($payload);
        return hash_hmac('sha256', $data, $secret);
    }

    /**
     * Retry failed notification
     *
     * @param AlertNotification $notification
     * @return bool
     */
    public function retryNotification(AlertNotification $notification): bool
    {
        if (!$notification->canRetry()) {
            return false;
        }

        $alert = $notification->alert;
        $rule = $alert->rule;

        try {
            $this->sendToChannel($alert, $rule, $notification->channel);
            return true;
        } catch (\Exception $e) {
            Log::error('Notification retry failed', [
                'notification_id' => $notification->notification_id,
                'channel' => $notification->channel,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
