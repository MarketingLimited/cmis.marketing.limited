<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

    /**
     * Scope: By channel
     */
    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);

    /**
     * Scope: Pending notifications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');

    /**
     * Scope: Failed notifications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');

    /**
     * Scope: Successfully delivered
     */
    public function scopeDelivered($query)
    {
        return $query->whereIn('status', ['delivered', 'read']);

    /**
     * Mark as sent
     */
    public function markSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);

    /**
     * Mark as delivered
     */
    public function markDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now()
        ]);

    /**
     * Mark as read
     */
    public function markRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now()
        ]);

    /**
     * Mark as failed
     */
    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1
        ]);

    /**
     * Check if notification can be retried
     */
    public function canRetry(int $maxRetries = 3): bool
    {
        return $this->status === 'failed' &&
               $this->retry_count < $maxRetries;
}
