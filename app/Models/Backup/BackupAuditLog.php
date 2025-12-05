<?php

namespace App\Models\Backup;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BackupAuditLog Model
 *
 * Audit trail for all backup and restore operations.
 * Records who did what and when for compliance and security.
 *
 * @property string $id
 * @property string $org_id
 * @property string $action
 * @property string|null $entity_id
 * @property string|null $entity_type
 * @property string $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array|null $details
 * @property array|null $changes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BackupAuditLog extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.backup_audit_logs';

    // Backup actions
    public const ACTION_BACKUP_CREATED = 'backup_created';
    public const ACTION_BACKUP_STARTED = 'backup_started';
    public const ACTION_BACKUP_COMPLETED = 'backup_completed';
    public const ACTION_BACKUP_FAILED = 'backup_failed';
    public const ACTION_BACKUP_DOWNLOADED = 'backup_downloaded';
    public const ACTION_BACKUP_DELETED = 'backup_deleted';
    public const ACTION_BACKUP_EXPIRED = 'backup_expired';

    // Restore actions
    public const ACTION_RESTORE_STARTED = 'restore_started';
    public const ACTION_RESTORE_CONFIRMED = 'restore_confirmed';
    public const ACTION_RESTORE_COMPLETED = 'restore_completed';
    public const ACTION_RESTORE_FAILED = 'restore_failed';
    public const ACTION_RESTORE_ROLLED_BACK = 'restore_rolled_back';

    // Schedule actions
    public const ACTION_SCHEDULE_CREATED = 'schedule_created';
    public const ACTION_SCHEDULE_UPDATED = 'schedule_updated';
    public const ACTION_SCHEDULE_DELETED = 'schedule_deleted';
    public const ACTION_SCHEDULE_TRIGGERED = 'schedule_triggered';

    // Settings actions
    public const ACTION_SETTINGS_UPDATED = 'settings_updated';

    // External upload
    public const ACTION_EXTERNAL_UPLOAD = 'external_upload';

    // Encryption key actions
    public const ACTION_ENCRYPTION_KEY_CREATED = 'encryption_key_created';
    public const ACTION_ENCRYPTION_KEY_DELETED = 'encryption_key_deleted';

    // Entity types
    public const ENTITY_BACKUP = 'backup';
    public const ENTITY_RESTORE = 'restore';
    public const ENTITY_SCHEDULE = 'schedule';
    public const ENTITY_SETTINGS = 'settings';
    public const ENTITY_ENCRYPTION_KEY = 'encryption_key';

    protected $fillable = [
        'org_id',
        'action',
        'entity_id',
        'entity_type',
        'user_id',
        'ip_address',
        'user_agent',
        'details',
        'changes',
    ];

    protected $casts = [
        'details' => 'array',
        'changes' => 'array',
    ];

    // Disable soft deletes for audit logs - they should never be deleted
    public $forceDeleting = true;

    /**
     * Disable soft deletes for this model
     */
    public function getDeletedAtColumn()
    {
        return null;
    }

    // ==================== Relationships ====================

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // ==================== Scopes ====================

    /**
     * Scope: By action
     */
    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: By entity type
     */
    public function scopeForEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope: By entity
     */
    public function scopeForEntity($query, string $entityType, string $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    /**
     * Scope: By user
     */
    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Backup-related actions
     */
    public function scopeBackupActions($query)
    {
        return $query->whereIn('action', [
            self::ACTION_BACKUP_CREATED,
            self::ACTION_BACKUP_STARTED,
            self::ACTION_BACKUP_COMPLETED,
            self::ACTION_BACKUP_FAILED,
            self::ACTION_BACKUP_DOWNLOADED,
            self::ACTION_BACKUP_DELETED,
            self::ACTION_BACKUP_EXPIRED,
        ]);
    }

    /**
     * Scope: Restore-related actions
     */
    public function scopeRestoreActions($query)
    {
        return $query->whereIn('action', [
            self::ACTION_RESTORE_STARTED,
            self::ACTION_RESTORE_CONFIRMED,
            self::ACTION_RESTORE_COMPLETED,
            self::ACTION_RESTORE_FAILED,
            self::ACTION_RESTORE_ROLLED_BACK,
        ]);
    }

    /**
     * Scope: Recent logs
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==================== Factory Methods ====================

    /**
     * Log a backup action
     */
    public static function logBackupAction(
        string $action,
        OrganizationBackup $backup,
        ?array $details = null
    ): self {
        return self::create([
            'org_id' => $backup->org_id,
            'action' => $action,
            'entity_id' => $backup->id,
            'entity_type' => self::ENTITY_BACKUP,
            'user_id' => auth()->id() ?? $backup->created_by,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => array_merge($details ?? [], [
                'backup_code' => $backup->backup_code,
                'backup_name' => $backup->name,
            ]),
        ]);
    }

    /**
     * Log a restore action
     */
    public static function logRestoreAction(
        string $action,
        BackupRestore $restore,
        ?array $details = null
    ): self {
        return self::create([
            'org_id' => $restore->org_id,
            'action' => $action,
            'entity_id' => $restore->id,
            'entity_type' => self::ENTITY_RESTORE,
            'user_id' => auth()->id() ?? $restore->created_by,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => array_merge($details ?? [], [
                'restore_code' => $restore->restore_code,
                'restore_type' => $restore->type,
            ]),
        ]);
    }

    /**
     * Log a schedule action
     */
    public static function logScheduleAction(
        string $action,
        BackupSchedule $schedule,
        ?array $changes = null
    ): self {
        return self::create([
            'org_id' => $schedule->org_id,
            'action' => $action,
            'entity_id' => $schedule->id,
            'entity_type' => self::ENTITY_SCHEDULE,
            'user_id' => auth()->id() ?? $schedule->created_by,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => [
                'schedule_name' => $schedule->name,
                'frequency' => $schedule->frequency,
            ],
            'changes' => $changes,
        ]);
    }

    /**
     * Log a settings update
     */
    public static function logSettingsUpdate(
        BackupSetting $settings,
        array $changes
    ): self {
        return self::create([
            'org_id' => $settings->org_id,
            'action' => self::ACTION_SETTINGS_UPDATED,
            'entity_id' => $settings->id,
            'entity_type' => self::ENTITY_SETTINGS,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'changes' => $changes,
        ]);
    }

    // ==================== Helper Methods ====================

    /**
     * Get action label for display
     */
    public function getActionLabelAttribute(): string
    {
        return __("backup.audit.{$this->action}");
    }

    /**
     * Get formatted details for display
     */
    public function getFormattedDetailsAttribute(): string
    {
        if (empty($this->details)) {
            return '';
        }

        return collect($this->details)
            ->map(fn ($value, $key) => "{$key}: {$value}")
            ->implode(', ');
    }

    /**
     * Check if this is a critical action
     */
    public function isCriticalAction(): bool
    {
        return in_array($this->action, [
            self::ACTION_RESTORE_STARTED,
            self::ACTION_RESTORE_COMPLETED,
            self::ACTION_RESTORE_ROLLED_BACK,
            self::ACTION_BACKUP_DELETED,
            self::ACTION_ENCRYPTION_KEY_DELETED,
        ]);
    }
}
