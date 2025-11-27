<?php

namespace App\Models\Intelligence;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Anomaly extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_intelligence.anomalies';
    protected $primaryKey = 'anomaly_id';

    protected $fillable = [
        'anomaly_id',
        'org_id',
        'entity_type',
        'entity_id',
        'metric_name',
        'expected_value',
        'actual_value',
        'deviation_score',
        'deviation_percentage',
        'severity',
        'anomaly_type',
        'detected_at',
        'status',
        'resolution',
        'resolved_at',
        'resolved_by',
        'false_positive',
        'impact_assessment',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'expected_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'deviation_score' => 'decimal:4',
        'deviation_percentage' => 'decimal:2',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'false_positive' => 'boolean',
        'impact_assessment' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Severity constants
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    // Status constants
    public const STATUS_DETECTED = 'detected';
    public const STATUS_INVESTIGATING = 'investigating';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_IGNORED = 'ignored';

    // Anomaly type constants
    public const TYPE_SPIKE = 'spike';
    public const TYPE_DROP = 'drop';
    public const TYPE_TREND_CHANGE = 'trend_change';
    public const TYPE_PATTERN_BREAK = 'pattern_break';
    public const TYPE_OUTLIER = 'outlier';

    // Relationships
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by', 'user_id');
    }

    // Scopes
    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', [self::STATUS_DETECTED, self::STATUS_INVESTIGATING]);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    public function scopeHigh($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('detected_at', '>=', now()->subHours($hours));
    }

    public function scopeNotFalsePositive($query)
    {
        return $query->where('false_positive', false);
    }

    // Helper Methods
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isCritical(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    public function isHigh(): bool
    {
        return in_array($this->severity, [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    public function isFalsePositive(): bool
    {
        return $this->false_positive === true;
    }

    public function markAsResolved(string $resolution, ?string $userId = null): bool
    {
        return $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolution' => $resolution,
            'resolved_at' => now(),
            'resolved_by' => $userId ?? auth()->id(),
        ]);
    }

    public function markAsFalsePositive(?string $userId = null): bool
    {
        return $this->update([
            'false_positive' => true,
            'status' => self::STATUS_IGNORED,
            'resolved_at' => now(),
            'resolved_by' => $userId ?? auth()->id(),
        ]);
    }

    public function markAsInvestigating(): bool
    {
        return $this->update(['status' => self::STATUS_INVESTIGATING]);
    }

    public function getDeviationDescription(): string
    {
        $direction = $this->actual_value > $this->expected_value ? 'increase' : 'decrease';
        $percentage = abs($this->deviation_percentage);

        return sprintf(
            '%s of %.2f%% (%s → %s)',
            ucfirst($direction),
            $percentage,
            number_format($this->expected_value, 2),
            number_format($this->actual_value, 2)
        );
    }

    public function getSeverityColor(): string
    {
        return match($this->severity) {
            self::SEVERITY_LOW => 'blue',
            self::SEVERITY_MEDIUM => 'yellow',
            self::SEVERITY_HIGH => 'orange',
            self::SEVERITY_CRITICAL => 'red',
            default => 'gray',
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_DETECTED => 'red',
            self::STATUS_INVESTIGATING => 'yellow',
            self::STATUS_RESOLVED => 'green',
            self::STATUS_IGNORED => 'gray',
            default => 'gray',
        };
    }

    // Static Methods
    public static function getSeverityOptions(): array
    {
        return [
            self::SEVERITY_LOW => 'Low',
            self::SEVERITY_MEDIUM => 'Medium',
            self::SEVERITY_HIGH => 'High',
            self::SEVERITY_CRITICAL => 'Critical',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DETECTED => 'Detected',
            self::STATUS_INVESTIGATING => 'Investigating',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_IGNORED => 'Ignored',
        ];
    }

    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_SPIKE => 'Spike',
            self::TYPE_DROP => 'Drop',
            self::TYPE_TREND_CHANGE => 'Trend Change',
            self::TYPE_PATTERN_BREAK => 'Pattern Break',
            self::TYPE_OUTLIER => 'Outlier',
        ];
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'entity_type' => 'required|string|max:255',
            'entity_id' => 'required|uuid',
            'metric_name' => 'required|string|max:255',
            'expected_value' => 'required|numeric',
            'actual_value' => 'required|numeric',
            'deviation_score' => 'required|numeric|min:0',
            'deviation_percentage' => 'nullable|numeric',
            'severity' => 'required|in:' . implode(',', array_keys(self::getSeverityOptions())),
            'anomaly_type' => 'required|in:' . implode(',', array_keys(self::getTypeOptions())),
            'detected_at' => 'required|date',
            'impact_assessment' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'status' => 'sometimes|in:' . implode(',', array_keys(self::getStatusOptions())),
            'resolution' => 'sometimes|string|max:1000',
            'false_positive' => 'sometimes|boolean',
            'impact_assessment' => 'sometimes|array',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'org_id.required' => 'Organization is required',
            'entity_type.required' => 'Entity type is required',
            'entity_id.required' => 'Entity ID is required',
            'metric_name.required' => 'Metric name is required',
            'expected_value.required' => 'Expected value is required',
            'actual_value.required' => 'Actual value is required',
            'severity.required' => 'Severity is required',
            'severity.in' => 'Invalid severity level',
        ];
    }
}
