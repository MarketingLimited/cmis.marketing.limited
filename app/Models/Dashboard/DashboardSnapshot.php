<?php

namespace App\Models\Dashboard;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardSnapshot extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_dashboard.dashboard_snapshots';
    protected $primaryKey = 'snapshot_id';

    protected $fillable = [
        'snapshot_id',
        'template_id',
        'org_id',
        'name',
        'description',
        'snapshot_data',
        'widget_states',
        'filters_applied',
        'date_range_start',
        'date_range_end',
        'snapshot_type',
        'file_path',
        'file_format',
        'file_size',
        'is_scheduled',
        'schedule_id',
        'expires_at',
        'is_shared',
        'shared_with',
        'access_count',
        'last_accessed_at',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
        'widget_states' => 'array',
        'filters_applied' => 'array',
        'date_range_start' => 'date',
        'date_range_end' => 'date',
        'file_size' => 'integer',
        'is_scheduled' => 'boolean',
        'expires_at' => 'datetime',
        'is_shared' => 'boolean',
        'shared_with' => 'array',
        'access_count' => 'integer',
        'last_accessed_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Snapshot type constants
    public const TYPE_MANUAL = 'manual';
    public const TYPE_SCHEDULED = 'scheduled';
    public const TYPE_AUTOMATED = 'automated';
    public const TYPE_EXPORT = 'export';

    // File format constants
    public const FORMAT_JSON = 'json';
    public const FORMAT_PDF = 'pdf';
    public const FORMAT_PNG = 'png';
    public const FORMAT_CSV = 'csv';
    public const FORMAT_EXCEL = 'excel';

    // Relationships
    public function template(): BelongsTo
    {
        return $this->belongsTo(DashboardTemplate::class, 'template_id', 'template_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ReportSchedule::class, 'schedule_id', 'schedule_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // Scopes
    public function scopeManual($query)
    {
        return $query->where('snapshot_type', self::TYPE_MANUAL);
    }

    public function scopeScheduled($query)
    {
        return $query->where('snapshot_type', self::TYPE_SCHEDULED);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
    }

    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByFormat($query, string $format)
    {
        return $query->where('file_format', $format);
    }

    // Helper Methods
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isShared(): bool
    {
        return $this->is_shared === true;
    }

    public function isScheduled(): bool
    {
        return $this->is_scheduled === true;
    }

    public function hasFile(): bool
    {
        return !empty($this->file_path) && file_exists(storage_path($this->file_path));
    }

    public function getFileSizeMB(): float
    {
        return $this->file_size ? round($this->file_size / 1024 / 1024, 2) : 0;
    }

    public function incrementAccessCount(): bool
    {
        return $this->increment('access_count', 1, [
            'last_accessed_at' => now()
        ]);
    }

    public function share(array $userIds): bool
    {
        return $this->update([
            'is_shared' => true,
            'shared_with' => array_unique(array_merge($this->shared_with ?? [], $userIds))
        ]);
    }

    public function unshare(?array $userIds = null): bool
    {
        if ($userIds === null) {
            return $this->update([
                'is_shared' => false,
                'shared_with' => []
            ]);
        }

        $currentShares = $this->shared_with ?? [];
        $newShares = array_diff($currentShares, $userIds);

        return $this->update([
            'shared_with' => array_values($newShares),
            'is_shared' => !empty($newShares)
        ]);
    }

    public function setExpiration(\DateTime $expiresAt): bool
    {
        return $this->update(['expires_at' => $expiresAt]);
    }

    public function removeExpiration(): bool
    {
        return $this->update(['expires_at' => null]);
    }

    public function getDateRangeDays(): int
    {
        if (!$this->date_range_start || !$this->date_range_end) {
            return 0;
        }

        return $this->date_range_start->diffInDays($this->date_range_end);
    }

    public function getWidgetCount(): int
    {
        return is_array($this->widget_states) ? count($this->widget_states) : 0;
    }

    public function hasFilters(): bool
    {
        return !empty($this->filters_applied);
    }

    public function canAccess(string $userId): bool
    {
        if ($this->created_by === $userId) {
            return true;
        }

        if (!$this->isShared()) {
            return false;
        }

        return in_array($userId, $this->shared_with ?? []);
    }

    public function deleteFile(): bool
    {
        if (!$this->hasFile()) {
            return true;
        }

        $filePath = storage_path($this->file_path);

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        return $this->update(['file_path' => null, 'file_size' => null]);
    }

    public function getTypeColor(): string
    {
        return match($this->snapshot_type) {
            self::TYPE_MANUAL => 'blue',
            self::TYPE_SCHEDULED => 'green',
            self::TYPE_AUTOMATED => 'purple',
            self::TYPE_EXPORT => 'orange',
            default => 'gray',
        };
    }

    public function getFormatIcon(): string
    {
        return match($this->file_format) {
            self::FORMAT_JSON => 'file-code',
            self::FORMAT_PDF => 'file-pdf',
            self::FORMAT_PNG => 'file-image',
            self::FORMAT_CSV => 'file-csv',
            self::FORMAT_EXCEL => 'file-excel',
            default => 'file',
        };
    }

    // Static Methods
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_MANUAL => 'Manual',
            self::TYPE_SCHEDULED => 'Scheduled',
            self::TYPE_AUTOMATED => 'Automated',
            self::TYPE_EXPORT => 'Export',
        ];
    }

    public static function getFormatOptions(): array
    {
        return [
            self::FORMAT_JSON => 'JSON',
            self::FORMAT_PDF => 'PDF',
            self::FORMAT_PNG => 'PNG',
            self::FORMAT_CSV => 'CSV',
            self::FORMAT_EXCEL => 'Excel',
        ];
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'template_id' => 'required|uuid|exists:cmis_dashboard.dashboard_templates,template_id',
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'snapshot_data' => 'required|array',
            'widget_states' => 'nullable|array',
            'filters_applied' => 'nullable|array',
            'date_range_start' => 'nullable|date',
            'date_range_end' => 'nullable|date|after_or_equal:date_range_start',
            'snapshot_type' => 'required|in:' . implode(',', array_keys(self::getTypeOptions())),
            'file_format' => 'nullable|in:' . implode(',', array_keys(self::getFormatOptions())),
            'expires_at' => 'nullable|date|after:now',
            'is_shared' => 'nullable|boolean',
            'shared_with' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'is_shared' => 'sometimes|boolean',
            'shared_with' => 'sometimes|array',
            'expires_at' => 'sometimes|date|after:now',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'template_id.required' => 'Dashboard template is required',
            'org_id.required' => 'Organization is required',
            'name.required' => 'Snapshot name is required',
            'snapshot_data.required' => 'Snapshot data is required',
            'snapshot_type.required' => 'Snapshot type is required',
            'date_range_end.after_or_equal' => 'End date must be on or after start date',
            'expires_at.after' => 'Expiration date must be in the future',
        ];
    }
}
