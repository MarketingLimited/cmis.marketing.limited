<?php

namespace App\Notifications\Backup;

use App\Models\Backup\OrganizationBackup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Backup Expiring Notification
 *
 * Sent when a backup is about to expire (e.g., 7 days before).
 */
class BackupExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected OrganizationBackup $backup;
    protected int $daysUntilExpiry;

    /**
     * Create a new notification instance
     */
    public function __construct(OrganizationBackup $backup, int $daysUntilExpiry = 7)
    {
        $this->backup = $backup;
        $this->daysUntilExpiry = $daysUntilExpiry;
    }

    /**
     * Get the notification's delivery channels
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Only database notification, no email spam
    }

    /**
     * Get the mail representation of the notification (optional)
     */
    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->locale ?? app()->getLocale();

        return (new MailMessage)
            ->subject(__('backup.notifications.backup_expiring_subject', [
                'days' => $this->daysUntilExpiry,
            ], $locale))
            ->greeting(__('backup.notifications.greeting', ['name' => $notifiable->name], $locale))
            ->line(__('backup.notifications.backup_expiring_line1', [
                'code' => $this->backup->backup_code,
                'days' => $this->daysUntilExpiry,
            ], $locale))
            ->line(__('backup.notifications.backup_expiring_line2', [
                'date' => $this->backup->expires_at?->format('Y-m-d'),
            ], $locale))
            ->action(
                __('backup.notifications.download_backup', [], $locale),
                route('backup.download', ['org' => $this->backup->org_id, 'backup' => $this->backup->id])
            )
            ->line(__('backup.notifications.backup_expiring_footer', [], $locale));
    }

    /**
     * Get the array representation of the notification
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'backup_expiring',
            'backup_id' => $this->backup->id,
            'backup_code' => $this->backup->backup_code,
            'days_until_expiry' => $this->daysUntilExpiry,
            'expires_at' => $this->backup->expires_at?->toISOString(),
            'message' => __('backup.notifications.backup_expiring_message', [
                'code' => $this->backup->backup_code,
                'days' => $this->daysUntilExpiry,
            ]),
        ];
    }
}
