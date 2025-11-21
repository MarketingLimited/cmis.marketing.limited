<?php

namespace App\Models\Analytics;

use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Scheduled Report Model (Phase 12)
 *
 * Represents automated report delivery schedules
 *
 * @property string $schedule_id
 * @property string $org_id
 * @property string $user_id
 * @property string $name
 * @property string $report_type
 * @property string $frequency
 * @property array $config
 * @property array $recipients
 * @property string $format
 * @property string $timezone
 * @property string $delivery_time
 * @property int|null $day_of_week
 * @property int|null $day_of_month
 * @property bool $is_active
 * @property \Carbon\Carbon|null $last_run_at
 * @property \Carbon\Carbon|null $next_run_at
 * @property int $run_count
 */
class ScheduledReport extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'cmis.scheduled_reports';
    protected $primaryKey = 'schedule_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'user_id',
        'name',
        'report_type',
        'frequency',
        'config',
        'recipients',
        'format',
        'timezone',
        'delivery_time',
        'day_of_week',
        'day_of_month',
        'is_active',
        'last_run_at',
        'next_run_at',
        'run_count'
    ];

    protected $casts = [
        'config' => 'array',
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'run_count' => 'integer',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the organization that owns the schedule
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the user who created the schedule
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get execution logs for this schedule
     */
    public function executionLogs(): HasMany
    {
        return $this->hasMany(ReportExecutionLog::class, 'schedule_id', 'schedule_id');
    }

    /**
     * Get the latest execution log
     */
    public function latestExecution(): HasMany
    {
        return $this->executionLogs()->latest('executed_at')->limit(1);
    }

    /**
     * Scope: Active schedules only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Due for execution
     */
    public function scopeDue($query)
    {
        return $query->active()
            ->where('next_run_at', '<=', now())
            ->orWhereNull('next_run_at');
    }

    /**
     * Scope: By frequency
     */
    public function scopeFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Calculate next run time based on frequency
     */
    public function calculateNextRunAt(): \Carbon\Carbon
    {
        $now = now($this->timezone);
        $deliveryTime = \Carbon\Carbon::parse($this->delivery_time);

        switch ($this->frequency) {
            case 'daily':
                $next = $now->copy()
                    ->setTime($deliveryTime->hour, $deliveryTime->minute, 0);
                if ($next->isPast()) {
                    $next->addDay();
                }
                break;

            case 'weekly':
                $next = $now->copy()
                    ->next($this->day_of_week)
                    ->setTime($deliveryTime->hour, $deliveryTime->minute, 0);
                break;

            case 'monthly':
                $next = $now->copy()
                    ->day($this->day_of_month)
                    ->setTime($deliveryTime->hour, $deliveryTime->minute, 0);
                if ($next->isPast()) {
                    $next->addMonth();
                }
                break;

            case 'quarterly':
                $next = $now->copy()
                    ->addMonths(3)
                    ->day($this->day_of_month ?? 1)
                    ->setTime($deliveryTime->hour, $deliveryTime->minute, 0);
                break;

            default:
                $next = $now->copy()->addDay();
        }

        return $next;
    }

    /**
     * Mark as executed and update next run time
     */
    public function markAsExecuted(): void
    {
        $this->update([
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRunAt(),
            'run_count' => $this->run_count + 1
        ]);
    }

    /**
     * Check if schedule is due for execution
     */
    public function isDue(): bool
    {
        return $this->is_active &&
               ($this->next_run_at === null || $this->next_run_at->isPast());
    }
}
