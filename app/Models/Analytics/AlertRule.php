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
     * Get the user who created the rule
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');

    /**
     * Get triggered alerts for this rule
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(AlertHistory::class, 'rule_id', 'rule_id');

    /**
     * Get recent triggered alerts
     */
    public function recentAlerts(): HasMany
    {
        return $this->alerts()
            ->where('triggered_at', '>=', now()->subDays(30))
            ->latest('triggered_at');

    /**
     * Scope: Active rules only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);

    /**
     * Scope: By entity type
     */
    public function scopeForEntity($query, string $entityType, ?string $entityId = null)
    {
        $query->where('entity_type', $entityType);

        if ($entityId) {
            $query->where(function ($q) use ($entityId) {
                $q->where('entity_id', $entityId)
                  ->orWhereNull('entity_id');

        return $query;

    /**
     * Scope: By severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);

    /**
     * Scope: Due for evaluation (cooldown period expired)
     */
    public function scopeDueForEvaluation($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('last_triggered_at')
                  ->orWhereRaw('last_triggered_at <= NOW() - INTERVAL \'1 minute\' * cooldown_minutes');

    /**
     * Check if rule is in cooldown period
     */
    public function isInCooldown(): bool
    {
        if (!$this->last_triggered_at) {
            return false;

        $cooldownEnd = $this->last_triggered_at->addMinutes($this->cooldown_minutes);
        return now()->lt($cooldownEnd);

    /**
     * Evaluate condition against actual value
     */
    public function evaluateCondition(float $actualValue): bool
    {
        return match ($this->condition) {
            'gt' => $actualValue > $this->threshold,
            'gte' => $actualValue >= $this->threshold,
            'lt' => $actualValue < $this->threshold,
            'lte' => $actualValue <= $this->threshold,
            'eq' => abs($actualValue - $this->threshold) < 0.0001,
            'ne' => abs($actualValue - $this->threshold) >= 0.0001,
            default => false
        };

    /**
     * Get human-readable condition text
     */
    public function getConditionText(): string
    {
        return match ($this->condition) {
            'gt' => 'greater than',
            'gte' => 'greater than or equal to',
            'lt' => 'less than',
            'lte' => 'less than or equal to',
            'eq' => 'equal to',
            'ne' => 'not equal to',
            'change_pct' => 'changes by',
            default => 'unknown'
        };

    /**
     * Mark as triggered
     */
    public function markTriggered(): void
    {
        $this->update([
            'last_triggered_at' => now(),
            'trigger_count' => $this->trigger_count + 1
        ]);
}
