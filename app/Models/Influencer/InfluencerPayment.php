<?php

namespace App\Models\Influencer;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerPayment extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_influencer.influencer_payments';
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'payment_id',
        'influencer_id',
        'influencer_campaign_id',
        'partnership_id',
        'org_id',
        'payment_type',
        'amount',
        'currency',
        'payment_method',
        'status',
        'due_date',
        'paid_date',
        'invoice_number',
        'invoice_url',
        'payment_reference',
        'payment_details',
        'tax_amount',
        'net_amount',
        'commission_percentage',
        'platform_fee',
        'description',
        'notes',
        'approved_by',
        'approved_at',
        'processed_by',
        'processed_at',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'commission_percentage' => 'decimal:4',
        'platform_fee' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'payment_details' => 'array',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Payment type constants
    public const TYPE_CAMPAIGN_FEE = 'campaign_fee';
    public const TYPE_PERFORMANCE_BONUS = 'performance_bonus';
    public const TYPE_AFFILIATE_COMMISSION = 'affiliate_commission';
    public const TYPE_BASE_RETAINER = 'base_retainer';
    public const TYPE_PRODUCT_SEEDING = 'product_seeding';
    public const TYPE_EVENT_APPEARANCE = 'event_appearance';
    public const TYPE_CONTENT_RIGHTS = 'content_rights';
    public const TYPE_REIMBURSEMENT = 'reimbursement';
    public const TYPE_OTHER = 'other';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_ON_HOLD = 'on_hold';

    // Payment method constants
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_PAYPAL = 'paypal';
    public const METHOD_STRIPE = 'stripe';
    public const METHOD_WIRE = 'wire';
    public const METHOD_CHECK = 'check';
    public const METHOD_CRYPTO = 'crypto';
    public const METHOD_OTHER = 'other';

    // Relationships
    public function influencer(): BelongsTo
    {
        return $this->belongsTo(Influencer::class, 'influencer_id', 'influencer_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(InfluencerCampaign::class, 'influencer_campaign_id', 'influencer_campaign_id');
    }

    public function partnership(): BelongsTo
    {
        return $this->belongsTo(InfluencerPartnership::class, 'partnership_id', 'partnership_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by', 'user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED])
                     ->where('due_date', '<', now());
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED])
                     ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('payment_type', $type);
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    // Helper Methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED])
            && $this->due_date !== null
            && $this->due_date->isPast();
    }

    public function approve(?string $userId = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId ?? auth()->id(),
            'approved_at' => now(),
        ]);
    }

    public function markAsProcessing(?string $userId = null): bool
    {
        return $this->update([
            'status' => self::STATUS_PROCESSING,
            'processed_by' => $userId ?? auth()->id(),
            'processed_at' => now(),
        ]);
    }

    public function markAsPaid(?string $userId = null, ?string $reference = null): bool
    {
        $updates = [
            'status' => self::STATUS_PAID,
            'paid_date' => now(),
            'processed_by' => $userId ?? auth()->id(),
            'processed_at' => now(),
        ];

        if ($reference) {
            $updates['payment_reference'] = $reference;
        }

        return $this->update($updates);
    }

    public function markAsFailed(?string $reason = null): bool
    {
        $updates = ['status' => self::STATUS_FAILED];

        if ($reason) {
            $updates['notes'] = ($this->notes ? $this->notes . "\n" : '') . "Failed: {$reason}";
        }

        return $this->update($updates);
    }

    public function cancel(?string $reason = null): bool
    {
        $updates = ['status' => self::STATUS_CANCELLED];

        if ($reason) {
            $updates['notes'] = ($this->notes ? $this->notes . "\n" : '') . "Cancelled: {$reason}";
        }

        return $this->update($updates);
    }

    public function refund(?string $reason = null): bool
    {
        $updates = ['status' => self::STATUS_REFUNDED];

        if ($reason) {
            $updates['notes'] = ($this->notes ? $this->notes . "\n" : '') . "Refunded: {$reason}";
        }

        return $this->update($updates);
    }

    public function hold(?string $reason = null): bool
    {
        $updates = ['status' => self::STATUS_ON_HOLD];

        if ($reason) {
            $updates['notes'] = ($this->notes ? $this->notes . "\n" : '') . "On Hold: {$reason}";
        }

        return $this->update($updates);
    }

    public function calculateNetAmount(): float
    {
        $net = $this->amount;

        if ($this->tax_amount) {
            $net -= $this->tax_amount;
        }

        if ($this->platform_fee) {
            $net -= $this->platform_fee;
        }

        if ($this->commission_percentage) {
            $net -= ($this->amount * $this->commission_percentage);
        }

        return round($net, 2);
    }

    public function updateNetAmount(): bool
    {
        return $this->update(['net_amount' => $this->calculateNetAmount()]);
    }

    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return now()->diffInDays($this->due_date);
    }

    public function getDaysUntilDue(): int
    {
        if (!$this->due_date) {
            return 0;
        }

        return max(0, now()->diffInDays($this->due_date, false));
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED => 'blue',
            self::STATUS_PROCESSING => 'purple',
            self::STATUS_PAID => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_REFUNDED => 'orange',
            self::STATUS_ON_HOLD => 'orange',
            default => 'gray',
        };
    }

    public function getTypeIcon(): string
    {
        return match($this->payment_type) {
            self::TYPE_CAMPAIGN_FEE => 'dollar-sign',
            self::TYPE_PERFORMANCE_BONUS => 'award',
            self::TYPE_AFFILIATE_COMMISSION => 'percentage',
            self::TYPE_BASE_RETAINER => 'calendar',
            self::TYPE_PRODUCT_SEEDING => 'gift',
            self::TYPE_EVENT_APPEARANCE => 'calendar-alt',
            self::TYPE_CONTENT_RIGHTS => 'copyright',
            self::TYPE_REIMBURSEMENT => 'receipt',
            default => 'money-bill',
        };
    }

    // Static Methods
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_CAMPAIGN_FEE => 'Campaign Fee',
            self::TYPE_PERFORMANCE_BONUS => 'Performance Bonus',
            self::TYPE_AFFILIATE_COMMISSION => 'Affiliate Commission',
            self::TYPE_BASE_RETAINER => 'Base Retainer',
            self::TYPE_PRODUCT_SEEDING => 'Product Seeding',
            self::TYPE_EVENT_APPEARANCE => 'Event Appearance',
            self::TYPE_CONTENT_RIGHTS => 'Content Rights',
            self::TYPE_REIMBURSEMENT => 'Reimbursement',
            self::TYPE_OTHER => 'Other',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_PAID => 'Paid',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_ON_HOLD => 'On Hold',
        ];
    }

    public static function getMethodOptions(): array
    {
        return [
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_PAYPAL => 'PayPal',
            self::METHOD_STRIPE => 'Stripe',
            self::METHOD_WIRE => 'Wire Transfer',
            self::METHOD_CHECK => 'Check',
            self::METHOD_CRYPTO => 'Cryptocurrency',
            self::METHOD_OTHER => 'Other',
        ];
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'influencer_id' => 'required|uuid|exists:cmis_influencer.influencers,influencer_id',
            'influencer_campaign_id' => 'nullable|uuid|exists:cmis_influencer.influencer_campaigns,influencer_campaign_id',
            'partnership_id' => 'nullable|uuid|exists:cmis_influencer.influencer_partnerships,partnership_id',
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'payment_type' => 'required|in:' . implode(',', array_keys(self::getTypeOptions())),
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'payment_method' => 'required|in:' . implode(',', array_keys(self::getMethodOptions())),
            'status' => 'nullable|in:' . implode(',', array_keys(self::getStatusOptions())),
            'due_date' => 'nullable|date',
            'invoice_number' => 'nullable|string|max:255',
            'invoice_url' => 'nullable|url',
            'payment_reference' => 'nullable|string|max:255',
            'payment_details' => 'nullable|array',
            'tax_amount' => 'nullable|numeric|min:0',
            'platform_fee' => 'nullable|numeric|min:0',
            'commission_percentage' => 'nullable|numeric|min:0|max:1',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'status' => 'sometimes|in:' . implode(',', array_keys(self::getStatusOptions())),
            'paid_date' => 'sometimes|date',
            'payment_reference' => 'sometimes|string|max:255',
            'notes' => 'sometimes|string',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'influencer_id.required' => 'Influencer is required',
            'org_id.required' => 'Organization is required',
            'payment_type.required' => 'Payment type is required',
            'amount.required' => 'Amount is required',
            'amount.min' => 'Amount must be a positive number',
            'currency.required' => 'Currency is required',
            'currency.size' => 'Currency must be a 3-letter code (e.g., USD)',
            'payment_method.required' => 'Payment method is required',
            'commission_percentage.max' => 'Commission percentage must be between 0 and 1',
        ];
    }
}
