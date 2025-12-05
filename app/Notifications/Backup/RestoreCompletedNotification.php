<?php

namespace App\Notifications\Backup;

use App\Models\Backup\BackupRestore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Restore Completed Notification
 *
 * Sent when a restore operation completes successfully.
 */
class RestoreCompletedNotification extends Notification implements ShouldQueue
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
        $report = $this->restore->execution_report ?? [];

        return (new MailMessage)
            ->subject(__('backup.notifications.restore_completed_subject', [], $locale))
            ->greeting(__('backup.notifications.greeting', ['name' => $notifiable->name], $locale))
            ->line(__('backup.notifications.restore_completed_line1', [
                'code' => $this->restore->restore_code,
            ], $locale))
            ->line(__('backup.notifications.restore_completed_stats', [
                'restored' => $report['records_restored'] ?? 0,
                'updated' => $report['records_updated'] ?? 0,
                'skipped' => $report['records_skipped'] ?? 0,
            ], $locale))
            ->when(
                $this->restore->safety_backup_id,
                fn ($mail) => $mail->line(__('backup.notifications.restore_completed_safety', [], $locale))
            )
            ->action(
                __('backup.notifications.view_details', [], $locale),
                route('backup.restore.progress', ['org' => $this->restore->org_id, 'restore' => $this->restore->id])
            )
            ->line(__('backup.notifications.restore_completed_footer', [
                'expires' => $this->restore->rollback_expires_at?->format('Y-m-d H:i'),
            ], $locale));
    }

    /**
     * Get the array representation of the notification
     */
    public function toArray(object $notifiable): array
    {
        $report = $this->restore->execution_report ?? [];

        return [
            'type' => 'restore_completed',
            'restore_id' => $this->restore->id,
            'restore_code' => $this->restore->restore_code,
            'restore_type' => $this->restore->type,
            'records_restored' => $report['records_restored'] ?? 0,
            'records_updated' => $report['records_updated'] ?? 0,
            'records_skipped' => $report['records_skipped'] ?? 0,
            'safety_backup_id' => $this->restore->safety_backup_id,
            'rollback_expires_at' => $this->restore->rollback_expires_at?->toISOString(),
            'completed_at' => $this->restore->completed_at?->toISOString(),
            'message' => __('backup.notifications.restore_completed_message', [
                'code' => $this->restore->restore_code,
            ]),
        ];
    }
}
