<?php

namespace App\Notifications\Backup;

use App\Models\Backup\BackupRestore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Restore Started Notification
 *
 * Sent to all admins when a restore operation begins.
 */
class RestoreStartedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected BackupRestore $restore;

    /**
     * Create a new notification instance
     */
    public function __construct(BackupRestore $restore)
    {
        $this->restore = $restore;
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
        $initiator = \App\Models\User::find($this->restore->created_by);

        return (new MailMessage)
            ->subject(__('backup.notifications.restore_started_subject', [], $locale))
            ->greeting(__('backup.notifications.greeting', ['name' => $notifiable->name], $locale))
            ->line(__('backup.notifications.restore_started_line1', [
                'code' => $this->restore->restore_code,
                'user' => $initiator?->name ?? 'Unknown',
            ], $locale))
            ->line(__('backup.notifications.restore_started_line2', [
                'type' => __('backup.restore_type_' . $this->restore->type, [], $locale),
            ], $locale))
            ->action(
                __('backup.notifications.view_progress', [], $locale),
                route('backup.restore.progress', ['org' => $this->restore->org_id, 'restore' => $this->restore->id])
            )
            ->line(__('backup.notifications.restore_started_footer', [], $locale));
    }

    /**
     * Get the array representation of the notification
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'restore_started',
            'restore_id' => $this->restore->id,
            'restore_code' => $this->restore->restore_code,
            'restore_type' => $this->restore->type,
            'initiated_by' => $this->restore->created_by,
            'started_at' => $this->restore->started_at?->toISOString(),
            'message' => __('backup.notifications.restore_started_message', [
                'code' => $this->restore->restore_code,
            ]),
        ];
    }
}
