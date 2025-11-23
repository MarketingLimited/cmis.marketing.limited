<?php

namespace App\Models\Automation;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationSchedule extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.automation_schedules';
    protected $primaryKey = 'schedule_id';
    protected $fillable = [
        'org_id', 'rule_id', 'frequency', 'cron_expression', 'time_of_day',
        'days_of_week', 'day_of_month', 'timezone', 'starts_at', 'ends_at',
        'last_run_at', 'next_run_at', 'enabled'
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'day_of_month' => 'integer',
        'enabled' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // ===== Relationships =====

    

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'rule_id', 'rule_id');
    }

    // ===== Schedule Management =====

    public function enable(): void
    {
        $this->update(['enabled' => true]);
        $this->calculateNextRun();
    }

    public function disable(): void
    {
        $this->update([
            'enabled' => false,
            'next_run_at' => null
        ]);
    }

    public function markAsRun(): void
    {
        $this->update([
            'last_run_at' => now()
        ]);

        $this->calculateNextRun();
    }

    public function calculateNextRun(): void
    {
        if (!$this->enabled) {
            return;
        }

        $now = now($this->timezone);
        $nextRun = null;

        switch ($this->frequency) {
            case 'once':
                // Run only at starts_at, no repeat
                $nextRun = $this->starts_at;
                if ($this->last_run_at) {
                    $nextRun = null; // Already ran
                }
                break;

            case 'hourly':
                $nextRun = $now->copy()->addHour()->startOfHour();
                break;

            case 'daily':
                $nextRun = $now->copy()->addDay();
                if ($this->time_of_day) {
                    list($hour, $minute) = explode(':', $this->time_of_day);
                    $nextRun->setTime((int)$hour, (int)$minute, 0);
                }
                break;

            case 'weekly':
                $nextRun = $this->calculateNextWeeklyRun($now);
                break;

            case 'monthly':
                $nextRun = $now->copy()->addMonth();
                if ($this->day_of_month) {
                    $nextRun->setDay($this->day_of_month);
                }
                if ($this->time_of_day) {
                    list($hour, $minute) = explode(':', $this->time_of_day);
                    $nextRun->setTime((int)$hour, (int)$minute, 0);
                }
                break;

            case 'custom':
                // Use cron expression (implement cron parser if needed)
                $nextRun = $this->parseCronExpression();
                break;
        }

        // Check if next run is within start/end dates
        if ($nextRun) {
            if ($this->starts_at && $nextRun->isBefore($this->starts_at)) {
                $nextRun = $this->starts_at;
            }

            if ($this->ends_at && $nextRun->isAfter($this->ends_at)) {
                $nextRun = null; // Schedule has ended
            }
        }

        $this->update(['next_run_at' => $nextRun]);
    }

    protected function calculateNextWeeklyRun($from): ?\Carbon\Carbon
    {
        if (!$this->days_of_week || empty($this->days_of_week)) {
            return null;
        }

        $next = $from->copy();
        $found = false;

        // Look up to 7 days ahead
        for ($i = 0; $i < 7; $i++) {
            if (in_array($next->dayOfWeek, $this->days_of_week)) {
                $found = true;
                break;
            }
            $next->addDay();
        }

        if (!$found) {
            return null;
        }

        if ($this->time_of_day) {
            list($hour, $minute) = explode(':', $this->time_of_day);
            $next->setTime((int)$hour, (int)$minute, 0);
        }

        return $next;
    }

    protected function parseCronExpression(): ?\Carbon\Carbon
    {
        // Simplified cron parsing - in production use a cron library
        // For now, return null and handle custom schedules differently
        return null;
    }

    public function isDue(): bool
    {
        if (!$this->enabled || !$this->next_run_at) {
            return false;
        }

        return now($this->timezone)->isAfter($this->next_run_at);
    }

    public function hasEnded(): bool
    {
        return $this->ends_at && now($this->timezone)->isAfter($this->ends_at);
    }

    // ===== Scopes =====

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeDue($query)
    {
        return $query->enabled()
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now());
    }

    public function scopeActive($query)
    {
        return $query->enabled()
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>', now());
            });
    }
}
