<?php

namespace App\Models\Backup;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BackupRestore Model
 *
 * Represents a restore operation from a backup.
 * Tracks restore progress, conflicts, and execution reports.
 *
 * @property string $id
 * @property string $org_id
 * @property string|null $backup_id
 * @property string $restore_code
 * @property string $type
 * @property string $status
 * @property string $source_type
 * @property string|null $external_file_path
 * @property array|null $selected_categories
 * @property array|null $conflict_resolution
 * @property array|null $reconciliation_report
 * @property array|null $execution_report
 * @property string|null $safety_backup_id
 * @property int $total_records
 * @property int $processed_records
 * @property int $progress_percent
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $rollback_expires_at
 * @property bool $rollback_available
 * @property string|null $error_message
 * @property string $created_by
 * @property string|null $confirmed_by
 * @property \Carbon\Carbon|null $confirmed_at
 * @property string|null $confirmation_method
 */
class BackupRestore extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.backup_restores';

    // Restore types
    public const TYPE_FULL = 'full';
    public const TYPE_SELECTIVE = 'selective';
    public const TYPE_MERGE = 'merge';

    // Restore statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_ANALYZING = 'analyzing';
    public const STATUS_AWAITING_CONFIRMATION = 'awaiting_confirmation';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ROLLED_BACK = 'rolled_back';

    // Source types
    public const SOURCE_INTERNAL = 'internal';
    public const SOURCE_EXTERNAL = 'external_upload';

    // Conflict resolution strategies
    public const STRATEGY_SKIP = 'skip';
    public const STRATEGY_REPLACE = 'replace';
    public const STRATEGY_MERGE = 'merge';
    public const STRATEGY_ASK = 'ask';

    // Confirmation methods
    public const CONFIRM_SIMPLE = 'simple';
    public const CONFIRM_ORG_NAME = 'org_name';
    public const CONFIRM_EMAIL_CODE = 'email_code';

    protected $fillable = [
        'org_id',
        'backup_id',
        'restore_code',
        'type',
        'status',
        'source_type',
        'external_file_path',
        'selected_categories',
        'conflict_resolution',
        'reconciliation_report',
        'execution_report',
        'safety_backup_id',
        'total_records',
        'processed_records',
        'progress_percent',
        'started_at',
        'completed_at',
        'rollback_expires_at',
        'rollback_available',
        'error_message',
        'created_by',
        'confirmed_by',
        'confirmed_at',
        'confirmation_method',
    ];

    protected $casts = [
        'selected_categories' => 'array',
        'conflict_resolution' => 'array',
        'reconciliation_report' => 'array',
        'execution_report' => 'array',
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'progress_percent' => 'integer',
        'rollback_available' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'rollback_expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $restore) {
            if (empty($restore->restore_code)) {
                $restore->restore_code = self::generateRestoreCode();
            }
            if (empty($restore->status)) {
                $restore->status = self::STATUS_PENDING;
            }
            if (empty($restore->source_type)) {
                $restore->source_type = self::SOURCE_INTERNAL;
            }
        });
    }

    /**
     * Generate a unique restore code
     */
    public static function generateRestoreCode(): string
    {
        $year = date('Y');
        $lastRestore = self::withoutGlobalScopes()
            ->where('restore_code', 'like', "REST-{$year}-%")
            ->orderBy('restore_code', 'desc')
            ->first();

        if ($lastRestore) {
            $lastNumber = (int) substr($lastRestore->restore_code, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "REST-{$year}-{$newNumber}";
    }

    // ==================== Relationships ====================

    /**
     * Get the backup being restored
     */
    public function backup(): BelongsTo
    {
        return $this->belongsTo(OrganizationBackup::class, 'backup_id');
    }

    /**
     * Get the safety backup created before restore
     */
    public function safetyBackup(): BelongsTo
    {
        return $this->belongsTo(OrganizationBackup::class, 'safety_backup_id');
    }

    /**
     * Get the user who initiated the restore
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Get the user who confirmed the restore
     */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by', 'user_id');
    }

    // ==================== Scopes ====================

    /**
     * Scope: Pending restores
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Processing restores
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope: Completed restores
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Restores with available rollback
     */
    public function scopeWithRollbackAvailable($query)
    {
        return $query->where('rollback_available', true)
            ->where('rollback_expires_at', '>', now());
    }

    /**
     * Scope: By type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ==================== Status Methods ====================

    /**
     * Check if restore is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if restore is analyzing
     */
    public function isAnalyzing(): bool
    {
        return $this->status === self::STATUS_ANALYZING;
    }

    /**
     * Check if restore is awaiting confirmation
     */
    public function isAwaitingConfirmation(): bool
    {
        return $this->status === self::STATUS_AWAITING_CONFIRMATION;
    }

    /**
     * Check if restore is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if restore is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if restore failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if restore was rolled back
     */
    public function isRolledBack(): bool
    {
        return $this->status === self::STATUS_ROLLED_BACK;
    }

    /**
     * Check if rollback is still available
     */
    public function canRollback(): bool
    {
        return $this->rollback_available
            && $this->rollback_expires_at
            && $this->rollback_expires_at->isFuture()
            && $this->isCompleted();
    }

    // ==================== Status Update Methods ====================

    /**
     * Mark as analyzing
     */
    public function markAsAnalyzing(): void
    {
        $this->update(['status' => self::STATUS_ANALYZING]);
    }

    /**
     * Set reconciliation report and mark as awaiting confirmation
     */
    public function setReconciliationReport(array $report): void
    {
        $this->update([
            'status' => self::STATUS_AWAITING_CONFIRMATION,
            'reconciliation_report' => $report,
        ]);
    }

    /**
     * Mark as confirmed
     */
    public function markAsConfirmed(string $userId, string $method): void
    {
        $this->update([
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
            'confirmation_method' => $method,
        ]);
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(int $totalRecords): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
            'total_records' => $totalRecords,
            'processed_records' => 0,
            'progress_percent' => 0,
        ]);
    }

    /**
     * Update progress
     */
    public function updateProgress(int $processedRecords): void
    {
        $percent = $this->total_records > 0
            ? (int) (($processedRecords / $this->total_records) * 100)
            : 0;

        $this->update([
            'processed_records' => $processedRecords,
            'progress_percent' => min($percent, 100),
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(array $executionReport, string $safetyBackupId): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'execution_report' => $executionReport,
            'safety_backup_id' => $safetyBackupId,
            'completed_at' => now(),
            'rollback_available' => true,
            'rollback_expires_at' => now()->addHours(24),
            'progress_percent' => 100,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage, ?array $executionReport = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'execution_report' => $executionReport,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as rolled back
     */
    public function markAsRolledBack(): void
    {
        $this->update([
            'status' => self::STATUS_ROLLED_BACK,
            'rollback_available' => false,
        ]);
    }

    // ==================== Conflict Resolution ====================

    /**
     * Set conflict resolution strategy
     */
    public function setConflictStrategy(string $strategy, array $decisions = []): void
    {
        $this->update([
            'conflict_resolution' => [
                'strategy' => $strategy,
                'decisions' => $decisions,
            ],
        ]);
    }

    /**
     * Get conflict resolution strategy
     */
    public function getConflictStrategy(): string
    {
        return $this->conflict_resolution['strategy'] ?? self::STRATEGY_SKIP;
    }

    /**
     * Get conflict decision for a specific record
     */
    public function getConflictDecision(string $recordId): string
    {
        $decisions = $this->conflict_resolution['decisions'] ?? [];
        return $decisions[$recordId] ?? $this->getConflictStrategy();
    }

    /**
     * Add individual conflict decision
     */
    public function addConflictDecision(string $recordId, string $decision): void
    {
        $resolution = $this->conflict_resolution ?? ['strategy' => self::STRATEGY_ASK, 'decisions' => []];
        $resolution['decisions'][$recordId] = $decision;
        $this->update(['conflict_resolution' => $resolution]);
    }

    // ==================== Helper Methods ====================

    /**
     * Get required confirmation method based on restore type
     */
    public function getRequiredConfirmationMethod(): string
    {
        return match ($this->type) {
            self::TYPE_FULL => self::CONFIRM_EMAIL_CODE,
            self::TYPE_MERGE => self::CONFIRM_ORG_NAME,
            default => self::CONFIRM_SIMPLE,
        };
    }

    /**
     * Get duration of restore process
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    /**
     * Get time until rollback expires
     */
    public function getRollbackTimeRemainingAttribute(): ?int
    {
        if (!$this->rollback_expires_at || !$this->canRollback()) {
            return null;
        }

        return max(0, now()->diffInMinutes($this->rollback_expires_at, false));
    }

    /**
     * Get summary from execution report
     */
    public function getExecutionSummary(): array
    {
        return [
            'records_restored' => $this->execution_report['records_restored'] ?? 0,
            'records_skipped' => $this->execution_report['records_skipped'] ?? 0,
            'records_merged' => $this->execution_report['records_merged'] ?? 0,
            'errors_count' => count($this->execution_report['errors'] ?? []),
        ];
    }
}
