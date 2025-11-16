<?php

namespace App\Models\Publishing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Core\Org;
use App\Models\Social\SocialAccount;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class PublishingQueue extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'cmis.publishing_queues';
    protected $primaryKey = 'queue_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'queue_id',
        'org_id',
        'social_account_id',
        'weekdays_enabled',
        'time_slots',
        'timezone',
        'is_active',
        'provider',
    ];

    protected $casts = [
        'queue_id' => 'string',
        'org_id' => 'string',
        'social_account_id' => 'string',
        'time_slots' => 'array',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot method to set UUID on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Get the organization that owns this queue
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the social account for this queue
     */
    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id', 'account_id');
    }

    /**
     * Scope to get active queues only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by organization
     */
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Scope by social account
     */
    public function scopeForAccount($query, string $accountId)
    {
        return $query->where('social_account_id', $accountId);
    }

    /**
     * Check if a specific weekday is enabled
     */
    public function isWeekdayEnabled(int $dayIndex): bool
    {
        // dayIndex: 0=Monday, 6=Sunday
        if ($dayIndex < 0 || $dayIndex > 6) {
            return false;
        }

        return isset($this->weekdays_enabled[$dayIndex]) && $this->weekdays_enabled[$dayIndex] === '1';
    }

    /**
     * Get enabled time slots for a specific day
     */
    public function getEnabledTimeSlotsForDay(int $dayIndex): array
    {
        if (!$this->isWeekdayEnabled($dayIndex)) {
            return [];
        }

        return array_filter($this->time_slots ?? [], function ($slot) {
            return isset($slot['enabled']) && $slot['enabled'] === true;
        });
    }

    /**
     * Get all enabled time slots
     */
    public function getAllEnabledTimeSlots(): array
    {
        return array_filter($this->time_slots ?? [], function ($slot) {
            return isset($slot['enabled']) && $slot['enabled'] === true;
        });
    }

    /**
     * Get next available posting time
     */
    public function getNextAvailableTime(\DateTime $after = null): ?\DateTime
    {
        if (!$this->is_active) {
            return null;
        }

        $after = $after ?? new \DateTime('now', new \DateTimeZone($this->timezone));
        $enabledSlots = $this->getAllEnabledTimeSlots();

        if (empty($enabledSlots)) {
            return null;
        }

        // Find next available slot (implementation would go here)
        // This is a simplified version
        return $after->modify('+1 hour');
    }
}
