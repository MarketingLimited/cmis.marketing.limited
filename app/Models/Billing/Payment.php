<?php

namespace App\Models\Billing;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.payments';
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'payment_id',
        'org_id',
        'invoice_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'payment_gateway',
        'transaction_id',
        'gateway_response_code',
        'gateway_response_message',
        'metadata',
        'paid_at',
        'refunded_at',
        'refund_amount',
        'refund_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Get the invoice this payment is for.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    /**
     * Scope to get completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to get refunded payments.
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    /**
     * Scope to get payments within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('paid_at', [$startDate, $endDate]);
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if payment is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /**
     * Mark payment as completed.
     */
    public function markAsCompleted(?string $transactionId = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'paid_at' => now(),
            'transaction_id' => $transactionId ?? $this->transaction_id,
        ]);
    }

    /**
     * Process refund.
     */
    public function refund(float $amount, ?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REFUNDED,
            'refunded_at' => now(),
            'refund_amount' => $amount,
            'refund_reason' => $reason,
        ]);
    }
}
