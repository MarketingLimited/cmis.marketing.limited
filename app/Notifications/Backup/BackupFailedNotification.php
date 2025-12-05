<?php

namespace App\Notifications\Backup;

use App\Models\Backup\OrganizationBackup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Backup Failed Notification
 *
 * Sent when a backup operation fails.
 */
class BackupFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected OrganizationBackup $backup;
    protected string $errorMessage;

    /**
     * Create a new notification instance
     */
    public function __construct(OrganizationBackup $backup, string $errorMessage = '')
    {
        $this->backup = $backup;
        $this->errorMessage = $errorMessage ?: $backup->error_message ?? 'Unknown error';
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
            ->subject(__('backup.notifications.backup_failed_subject', [], $locale))
            ->greeting(__('backup.notifications.greeting', ['name' => $notifiable->name], $locale))
            ->error()
            ->line(__('backup.notifications.backup_failed_line1', [
                'code' => $this->backup->backup_code,
            ], $locale))
            ->line(__('backup.notifications.backup_failed_error', [
                'error' => $this->errorMessage,
            ], $locale))
            ->action(
                __('backup.notifications.view_details', [], $locale),
                route('backup.show', ['org' => $this->backup->org_id, 'backup' => $this->backup->id])
            )
            ->line(__('backup.notifications.backup_failed_footer', [], $locale));
    }

    /**
     * Get the array representation of the notification
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'backup_failed',
            'backup_id' => $this->backup->id,
            'backup_code' => $this->backup->backup_code,
            'error' => $this->errorMessage,
            'failed_at' => now()->toISOString(),
            'message' => __('backup.notifications.backup_failed_message', [
                'code' => $this->backup->backup_code,
            ]),
        ];
    }
}
