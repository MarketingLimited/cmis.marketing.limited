<?php

namespace App\Services;

use App\Models\Notification\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * General-purpose Notification Service
 *
 * Provides unified notification delivery across multiple channels:
 * - In-app notifications (stored in database)
 * - Email notifications
 * - Slack notifications
 *
 * Used by JobStatusService, WebhookRetryService, and other system components.
 */
class NotificationService
{
    /**
     * Notification types
     */
    public const TYPE_JOB_FAILED = 'job_failed';
    public const TYPE_JOB_COMPLETED = 'job_completed';
    public const TYPE_WEBHOOK_FAILED = 'webhook_failed';
    public const TYPE_SYSTEM_ALERT = 'system_alert';
    public const TYPE_CAMPAIGN_ALERT = 'campaign_alert';
    public const TYPE_TOKEN_EXPIRING = 'token_expiring';
    public const TYPE_PUBLISHING_FAILED = 'publishing_failed';
    public const TYPE_PUBLISHING_COMPLETED = 'publishing_completed';
    public const TYPE_BUDGET_ALERT = 'budget_alert';
    public const TYPE_CAMPAIGN_STATUS = 'campaign_status';
    public const TYPE_PERFORMANCE_ALERT = 'performance_alert';
    public const TYPE_INTEGRATION_CONNECTED = 'integration_connected';
    public const TYPE_INTEGRATION_DISCONNECTED = 'integration_disconnected';
    public const TYPE_SYNC_COMPLETED = 'sync_completed';
    public const TYPE_SYNC_FAILED = 'sync_failed';

