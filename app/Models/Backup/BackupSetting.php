<?php

namespace App\Models\Backup;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * BackupSetting Model
 *
 * Per-organization backup settings including notifications,
 * storage preferences, and encryption defaults.
 *
 * @property string $id
 * @property string $org_id
 * @property bool $email_on_backup_complete
 * @property bool $email_on_backup_failed
 * @property bool $email_on_restore_started
 * @property bool $email_on_restore_complete
 * @property bool $email_on_restore_failed
 * @property bool $email_on_backup_expiring
 * @property bool $email_on_storage_warning
 * @property bool $notify_all_admins
 * @property array|null $notification_emails
 * @property bool $inapp_notifications
 * @property string $default_storage_disk
 * @property array|null $storage_credentials
 * @property bool $encrypt_by_default
 * @property string|null $default_encryption_key_id
 * @property int $default_retention_days
 * @property bool $auto_delete_expired
 * @property int $storage_used_bytes
 * @property int|null $storage_quota_bytes
 * @property int $storage_warning_percent
 */
class BackupSetting extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.backup_settings';

    // Storage disk options
    public const DISK_LOCAL = 'local';
    public const DISK_GOOGLE = 'google';
    public const DISK_ONEDRIVE = 'onedrive';
    public const DISK_DROPBOX = 'dropbox';

    protected $fillable = [
        'org_id',
        'email_on_backup_complete',
        'email_on_backup_failed',
        'email_on_restore_started',
        'email_on_restore_complete',
        'email_on_restore_failed',
        'email_on_backup_expiring',
        'email_on_storage_warning',
        'notify_all_admins',
        'notification_emails',
        'inapp_notifications',
        'default_storage_disk',
        'storage_credentials',
        'encrypt_by_default',
        'default_encryption_key_id',
        'default_retention_days',
        'auto_delete_expired',
        'storage_used_bytes',
        'storage_quota_bytes',
        'storage_warning_percent',
    ];

    protected $casts = [
        'email_on_backup_complete' => 'boolean',
        'email_on_backup_failed' => 'boolean',
        'email_on_restore_started' => 'boolean',
        'email_on_restore_complete' => 'boolean',
        'email_on_restore_failed' => 'boolean',
        'email_on_backup_expiring' => 'boolean',
        'email_on_storage_warning' => 'boolean',
        'notify_all_admins' => 'boolean',
        'notification_emails' => 'array',
        'inapp_notifications' => 'boolean',
        'encrypt_by_default' => 'boolean',
        'auto_delete_expired' => 'boolean',
        'default_retention_days' => 'integer',
        'storage_used_bytes' => 'integer',
        'storage_quota_bytes' => 'integer',
        'storage_warning_percent' => 'integer',
    ];

    protected $hidden = [
        'storage_credentials', // Sensitive data
    ];

    // ==================== Encrypted Attributes ====================

    /**
     * Set storage credentials (encrypted)
     */
    public function setStorageCredentialsAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['storage_credentials'] = null;
            return;
        }

        $this->attributes['storage_credentials'] = Crypt::encryptString(
            is_array($value) ? json_encode($value) : $value
        );
    }

    /**
     * Get storage credentials (decrypted)
     */
    public function getStorageCredentialsAttribute($value): ?array
    {
        if ($value === null) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($value);
            return json_decode($decrypted, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    // ==================== Relationships ====================

    /**
     * Get the default encryption key
     */
    public function defaultEncryptionKey(): BelongsTo
    {
        return $this->belongsTo(BackupEncryptionKey::class, 'default_encryption_key_id');
    }

    // ==================== Factory Methods ====================

    /**
     * Get or create settings for an organization
     */
    public static function getOrCreate(string $orgId): self
    {
        return self::firstOrCreate(
            ['org_id' => $orgId],
            self::getDefaults()
        );
    }

    /**
     * Get default settings values
     */
    public static function getDefaults(): array
    {
        return [
            'email_on_backup_complete' => true,
            'email_on_backup_failed' => true,
            'email_on_restore_started' => true,
            'email_on_restore_complete' => true,
            'email_on_restore_failed' => true,
            'email_on_backup_expiring' => true,
            'email_on_storage_warning' => true,
            'notify_all_admins' => false,
            'notification_emails' => [],
            'inapp_notifications' => true,
            'default_storage_disk' => self::DISK_LOCAL,
            'encrypt_by_default' => false,
            'default_retention_days' => 30,
            'auto_delete_expired' => true,
            'storage_used_bytes' => 0,
            'storage_warning_percent' => 80,
        ];
    }

    // ==================== Storage Management ====================

    /**
     * Update storage used
     */
    public function updateStorageUsed(int $bytes): void
    {
        $this->increment('storage_used_bytes', $bytes);
    }

    /**
     * Reduce storage used
     */
    public function reduceStorageUsed(int $bytes): void
    {
        $this->decrement('storage_used_bytes', min($bytes, $this->storage_used_bytes));
    }

    /**
     * Recalculate storage used from backups
     */
    public function recalculateStorageUsed(): void
    {
        $totalBytes = OrganizationBackup::where('org_id', $this->org_id)
            ->where('status', OrganizationBackup::STATUS_COMPLETED)
            ->sum('file_size');

        $this->update(['storage_used_bytes' => $totalBytes]);
    }

    /**
     * Get storage usage percentage
     */
    public function getStorageUsagePercentAttribute(): ?int
    {
        if (!$this->storage_quota_bytes) {
            return null;
        }

        return (int) (($this->storage_used_bytes / $this->storage_quota_bytes) * 100);
    }

    /**
     * Check if storage warning threshold is exceeded
     */
    public function isStorageWarningExceeded(): bool
    {
        $percentage = $this->storage_usage_percent;
        return $percentage !== null && $percentage >= $this->storage_warning_percent;
    }

    /**
     * Check if storage quota is exceeded
     */
    public function isStorageQuotaExceeded(): bool
    {
        if (!$this->storage_quota_bytes) {
            return false;
        }

        return $this->storage_used_bytes >= $this->storage_quota_bytes;
    }

    /**
     * Get remaining storage space
     */
    public function getRemainingStorageBytes(): ?int
    {
        if (!$this->storage_quota_bytes) {
            return null;
        }

        return max(0, $this->storage_quota_bytes - $this->storage_used_bytes);
    }

    // ==================== Notification Helpers ====================

    /**
     * Should send email notification for an event
     */
    public function shouldEmailFor(string $event): bool
    {
        $property = "email_on_{$event}";
        return $this->{$property} ?? false;
    }

    /**
     * Get all notification recipients
     */
    public function getNotificationRecipients(): array
    {
        $recipients = $this->notification_emails ?? [];

        if ($this->notify_all_admins) {
            // TODO: Get all admin emails for the organization
            // This would require querying users with admin role
        }

        return array_unique($recipients);
    }

    // ==================== Storage Disk Helpers ====================

    /**
     * Get credentials for a specific disk
     */
    public function getDiskCredentials(string $disk): ?array
    {
        $credentials = $this->storage_credentials;
        return $credentials[$disk] ?? null;
    }

    /**
     * Set credentials for a specific disk
     */
    public function setDiskCredentials(string $disk, array $credentials): void
    {
        $allCredentials = $this->storage_credentials ?? [];
        $allCredentials[$disk] = $credentials;
        $this->update(['storage_credentials' => $allCredentials]);
    }

    /**
     * Remove credentials for a specific disk
     */
    public function removeDiskCredentials(string $disk): void
    {
        $allCredentials = $this->storage_credentials ?? [];
        unset($allCredentials[$disk]);
        $this->update(['storage_credentials' => $allCredentials]);
    }

    /**
     * Check if a disk is configured
     */
    public function isDiskConfigured(string $disk): bool
    {
        if ($disk === self::DISK_LOCAL) {
            return true;
        }

        $credentials = $this->getDiskCredentials($disk);
        return !empty($credentials);
    }

    /**
     * Get list of configured disks
     */
    public function getConfiguredDisks(): array
    {
        $disks = [self::DISK_LOCAL];

        foreach ([self::DISK_GOOGLE, self::DISK_ONEDRIVE, self::DISK_DROPBOX] as $disk) {
            if ($this->isDiskConfigured($disk)) {
                $disks[] = $disk;
            }
        }

        return $disks;
    }

    // ==================== Human Readable ====================

    /**
     * Get human-readable storage used
     */
    public function getStorageUsedHumanAttribute(): string
    {
        return $this->formatBytes($this->storage_used_bytes);
    }

    /**
     * Get human-readable storage quota
     */
    public function getStorageQuotaHumanAttribute(): ?string
    {
        if (!$this->storage_quota_bytes) {
            return null;
        }

        return $this->formatBytes($this->storage_quota_bytes);
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
