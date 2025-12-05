<?php

namespace App\Notifications\Backup;

use App\Models\Backup\BackupRestore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Restore Failed Notification
 *
 * Sent when a restore operation fails.
 */
class RestoreFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected BackupRestore $restore;
    protected string $errorMessage;

    /**
     * Create a new notification instance
     */
    public function __construct(BackupRestore $restore, string $errorMessage = '')
    {
        $this->restore = $restore;
        $this->errorMessage = $errorMessage ?: $restore->error_message ?? 'Unknown error';
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

        $mail = (new MailMessage)
            ->subject(__('backup.notifications.restore_failed_subject', [], $locale))
            ->greeting(__('backup.notifications.greeting', ['name' => $notifiable->name], $locale))
            ->error()
            ->line(__('backup.notifications.restore_failed_line1', [
                'code' => $this->restore->restore_code,
            ], $locale))
            ->line(__('backup.notifications.restore_failed_error', [
                'error' => $this->errorMessage,
            ], $locale));

        // Add safety backup info if available
        if ($this->restore->safety_backup_id) {
            $mail->line(__('backup.notifications.restore_failed_safety', [], $locale));
        }

        return $mail
            ->action(
                __('backup.notifications.view_details', [], $locale),
                route('backup.restore.progress', ['org' => $this->restore->org_id, 'restore' => $this->restore->id])
            )
            ->line(__('backup.notifications.restore_failed_footer', [], $locale));
    }

    /**
     * Get the array representation of the notification
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'restore_failed',
            'restore_id' => $this->restore->id,
            'restore_code' => $this->restore->restore_code,
            'restore_type' => $this->restore->type,
            'error' => $this->errorMessage,
            'safety_backup_id' => $this->restore->safety_backup_id,
            'failed_at' => now()->toISOString(),
            'message' => __('backup.notifications.restore_failed_message', [
                'code' => $this->restore->restore_code,
            ]),
        ];
    }
}
