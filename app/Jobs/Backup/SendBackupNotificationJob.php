<?php

namespace App\Jobs\Backup;

use App\Models\Backup\OrganizationBackup;
use App\Models\Backup\BackupRestore;
use App\Models\Backup\BackupSetting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Send Backup Notification Job
 *
 * Handles asynchronous sending of backup-related notifications
 * to users based on organization notification settings.
 */
class SendBackupNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of retry attempts
     */
    public int $tries = 3;

    /**
     * Job timeout in seconds
     */
    public int $timeout = 60;

    /**
     * Retry backoff in seconds
     */
    public array $backoff = [10, 30, 60];

    /**
     * Organization ID
     */
    protected string $orgId;

    /**
     * Notification class name
     */
    protected string $notificationClass;

    /**
     * Notification parameters
     */
    protected array $params;

    /**
     * Additional recipient user IDs
     */
    protected array $additionalRecipients;

    /**
     * Create a new job instance
     */
    public function __construct(
        string $orgId,
        string $notificationClass,
        array $params = [],
        array $additionalRecipients = []
    ) {
        $this->orgId = $orgId;
        $this->notificationClass = $notificationClass;
        $this->params = $params;
        $this->additionalRecipients = $additionalRecipients;
        $this->onQueue('notifications');
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        try {
            // Get organization notification settings
            $settings = BackupSetting::where('org_id', $this->orgId)->first();

            // Check if notification is enabled
            if (!$this->isNotificationEnabled($settings)) {
                Log::debug("Notification disabled for organization", [
                    'org_id' => $this->orgId,
                    'notification' => $this->notificationClass,
                ]);
                return;
            }

            // Get recipients
            $recipients = $this->getRecipients($settings);

            if (empty($recipients)) {
                Log::debug("No recipients for notification", [
                    'org_id' => $this->orgId,
                    'notification' => $this->notificationClass,
                ]);
                return;
            }

            // Create notification instance
            $notification = $this->createNotification();

            // Send to each recipient
            foreach ($recipients as $recipient) {
                try {
                    $recipient->notify($notification);
                } catch (\Exception $e) {
                    Log::warning("Failed to send notification to user", [
                        'user_id' => $recipient->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info("Backup notification sent", [
                'org_id' => $this->orgId,
                'notification' => class_basename($this->notificationClass),
                'recipient_count' => count($recipients),
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send backup notification", [
                'org_id' => $this->orgId,
                'notification' => $this->notificationClass,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if notification type is enabled
     */
    protected function isNotificationEnabled(?BackupSetting $settings): bool
    {
        if (!$settings) {
            return true; // Default to enabled
        }

        $notificationName = class_basename($this->notificationClass);

        return match ($notificationName) {
            'BackupCompletedNotification' => $settings->email_on_backup_complete,
            'BackupFailedNotification' => $settings->email_on_backup_failed,
            'RestoreCompletedNotification' => $settings->email_on_restore_complete,
            'RestoreFailedNotification' => $settings->email_on_restore_failed,
            default => true,
        };
    }

    /**
     * Get notification recipients
     */
    protected function getRecipients(?BackupSetting $settings): array
    {
        $recipients = [];

        // Add additional recipients from parameters
        foreach ($this->additionalRecipients as $userId) {
            $user = User::find($userId);
            if ($user) {
                $recipients[$user->id] = $user;
            }
        }

        // Add all admins if configured
        if ($settings?->notify_all_admins) {
            $admins = User::whereHas('organizationMembers', function ($query) {
                $query->where('org_id', $this->orgId)
                    ->where('role', 'admin');
            })->get();

            foreach ($admins as $admin) {
                $recipients[$admin->id] = $admin;
            }
        }

        // Add additional notification emails (create temporary notifiable objects)
        if ($settings?->notification_emails) {
            $emails = $settings->notification_emails;
            foreach ($emails as $email) {
                $recipients['email_' . $email] = new \App\Notifications\AnonymousNotifiable($email);
            }
        }

        return array_values($recipients);
    }

    /**
     * Create notification instance from class name and params
     */
    protected function createNotification(): Notification
    {
        if (!class_exists($this->notificationClass)) {
            throw new \RuntimeException("Notification class not found: {$this->notificationClass}");
        }

        // Resolve model references in params
        $resolvedParams = $this->resolveParams();

        return new $this->notificationClass(...$resolvedParams);
    }

    /**
     * Resolve model references in notification params
     */
    protected function resolveParams(): array
    {
        $resolved = [];

        foreach ($this->params as $param) {
            if (is_array($param) && isset($param['_model'])) {
                // Resolve model reference
                $modelClass = $param['_model'];
                $id = $param['_id'];
                $resolved[] = $modelClass::find($id);
            } else {
                $resolved[] = $param;
            }
        }

        return $resolved;
    }

    /**
     * Create a job for a backup notification
     */
    public static function forBackup(OrganizationBackup $backup, string $notificationClass): self
    {
        return new self(
            $backup->org_id,
            $notificationClass,
            [['_model' => OrganizationBackup::class, '_id' => $backup->id]],
            [$backup->created_by]
        );
    }

    /**
     * Create a job for a restore notification
     */
    public static function forRestore(BackupRestore $restore, string $notificationClass): self
    {
        return new self(
            $restore->org_id,
            $notificationClass,
            [['_model' => BackupRestore::class, '_id' => $restore->id]],
            [$restore->created_by]
        );
    }
}
