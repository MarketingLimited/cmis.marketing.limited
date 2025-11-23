<?php

namespace App\Models\Lead;

use App\Models\Concerns\HasOrganization;
use App\Models\BaseModel;
use App\Models\Campaign\Campaign;
use App\Models\Contact\Contact;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lead Model
 *
 * Represents a lead in the CRM system with scoring, lifecycle tracking,
 * and multi-tenancy support via RLS.
 */
class Lead extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis.leads';
    protected $primaryKey = 'lead_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Lead status constants
    const STATUS_NEW = 'new';
    const STATUS_CONTACTED = 'contacted';
    const STATUS_QUALIFIED = 'qualified';
    const STATUS_CONVERTED = 'converted';
    const STATUS_LOST = 'lost';

    protected $fillable = [
        'org_id',
        'campaign_id',
        'contact_id',
        'name',
        'email',
        'phone',
        'source',
        'status',
        'score',
        'metadata',
        'additional_data',
        'utm_parameters',
        'estimated_value',
        'assigned_to',
        'last_contacted_at',
        'converted_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'additional_data' => 'array',
        'utm_parameters' => 'array',
        'score' => 'integer',
        'estimated_value' => 'decimal:2',
        'last_contacted_at' => 'datetime',
        'converted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the campaign this lead belongs to
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Get the user assigned to this lead
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to', 'user_id');
    }

    /**
     * Get the contact associated with this lead
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'contact_id');
    }

    /**
     * Scope for leads with a specific status
     */
    public function scopeWithStatus($query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for high-scoring leads
     */
    public function scopeHighScore($query, int $minScore = 70): Builder
    {
        return $query->where('score', '>=', $minScore);
    }

    /**
     * Scope for qualified leads
     */
    public function scopeQualified($query): Builder
    {
        return $query->where('status', self::STATUS_QUALIFIED);
    }

    /**
     * Scope for converted leads
     */
    public function scopeConverted($query): Builder
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    /**
     * Scope for leads assigned to a user
     */
    public function scopeAssignedTo($query, string $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Check if lead is qualified
     */
    public function isQualified(): bool
    {
        return $this->status === self::STATUS_QUALIFIED;
    }

    /**
     * Check if lead is converted
     */
    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    /**
     * Get all valid status values
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_CONTACTED,
            self::STATUS_QUALIFIED,
            self::STATUS_CONVERTED,
            self::STATUS_LOST,
        ];
    }
}
