<?php

namespace App\Notifications\Backup;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Storage Quota Warning Notification
 *
 * Sent when organization backup storage is approaching quota limit.
 */
class StorageQuotaWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $orgId;
    protected int $usedBytes;
    protected int $limitBytes;
    protected int $percentUsed;

    /**
     * Create a new notification instance
     */
    public function __construct(string $orgId, int $usedBytes, int $limitBytes)
    {
        $this->orgId = $orgId;
        $this->usedBytes = $usedBytes;
        $this->limitBytes = $limitBytes;
        $this->percentUsed = $limitBytes > 0 ? round(($usedBytes / $limitBytes) * 100) : 0;
    }

    /**
     * Get the notification's delivery channels
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification
     */
    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->locale ?? app()->getLocale();

        return (new MailMessage)
            ->subject(__('backup.notifications.storage_quota_subject', [
                'percent' => $this->percentUsed,
            ], $locale))
            ->greeting(__('backup.notifications.greeting', ['name' => $notifiable->name], $locale))
            ->line(__('backup.notifications.storage_quota_line1', [
                'percent' => $this->percentUsed,
            ], $locale))
            ->line(__('backup.notifications.storage_quota_line2', [
                'used' => $this->formatBytes($this->usedBytes),
                'limit' => $this->formatBytes($this->limitBytes),
            ], $locale))
            ->line(__('backup.notifications.storage_quota_line3', [], $locale))
            ->action(
                __('backup.notifications.manage_backups', [], $locale),
                route('backup.index', ['org' => $this->orgId])
            )
            ->line(__('backup.notifications.storage_quota_footer', [], $locale));
    }

    /**
     * Get the array representation of the notification
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'storage_quota_warning',
            'org_id' => $this->orgId,
            'used_bytes' => $this->usedBytes,
            'limit_bytes' => $this->limitBytes,
            'percent_used' => $this->percentUsed,
            'message' => __('backup.notifications.storage_quota_message', [
                'percent' => $this->percentUsed,
            ]),
        ];
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
