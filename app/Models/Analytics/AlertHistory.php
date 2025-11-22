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

    /**
     * Get the user who acknowledged the alert
     */
    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by', 'user_id');

    /**
     * Get notifications for this alert
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(AlertNotification::class, 'alert_id', 'alert_id');

    /**
     * Scope: New alerts
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');

    /**
     * Scope: Active alerts (not resolved)
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['new', 'acknowledged', 'snoozed']);

    /**
     * Scope: By severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);

    /**
     * Scope: Critical alerts
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');

    /**
     * Scope: Recent alerts
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('triggered_at', '>=', now()->subDays($days));

    /**
     * Scope: Snoozed alerts that should be unsnoozed
     */
    public function scopeDueForUnsnooze($query)
    {
        return $query->where('status', 'snoozed')
            ->where('snoozed_until', '<=', now());

    /**
     * Acknowledge alert
     */
    public function acknowledge(string $userId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
            'resolution_notes' => $notes
        ]);

    /**
     * Resolve alert
     */
    public function resolve(string $userId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'resolved',
            'acknowledged_by' => $acknowledged_by ?? $userId,
            'resolved_at' => now(),
            'resolution_notes' => $notes
        ]);

    /**
     * Snooze alert
     */
    public function snooze(int $minutes): void
    {
        $this->update([
            'status' => 'snoozed',
            'snoozed_until' => now()->addMinutes($minutes)
        ]);

    /**
     * Unsnooze alert
     */
    public function unsnooze(): void
    {
        $this->update([
            'status' => 'new',
            'snoozed_until' => null
        ]);

    /**
     * Check if alert is snoozed
     */
    public function isSnoozed(): bool
    {
        return $this->status === 'snoozed' &&
               $this->snoozed_until &&
               $this->snoozed_until->isFuture();

    /**
     * Check if alert requires attention
     */
    public function requiresAttention(): bool
    {
        return in_array($this->status, ['new', 'acknowledged']) &&
               in_array($this->severity, ['critical', 'high']);
}
