<?php

namespace App\Models\Billing;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Subscription\Subscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.invoices';
    protected $primaryKey = 'invoice_id';

    protected $fillable = [
        'invoice_id',
        'org_id',
        'subscription_id',
        'invoice_number',
        'amount',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'status',
        'description',
        'billing_period_start',
        'billing_period_end',
        'due_date',
        'paid_at',
        'payment_method',
        'payment_reference',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
        'due_date' => 'date',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            // Generate invoice number if not set
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }

            // Calculate total amount if not set
            if (empty($invoice->total_amount)) {
                $invoice->total_amount = ($invoice->amount ?? 0)
                    + ($invoice->tax_amount ?? 0)
                    - ($invoice->discount_amount ?? 0);
            }
        });
    }

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');

        // Get the latest invoice number for this month
        $latestInvoice = self::where('invoice_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($latestInvoice) {
            $lastNumber = (int) substr($latestInvoice->invoice_number, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s%s-%05d', $prefix, $year, $month, $newNumber);
    }

    /**
     * Get the subscription this invoice belongs to.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'subscription_id');
    }

    /**
     * Get payments for this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id', 'invoice_id');
    }

    /**
     * Scope to get pending invoices.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_SENT]);
    }

    /**
     * Scope to get paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope to get overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE)
            ->orWhere(function ($q) {
                $q->whereIn('status', [self::STATUS_PENDING, self::STATUS_SENT])
                    ->where('due_date', '<', now());
            });
    }

    /**
     * Scope to get invoices within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->status === self::STATUS_OVERDUE) {
            return true;
        }

        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_SENT])
            && $this->due_date
            && $this->due_date->isPast();
    }

    /**
     * Check if invoice can be edited.
     */
    public function canEdit(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(?string $paymentMethod = null, ?string $paymentReference = null): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference,
        ]);
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(): void
    {
        $this->update(['status' => self::STATUS_SENT]);
    }

    /**
     * Cancel invoice.
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Get the total paid amount.
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->payments()->completed()->sum('amount');
    }

    /**
     * Get the remaining balance.
     */
    public function getRemainingBalanceAttribute(): float
    {
        return ($this->total_amount ?? 0) - $this->total_paid;
    }
}