    /**
     * Priority levels
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    /**
     * Send notification to a user via multiple channels.
     *
     * @param string $userId User ID to notify
     * @param string $type Notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array $options Additional options:
     *   - org_id: Organization ID
     *   - priority: low, medium, high, critical
     *   - category: Category for grouping
     *   - action_url: URL for user to take action
     *   - data: Additional data array
     *   - related_entity_type: Type of related entity
     *   - related_entity_id: ID of related entity
     *   - channels: Array of channels ['in_app', 'email', 'slack']
     *   - expires_at: Expiration datetime
     * @return array Result with notification_id
     */
    public function notify(string $userId, string $type, string $title, string $message, array $options = []): array
    {
        $channels = $options['channels'] ?? ['in_app'];
        $results = [];

        foreach ($channels as $channel) {
            try {
                $result = match ($channel) {
                    'in_app' => $this->sendInAppNotification($userId, $type, $title, $message, $options),
                    'email' => $this->sendEmailNotification($userId, $type, $title, $message, $options),
                    'slack' => $this->sendSlackNotification($type, $title, $message, $options),
                    default => ['success' => false, 'error' => "Unknown channel: {$channel}"],
                };
                $results[$channel] = $result;
            } catch (\Exception $e) {
                Log::error("Failed to send notification via {$channel}", [
                    'user_id' => $userId,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
                $results[$channel] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return [
            'success' => collect($results)->contains('success', true),
            'results' => $results,
        ];
    }

    /**
     * Notify user of job failure.
     *
     * @param string $userId User ID
     * @param string $jobId Job ID
     * @param string $jobType Type of job
     * @param string $errorMessage Error message
     * @param array $options Additional options
     * @return array
     */
    public function notifyJobFailure(string $userId, string $jobId, string $jobType, string $errorMessage, array $options = []): array
    {
        $title = $this->getJobTypeLabel($jobType) . ' Failed';
        $message = "Your {$this->getJobTypeLabel($jobType)} job has failed: {$errorMessage}";

        return $this->notify($userId, self::TYPE_JOB_FAILED, $title, $message, array_merge($options, [
            'priority' => self::PRIORITY_HIGH,
            'category' => 'jobs',
            'related_entity_type' => 'job',
            'related_entity_id' => $jobId,
            'data' => [
                'job_id' => $jobId,
                'job_type' => $jobType,
                'error_message' => $errorMessage,
            ],
            'action_url' => route('jobs.status', ['id' => $jobId], false),
            'channels' => $options['channels'] ?? ['in_app', 'email'],
        ]));
    }

    /**
     * Notify user of job completion.
     *
     * @param string $userId User ID
     * @param string $jobId Job ID
     * @param string $jobType Type of job
     * @param mixed $result Job result
     * @param array $options Additional options
     * @return array
     */
    public function notifyJobCompletion(string $userId, string $jobId, string $jobType, $result = null, array $options = []): array
    {
        $title = $this->getJobTypeLabel($jobType) . ' Completed';
        $message = "Your {$this->getJobTypeLabel($jobType)} job has completed successfully.";

        return $this->notify($userId, self::TYPE_JOB_COMPLETED, $title, $message, array_merge($options, [
            'priority' => self::PRIORITY_LOW,
            'category' => 'jobs',
            'related_entity_type' => 'job',
            'related_entity_id' => $jobId,
            'data' => [
                'job_id' => $jobId,
                'job_type' => $jobType,
                'result' => $result,
            ],
            'channels' => $options['channels'] ?? ['in_app'],
        ]));
    }

    /**
     * Notify admins of webhook failure (dead letter queue).
     *
     * @param string $webhookId Webhook ID
     * @param string $platform Platform name
     * @param string $reason Failure reason
     * @param array $options Additional options
     * @return array
     */
    public function notifyWebhookFailure(string $webhookId, string $platform, string $reason, array $options = []): array
    {
        $title = "Webhook Delivery Failed - {$platform}";
        $message = "A webhook has been moved to the dead letter queue after exhausting all retries. Platform: {$platform}. Reason: {$reason}";

        // Get admin users to notify
        $admins = $this->getAdminUsers($options['org_id'] ?? null);

        $results = [];
        foreach ($admins as $admin) {
            $results[$admin->id] = $this->notify($admin->id, self::TYPE_WEBHOOK_FAILED, $title, $message, array_merge($options, [
                'priority' => self::PRIORITY_CRITICAL,
                'category' => 'webhooks',
                'related_entity_type' => 'webhook',
                'related_entity_id' => $webhookId,
                'data' => [
                    'webhook_id' => $webhookId,
                    'platform' => $platform,
                    'reason' => $reason,
                ],
                'channels' => ['in_app', 'email', 'slack'],
            ]));
        }

        return [
            'success' => !empty($results),
            'admins_notified' => count($results),
        ];
    }

    /**
     * Notify user of publishing failure.
     *
     * @param string $userId User ID
     * @param string $postId Post ID
     * @param string $platform Platform name
     * @param string $errorMessage Error message
     * @param array $options Additional options
     * @return array
     */
    public function notifyPublishingFailure(string $userId, string $postId, string $platform, string $errorMessage, array $options = []): array
    {
        $title = "Publishing Failed - {$platform}";
        $message = "Your post could not be published to {$platform}: {$errorMessage}";

        return $this->notify($userId, self::TYPE_PUBLISHING_FAILED, $title, $message, array_merge($options, [
            'priority' => self::PRIORITY_HIGH,
            'category' => 'publishing',
            'related_entity_type' => 'social_post',
            'related_entity_id' => $postId,
            'data' => [
                'post_id' => $postId,
                'platform' => $platform,
                'error_message' => $errorMessage,
            ],
            'channels' => $options['channels'] ?? ['in_app', 'email'],
        ]));
    }

    /**
     * Notify user of token expiration.
     *
     * @param string $userId User ID
     * @param string $platform Platform name
     * @param string $connectionId Connection ID
     * @param \DateTime $expiresAt Expiration date
     * @param array $options Additional options
     * @return array
     */
    public function notifyTokenExpiring(string $userId, string $platform, string $connectionId, \DateTime $expiresAt, array $options = []): array
    {
        $daysUntilExpiry = now()->diffInDays($expiresAt);
        $title = "{$platform} Connection Expiring";
        $message = "Your {$platform} connection will expire in {$daysUntilExpiry} days. Please reconnect to avoid service interruption.";

        return $this->notify($userId, self::TYPE_TOKEN_EXPIRING, $title, $message, array_merge($options, [
            'priority' => $daysUntilExpiry <= 3 ? self::PRIORITY_HIGH : self::PRIORITY_MEDIUM,
            'category' => 'connections',
            'related_entity_type' => 'platform_connection',
            'related_entity_id' => $connectionId,
            'data' => [
                'platform' => $platform,
                'connection_id' => $connectionId,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'days_until_expiry' => $daysUntilExpiry,
            ],
            'action_url' => route('settings.platform-connections', [], false),
            'channels' => $options['channels'] ?? ['in_app', 'email'],
        ]));
    }

    /**
     * Send in-app notification (stored in database).
     *
     * @param string $userId User ID
     * @param string $type Notification type
     * @param string $title Title
     * @param string $message Message
     * @param array $options Options
     * @return array
     */
    protected function sendInAppNotification(string $userId, string $type, string $title, string $message, array $options = []): array
    {
        try {
            $notification = Notification::create([
                'notification_id' => Str::uuid()->toString(),
                'user_id' => $userId,
                'org_id' => $options['org_id'] ?? null,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'action_url' => $options['action_url'] ?? null,
                'priority' => $options['priority'] ?? self::PRIORITY_MEDIUM,
                'category' => $options['category'] ?? 'general',
                'related_entity_type' => $options['related_entity_type'] ?? null,
                'related_entity_id' => $options['related_entity_id'] ?? null,
                'data' => $options['data'] ?? null,
                'is_read' => false,
                'expires_at' => $options['expires_at'] ?? null,
            ]);

            Log::info('In-app notification created', [
                'notification_id' => $notification->notification_id,
                'user_id' => $userId,
                'type' => $type,
            ]);

            return [
                'success' => true,
                'notification_id' => $notification->notification_id,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create in-app notification', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send email notification.
     *
     * @param string $userId User ID
     * @param string $type Notification type
     * @param string $title Title
     * @param string $message Message
     * @param array $options Options
     * @return array
     */
    protected function sendEmailNotification(string $userId, string $type, string $title, string $message, array $options = []): array
    {
        try {
            $user = User::find($userId);
            if (!$user || !$user->email) {
                return ['success' => false, 'error' => 'User not found or no email'];
            }

            // Check user notification preferences
            if (!$this->shouldSendEmail($user, $type)) {
                return ['success' => true, 'skipped' => true, 'reason' => 'User opted out'];
            }

            $priority = $options['priority'] ?? self::PRIORITY_MEDIUM;
            $severityEmoji = $this->getPriorityEmoji($priority);

            Mail::send('emails.notification', [
                'title' => $title,
                'message' => $message,
                'priority' => $priority,
                'actionUrl' => $options['action_url'] ?? null,
                'data' => $options['data'] ?? [],
            ], function ($mail) use ($user, $title, $severityEmoji) {
                $mail->to($user->email, $user->name)
                    ->subject("{$severityEmoji} CMIS: {$title}");
            });

            Log::info('Email notification sent', [
                'user_id' => $userId,
                'email' => $user->email,
                'type' => $type,
            ]);

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send Slack notification.
     *
     * @param string $type Notification type
     * @param string $title Title
     * @param string $message Message
     * @param array $options Options
     * @return array
     */
    protected function sendSlackNotification(string $type, string $title, string $message, array $options = []): array
    {
        try {
            $webhookUrl = config('services.slack.webhook_url');
            if (!$webhookUrl) {
                return ['success' => false, 'error' => 'Slack webhook URL not configured'];
            }

            $priority = $options['priority'] ?? self::PRIORITY_MEDIUM;
            $color = $this->getSlackColor($priority);

            $payload = [
                'text' => "{$this->getPriorityEmoji($priority)} {$title}",
                'attachments' => [
                    [
                        'color' => $color,
                        'title' => $title,
                        'text' => $message,
                        'fields' => [
                            [
                                'title' => 'Priority',
                                'value' => ucfirst($priority),
                                'short' => true,
                            ],
                            [
                                'title' => 'Type',
                                'value' => ucfirst(str_replace('_', ' ', $type)),
                                'short' => true,
                            ],
                        ],
                        'footer' => 'CMIS Notification',
                        'ts' => now()->timestamp,
                    ],
                ],
            ];

            // Add action URL if provided
            if (!empty($options['action_url'])) {
                $payload['attachments'][0]['actions'] = [
                    [
                        'type' => 'button',
                        'text' => 'View Details',
                        'url' => url($options['action_url']),
                    ],
                ];
            }

            $response = Http::post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info('Slack notification sent', ['type' => $type]);
                return ['success' => true];
            }

            return ['success' => false, 'error' => "HTTP {$response->status()}"];
        } catch (\Exception $e) {
            Log::error('Failed to send Slack notification', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get unread notification count for user.
     *
     * @param string $userId User ID
     * @return int
     */
    public function getUnreadCount(string $userId): int
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->active()
            ->count();
    }

    /**
     * Get user's recent notifications.
     *
     * @param string $userId User ID
     * @param int $limit Number of notifications
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserNotifications(string $userId, int $limit = 20)
    {
        return Notification::where('user_id', $userId)
            ->active()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark notification as read.
     *
     * @param string $notificationId Notification ID
     * @param string $userId User ID (for verification)
     * @return bool
     */
    public function markAsRead(string $notificationId, string $userId): bool
    {
        $notification = Notification::where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    /**
     * Mark all notifications as read for user.
     *
     * @param string $userId User ID
     * @return int Number updated
     */
    public function markAllAsRead(string $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get admin users for notifications.
     *
     * @param string|null $orgId Organization ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAdminUsers(?string $orgId = null)
    {
        $query = User::where('is_super_admin', true);

        if ($orgId) {
            // Also include org admins
            $query->orWhere(function ($q) use ($orgId) {
                $q->where('org_id', $orgId)
                    ->whereHas('roles', function ($roleQuery) {
                        $roleQuery->where('name', 'admin');
                    });
            });
        }

        return $query->get();
    }

    /**
     * Check if user should receive email for notification type.
     *
     * @param User $user User model
     * @param string $type Notification type
     * @return bool
     */
    protected function shouldSendEmail(User $user, string $type): bool
    {
        // Check notification preferences
        $preference = DB::table('cmis.notification_preferences')
            ->where('user_id', $user->id)
            ->first();

        if (!$preference) {
            return true; // Default to sending
        }

        $preferences = json_decode($preference->email_preferences ?? '{}', true);

        // Check if this type is explicitly disabled
        return !isset($preferences[$type]) || $preferences[$type] !== false;
    }

    /**
     * Get human-readable job type label.
     *
     * @param string $jobType Job type
     * @return string
     */
    protected function getJobTypeLabel(string $jobType): string
    {
        return match ($jobType) {
            'embedding_generation' => 'AI Embedding Generation',
            'report_generation' => 'Report Generation',
            'platform_sync' => 'Platform Sync',
            'bulk_operation' => 'Bulk Operation',
            'import' => 'Data Import',
            'export' => 'Data Export',
            'knowledge_base' => 'Knowledge Base Build',
            default => ucwords(str_replace('_', ' ', $jobType)),
        };
    }

    /**
     * Get emoji for priority level.
     *
     * @param string $priority Priority level
     * @return string
     */
    protected function getPriorityEmoji(string $priority): string
    {
        return match ($priority) {
            self::PRIORITY_CRITICAL => "\u{1F6A8}", // 🚨
            self::PRIORITY_HIGH => "\u{26A0}\u{FE0F}", // ⚠️
            self::PRIORITY_MEDIUM => "\u{1F4CA}", // 📊
            self::PRIORITY_LOW => "\u{2139}\u{FE0F}", // ℹ️
            default => '',
        };
    }

    /**
     * Get Slack attachment color for priority.
     *
     * @param string $priority Priority level
     * @return string
     */
    protected function getSlackColor(string $priority): string
    {
        return match ($priority) {
            self::PRIORITY_CRITICAL => 'danger',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_MEDIUM => '#3AA3E3',
            self::PRIORITY_LOW => 'good',
            default => '#808080',
        };
    }
}
