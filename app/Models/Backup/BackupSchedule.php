<?php

namespace App\Models\Backup;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BackupSchedule Model
 *
 * Represents an automatic backup schedule for an organization.
 * Supports hourly, daily, weekly, and monthly frequencies.
 *
 * @property string $id
 * @property string $org_id
 * @property string $name
 * @property string $frequency
 * @property string $time
 * @property int|null $day_of_week
 * @property int|null $day_of_month
 * @property string $timezone
 * @property bool $is_active
 * @property int $retention_days
 * @property array|null $categories
 * @property string $storage_disk
 * @property bool $encrypt_backup
 * @property string|null $encryption_key_id
 * @property \Carbon\Carbon|null $last_run_at
 * @property \Carbon\Carbon|null $next_run_at
 * @property string|null $last_status
 * @property string|null $last_error
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class BackupSchedule extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.backup_schedules';

    // Frequency options
    public const FREQUENCY_HOURLY = 'hourly';
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';

    // Days of week
    public const DAYS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    protected $fillable = [
        'org_id',
        'name',
        'frequency',
        'time',
        'day_of_week',
        'day_of_month',
        'timezone',
        'is_active',
        'retention_days',
        'categories',
        'storage_disk',
        'encrypt_backup',
        'encryption_key_id',
        'last_run_at',
        'next_run_at',
        'last_status',
        'last_error',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'encrypt_backup' => 'boolean',
        'retention_days' => 'integer',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'categories' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $schedule) {
            if (empty($schedule->next_run_at)) {
                $schedule->next_run_at = $schedule->calculateNextRunTime();
            }
        });

        static::updating(function (self $schedule) {
            // Recalculate next run time if schedule parameters changed
            if ($schedule->isDirty(['frequency', 'time', 'day_of_week', 'day_of_month', 'timezone', 'is_active'])) {
                $schedule->next_run_at = $schedule->calculateNextRunTime();
            }
        });
    }

    // ==================== Relationships ====================

    /**
     * Get the user who created this schedule
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Get the encryption key for this schedule
     */
    public function encryptionKey(): BelongsTo
    {
        return $this->belongsTo(BackupEncryptionKey::class, 'encryption_key_id');
    }

    // ==================== Scopes ====================

    /**
     * Scope: Only active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Schedules due to run
     */
    public function scopeDue($query)
    {
        return $query->where('is_active', true)
            ->where('next_run_at', '<=', now());
    }

    /**
     * Scope: By frequency
     */
    public function scopeOfFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    // ==================== Schedule Calculation ====================

    /**
     * Calculate the next run time based on schedule parameters
     */
    public function calculateNextRunTime(?Carbon $from = null): Carbon
    {
        $from = $from ?? now();
        $tz = $this->timezone ?? 'UTC';

        // Parse the time
        [$hour, $minute] = explode(':', $this->time);

        $next = $from->copy()->setTimezone($tz);

        switch ($this->frequency) {
            case self::FREQUENCY_HOURLY:
                // Run at the specified minute of each hour
                $next->minute((int) $minute)->second(0);
                if ($next->lte($from)) {
                    $next->addHour();
                }
                break;

            case self::FREQUENCY_DAILY:
                // Run at specified time each day
                $next->setTime((int) $hour, (int) $minute, 0);
                if ($next->lte($from)) {
                    $next->addDay();
                }
                break;

            case self::FREQUENCY_WEEKLY:
                // Run on specified day of week at specified time
                $next->setTime((int) $hour, (int) $minute, 0);
                $targetDay = $this->day_of_week ?? 0;

                while ($next->dayOfWeek !== $targetDay || $next->lte($from)) {
                    $next->addDay();
                }
                break;

            case self::FREQUENCY_MONTHLY:
                // Run on specified day of month at specified time
                $next->setTime((int) $hour, (int) $minute, 0);
                $targetDay = min($this->day_of_month ?? 1, $next->daysInMonth);
                $next->day($targetDay);

                if ($next->lte($from)) {
                    $next->addMonth();
                    // Adjust for months with fewer days
                    $targetDay = min($this->day_of_month ?? 1, $next->daysInMonth);
                    $next->day($targetDay);
                }
                break;
        }

        return $next->setTimezone('UTC');
    }

    /**
     * Update after a run
     */
    public function recordRun(bool $success, ?string $error = null): void
    {
        $this->update([
            'last_run_at' => now(),
            'last_status' => $success ? 'completed' : 'failed',
            'last_error' => $error,
            'next_run_at' => $this->calculateNextRunTime(),
        ]);
    }

    // ==================== Helper Methods ====================

    /**
     * Check if this schedule is due to run
     */
    public function isDue(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return $this->next_run_at && $this->next_run_at->lte(now());
    }

    /**
     * Get human-readable schedule description
     */
    public function getScheduleDescriptionAttribute(): string
    {
        $time = $this->time;
        $tz = $this->timezone;

        switch ($this->frequency) {
            case self::FREQUENCY_HOURLY:
                return __('backup.schedule_hourly', ['minute' => explode(':', $time)[1]]);

            case self::FREQUENCY_DAILY:
                return __('backup.schedule_daily', ['time' => $time, 'tz' => $tz]);

            case self::FREQUENCY_WEEKLY:
                $day = self::DAYS[$this->day_of_week] ?? 'Sunday';
                return __('backup.schedule_weekly', ['day' => $day, 'time' => $time, 'tz' => $tz]);

            case self::FREQUENCY_MONTHLY:
                return __('backup.schedule_monthly', ['day' => $this->day_of_month, 'time' => $time, 'tz' => $tz]);

            default:
                return $this->frequency;
        }
    }

    /**
     * Get available frequencies for a plan
     */
    public static function getFrequenciesForPlan(string $plan): array
    {
        $frequencies = config("backup.plans.{$plan}.allowed_schedules", []);

        return array_filter([
            self::FREQUENCY_HOURLY => in_array(self::FREQUENCY_HOURLY, $frequencies),
            self::FREQUENCY_DAILY => in_array(self::FREQUENCY_DAILY, $frequencies),
            self::FREQUENCY_WEEKLY => in_array(self::FREQUENCY_WEEKLY, $frequencies),
            self::FREQUENCY_MONTHLY => in_array(self::FREQUENCY_MONTHLY, $frequencies),
        ]);
    }

    /**
     * Check if schedule includes all categories
     */
    public function includesAllCategories(): bool
    {
        return empty($this->categories);
    }

    /**
     * Get retention period as Carbon interval
     */
    public function getRetentionPeriod(): \DateInterval
    {
        return new \DateInterval("P{$this->retention_days}D");
    }
}
