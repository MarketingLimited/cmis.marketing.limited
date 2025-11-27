<?php

namespace App\Models\Dashboard;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportSchedule extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_dashboard.report_schedules';
    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'schedule_id',
        'template_id',
        'org_id',
        'name',
        'description',
        'frequency',
        'day_of_week',
        'day_of_month',
        'time_of_day',
        'timezone',
        'recipients',
        'recipient_groups',
        'delivery_method',
        'file_format',
        'include_filters',
        'custom_filters',
        'date_range_type',
        'custom_date_range',
        'is_active',
        'last_run_at',
        'next_run_at',
        'run_count',
        'failure_count',
        'last_error',
        'notify_on_completion',
        'notify_on_failure',
        'auto_pause_on_failure',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'recipients' => 'array',
        'recipient_groups' => 'array',
        'include_filters' => 'boolean',
        'custom_filters' => 'array',
        'custom_date_range' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'run_count' => 'integer',
        'failure_count' => 'integer',
        'notify_on_completion' => 'boolean',
        'notify_on_failure' => 'boolean',
        'auto_pause_on_failure' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Frequency constants
    public const FREQ_HOURLY = 'hourly';
    public const FREQ_DAILY = 'daily';
    public const FREQ_WEEKLY = 'weekly';
    public const FREQ_MONTHLY = 'monthly';
    public const FREQ_QUARTERLY = 'quarterly';

    // Delivery method constants
    public const DELIVERY_EMAIL = 'email';
    public const DELIVERY_SLACK = 'slack';
    public const DELIVERY_WEBHOOK = 'webhook';
    public const DELIVERY_FTP = 'ftp';
    public const DELIVERY_S3 = 's3';

    // File format constants (same as DashboardSnapshot)
    public const FORMAT_PDF = 'pdf';
    public const FORMAT_CSV = 'csv';
    public const FORMAT_EXCEL = 'excel';
    public const FORMAT_JSON = 'json';

    // Date range type constants (same as DashboardWidget)
    public const RANGE_TODAY = 'today';
    public const RANGE_YESTERDAY = 'yesterday';
    public const RANGE_LAST_7_DAYS = 'last_7_days';
    public const RANGE_LAST_30_DAYS = 'last_30_days';
    public const RANGE_THIS_WEEK = 'this_week';
    public const RANGE_LAST_WEEK = 'last_week';
    public const RANGE_THIS_MONTH = 'this_month';
    public const RANGE_LAST_MONTH = 'last_month';
    public const RANGE_THIS_QUARTER = 'this_quarter';
    public const RANGE_LAST_QUARTER = 'last_quarter';
    public const RANGE_CUSTOM = 'custom';

    // Relationships
    public function template(): BelongsTo
    {
        return $this->belongsTo(DashboardTemplate::class, 'template_id', 'template_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(DashboardSnapshot::class, 'schedule_id', 'schedule_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeDue($query)
    {
        return $query->where('is_active', true)
                     ->where('next_run_at', '<=', now());
    }

    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    public function scopeByDeliveryMethod($query, string $method)
    {
        return $query->where('delivery_method', $method);
    }

    public function scopeWithFailures($query, int $minFailures = 1)
    {
        return $query->where('failure_count', '>=', $minFailures);
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function isDue(): bool
    {
        return $this->isActive() && $this->next_run_at !== null && $this->next_run_at->isPast();
    }

    public function hasFailures(): bool
    {
        return $this->failure_count > 0;
    }

    public function activate(): bool
    {
        return $this->update([
            'is_active' => true,
            'next_run_at' => $this->calculateNextRun(),
        ]);
    }

    public function pause(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function recordRun(bool $success, ?string $error = null): bool
    {
        $updates = [
            'last_run_at' => now(),
            'run_count' => $this->run_count + 1,
        ];

        if ($success) {
            $updates['next_run_at'] = $this->calculateNextRun();
            $updates['last_error'] = null;
        } else {
            $updates['failure_count'] = $this->failure_count + 1;
            $updates['last_error'] = $error;

            if ($this->auto_pause_on_failure && $this->failure_count >= 3) {
                $updates['is_active'] = false;
            }
        }

        return $this->update($updates);
    }

    public function resetFailureCount(): bool
    {
        return $this->update([
            'failure_count' => 0,
            'last_error' => null,
        ]);
    }

    public function calculateNextRun(): ?\DateTime
    {
        if (!$this->isActive()) {
            return null;
        }

        $now = now($this->timezone ?? 'UTC');
        $baseTime = $this->last_run_at ? $this->last_run_at->copy()->timezone($this->timezone ?? 'UTC') : $now->copy();

        [$hour, $minute] = $this->parseTimeOfDay();

        return match($this->frequency) {
            self::FREQ_HOURLY => $now->addHour(),
            self::FREQ_DAILY => $baseTime->addDay()->setTime($hour, $minute),
            self::FREQ_WEEKLY => $this->calculateNextWeekly($baseTime, $hour, $minute),
            self::FREQ_MONTHLY => $this->calculateNextMonthly($baseTime, $hour, $minute),
            self::FREQ_QUARTERLY => $baseTime->addMonths(3)->setTime($hour, $minute),
            default => null,
        };
    }

    protected function calculateNextWeekly(\DateTime $baseTime, int $hour, int $minute): \DateTime
    {
        $targetDay = $this->day_of_week ?? 1; // Default to Monday
        $next = $baseTime->copy()->next($this->getDayName($targetDay))->setTime($hour, $minute);

        if ($next <= now($this->timezone ?? 'UTC')) {
            $next->addWeek();
        }

        return $next;
    }

    protected function calculateNextMonthly(\DateTime $baseTime, int $hour, int $minute): \DateTime
    {
        $targetDay = $this->day_of_month ?? 1;
        $next = $baseTime->copy()->addMonth()->day($targetDay)->setTime($hour, $minute);

        if ($next <= now($this->timezone ?? 'UTC')) {
            $next->addMonth();
        }

        return $next;
    }

    protected function parseTimeOfDay(): array
    {
        if (!$this->time_of_day) {
            return [9, 0]; // Default to 9:00 AM
        }

        [$hour, $minute] = explode(':', $this->time_of_day);
        return [(int) $hour, (int) $minute];
    }

    protected function getDayName(int $dayOfWeek): string
    {
        return match($dayOfWeek) {
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            default => 'Monday',
        };
    }

    public function addRecipient(string $email): bool
    {
        $recipients = $this->recipients ?? [];

        if (!in_array($email, $recipients)) {
            $recipients[] = $email;
            return $this->update(['recipients' => $recipients]);
        }

        return true;
    }

    public function removeRecipient(string $email): bool
    {
        $recipients = $this->recipients ?? [];
        $recipients = array_values(array_diff($recipients, [$email]));

        return $this->update(['recipients' => $recipients]);
    }

    public function getRecipientCount(): int
    {
        return count($this->recipients ?? []);
    }

    public function getSuccessRate(): float
    {
        if ($this->run_count === 0) {
            return 100.0;
        }

        $successCount = $this->run_count - $this->failure_count;
        return round(($successCount / $this->run_count) * 100, 2);
    }

    public function getFrequencyDescription(): string
    {
        return match($this->frequency) {
            self::FREQ_HOURLY => 'Every hour',
            self::FREQ_DAILY => "Daily at {$this->time_of_day}",
            self::FREQ_WEEKLY => "Weekly on {$this->getDayName($this->day_of_week ?? 1)} at {$this->time_of_day}",
            self::FREQ_MONTHLY => "Monthly on day {$this->day_of_month} at {$this->time_of_day}",
            self::FREQ_QUARTERLY => "Quarterly at {$this->time_of_day}",
            default => 'Unknown frequency',
        };
    }

    public function getStatusColor(): string
    {
        if (!$this->isActive()) {
            return 'gray';
        }

        if ($this->failure_count >= 3) {
            return 'red';
        }

        if ($this->failure_count > 0) {
            return 'yellow';
        }

        return 'green';
    }

    // Static Methods
    public static function getFrequencyOptions(): array
    {
        return [
            self::FREQ_HOURLY => 'Hourly',
            self::FREQ_DAILY => 'Daily',
            self::FREQ_WEEKLY => 'Weekly',
            self::FREQ_MONTHLY => 'Monthly',
            self::FREQ_QUARTERLY => 'Quarterly',
        ];
    }

    public static function getDeliveryMethodOptions(): array
    {
        return [
            self::DELIVERY_EMAIL => 'Email',
            self::DELIVERY_SLACK => 'Slack',
            self::DELIVERY_WEBHOOK => 'Webhook',
            self::DELIVERY_FTP => 'FTP',
            self::DELIVERY_S3 => 'Amazon S3',
        ];
    }

    public static function getFormatOptions(): array
    {
        return [
            self::FORMAT_PDF => 'PDF',
            self::FORMAT_CSV => 'CSV',
            self::FORMAT_EXCEL => 'Excel',
            self::FORMAT_JSON => 'JSON',
        ];
    }

    public static function getDateRangeOptions(): array
    {
        return [
            self::RANGE_TODAY => 'Today',
            self::RANGE_YESTERDAY => 'Yesterday',
            self::RANGE_LAST_7_DAYS => 'Last 7 Days',
            self::RANGE_LAST_30_DAYS => 'Last 30 Days',
            self::RANGE_THIS_WEEK => 'This Week',
            self::RANGE_LAST_WEEK => 'Last Week',
            self::RANGE_THIS_MONTH => 'This Month',
            self::RANGE_LAST_MONTH => 'Last Month',
            self::RANGE_THIS_QUARTER => 'This Quarter',
            self::RANGE_LAST_QUARTER => 'Last Quarter',
            self::RANGE_CUSTOM => 'Custom Range',
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
            'frequency' => 'required|in:' . implode(',', array_keys(self::getFrequencyOptions())),
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'time_of_day' => 'nullable|date_format:H:i',
            'timezone' => 'nullable|timezone',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'recipient_groups' => 'nullable|array',
            'delivery_method' => 'required|in:' . implode(',', array_keys(self::getDeliveryMethodOptions())),
            'file_format' => 'required|in:' . implode(',', array_keys(self::getFormatOptions())),
            'include_filters' => 'nullable|boolean',
            'custom_filters' => 'nullable|array',
            'date_range_type' => 'required|in:' . implode(',', array_keys(self::getDateRangeOptions())),
            'custom_date_range' => 'nullable|array',
            'notify_on_completion' => 'nullable|boolean',
            'notify_on_failure' => 'nullable|boolean',
            'auto_pause_on_failure' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'frequency' => 'sometimes|in:' . implode(',', array_keys(self::getFrequencyOptions())),
            'recipients' => 'sometimes|array|min:1',
            'recipients.*' => 'email',
            'is_active' => 'sometimes|boolean',
            'notify_on_completion' => 'sometimes|boolean',
            'notify_on_failure' => 'sometimes|boolean',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'template_id.required' => 'Dashboard template is required',
            'org_id.required' => 'Organization is required',
            'name.required' => 'Schedule name is required',
            'frequency.required' => 'Frequency is required',
            'recipients.required' => 'At least one recipient is required',
            'recipients.min' => 'At least one recipient is required',
            'recipients.*.email' => 'All recipients must be valid email addresses',
            'delivery_method.required' => 'Delivery method is required',
            'file_format.required' => 'File format is required',
            'date_range_type.required' => 'Date range type is required',
        ];
    }
}
