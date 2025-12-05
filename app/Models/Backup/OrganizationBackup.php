<?php

namespace App\Models\Backup;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * OrganizationBackup Model
 *
 * Represents a backup record for an organization.
 * Stores metadata, status, file information, and schema snapshot.
 *
 * @property string $id
 * @property string $org_id
 * @property string $backup_code
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property string $status
 * @property string|null $file_path
 * @property string $storage_disk
 * @property int $file_size
 * @property string|null $checksum_sha256
 * @property bool $is_encrypted
 * @property string|null $encryption_key_id
 * @property array|null $summary
 * @property array|null $schema_snapshot
 * @property array|null $metadata
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $expires_at
 * @property string|null $error_message
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class OrganizationBackup extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.organization_backups';

    // Backup types
    public const TYPE_MANUAL = 'manual';
    public const TYPE_SCHEDULED = 'scheduled';
    public const TYPE_PRE_RESTORE = 'pre_restore';

    // Backup statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'org_id',
        'backup_code',
        'name',
        'description',
        'type',
        'status',
        'file_path',
        'storage_disk',
        'file_size',
        'checksum_sha256',
        'is_encrypted',
        'encryption_key_id',
        'summary',
        'schema_snapshot',
        'metadata',
        'started_at',
        'completed_at',
        'expires_at',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'file_size' => 'integer',
        'summary' => 'array',
        'schema_snapshot' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'schema_snapshot', // May contain sensitive structure info
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $backup) {
            if (empty($backup->backup_code)) {
                $backup->backup_code = self::generateBackupCode();
            }
            if (empty($backup->status)) {
                $backup->status = self::STATUS_PENDING;
            }
            if (empty($backup->type)) {
                $backup->type = self::TYPE_MANUAL;
            }
        });
    }

    /**
     * Generate a unique backup code
     */
    public static function generateBackupCode(): string
    {
        $year = date('Y');
        $lastBackup = self::withoutGlobalScopes()
            ->where('backup_code', 'like', "BKUP-{$year}-%")
            ->orderBy('backup_code', 'desc')
            ->first();

        if ($lastBackup) {
            $lastNumber = (int) substr($lastBackup->backup_code, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "BKUP-{$year}-{$newNumber}";
    }

    // ==================== Relationships ====================

    /**
     * Get the user who created this backup
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Get the encryption key used for this backup
     */
    public function encryptionKey(): BelongsTo
    {
        return $this->belongsTo(BackupEncryptionKey::class, 'encryption_key_id');
    }

    /**
     * Get restore operations for this backup
     */
    public function restores(): HasMany
    {
        return $this->hasMany(BackupRestore::class, 'backup_id');
    }

    /**
     * Get restores that used this as safety backup
     */
    public function safetyRestores(): HasMany
    {
        return $this->hasMany(BackupRestore::class, 'safety_backup_id');
    }

    // ==================== Scopes ====================

    /**
     * Scope: Only pending backups
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Only processing backups
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope: Only completed backups
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Only failed backups
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: Expired backups
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->where('status', '!=', self::STATUS_EXPIRED);
    }

    /**
     * Scope: By type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Available for restore (completed and not expired)
     */
    public function scopeAvailableForRestore($query)
    {
        return $query->where('status', self::STATUS_COMPLETED)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope: Created this month
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    // ==================== Status Methods ====================

    /**
     * Check if backup is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if backup is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if backup is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if backup failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if backup is expired
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if backup can be restored
     */
    public function canBeRestored(): bool
    {
        return $this->isCompleted() && !$this->isExpired();
    }

    /**
     * Mark backup as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark backup as completed
     */
    public function markAsCompleted(string $filePath, int $fileSize, string $checksum, array $summary): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'checksum_sha256' => $checksum,
            'summary' => $summary,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark backup as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark backup as expired
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    // ==================== Helper Methods ====================

    /**
     * Get human-readable file size
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get total records count from summary
     */
    public function getTotalRecordsAttribute(): int
    {
        return $this->summary['total_records'] ?? 0;
    }

    /**
     * Get category counts from summary
     */
    public function getCategoryCountsAttribute(): array
    {
        $categories = $this->summary['categories'] ?? [];
        $counts = [];

        foreach ($categories as $name => $data) {
            $counts[$name] = $data['count'] ?? 0;
        }

        return $counts;
    }

    /**
     * Get duration of backup process
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }
}
