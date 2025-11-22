<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

    /**
     * Get the user who created the schedule
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');

    /**
     * Get execution logs for this schedule
     */
    public function executionLogs(): HasMany
    {
        return $this->hasMany(ReportExecutionLog::class, 'schedule_id', 'schedule_id');

    /**
     * Get the latest execution log
     */
    public function latestExecution(): HasMany
    {
        return $this->executionLogs()->latest('executed_at')->limit(1);

    /**
     * Scope: Active schedules only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);

    /**
     * Scope: Due for execution
     */
    public function scopeDue($query)
    {
        return $query->active()
            ->where('next_run_at', '<=', now())
            ->orWhereNull('next_run_at');

    /**
     * Scope: By frequency
     */
    public function scopeFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);

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
                break;

            case 'quarterly':
                $next = $now->copy()
                    ->addMonths(3)
                    ->day($this->day_of_month ?? 1)
                    ->setTime($deliveryTime->hour, $deliveryTime->minute, 0);
                break;

            default:
                $next = $now->copy()->addDay();

        return $next;

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

    /**
     * Check if schedule is due for execution
     */
    public function isDue(): bool
    {
        return $this->is_active &&
               ($this->next_run_at === null || $this->next_run_at->isPast());
}
