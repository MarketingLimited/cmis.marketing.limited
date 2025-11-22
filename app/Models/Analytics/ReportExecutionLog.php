<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

    /**
     * Scope: Successful executions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');

    /**
     * Scope: Failed executions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');

    /**
     * Scope: Recent executions
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('executed_at', '>=', now()->subDays($days));

    /**
     * Check if execution was successful
     */
    public function wasSuccessful(): bool
    {
        return $this->status === 'success';

    /**
     * Get success rate as percentage
     */
    public function getSuccessRate(): float
    {
        if ($this->recipients_count === 0) {
            return 0.0;

        return ($this->emails_sent / $this->recipients_count) * 100;
}
