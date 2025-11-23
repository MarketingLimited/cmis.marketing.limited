<?php

namespace App\Models\Contact;

use App\Models\Concerns\HasOrganization;
use App\Models\BaseModel;
use App\Models\Lead\Lead;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Contact Model
 *
 * Represents a contact in the CRM system with multi-tenancy support via RLS.
 */
class Contact extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis.contacts';
    protected $primaryKey = 'contact_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'first_name',
        'last_name',
        'name',  // Keep for backward compatibility
        'email',
        'phone',
        'company',
        'source',
        'segments',
        'custom_fields',
        'social_profiles',
        'is_subscribed',
        'last_engaged_at',
        'metadata',
    ];

    protected $casts = [
        'segments' => 'array',
        'custom_fields' => 'array',
        'social_profiles' => 'array',
        'metadata' => 'array',
        'is_subscribed' => 'boolean',
        'last_engaged_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get all leads associated with this contact
     */
    public function leads()
    {
        return $this->hasMany(Lead::class, 'contact_id', 'contact_id');
    }

    /**
     * Get full name accessor
     *
     * Returns combined first_name and last_name if available,
     * otherwise falls back to the name field
     */
    public function getFullNameAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return trim("{$this->first_name} {$this->last_name}");
        }
        return $this->name ?? '';
    }

    /**
     * Scope for subscribed contacts
     */
    public function scopeSubscribed($query)
    {
        return $query->where('is_subscribed', true);
    }

    /**
     * Scope for contacts with a specific source
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope for contacts in a specific segment
     */
    public function scopeInSegment($query, string $segment)
    {
        return $query->whereJsonContains('segments', $segment);
    }
}
