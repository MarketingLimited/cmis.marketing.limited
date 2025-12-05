<?php

namespace App\Notifications\Backup;

use App\Models\Backup\BackupRestore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Restore Rolled Back Notification
 *
 * Sent when a restore operation is rolled back to safety backup.
 */
class RestoreRolledBackNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected BackupRestore $restore;
    protected ?string $initiatedBy;

    /**
     * Create a new notification instance
     */
    public function __construct(BackupRestore $restore, ?string $initiatedBy = null)
    {
        $this->restore = $restore;
        $this->initiatedBy = $initiatedBy;
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
        $initiator = $this->initiatedBy ? \App\Models\User::find($this->initiatedBy) : null;

        return (new MailMessage)
            ->subject(__('backup.notifications.restore_rolledback_subject', [], $locale))
            ->greeting(__('backup.notifications.greeting', ['name' => $notifiable->name], $locale))
            ->line(__('backup.notifications.restore_rolledback_line1', [
                'code' => $this->restore->restore_code,
                'user' => $initiator?->name ?? __('backup.notifications.system', [], $locale),
            ], $locale))
            ->line(__('backup.notifications.restore_rolledback_line2', [], $locale))
            ->action(
                __('backup.notifications.view_details', [], $locale),
                route('backup.restore.progress', ['org' => $this->restore->org_id, 'restore' => $this->restore->id])
            )
            ->line(__('backup.notifications.restore_rolledback_footer', [], $locale));
    }

    /**
     * Get the array representation of the notification
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'restore_rolled_back',
            'restore_id' => $this->restore->id,
            'restore_code' => $this->restore->restore_code,
            'safety_backup_id' => $this->restore->safety_backup_id,
            'initiated_by' => $this->initiatedBy,
            'rolled_back_at' => now()->toISOString(),
            'message' => __('backup.notifications.restore_rolledback_message', [
                'code' => $this->restore->restore_code,
            ]),
        ];
    }
}
