<?php

namespace App\Notifications\Backup;

use App\Models\Backup\OrganizationBackup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Backup Completed Notification
 *
 * Sent when a backup operation completes successfully.
 */
class BackupCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected OrganizationBackup $backup;

    /**
     * Create a new notification instance
     */
    public function __construct(OrganizationBackup $backup)
    {
        $this->backup = $backup;
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
            ->subject(__('backup.notifications.backup_completed_subject', [], $locale))
            ->greeting(__('backup.notifications.greeting', ['name' => $notifiable->name], $locale))
            ->line(__('backup.notifications.backup_completed_line1', [
                'code' => $this->backup->backup_code,
            ], $locale))
            ->line(__('backup.notifications.backup_completed_line2', [
                'size' => $this->formatBytes($this->backup->file_size),
                'records' => $this->backup->summary['total_records'] ?? 0,
            ], $locale))
            ->action(
                __('backup.notifications.view_backup', [], $locale),
                route('backup.show', ['org' => $this->backup->org_id, 'backup' => $this->backup->id])
            )
            ->line(__('backup.notifications.backup_completed_footer', [], $locale));
    }

    /**
     * Get the array representation of the notification
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'backup_completed',
            'backup_id' => $this->backup->id,
            'backup_code' => $this->backup->backup_code,
            'file_size' => $this->backup->file_size,
            'record_count' => $this->backup->summary['total_records'] ?? 0,
            'completed_at' => $this->backup->completed_at?->toISOString(),
            'message' => __('backup.notifications.backup_completed_message', [
                'code' => $this->backup->backup_code,
            ]),
        ];
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
